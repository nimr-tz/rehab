<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Album;
use App\Models\Edition;
use App\Models\Photo;
use App\Models\PhotoRemovalRequest;
use App\Models\User;
use App\Notifications\PhotoRemovalDecided;
use App\Notifications\PhotoRemovalRequested;
use App\Support\Summit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Notification::fake();
        config(['gallery.chunk_kb' => 2]); // small chunks, so test photos go up in several pieces

        $this->edition = Edition::create([
            'year' => 2027, 'name' => 'Rehabilitation Summit', 'short_name' => 'Rehab Summit', 'ordinal' => '5th',
            'start_date' => '2027-09-15', 'end_date' => '2027-09-17', 'is_current' => true,
        ]);
        app(Summit::class)->refresh();
    }

    private function user(Role ...$roles): User
    {
        return User::factory()->create()->assignRole(array_map(fn (Role $role) => $role->value, $roles));
    }

    private function album(User $creator, string $title = 'Official opening'): Album
    {
        $this->actingAs($creator)->post(route('media.albums.store'), ['title' => $title, 'day' => '2027-09-15'])->assertRedirect();

        return Album::latest('id')->first();
    }

    /** Uploads a photo the way the browser does: in chunks, and the response to the last chunk. */
    private function upload(User $user, Album $album, string $bytes, string $name = 'DSC_0001.jpg', bool $publish = false): TestResponse
    {
        $chunkSize = config('gallery.chunk_kb') * 1024;
        $chunks = str_split($bytes, $chunkSize);
        $uploadId = (string) Str::uuid();

        foreach ($chunks as $index => $chunk) {
            $response = $this->actingAs($user)->postJson(route('media.albums.uploads', $album), [
                'upload_id' => $uploadId, 'name' => $name, 'size' => strlen($bytes), 'total' => count($chunks), 'index' => $index,
                'publish' => $publish ? '1' : '0',
                'chunk' => UploadedFile::fake()->createWithContent('chunk', $chunk),
            ]);

            if ($index < count($chunks) - 1) {
                $response->assertOk()->assertJson(['done' => false]);
            }
        }

        return $response;
    }

    /** A 300×200 JPEG, red on the left and blue on the right, with an optional EXIF block (orientation and a GPS tag). */
    private function jpeg(?int $orientation = null): string
    {
        $image = imagecreatetruecolor(300, 200);
        imagefilledrectangle($image, 0, 0, 149, 199, imagecolorallocate($image, 220, 0, 0));
        imagefilledrectangle($image, 150, 0, 299, 199, imagecolorallocate($image, 0, 0, 220));
        ob_start();
        imagejpeg($image, null, 95);
        $jpeg = ob_get_clean();

        if ($orientation === null) {
            return $jpeg;
        }

        // TIFF, little-endian: IFD0 with Orientation and a pointer to a GPS IFD holding GPSLatitudeRef = "S".
        $tiff = 'II'.pack('v', 42).pack('V', 8)
            .pack('v', 2)
            .pack('vvVvv', 0x0112, 3, 1, $orientation, 0)
            .pack('vvVV', 0x8825, 4, 1, 38)
            .pack('V', 0)
            .pack('v', 1)
            .pack('vvV', 0x0001, 2, 2)."S\0\0\0"
            .pack('V', 0);
        $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\0\0".$tiff;

        return "\xFF\xD8".$app1.substr($jpeg, 2);
    }

    private function colourAt(string $jpeg, int $x, int $y): array
    {
        $rgb = imagecolorat(imagecreatefromstring($jpeg), $x, $y);

        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

    public function test_a_photographer_uploads_a_photo_in_chunks_and_it_is_stored_upright_without_location(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);
        $bytes = $this->jpeg(orientation: 6); // the camera was turned: rotate 90° clockwise to view

        $this->assertGreaterThan(1, ceil(strlen($bytes) / 2048), 'the photo should need several chunks');
        $this->upload($photographer, $album, $bytes)->assertOk()->assertJson(['done' => true, 'duplicate' => false]);

        $photo = Photo::sole();
        $this->assertSame([200, 300], [$photo->width, $photo->height]);
        $this->assertNull($photo->published_at);
        $this->assertSame($photographer->id, $photo->user_id);

        $disk = Storage::disk('local');
        $original = $disk->get($photo->original_path);
        $this->assertStringNotContainsString('Exif', $original);
        $this->assertSame([200, 300], array_slice(getimagesizefromstring($original), 0, 2));

        // The left (red) half of the sensor is now at the top.
        [$r, , $b] = $this->colourAt($disk->get($photo->display_path), 100, 30);
        $this->assertGreaterThan(150, $r);
        $this->assertLessThan(80, $b);

        $this->assertSame([200, 300], array_slice(getimagesizefromstring($disk->get($photo->thumb_path)), 0, 2));
        $this->assertSame([], Storage::disk('local')->allFiles('gallery-uploads'), 'chunks are cleaned up');
    }

    public function test_an_upright_photo_keeps_its_exact_pixels_but_loses_its_metadata(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);
        $bytes = $this->jpeg(orientation: 1);

        $this->upload($photographer, $album, $bytes)->assertOk();

        $original = Storage::disk('local')->get(Photo::sole()->original_path);
        $this->assertStringNotContainsString('Exif', $original);
        $this->assertSame($this->jpeg(), $original, 'only the EXIF segment is removed');
    }

    public function test_the_same_photo_is_not_added_to_an_album_twice(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);

        $this->upload($photographer, $album, $this->jpeg())->assertJson(['done' => true, 'duplicate' => false]);
        $this->upload($photographer, $album, $this->jpeg(), 'DSC_0001 (copy).jpg')->assertJson(['done' => true, 'duplicate' => true]);

        $this->assertSame(1, Photo::count());
    }

    public function test_files_that_are_not_photos_are_rejected(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);

        $this->upload($photographer, $album, str_repeat('not a photo ', 50))
            ->assertStatus(422)->assertJson(['message' => 'This file is not a JPEG or PNG photo.']);

        $this->actingAs($photographer)->postJson(route('media.albums.uploads', $album), [
            'upload_id' => (string) Str::uuid(), 'name' => 'clip.mov', 'size' => 10, 'total' => 1, 'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('chunk', '0123456789'),
        ])->assertStatus(422)->assertJsonValidationErrors(['name' => 'Only JPEG and PNG photos can be uploaded.']);

        $this->assertSame(0, Photo::count());
    }

    public function test_photos_stay_private_until_the_photographer_publishes_them(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);
        $this->upload($photographer, $album, $this->jpeg());
        $photo = Photo::sole();

        auth()->logout();
        $this->get(route('gallery.album', $album))->assertNotFound();
        $this->get($photo->url())->assertNotFound();
        $this->get($photo->downloadUrl())->assertNotFound();
        $this->get(route('gallery.index'))->assertOk()->assertSee('Photos are on their way')->assertDontSee('Official opening');

        // The photographer can preview it.
        $this->actingAs($photographer)->get($photo->url())->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($photographer)->from(route('media.albums.show', $album))
            ->post(route('media.albums.photos', $album), ['action' => 'publish', 'photos' => [$photo->id]])
            ->assertRedirect(route('media.albums.show', $album))->assertSessionHas('status', '1 photo published.');

        auth()->logout();
        $this->get(route('gallery.index'))->assertOk()->assertSee('Official opening')->assertSee('1 photo');
        $this->get(route('gallery.album', $album))->assertOk()->assertSee($photo->url('display'))->assertSee('Photos by '.$photographer->name);
        $this->get($photo->url('display'))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get($photo->downloadUrl())->assertOk()->assertDownload('rehab-summit-official-opening-'.$photo->id.'.jpg');
    }

    public function test_the_upload_option_publishes_photos_straight_away(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);

        $this->upload($photographer, $album, $this->jpeg(), publish: true)->assertOk();

        $this->assertTrue(Photo::sole()->isPublished());
    }

    public function test_photographers_can_only_change_their_own_photos(): void
    {
        $neema = $this->user(Role::Photographer);
        $daudi = $this->user(Role::Photographer);
        $album = $this->album($neema);
        $this->upload($neema, $album, $this->jpeg());
        $photo = Photo::sole();

        // Daudi can add to Neema's album, but not touch her photo or change the album.
        $this->upload($daudi, $album, $this->jpeg(orientation: 3))->assertJson(['done' => true]);
        $this->actingAs($daudi)->post(route('media.albums.photos', $album), ['action' => 'delete', 'photos' => [$photo->id]])
            ->assertSessionHas('status', '0 photos deleted. 1 photo by other photographers were left as they were.');
        $this->assertModelExists($photo);
        $this->actingAs($daudi)->put(route('media.albums.update', $album), ['title' => 'Renamed'])->assertForbidden();

        // An admin can change anything.
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->post(route('media.albums.photos', $album), ['action' => 'delete', 'photos' => [$photo->id]]);
        $this->assertModelMissing($photo);
        Storage::disk('local')->assertMissing($photo->original_path);
    }

    public function test_a_photographer_cannot_delete_an_album_that_has_photos(): void
    {
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);
        $this->upload($photographer, $album, $this->jpeg());

        $this->actingAs($photographer)->delete(route('media.albums.destroy', $album))->assertSessionHasErrors('album');
        $this->assertModelExists($album);

        $this->actingAs($this->user(Role::Admin))->delete(route('media.albums.destroy', $album))->assertRedirect(route('media.albums.index'));
        $this->assertModelMissing($album);
        $this->assertSame([], Storage::disk('local')->allFiles('gallery'));
    }

    public function test_only_photographers_and_admins_reach_the_photographer_pages(): void
    {
        $this->get(route('media.albums.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user(Role::Participant))->get(route('media.albums.index'))->assertForbidden();
        $this->actingAs($this->user(Role::Photographer))->get(route('media.removal-requests.index'))->assertForbidden();

        $photographer = $this->user(Role::Photographer);
        $this->actingAs($photographer)->get(route('dashboard'))->assertRedirect(route('media.albums.index'));
        $this->actingAs($photographer)->get(route('media.albums.index'))->assertOk()->assertSee('Albums &amp; uploads', false);
        $this->actingAs($photographer)->get(route('media.albums.create'))->assertOk();
    }

    public function test_an_admin_can_make_someone_a_photographer(): void
    {
        $admin = $this->user(Role::Admin);
        $user = $this->user(Role::Participant);

        $this->actingAs($admin)->put(route('admin.users.roles', $user), ['roles' => ['photographer']])->assertRedirect();

        $this->assertTrue($user->fresh()->hasRole('photographer'));
    }

    public function test_someone_in_a_photo_can_ask_for_it_to_be_removed(): void
    {
        $admin = $this->user(Role::Admin);
        $photographer = $this->user(Role::Photographer);
        $album = $this->album($photographer);
        $this->upload($photographer, $album, $this->jpeg(), publish: true);
        $photo = Photo::sole();
        auth()->logout();

        $this->from(route('gallery.album', $album))->post(route('gallery.photos.removal', $photo), [
            'name' => 'Asha Mohamed', 'email' => 'asha@example.com', 'reason' => 'I did not agree to this.',
        ])->assertRedirect(route('gallery.album', $album))->assertSessionHas('status');

        $removal = PhotoRemovalRequest::sole();
        Notification::assertSentTo($admin, PhotoRemovalRequested::class);
        $this->actingAs($admin)->get(route('media.removal-requests.index'))->assertOk()->assertSee('Asha Mohamed')->assertSee('I did not agree to this.');

        $this->actingAs($admin)->put(route('media.removal-requests.update', $removal), ['decision' => 'remove'])->assertRedirect();

        $this->assertModelMissing($photo);
        Storage::disk('local')->assertMissing($photo->display_path);
        $removal->refresh();
        $this->assertSame('removed', $removal->outcome);
        $this->assertNull($removal->photo_id);
        $this->assertSame($admin->id, $removal->resolved_by);
        Notification::assertSentOnDemand(PhotoRemovalDecided::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'asha@example.com');
    }

    public function test_albums_link_to_programme_sessions(): void
    {
        $session = $this->edition->sessions()->create([
            'title' => 'Official opening and keynote address', 'kind' => 'plenary', 'hall' => 'Main Hall',
            'starts_at' => '2027-09-16 09:00', 'ends_at' => '2027-09-16 10:30',
        ]);
        $photographer = $this->user(Role::Photographer);

        $this->actingAs($photographer)->post(route('media.albums.store'), [
            'title' => 'Opening', 'programme_session_id' => $session->id, 'day' => '',
        ])->assertRedirect();
        $album = Album::sole();
        $this->assertSame('2027-09-16', $album->day->toDateString(), 'the day comes from the session');
        $this->assertSame('2027-opening', $album->slug);

        $this->upload($photographer, $album, $this->jpeg(), publish: true);
        auth()->logout();

        $this->get(route('programme'))->assertOk()->assertSee(route('gallery.album', $album))->assertSee('Photos: Opening');
        $this->get(route('gallery.index'))->assertOk()->assertSee('Day 2 · Thu 16 Sep')->assertSee('Main Hall');
        $this->get(route('gallery.album', $album))->assertOk()->assertSee('Official opening and keynote address');
    }
}
