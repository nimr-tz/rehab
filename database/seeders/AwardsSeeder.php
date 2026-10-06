<?php

namespace Database\Seeders;

use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AwardCategory;
use App\Models\AwardEntry;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\AwardWon;
use App\Services\AwardService;
use App\Support\AwardRubric;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SAMPLE AWARDS FOR THE DEMONSTRATION. Never runs in production.
 *
 * The suggested award categories, three judges, and every stage at once:
 * Best Oral Presentation announced (the demo participant wins it), Best Poster
 * and the Student Research Award being judged (the demo judge has finalists
 * waiting), the Innovation award ready for the committee to decide, the
 * Rehabilitation Champion Award open for nominations, and the Distinguished
 * Service Award announced.
 */
class AwardsSeeder extends Seeder
{
    private const PRIZES = [
        'Best Oral Presentation' => 'Trophy, certificate and free registration for next year’s summit',
        'Best Poster' => 'Certificate and a textbook voucher',
        'Student Research Award' => 'Travel grant of TZS 500,000 and a certificate',
        'Innovation in Rehabilitation Award' => 'Trophy and certificate',
        'Rehabilitation Champion Award' => 'Plaque and certificate',
        'Distinguished Service Award' => 'Plaque and certificate',
    ];

    /** [nominee, institution, citation] */
    private const NOMINEES = [
        ['Neema Shayo', 'Upendo Community Rehabilitation Group, Moshi', 'Neema started a parents’ group for children with cerebral palsy in her village in 2015. Today it runs weekly therapy sessions in six villages, trains caregivers, and has helped more than 200 children start school.'],
        ['Tumaini Wheelchair Workshop', 'Arusha', 'A workshop run by wheelchair users that builds, fits and repairs wheelchairs for rural families. They have fitted over 1,500 wheelchairs at a fraction of the imported cost and train young people with disabilities as technicians.'],
        ['Bakari Mohamed', 'Zanzibar Association of People with Disabilities', 'Bakari has campaigned for accessible health facilities across Unguja and Pemba. His audits led to ramps and accessible toilets in eleven health centres and a disability desk at Mnazi Mmoja Hospital.'],
        ['Sr Agnes Mbena', 'Iringa Regional Referral Hospital', 'A nurse who set up the first stroke rehabilitation corner in the region’s medical ward, trained ward staff in early mobilisation, and follows up patients at home by phone after discharge.'],
    ];

