<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}
.footer {
width: 100% !important;
}
}
@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}
</style>
</head>
<body>
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="wrapper-inner" style="background-color: #f8fafc; padding: 20px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="text-align: center; padding: 20px 0;">
<!-- {{ config('conference.short_name') }} {{ config('conference.year') }} Header -->
<div style="background-color: #ffffff; border-radius: 8px; padding: 30px; margin: 0 auto; max-width: 600px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
<!-- Logo and Conference Info -->
<div style="text-align: center; margin-bottom: 20px;">
<img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}" style="max-height: 60px; max-width: 200px; margin-bottom: 15px;">
<h1 style="color: #2d3748; font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; margin: 0; margin-bottom: 5px;">{{ config('conference.name') }}</h1>
<h2 style="color: #667eea; font-family: Arial, sans-serif; font-size: 20px; font-weight: 600; margin: 0; margin-bottom: 10px;">{{ config('conference.short_name') }} {{ config('conference.year') }}</h2>
<p style="color: #718096; font-family: Arial, sans-serif; font-size: 14px; margin: 0;">Conference Portal</p>
</div>

<!-- Main Content Area -->
<div style="background-color: #ffffff; border-radius: 8px; padding: 40px; margin: 20px 0; border: 1px solid #e2e8f0;">
{{ $slot }}
</div>

<!-- Footer -->
<div style="text-align: center; margin-top: 30px; padding: 20px; border-top: 1px solid #e2e8f0;">
<p style="color: #718096; font-family: Arial, sans-serif; font-size: 12px; margin: 0;">
© 2026 {{ config('conference.host') }}. All rights reserved.
</p>
<p style="color: #a0aec0; font-family: Arial, sans-serif; font-size: 11px; margin: 10px 0 0 0;">
This email was sent from the {{ config('conference.short_name') }} {{ config('conference.year') }} Conference Portal
</p>
</div>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
