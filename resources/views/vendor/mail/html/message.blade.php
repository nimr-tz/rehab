<x-mail::layout>
{{-- Header --}}
@hasSection('header')
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === config('conference.short_name') || trim($slot) === config('app.name'))
<img src="{{ asset(config('conference.logo_mark_path')) }}" class="logo" alt="{{ config('conference.host') }}" style="max-height: 50px;">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
@endif

{{-- Body --}}
@hasSection('body')
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell" style="padding: 0;">
<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #2d3748;">
{{ $slot }}
</div>
</td>
</tr>
</table>
</td>
</tr>
@endif

{{-- Subcopy --}}
@isset($subcopy)
<tr>
<td class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" style="padding: 0;">
<div style="font-family: Arial, sans-serif; font-size: 12px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 20px;">
{{ $subcopy }}
</div>
</td>
</tr>
</table>
</td>
</tr>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
