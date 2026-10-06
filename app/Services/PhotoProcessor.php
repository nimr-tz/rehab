<?php

namespace App\Services;

use App\Exceptions\UnreadablePhoto;
use Carbon\CarbonImmutable;
use GdImage;
use Throwable;

/**
 * Turns an uploaded photo into the three files the gallery keeps:
 *
 * - original: full resolution and upright, with camera metadata removed (the
 *   GPS location in particular), because the public can download it. A JPEG
 *   that needs no rotation keeps its exact pixels.
 * - display: up to 2048px, for the lightbox.
 * - thumb: up to 720px, for the grids.
 *
 * Colour profiles are carried over to every copy so colours do not shift.
 */
class PhotoProcessor
{
    /** JPEG segments dropped from originals: APP1 holds EXIF and XMP (where GPS lives), APP13 holds IPTC. */
    private const JPEG_METADATA = [0xE1, 0xED];

    /** PNG chunks kept in originals: image data and colour, but no text or EXIF. */
    private const PNG_KEEP = ['IHDR', 'PLTE', 'tRNS', 'IDAT', 'IEND', 'gAMA', 'cHRM', 'sRGB', 'iCCP', 'sBIT', 'pHYs'];

    /**
     * @return array{mime: string, width: int, height: int, taken_at: ?CarbonImmutable, original: string, display: string, thumb: string}
     */
    public function process(string $path): array
    {
        $info = @getimagesize($path);

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new UnreadablePhoto('This file is not a JPEG or PNG photo.');
        }

        $limit = config('gallery.max_megapixels');
        if ($limit * 1_000_000 < $info[0] * $info[1]) {
            throw new UnreadablePhoto("This photo is larger than {$limit} megapixels. Export it at a smaller size.");
        }

        $previousLimit = $this->raiseMemoryLimit(config('gallery.memory_limit'));

        try {
            return $info[2] === IMAGETYPE_JPEG ? $this->jpeg($path) : $this->png($path);
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }

    private function jpeg(string $path): array
    {
        $bytes = file_get_contents($path);
        $exif = @exif_read_data($path) ?: [];
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $profile = $this->colourProfile($bytes);

        $image = @imagecreatefromjpeg($path) ?: throw new UnreadablePhoto('This photo could not be read. The file may be damaged.');
        $image = $this->upright($image, $orientation);

        $original = $orientation === 1 ? $this->jpegWithout($bytes, self::JPEG_METADATA) : null;
        $original ??= $this->withProfile($this->encode($image, config('gallery.original_quality')), $profile);

        return $this->result($image, 'image/jpeg', $original, $profile, $this->takenAt($exif));
    }

    private function png(string $path): array
    {
        $source = @imagecreatefrompng($path) ?: throw new UnreadablePhoto('This photo could not be read. The file may be damaged.');

        // The JPEG copies have no transparency, so it is laid on white.
        $image = imagecreatetruecolor(imagesx($source), imagesy($source));
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagecopy($image, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));
        imagedestroy($source);

