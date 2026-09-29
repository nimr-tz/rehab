@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color ?? 'primary' }}" target="_blank" rel="noopener" style="font-family: Arial, sans-serif; box-sizing: border-box; border-radius: 6px; color: #ffffff; cursor: pointer; display: inline-block; font-size: 16px; font-weight: 600; line-height: 50px; margin: 0; text-decoration: none; text-transform: none; background-color: #667eea; border: none; padding: 0 30px; min-width: 200px;">
{{ $slot }}
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
