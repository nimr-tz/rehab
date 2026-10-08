<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Album;
use App\Models\Edition;
use App\Models\PhotoRemovalRequest;
use App\Models\User;
use App\Services\GalleryService;
use Carbon\CarbonImmutable;
use GdImage;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sample photo gallery for the demo summit: two photographer accounts and an
 * album for each highlight of the programme. The photos are generated
 * placeholders, marked "Sample photo", and go through the same processing as
 * real uploads. Real photos replace them when the photographers upload.
 */
class GallerySeeder extends Seeder
{
    /** [title, programme session (or null), day (when no session), description, photographer, photos, drafts] */
    private const ALBUMS = [
        ['Official opening and keynote', 'Official opening and keynote address', null, 'Welcome addresses from Rehab Health and the Ministry of Health, and the keynote on rehabilitation in universal health coverage.', 'neema', 9, 0],
        ['Panel: rehabilitation in primary health care', 'Rehabilitation in primary health care: from policy to practice', null, null, 'daudi', 6, 0],
        ['Parallel oral sessions', null, 1, 'Presenters sharing their research in the parallel oral sessions in Halls A and B.', 'neema', 7, 0],
        ['Keynote: assistive technology for all', 'Keynote: Assistive technology for all', null, null, 'daudi', 6, 0],
        ['Exhibition and partner stands', null, 2, 'Partners and exhibitors showing assistive devices, services and training programmes.', 'neema', 7, 0],
        ['Gala dinner', null, 2, 'An evening of music, recognition and conversation.', 'daudi', 8, 0],
        ['Closing ceremony and awards', 'Closing ceremony and awards', null, 'Best abstract awards, the summit declaration and farewells.', 'neema', 8, 3],
    ];

    /** Background and light colours for each album's placeholder scenes. */
    private const PALETTES = [
        ['#04202e', '#024f6d', ['#f2b302', '#ffffff', '#5fb4d4']],
        ['#0a2733', '#1b7fa3', ['#fd9a8f', '#ffffff', '#f2b302']],
        ['#1d2614', '#45582e', ['#f2b302', '#ffffff', '#b9cf8f']],
        ['#04202e', '#0e6688', ['#df670d', '#f2b302', '#ffffff']],
        ['#2a2208', '#7a5c00', ['#f2b302', '#ffffff', '#45582e']],
        ['#1f0d06', '#7a3405', ['#df670d', '#fd9a8f', '#f2b302']],
        ['#04202e', '#024f6d', ['#f2b302', '#df670d', '#ffffff']],
    ];

