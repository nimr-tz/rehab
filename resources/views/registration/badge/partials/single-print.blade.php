@php
    $badgeData = $badgeData ?? [];
    $badgeSubject = $badgeData['subject'] ?? null;
    $badgeName = preg_replace('/\s+/', ' ', trim($badgeData['name'] ?? ''));
    $badgeInstitution = preg_replace('/\s+/', ' ', trim((string) ($badgeData['institution'] ?? '')));
    $badgeQrImage = $badgeData['qrImage'] ?? null;
    $badgeConfig = config('print_design.badge');
    $template = $badgeConfig['template'] ?? [];
    $placeholders = $badgeConfig['placeholders'] ?? [];
    $background = public_path($template['background'] ?? 'images/brand/badge.png');
    $backgroundSrc = is_file($background)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($background))
        : $background;

    if ($badgeSubject && !empty($badgeSubject->title)) {
        $nameWithoutTitle = preg_replace('/^\s*' . preg_quote($badgeSubject->title, '/') . '\s+/i', '', $badgeName);
        $badgeName = trim($badgeSubject->title . "\u{00A0}" . $nameWithoutTitle);
    }

    $namePrintStyle = "
        left: " . ($placeholders['name']['left'] ?? '17%') . ";
        top: " . ($placeholders['name']['top_print'] ?? ($placeholders['name']['top'] ?? '39%')) . ";
        width: " . ($placeholders['name']['width'] ?? '66%') . ";
        min-height: " . ($placeholders['name']['min_height'] ?? '10%') . ";
        font-size: " . ($placeholders['name']['font_size_print'] ?? '8mm') . ";
        line-height: " . ($placeholders['name']['line_height'] ?? '1.05') . ";
        letter-spacing: " . ($placeholders['name']['letter_spacing'] ?? '-0.03em') . ";
        color: " . ($placeholders['name']['color_print'] ?? ($placeholders['name']['color'] ?? '#154b5f')) . ";
        font-weight: " . ($placeholders['name']['font_weight_print'] ?? ($placeholders['name']['font_weight'] ?? 800)) . ";
        text-align: " . ($placeholders['name']['align'] ?? 'center') . ";
        text-transform: " . ($placeholders['name']['text_transform'] ?? 'uppercase') . ";
        font-family: " . ($placeholders['name']['font_family_print'] ?? ($placeholders['name']['font_family'] ?? 'Helvetica, Arial, sans-serif')) . ";
    ";

    $institutionPrintStyle = "
        left: " . ($placeholders['institution']['left'] ?? '17%') . ";
        top: " . ($placeholders['institution']['top_print'] ?? ($placeholders['institution']['top'] ?? '51%')) . ";
        width: " . ($placeholders['institution']['width'] ?? '66%') . ";
        min-height: " . ($placeholders['institution']['min_height'] ?? '7%') . ";
        font-size: " . ($placeholders['institution']['font_size_print'] ?? '5mm') . ";
        line-height: " . ($placeholders['institution']['line_height'] ?? '1.18') . ";
        letter-spacing: " . ($placeholders['institution']['letter_spacing'] ?? '-0.01em') . ";
        color: " . ($placeholders['institution']['color_print'] ?? ($placeholders['institution']['color'] ?? '#154b5f')) . ";
        font-weight: " . ($placeholders['institution']['font_weight_print'] ?? ($placeholders['institution']['font_weight'] ?? 700)) . ";
        text-align: " . ($placeholders['institution']['align'] ?? 'center') . ";
        text-transform: " . ($placeholders['institution']['text_transform'] ?? 'uppercase') . ";
        font-family: " . ($placeholders['institution']['font_family_print'] ?? ($placeholders['institution']['font_family'] ?? 'Helvetica, Arial, sans-serif')) . ";
    ";
@endphp

<div class="badge">
    <div class="badge-shell">
        <img src="{{ $backgroundSrc }}" alt="Badge Template" class="badge-background">

        @foreach([['x' => '-0.13mm', 'y' => '0'], ['x' => '0.13mm', 'y' => '0'], ['x' => '0', 'y' => '-0.10mm'], ['x' => '0', 'y' => '0.10mm']] as $offset)
            <div class="badge-text badge-name badge-print-edge"
                 style="{{ $namePrintStyle }} margin-left: {{ $offset['x'] }}; margin-top: {{ $offset['y'] }};">
                {{ $badgeName ?: 'Conference Delegate' }}
            </div>
        @endforeach

        <div class="badge-text badge-name"
             style="{{ $namePrintStyle }}">
            {{ $badgeName ?: 'Conference Delegate' }}
        </div>

        @foreach([['x' => '-0.10mm', 'y' => '0'], ['x' => '0.10mm', 'y' => '0'], ['x' => '0', 'y' => '-0.08mm'], ['x' => '0', 'y' => '0.08mm']] as $offset)
            <div class="badge-text badge-institution badge-print-edge"
                 style="{{ $institutionPrintStyle }} margin-left: {{ $offset['x'] }}; margin-top: {{ $offset['y'] }};">
                {{ $badgeInstitution ?: config('conference.host_short') }}
            </div>
        @endforeach

        <div class="badge-text badge-institution"
             style="{{ $institutionPrintStyle }}">
            {{ $badgeInstitution ?: config('conference.host_short') }}
        </div>

        @if($badgeQrImage)
            <div class="badge-qr"
                 style="
                    left: {{ $placeholders['qr']['left'] ?? '35%' }};
                    top: {{ $placeholders['qr']['top_print'] ?? ($placeholders['qr']['top'] ?? '62%') }};
                    width: {{ $placeholders['qr']['width'] ?? '31%' }};
                    height: {{ $placeholders['qr']['height'] ?? '18%' }};
                 ">
                <img src="{{ $badgeQrImage }}" alt="QR Code" class="badge-qr-image">
            </div>
        @endif

    </div>
</div>
