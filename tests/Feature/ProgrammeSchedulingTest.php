<?php

namespace Tests\Feature;

use App\Enums\AbstractStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\ProgrammeSession;
use App\Models\User;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Accepted abstracts go into programme sessions, and presenters upload their slides. */
class ProgrammeSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private User $committee;

    private ProgrammeSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        $this->travelTo('2027-08-01 10:00');

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'presentation_deadline' => '2027-09-10', 'is_current' => true,
        ]);
        $this->edition->topics()->create(['name' => 'Stroke', 'code' => 'STR', 'sort' => 0]);
        app(Summit::class)->refresh();

        $this->committee = User::factory()->create()->assignRole(Role::ScientificAdmin->value);
        $this->session = $this->edition->sessions()->create([
            'title' => 'Oral session: Stroke', 'kind' => 'parallel', 'starts_at' => '2027-09-15 11:00', 'ends_at' => '2027-09-15 12:30',
        ]);
    }

    private function abstract(AbstractStatus $status = AbstractStatus::Accepted, ?string $code = null): AbstractSubmission
    {
        static $n = 0;
        $n++;

        return AbstractSubmission::create([
            'edition_id' => $this->edition->id,
            'user_id' => User::factory()->create()->assignRole(Role::Participant->value)->id,
            'topic_id' => $this->edition->topics->first()->id,
            'preferred_type' => 'oral', 'title' => "Abstract {$n}", 'background' => 'B', 'methods' => 'M', 'results' => 'R', 'conclusions' => 'C',
            'status' => $status, 'decision_type' => $status === AbstractStatus::Accepted ? 'oral' : null,
            'code' => $status === AbstractStatus::Accepted ? ($code ?? 'OR-STR-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT)) : null,
        ]);
    }

    private function schedule(AbstractSubmission $abstract, ?ProgrammeSession $session = null)
    {
        return $this->actingAs($this->committee)->post(route('scientific.programme.abstracts.store', $session ?? $this->session), ['abstract_id' => $abstract->id]);
    }

    /** Send a file the way the browser does: in chunks of the configured size. */
    private function uploadSlides(User $user, AbstractSubmission $abstract, string $name = 'slides.pptx', int $bytes = 2_500_000)
    {
        $chunk = config('presentations.chunk_kb') * 1024;
        $content = Str::random($bytes);
        $total = (int) ceil($bytes / $chunk);
        $id = (string) Str::uuid();
        $response = null;

        for ($i = 0; $i < $total; $i++) {
            $response = $this->actingAs($user)->postJson(route('presentations.upload', $abstract), [
                'upload_id' => $id, 'name' => $name, 'size' => $bytes, 'total' => $total, 'index' => $i,
                'chunk' => UploadedFile::fake()->createWithContent('chunk', substr($content, $i * $chunk, $chunk)),
            ]);
            if ($response->status() !== 200) {
                break;
            }
        }

        return $response;
    }

    public function test_the_committee_schedules_accepted_abstracts_in_order(): void
    {
        [$a, $b, $c] = [$this->abstract(), $this->abstract(), $this->abstract()];

        $this->actingAs($this->committee)->get(route('scientific.programme.index'))->assertSee('3 accepted abstracts are not in a session yet');
        $this->schedule($a)->assertSessionHasNoErrors();
        $this->schedule($b)->assertSessionHasNoErrors();
        $this->schedule($c)->assertSessionHasNoErrors();

        $this->assertSame([$a->id, $b->id, $c->id], $this->session->abstracts()->pluck('abstracts.id')->all());

        $this->actingAs($this->committee)->put(route('scientific.programme.abstracts.move', [$this->session, $c]), ['direction' => 'up']);
        $this->assertSame([$a->id, $c->id, $b->id], $this->session->abstracts()->pluck('abstracts.id')->all());

        $this->actingAs($this->committee)->delete(route('scientific.programme.abstracts.destroy', [$this->session, $a]));
        $this->assertSame([$c->id, $b->id], $this->session->abstracts()->pluck('abstracts.id')->all());
        $this->assertSame([1, 2], $this->session->abstracts()->get()->pluck('pivot.position')->all());

        // The presenter sees where they present.
        $this->actingAs($c->submitter)->get(route('abstracts.show', $c))->assertSeeText('Scheduled in "Oral session: Stroke"');
        $this->get(route('programme'))->assertSee($c->title);
    }

    public function test_only_accepted_abstracts_and_only_once(): void
    {
        $rejected = $this->abstract(AbstractStatus::Rejected);
        $accepted = $this->abstract();
        $other = $this->edition->sessions()->create(['title' => 'Oral session II', 'kind' => 'parallel', 'starts_at' => '2027-09-16 11:00', 'ends_at' => '2027-09-16 12:30']);
        $lunch = $this->edition->sessions()->create(['title' => 'Lunch', 'kind' => 'break', 'starts_at' => '2027-09-16 13:00', 'ends_at' => '2027-09-16 14:00']);

        $this->schedule($rejected)->assertSessionHasErrors('abstract_id');
        $this->schedule($accepted, $lunch)->assertSessionHasErrors('abstract_id');
        $this->schedule($accepted)->assertSessionHasNoErrors();
        $this->schedule($accepted, $other)->assertSessionHasErrors('abstract_id');

        $this->assertSame(1, $accepted->sessions()->count());
        $this->assertSame(0, $rejected->sessions()->count());
    }

    public function test_the_presenter_uploads_slides_in_chunks_and_replaces_them(): void
    {
        $abstract = $this->abstract();
        $presenter = $abstract->submitter;

        $this->actingAs($presenter)->get(route('abstracts.show', $abstract))->assertSee('Upload your slides');
        $this->uploadSlides($presenter, $abstract)->assertOk()->assertJson(['done' => true]);

        $file = $abstract->fresh()->presentationFile;
        $this->assertSame('slides.pptx', $file->original_name);
        $this->assertSame(2_500_000, (int) $file->bytes);
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($presenter)->get(route('presentations.download', $abstract))->assertOk();
        $this->actingAs($this->committee)->get(route('presentations.download', $abstract))->assertOk();

        // Replacing removes the old file.
        $this->uploadSlides($presenter, $abstract, 'slides-v2.pdf', 1_000_000)->assertOk();
        $this->assertSame('slides-v2.pdf', $abstract->fresh()->presentationFile->original_name);
        Storage::disk('local')->assertMissing($file->path);
        $this->assertSame(1, $abstract->presentationFile()->count());
    }

    public function test_slides_close_at_the_deadline_except_for_the_committee(): void
    {
        $abstract = $this->abstract();

        $this->travelTo('2027-09-11 09:00');
        $this->actingAs($abstract->submitter)->get(route('abstracts.show', $abstract))->assertSee('The upload deadline has passed');
        $this->uploadSlides($abstract->submitter, $abstract)->assertForbidden();

        $this->uploadSlides($this->committee, $abstract)->assertOk();
        $this->assertSame($this->committee->id, $abstract->fresh()->presentationFile->uploaded_by);
    }

    public function test_only_slides_and_only_the_presenter_or_committee(): void
    {
        $abstract = $this->abstract();
        $stranger = User::factory()->create()->assignRole(Role::Participant->value);

        $this->uploadSlides($abstract->submitter, $abstract, 'virus.exe')->assertUnprocessable();
        $this->uploadSlides($stranger, $abstract)->assertForbidden();
        $this->uploadSlides($this->abstract(AbstractStatus::UnderReview)->submitter, $this->abstract(AbstractStatus::UnderReview))->assertForbidden();

        $this->uploadSlides($abstract->submitter, $abstract)->assertOk();
        $this->actingAs($stranger)->get(route('presentations.download', $abstract))->assertNotFound();
    }
}
