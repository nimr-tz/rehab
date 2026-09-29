@php
    $badgeUser = $user ?? Auth::user();
    $displayInstitute = $badgeUser->institute ?: ($badgeUser->affiliation ?: $badgeUser->institution);
    $displayName = preg_replace('/\s+/', ' ', trim(($badgeUser->title ? $badgeUser->title . "\u{00A0}" : '') . ($badgeUser->first_name ?? '') . ' ' . ($badgeUser->last_name ?? '')));
    if ($displayName === '') {
        $displayName = $badgeUser->full_name ?? 'Conference Delegate';
    }

    $badgeConfig = config('print_design.badge');
    $template = $badgeConfig['template'] ?? [];
    $placeholders = $badgeConfig['placeholders'] ?? [];
    $background = asset($template['background'] ?? 'images/brand/badge.png');
    $previewWidth = $template['preview_width_px'] ?? 430;

    $qrCode = null;
    if ($badgeUser instanceof \App\Models\User) {
        $qrCode = $badgeUser->qr_code_base64;
    } elseif (!empty($qrToken)) {
        try {
            $options = new \chillerlan\QRCode\QROptions([
                'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
                'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
                'addQuietzone' => true,
                'quietzoneSize' => 1,
                'scale' => 10,
            ]);
            $qrCode = (new \chillerlan\QRCode\QRCode($options))->render(route('badge.public', $qrToken));
        } catch (\Exception $e) {
            $qrCode = null;
        }
    }
@endphp

<div class="relative mx-auto" style="width: min(100%, {{ $previewWidth }}px); container-type: inline-size;">
    <div class="relative overflow-hidden shadow-[0_22px_60px_-28px_rgba(15,23,42,0.45)]" id="badge-card-inner">
        <img src="{{ $background }}" alt="Badge Template" class="block w-full h-auto">

        <div class="absolute flex items-center justify-center px-4 text-center"
             style="
                left: {{ $placeholders['name']['left'] ?? '17%' }};
                top: {{ $placeholders['name']['top'] ?? '39.5%' }};
                width: {{ $placeholders['name']['width'] ?? '66%' }};
                min-height: {{ $placeholders['name']['min_height'] ?? '10%' }};
                font-size: clamp(0.75rem, 6.5cqw, {{ $placeholders['name']['font_size_preview'] ?? '2.7rem' }});
                line-height: {{ $placeholders['name']['line_height'] ?? '1.05' }};
                letter-spacing: {{ $placeholders['name']['letter_spacing'] ?? '-0.03em' }};
                color: {{ $placeholders['name']['color'] ?? '#154b5f' }};
                font-weight: {{ $placeholders['name']['font_weight'] ?? 800 }};
                text-transform: {{ $placeholders['name']['text_transform'] ?? 'uppercase' }};
                text-shadow: {{ $placeholders['name']['shadow'] ?? 'none' }};
                -webkit-text-stroke: 0.5px {{ $placeholders['name']['color'] ?? '#154b5f' }};
                word-break: normal;
                overflow-wrap: break-word;
                font-family: {!! json_encode($placeholders['name']['font_family'] ?? 'Arial, Helvetica, sans-serif') !!};
             ">
            {{ $displayName }}
        </div>

        <div class="absolute flex items-center justify-center px-4 text-center"
             style="
                left: {{ $placeholders['institution']['left'] ?? '17%' }};
                top: {{ $placeholders['institution']['top'] ?? '50.8%' }};
                width: {{ $placeholders['institution']['width'] ?? '66%' }};
                min-height: {{ $placeholders['institution']['min_height'] ?? '7%' }};
                font-size: clamp(0.55rem, 3.72cqw, {{ $placeholders['institution']['font_size_preview'] ?? '1.55rem' }});
                line-height: {{ $placeholders['institution']['line_height'] ?? '1.18' }};
                letter-spacing: {{ $placeholders['institution']['letter_spacing'] ?? '-0.01em' }};
                color: {{ $placeholders['institution']['color'] ?? '#154b5f' }};
                font-weight: {{ $placeholders['institution']['font_weight'] ?? 700 }};
                text-transform: {{ $placeholders['institution']['text_transform'] ?? 'uppercase' }};
                text-shadow: {{ $placeholders['institution']['shadow'] ?? 'none' }};
                -webkit-text-stroke: 0.4px {{ $placeholders['institution']['color'] ?? '#9a5b14' }};
                word-break: break-word;
                overflow-wrap: anywhere;
                font-family: {!! json_encode($placeholders['institution']['font_family'] ?? 'Arial, Helvetica, sans-serif') !!};
             ">
            {{ $displayInstitute ?: config('conference.host_short') }}
        </div>

        @if($qrCode)
            <div class="absolute"
                 style="
                    left: {{ $placeholders['qr']['left'] ?? '35%' }};
                    top: {{ $placeholders['qr']['top'] ?? '61.8%' }};
                    width: {{ $placeholders['qr']['width'] ?? '31%' }};
                    height: {{ $placeholders['qr']['height'] ?? '17.8%' }};
                 ">
                <img src="{{ $qrCode }}" alt="QR Code" class="block w-full h-full object-contain">
            </div>
        @endif

    </div>
</div>