        return $this->result($image, 'image/png', $this->pngWithoutMetadata(file_get_contents($path)), [], null);
    }

    /** @param  list<string>  $profile */
    private function result(GdImage $image, string $mime, string $original, array $profile, ?CarbonImmutable $takenAt): array
    {
        $display = $this->fit($image, config('gallery.display.size'));
        $thumb = $this->fit($display, config('gallery.thumb.size'));

        return [
            'mime' => $mime,
            'width' => imagesx($image),
            'height' => imagesy($image),
            'taken_at' => $takenAt,
            'original' => $original,
            'display' => $this->withProfile($this->encode($display, config('gallery.display.quality')), $profile),
            'thumb' => $this->withProfile($this->encode($thumb, config('gallery.thumb.quality')), $profile),
        ];
    }

    /** Applies the EXIF orientation, so every copy is stored the right way up. */
    private function upright(GdImage $image, int $orientation): GdImage
    {
        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        if ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        // imagerotate() turns anticlockwise.
        $angle = match ($orientation) {
            3 => 180,
            5, 8 => 90,
            6, 7 => 270,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated;
    }

    private function fit(GdImage $image, int $longest): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = $longest / max($width, $height);

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);

        return $resized;
    }

    private function encode(GdImage $image, int $quality): string
    {
        imageinterlace($image, true); // progressive, so large photos appear quickly

        ob_start();
        imagejpeg($image, null, $quality);

        return ob_get_clean();
    }

    private function takenAt(array $exif): ?CarbonImmutable
    {
        $value = $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null;

        try {
            $time = $value ? CarbonImmutable::createFromFormat('Y:m:d H:i:s', trim($value)) : null;
        } catch (Throwable) {
            return null;
        }

        return $time && $time->year >= 2000 ? $time : null;
    }

    /** The ICC profile segments of a JPEG, if it has one. @return list<string> */
    private function colourProfile(string $jpeg): array
    {
        [$segments] = $this->jpegSegments($jpeg) ?? [[]];

        return array_values(array_map(fn ($segment) => $segment[1], array_filter(
            $segments,
            fn ($segment) => $segment[0] === 0xE2 && substr($segment[1], 4, 12) === "ICC_PROFILE\0",
        )));
    }

    /** @param  list<string>  $profile */
    private function withProfile(string $jpeg, array $profile): string
    {
        $parsed = $profile ? $this->jpegSegments($jpeg) : null;

        if (! $parsed) {
            return $jpeg;
        }

        [$segments, $scan] = $parsed;
        $head = '';
        while ($segments && $segments[0][0] === 0xE0) { // the JFIF header stays first
            $head .= array_shift($segments)[1];
        }

        return "\xFF\xD8".$head.implode('', $profile).implode('', array_column($segments, 1)).$scan;
    }

    /** @param  list<int>  $markers */
    private function jpegWithout(string $jpeg, array $markers): ?string
    {
        $parsed = $this->jpegSegments($jpeg);

        if (! $parsed) {
            return null;
        }

        [$segments, $scan] = $parsed;
        $kept = array_filter($segments, fn ($segment) => ! in_array($segment[0], $markers, true));

        return "\xFF\xD8".implode('', array_column($kept, 1)).$scan;
    }

    /**
     * Splits a JPEG into its header segments and everything from the start of
     * scan onwards, which is copied untouched.
     *
     * @return array{0: list<array{0: int, 1: string}>, 1: string}|null
     */
    private function jpegSegments(string $jpeg): ?array
    {
        if (! str_starts_with($jpeg, "\xFF\xD8")) {
            return null;
        }

        $segments = [];
        $length = strlen($jpeg);
        $pos = 2;

        while ($pos + 4 <= $length) {
            if ($jpeg[$pos] !== "\xFF") {
                return null;
            }

            $marker = ord($jpeg[$pos + 1]);

            if ($marker === 0xFF) { // fill byte
                $pos++;

                continue;
            }

            if ($marker === 0xDA) {
                return [$segments, substr($jpeg, $pos)];
            }

            $size = unpack('n', substr($jpeg, $pos + 2, 2))[1];
            $segments[] = [$marker, substr($jpeg, $pos, $size + 2)];
            $pos += $size + 2;
        }

        return null;
    }

    private function pngWithoutMetadata(string $png): string
    {
        $out = substr($png, 0, 8);
        $length = strlen($png);
        $pos = 8;

        while ($pos + 12 <= $length) {
            $size = unpack('N', substr($png, $pos, 4))[1];
            $type = substr($png, $pos + 4, 4);

            if (in_array($type, self::PNG_KEEP, true)) {
                $out .= substr($png, $pos, $size + 12);
            }

            $pos += $size + 12;

            if ($type === 'IEND') {
                break;
            }
        }

        return $out;
    }

    /** Raises PHP's memory limit to $target unless it is already higher. Returns the previous limit. */
    private function raiseMemoryLimit(string $target): string
    {
        $current = ini_get('memory_limit');
        $bytes = fn (string $value) => (int) $value * match (strtolower(substr($value, -1))) {
            'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1,
        };

        if ($current !== '-1' && $bytes($current) < $bytes($target)) {
            ini_set('memory_limit', $target);
        }

        return $current;
    }
}
