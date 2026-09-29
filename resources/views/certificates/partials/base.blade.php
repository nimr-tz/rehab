@php
    /**
     * Certificate overlay template (badge-style).
     *
     * The variant's background image IS the certificate design — frames, logos,
     * headings and signatures are all baked into the artwork. This template only
     * stamps the dynamic pieces on top: participant name, optional body lines,
     * the verification QR code and the certificate metadata.
     *
     * Expected variables:
     *  - $variant    : key into config('print_design.certificate.variants')
     *  - $pageTitle  : browser/document title
     *  - $bodyLines  : array of plain-text lines to print in the body slot (optional)
     *  - $user, $certificate, $qrImage, $certificateNumber, $issueDate
     */
    $certificateConfig = config('print_design.certificate');
    $template = $certificateConfig['template'];
    $variantConfig = $certificateConfig['variants'][$variant] ?? [];
    $placeholders = array_replace_recursive(
        $certificateConfig['placeholders'],
        $variantConfig['placeholders'] ?? []
    );

    $pageW = $template['width_mm'];
    $pageH = $template['height_mm'];

    $backgroundPath = public_path($variantConfig['background'] ?? '');
    $backgroundSrc = is_file($backgroundPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($backgroundPath))
        : '';

    // Convert a placeholder's percent geometry to absolute mm + font styling.
    $textStyle = function (array $p) use ($pageW, $pageH) {
        return sprintf(
            'left: %.2fmm; top: %.2fmm; width: %.2fmm;',
            $pageW * $p['left'] / 100,
            $pageH * $p['top'] / 100,
            $pageW * $p['width'] / 100
        ) . "
            font-size: {$p['font_size']};
            line-height: {$p['line_height']};
            letter-spacing: {$p['letter_spacing']};
            color: {$p['color']};
            font-weight: {$p['font_weight']};
            text-align: {$p['align']};
            text-transform: {$p['text_transform']};
            font-family: {$p['font_family']};
        ";
    };

    $participantName = trim(($user->title ? $user->title . ' ' : '') . $user->full_name);
    $bodyLines = array_values(array_filter($bodyLines ?? []));

    // Shrink long names so they stay on one line inside the artwork's name slot
    $nameLength = mb_strlen($participantName);
    $nameMaxChars = $placeholders['name']['max_chars'] ?? 26;
    if ($nameLength > $nameMaxChars) {
        $baseSize = (float) $placeholders['name']['font_size'];
        $minSize = (float) ($placeholders['name']['min_font_size'] ?? '4.8mm');
        $placeholders['name']['font_size'] = max($minSize, $baseSize * $nameMaxChars / $nameLength) . 'mm';
    }

    // Shrink body text if the total content is very long, so it doesn't overflow into the footer
    if (!empty($bodyLines)) {
        $totalBodyChars = array_sum(array_map('mb_strlen', array_map('strip_tags', $bodyLines)));
        $bodyMaxChars = $placeholders['body']['max_chars'] ?? 180;
        if ($totalBodyChars > $bodyMaxChars) {
            $baseSize = (float) $placeholders['body']['font_size'];
            $minSize = (float) ($placeholders['body']['min_font_size'] ?? '3.5mm');
            $placeholders['body']['font_size'] = max($minSize, $baseSize * $bodyMaxChars / $totalBodyChars) . 'mm';
        }
    }
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $pageTitle }} - {{ $user->full_name }}</title>
    <style>
        @foreach(config('print_design.certificate.fonts', []) as $font)
        @font-face {
            font-family: '{{ $font['family'] }}';
            font-weight: {{ $font['weight'] }};
            font-style: {{ $font['style'] }};
            {{-- Local path first (dompdf), web URL fallback (browser preview) --}}
            src: url('{{ str_replace('\\', '/', public_path($font['file'])) }}') format('truetype'),
                 url('{{ asset($font['file']) }}') format('truetype');
        }
        @endforeach
        @page {
            margin: 0;
            size: {{ $pageW }}mm {{ $pageH }}mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            width: {{ $pageW }}mm;
            height: {{ $pageH }}mm;
        }
        .cert-shell {
            position: relative;
            width: {{ $pageW }}mm;
            height: {{ $pageH }}mm;
            overflow: hidden;
        }
        .cert-background {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $pageW }}mm;
            height: {{ $pageH }}mm;
        }
        .cert-text {
            position: absolute;
            z-index: 5;
        }
        .cert-qr {
            position: absolute;
            z-index: 5;
        }
        .cert-qr img {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <div class="cert-shell">
        @if($backgroundSrc)
            <img src="{{ $backgroundSrc }}" alt="Certificate Template" class="cert-background">
        @endif

        <div class="cert-text" style="{{ $textStyle($placeholders['name']) }}">
            {{ $participantName }}
        </div>

        @if(!empty($bodyLines))
            <div class="cert-text" style="{{ $textStyle($placeholders['body']) }}">
                @foreach($bodyLines as $line)
                    <div>{!! $line !!}</div>
                @endforeach
            </div>
        @endif

        <div class="cert-qr" style="
            left: {{ sprintf('%.2f', $pageW * $placeholders['qr']['left'] / 100) }}mm;
            top: {{ sprintf('%.2f', $pageH * $placeholders['qr']['top'] / 100) }}mm;
            width: {{ sprintf('%.2f', $pageW * $placeholders['qr']['width'] / 100) }}mm;
            height: {{ sprintf('%.2f', $pageH * $placeholders['qr']['height'] / 100) }}mm;
        ">
            <img src="{{ $qrImage }}" alt="Verification QR Code">
        </div>

        @if($placeholders['meta']['enabled'] ?? true)
            <div class="cert-text" style="{{ $textStyle($placeholders['meta']) }}">
                <div>Cert No: {{ $certificateNumber }}</div>
                <div>Issued: {{ $issueDate }}</div>
            </div>
        @endif
    </div>
</body>
</html>