    public function run(AwardService $awards): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The AwardsSeeder adds sample awards and must not run in production.');
        }

        $edition = Edition::current();
        if (! $edition) {
            return;
        }

        mt_srand(2027);
        $edition->awardCategories()->delete();

        $judges = $this->judges();
        $awards->addSuggested($edition);
        $categories = $edition->awardCategories()->get()->keyBy('name');
        $categories->each(fn (AwardCategory $category) => $category->update(['prize' => self::PRIZES[$category->name] ?? null]));

        $presentation = $categories->filter->isPresentation();
        $presentation->each(fn (AwardCategory $category) => $awards->syncJudges($category, $judges->pluck('id')->all()));

        $amina = User::where('email', 'participant@rehab.test')->first();

        // Best Oral Presentation: five finalists, all scored, announced. The demo participant wins.
        $oral = $categories['Best Oral Presentation'];
        $candidates = $awards->eligibleAbstracts($oral);
        $hers = $candidates->firstWhere('user_id', $amina?->id);
        $awards->shortlist($oral, $candidates->reject(fn ($a) => $a->is($hers))->take($hers ? 4 : 5)->push($hers)->filter()->pluck('id')->all());
        $this->score($awards, $oral, $judges, fn (AwardEntry $entry) => $entry->user_id === $amina?->id ? 9 : mt_rand(5, 8));
        $this->placeByScore($awards, $oral);
        $this->announce($oral);

        // Best Poster: four finalists. Two judges have scored them all; the demo judge has scored two.
        $poster = $categories['Best Poster'];
        $awards->shortlist($poster, $awards->eligibleAbstracts($poster)->take(4)->pluck('id')->all());
        $this->score($awards, $poster, $judges->slice(1), fn () => mt_rand(5, 9));
        $this->score($awards, $poster, $judges->take(1), fn () => mt_rand(5, 9), limit: 2);

        // Student Research Award: the demo judge has not started.
        $student = $categories['Student Research Award'];
        $eligible = $awards->eligibleAbstracts($student)->take(3);
        if ($eligible->isNotEmpty()) {
            $awards->shortlist($student, $eligible->pluck('id')->all());
            $this->score($awards, $student, $judges->slice(1), fn () => mt_rand(5, 9));
        }

        // Innovation: three finalists, technology first, all scored and waiting for the committee.
        $innovation = $categories['Innovation in Rehabilitation Award'];
        $awards->shortlist($innovation, $awards->eligibleAbstracts($innovation)
            ->sortByDesc(fn ($abstract) => $abstract->topic->code === 'TAI')->take(3)->pluck('id')->all());
        $this->score($awards, $innovation, $judges, fn () => mt_rand(6, 9));

        // Rehabilitation Champion: nominations from participants, one nominee twice.
        $champion = $categories['Rehabilitation Champion Award'];
        $nominators = Registration::where('edition_id', $edition->id)->where('status', RegistrationStatus::Confirmed)
            ->with('user')->get()->pluck('user')->reject(fn (User $user) => $user->is($amina))->shuffle(mt_rand())->take(4)->values();
        foreach (self::NOMINEES as $i => [$name, $institution, $citation]) {
            $by = $name === 'Tumaini Wheelchair Workshop' ? $amina : $nominators[$i % $nominators->count()];
            $awards->nominate($champion, $by, ['name' => $name, 'institution' => $institution, 'citation' => $citation]);
        }
        $awards->nominate($champion, $nominators->last(), ['name' => 'Neema Shayo', 'institution' => 'Upendo group, Moshi',
            'citation' => 'Neema gives her time every week to families of children with disabilities. Her parents’ group has changed how our whole community sees disability.']);

        // Distinguished Service: chosen by the committee and announced.
        $service = $categories['Distinguished Service Award'];
        $recipient = $awards->addRecipient($service, [
            'name' => 'Prof Emeritus Elias Mtenga',
            'institution' => 'Formerly Muhimbili University of Health and Allied Sciences',
            'citation' => 'For four decades of service to rehabilitation in East Africa: training generations of physiotherapists, founding the first community-based rehabilitation programme in the Coast Region, and championing rehabilitation in national health policy.',
        ]);
        $awards->choosePlaces($service, [$recipient->id => 1]);
        $this->announce($service);
    }

    /** @return Collection<int, User> the demo judge first */
    private function judges(): Collection
    {
        return collect([
            ['judge@rehab.test', 'Prof', 'Halima', 'Kiwelu', 'Catholic University of Health and Allied Sciences', 'Professor of rehabilitation science'],
            ['m.owino@rehab.test', 'Dr', 'Michael', 'Owino', 'Kenya Medical Training College', 'Occupational therapist'],
            ['r.nyirenda@rehab.test', 'Dr', 'Rose', 'Nyirenda', 'Kamuzu University of Health Sciences', 'Physiotherapist'],
        ])->map(function (array $person) {
            [$email, $title, $first, $last, $institution, $profession] = $person;
            $user = User::updateOrCreate(['email' => $email], [
                'title' => $title, 'first_name' => $first, 'last_name' => $last,
                'phone' => '+2557'.mt_rand(10000000, 99999999), 'country' => $email === 'judge@rehab.test' ? 'TZ' : ($last === 'Owino' ? 'KE' : 'MW'),
                'institution' => $institution, 'profession' => $profession,
                'password' => DemoSeeder::PASSWORD,
            ]);
            $user->forceFill(['email_verified_at' => now()->subMonths(2)])->save();
            $user->syncRoles([Role::Judge->value]);

            return $user;
        });
    }

    /** Each judge scores the finalists around a level for each finalist, with a point either way per criterion. */
    private function score(AwardService $awards, AwardCategory $category, Collection $judges, callable $level, ?int $limit = null): void
    {
        $entries = $category->entries()->orderBy('id')->get();
        $levels = $entries->mapWithKeys(fn (AwardEntry $entry) => [$entry->id => $level($entry)]);
        $comments = ['Clear message and confident delivery.', 'Strong data; the slides were crowded.', 'Excellent answers to questions.', 'Very relevant to district services.', null, null];

        foreach ($judges as $judge) {
            foreach ($entries->take($limit ?? $entries->count()) as $entry) {
                $points = collect(AwardRubric::criteria())->keys()
                    ->mapWithKeys(fn (string $criterion) => [$criterion => max(1, min(AwardRubric::perCriterion(), $levels[$entry->id] + mt_rand(-1, 1)))])
                    ->all();
                $awards->score($entry, $judge, $points, $comments[mt_rand(0, count($comments) - 1)]);
            }
        }
    }

    private function placeByScore(AwardService $awards, AwardCategory $category): void
    {
        $places = $awards->standings($category)->take($category->places)->values()
            ->mapWithKeys(fn (AwardEntry $entry, int $i) => [$entry->id => $i + 1])->all();
        $awards->choosePlaces($category, $places);
    }

    /** Announces without sending email, and puts the news in each winner's bell. */
    private function announce(AwardCategory $category): void
    {
        $category->update(['announced_at' => now()->subDay()]);

        $category->winners()->with('user', 'abstract.submitter', 'category')->get()->each(function (AwardEntry $winner) {
            $recipient = $winner->user ?? $winner->abstract?->submitter;
            $recipient?->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => AwardWon::class,
                'data' => (new AwardWon($winner))->toArray($recipient),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        });
    }
}
