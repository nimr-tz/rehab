<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Badge — {{ $visitor->name }}</title>
    @include('registration.badge.partials.print-styles')
</head>
<body>
@php
    $badgeConfig   = config('print_design.badge');
    $template      = $badgeConfig['template'] ?? [];
    $placeholders  = $badgeConfig['placeholders'] ?? [];

    $background    = public_path($template['background'] ?? 'images/brand/badge.png');
    $backgroundSrc = is_file($background)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($background))
        : $background;

    $visitorName = preg_replace('/\s+/', ' ', trim($visitor->effective_name));
    $institution  = preg_replace('/\s+/', ' ', trim((string) $visitor->institution));

    $namePrintStyle = "
        left: "      . ($placeholders['name']['left']            ?? '17%')               . ";
        top: "       . ($placeholders['name']['top_print']       ?? ($placeholders['name']['top'] ?? '39%')) . ";
        width: "     . ($placeholders['name']['width']           ?? '66%')               . ";
        min-height: ". ($placeholders['name']['min_height']      ?? '10%')               . ";
        font-size: " . ($placeholders['name']['font_size_print'] ?? '8mm')               . ";
        line-height: ". ($placeholders['name']['line_height']    ?? '1.05')              . ";
        letter-spacing: " . ($placeholders['name']['letter_spacing'] ?? '-0.03em')       . ";
        color: "     . ($placeholders['name']['color']           ?? '#154b5f')           . ";
        font-weight: ". ($placeholders['name']['font_weight_print'] ?? ($placeholders['name']['font_weight'] ?? 800)) . ";
        text-align: ". ($placeholders['name']['align']           ?? 'center')            . ";
        text-transform: " . ($placeholders['name']['text_transform'] ?? 'uppercase')     . ";
        font-family: " . ($placeholders['name']['font_family_print'] ?? 'Helvetica, Arial, sans-serif') . ";
    ";

    $institutionPrintStyle = "
        left: "      . ($placeholders['institution']['left']            ?? '17%')                . ";
        top: "       . ($placeholders['institution']['top_print']       ?? ($placeholders['institution']['top'] ?? '51%')) . ";
        width: "     . ($placeholders['institution']['width']           ?? '66%')                . ";
        min-height: ". ($placeholders['institution']['min_height']      ?? '7%')                 . ";
        font-size: " . ($placeholders['institution']['font_size_print'] ?? '5mm')                . ";
        line-height: ". ($placeholders['institution']['line_height']    ?? '1.18')               . ";
        letter-spacing: " . ($placeholders['institution']['letter_spacing'] ?? '-0.01em')        . ";
        color: "     . ($placeholders['institution']['color']           ?? '#154b5f')            . ";
        font-weight: ". ($placeholders['institution']['font_weight_print'] ?? ($placeholders['institution']['font_weight'] ?? 700)) . ";
        text-align: ". ($placeholders['institution']['align']           ?? 'center')             . ";
        text-transform: " . ($placeholders['institution']['text_transform'] ?? 'uppercase')      . ";
        font-family: " . ($placeholders['institution']['font_family_print'] ?? 'Helvetica, Arial, sans-serif') . ";
    ";
@endphp

<div class="badge">
    <div class="badge-shell">
        <img src="{{ $backgroundSrc }}" alt="Badge Template" class="badge-background">

        {{-- Name shadow strokes --}}
        @foreach([['x' => '-0.10mm', 'y' => '0'], ['x' => '0.10mm', 'y' => '0'], ['x' => '0', 'y' => '-0.08mm'], ['x' => '0', 'y' => '0.08mm']] as $offset)
            <div class="badge-text badge-name" style="{{ $namePrintStyle }} margin-left: {{ $offset['x'] }}; margin-top: {{ $offset['y'] }}; color: rgba(27,47,134,0.18);">
                {{ $visitorName ?: 'Visitor' }}
            </div>
        @endforeach

        <div class="badge-text badge-name" style="{{ $namePrintStyle }}">
            {{ $visitorName ?: 'Visitor' }}
        </div>

        {{-- Institution --}}
        @foreach([['x' => '-0.08mm', 'y' => '0'], ['x' => '0.08mm', 'y' => '0'], ['x' => '0', 'y' => '-0.06mm'], ['x' => '0', 'y' => '0.06mm']] as $offset)
            <div class="badge-text badge-institution" style="{{ $institutionPrintStyle }} margin-left: {{ $offset['x'] }}; margin-top: {{ $offset['y'] }}; color: rgba(27,47,134,0.18);">
                {{ $institution ?: config('conference.host_short') }}
            </div>
        @endforeach

        <div class="badge-text badge-institution" style="{{ $institutionPrintStyle }}">
            {{ $institution ?: config('conference.host_short') }}
        </div>

        {{-- Divider left arm --}}
        <div style="position:absolute;left:30%;top:{{ $placeholders['divider']['top_print'] ?? ($placeholders['divider']['top'] ?? '73%') }};width:17%;height:0.3mm;background:{{ $placeholders['divider']['background'] ?? '#d8a23b' }};z-index:3;"></div>
        {{-- Divider centre jewel --}}
        <div style="position:absolute;left:{{ $placeholders['divider_dot']['left'] ?? '48%' }};top:{{ $placeholders['divider_dot']['top_print'] ?? ($placeholders['divider_dot']['top'] ?? '72.1%') }};width:3.6mm;height:3.6mm;margin-left:-0.55mm;margin-top:-0.55mm;background:#f2ddb2;border-radius:50%;z-index:3;"></div>
        <div style="position:absolute;left:{{ $placeholders['divider_dot']['left'] ?? '48%' }};top:{{ $placeholders['divider_dot']['top_print'] ?? ($placeholders['divider_dot']['top'] ?? '72.1%') }};width:{{ $placeholders['divider_dot']['size_print'] ?? '2mm' }};height:{{ $placeholders['divider_dot']['size_print'] ?? '2mm' }};background:{{ $placeholders['divider_dot']['background'] ?? '#154b5f' }};border-radius:50%;border:0.35mm solid {{ $placeholders['divider']['background'] ?? '#d8a23b' }};z-index:4;"></div>
        {{-- Divider right arm --}}
        <div style="position:absolute;left:53%;top:{{ $placeholders['divider']['top_print'] ?? ($placeholders['divider']['top'] ?? '73%') }};width:17%;height:0.3mm;background:{{ $placeholders['divider']['background'] ?? '#d8a23b' }};z-index:3;"></div>

        @if($qrImage)
            <div class="badge-qr" style="left:{{ $placeholders['qr']['left'] ?? '35%' }};top:{{ $placeholders['qr']['top_print'] ?? ($placeholders['qr']['top'] ?? '62%') }};width:{{ $placeholders['qr']['width'] ?? '31%' }};height:{{ $placeholders['qr']['height'] ?? '18%' }};">
                <img src="{{ $qrImage }}" alt="QR Code" class="badge-qr-image">
            </div>
        @endif
    </div>
</div>
</body>
</html>
