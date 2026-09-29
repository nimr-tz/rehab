@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === config('conference.short_name') || trim($slot) === config('app.name'))
<img src="{{ asset(config('conference.logo_mark_path')) }}" class="logo" alt="{{ config('conference.host') }}" style="max-height: 50px;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