    public function run(GalleryService $gallery): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The GallerySeeder adds sample photos and must not run in production.');
        }

        $edition = Edition::current();
        if (! $edition) {
            return;
        }

        $edition->albums()->each(fn (Album $album) => $gallery->deleteAlbum($album));
        PhotoRemovalRequest::query()->delete();
        mt_srand(2027);

        $photographers = [
            'neema' => $this->photographer('photographer@rehab.test', 'Neema', 'Mushi'),
            'daudi' => $this->photographer('d.massawe@rehab.test', 'Daudi', 'Massawe'),
        ];
        $sessions = $edition->sessions()->get()->keyBy('title');
        $temp = storage_path('app/private/gallery-uploads/seed.jpg');
        @mkdir(dirname($temp), 0775, true);

        foreach (self::ALBUMS as $a => [$title, $sessionTitle, $day, $description, $who, $count, $drafts]) {
            $session = $sessionTitle ? $sessions->get($sessionTitle) : null;
            $start = $session
                ? CarbonImmutable::parse($session->starts_at)
                : CarbonImmutable::parse($edition->start_date)->addDays($day - 1)->setTime($title === 'Gala dinner' ? 19 : 10, 0);
            $photographer = $photographers[$who];

            $album = $edition->albums()->create([
                'title' => $title,
                'slug' => Album::uniqueSlug($edition, $title),
                'programme_session_id' => $session?->id,
                'day' => $start->toDateString(),
                'description' => $description,
                'created_by' => $photographer->id,
            ]);

            for ($i = 0; $i < $count; $i++) {
                imagejpeg($this->scene(self::PALETTES[$a], $i % 4 === 3), $temp, 90);

                $photo = $gallery->add($album, $photographer, $temp, sprintf('DSC_%04d.jpg', 1200 + $a * 40 + $i), $i < $count - $drafts);
                $photo?->update([
                    'taken_at' => $start->addMinutes(4 * $i + mt_rand(0, 3)),
                    'is_featured' => $i === 1 && $drafts === 0,
                ]);
            }
        }

        @unlink($temp);

        $gala = $edition->albums()->where('title', 'Gala dinner')->first();
        PhotoRemovalRequest::create([
            'photo_id' => $gala->photos()->published()->inShootingOrder()->skip(4)->first()->id,
            'album_title' => 'Gala dinner',
            'name' => 'Asha Mohamed',
            'email' => 'asha.mohamed@example.com',
            'reason' => 'I am in the front of this photo and would prefer it not to be online. Thank you.',
            'created_at' => now()->subHours(5),
        ]);
    }

    private function photographer(string $email, string $first, string $last): User
    {
        $user = User::updateOrCreate(['email' => $email], [
            'first_name' => $first, 'last_name' => $last,
            'phone' => '+2557'.mt_rand(10000000, 99999999), 'country' => 'TZ',
            'institution' => 'Rehab Health', 'profession' => 'Photographer',
            'password' => DemoSeeder::PASSWORD,
        ]);
        $user->forceFill(['email_verified_at' => now()->subMonths(2)])->save();
        $user->syncRoles([Role::Photographer->value]);

        return $user;
    }

    /**
     * A soft, out-of-focus "event" scene: stage lights over a crowd of
     * silhouettes. Drawn small and enlarged, which blurs it like a wide aperture.
     *
     * @param  array{0: string, 1: string, 2: list<string>}  $palette
     */
    private function scene(array $palette, bool $portrait): GdImage
    {
        [$w, $h] = $portrait ? [64, 96] : [96, 64];
        $small = imagecreatetruecolor($w, $h);
        imagealphablending($small, true);

        [$top, $bottom] = [$this->rgb($palette[0]), $this->rgb($palette[1])];
        for ($y = 0; $y < $h; $y++) {
            $t = $y / ($h - 1);
            $colour = array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $top, $bottom);
            imageline($small, 0, $y, $w, $y, imagecolorallocate($small, ...$colour));
        }

        // Lights: soft, overlapping discs.
        for ($i = 0; $i < 16; $i++) {
            $colour = $this->rgb($palette[2][mt_rand(0, 2)]);
            $size = mt_rand(4, 16);
            imagefilledellipse($small, mt_rand(0, $w), mt_rand(0, (int) ($h * 0.65)), $size, $size, imagecolorallocatealpha($small, ...[...$colour, mt_rand(70, 105)]));
        }

        // Crowd: rows of heads and shoulders, darker towards the front.
        foreach ([[0.72, 5, 95], [0.84, 7, 60], [0.97, 9, 20]] as [$row, $head, $alpha]) {
            $ink = imagecolorallocatealpha($small, 10, 14, 20, $alpha);
            for ($x = mt_rand(-4, 2); $x < $w + $head; $x += $head * 2 + mt_rand(0, 4)) {
                $y = (int) ($h * $row) + mt_rand(-2, 2);
                imagefilledellipse($small, $x, $y, $head, (int) ($head * 1.2), $ink);
                imagefilledellipse($small, $x, $y + $head * 2, $head * 3, $head * 3, $ink);
            }
        }

        [$W, $H] = $portrait ? [1200, 1800] : [1800, 1200];
        // Soften the small drawing, then enlarge it smoothly, like an out-of-focus lens.
        for ($i = 0; $i < 3; $i++) {
            imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
        }
        imagesetinterpolation($small, IMG_BICUBIC);
        $image = imagescale($small, $W, $H, IMG_BICUBIC);
        imagedestroy($small);

        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $white = imagecolorallocatealpha($image, 255, 255, 255, 40);
        if (is_file($font)) {
            $box = imagettfbbox(30, 0, $font, 'Sample photo');
            imagettftext($image, 30, 0, $W - ($box[2] - $box[0]) - 48, $H - 48, $white, $font, 'Sample photo');
        } else {
            imagestring($image, 5, $W - 160, $H - 48, 'Sample photo', $white);
        }

        return $image;
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function rgb(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }
}
