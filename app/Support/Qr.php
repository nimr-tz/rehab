<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Qr
{
    /** PNG data URI, which dompdf can embed. */
    public static function dataUri(string $text, int $scale = 8): string
    {
        return (new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => $scale,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ])))->render($text);
    }
}
