@php
    $template = config('print_design.badge.template', []);
    $printWidth  = $template['print_width_mm']  ?? 80.88;
    $printHeight = $template['print_height_mm'] ?? 137.4;
@endphp
<style>
    @page { margin: 0; size: {{ $printWidth }}mm {{ $printHeight }}mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #ffffff;
        width: {{ $printWidth }}mm;
        height: {{ $printHeight }}mm;
        overflow: hidden;
    }
    .badge {
        width: {{ $printWidth }}mm;
        height: {{ $printHeight }}mm;
        position: relative;
        page-break-inside: avoid;
        page-break-after: always;
        background: #ffffff;
    }
    .badge:last-child { page-break-after: avoid; }
    .badge-shell {
        position: absolute;
        left: 0;
        top: 0;
        width: {{ $printWidth }}mm;
        height: {{ $printHeight }}mm;
        overflow: hidden;
        background-repeat: no-repeat;
        background-position: center top;
        background-size: 100% 100%;
    }
    .badge-background {
        position: absolute;
        left: 0;
        top: 0;
        width: {{ $printWidth }}mm;
        height: {{ $printHeight }}mm;
        display: block;
        z-index: 0;
    }
    .badge-text {
        position: absolute;
        padding: 0 2.5mm;
        text-align: center;
        word-break: break-word;
        overflow-wrap: anywhere;
        z-index: 3;
        font-family: Arial, Helvetica, sans-serif;
    }
    .badge-qr {
        position: absolute;
        z-index: 3;
    }
    .badge-qr-image {
        width: 100%;
        height: 100%;
        display: block;
    }
</style>
