@extends('emails.layout')

@section('title', 'Your ' . config('conference.short_name') . ' ' . config('conference.year') . ' Certificate is Ready')

@section('content')
    <div class="message-intro">
        Dear Participant,
    </div>

    <p>We hope this message finds you well. On behalf of the organizing committee, we would like to once again thank you for being part of the <strong>{{ config('conference.edition') }} {{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong>, held on <strong>{{ config('conference.display_dates') }}</strong> at the <strong>{{ config('conference.venue') }}, {{ config('conference.city') }}</strong>. Your registration and support made the conference a resounding success.</p>

    <p>We are pleased to inform you that your <strong>conference certificate(s) are now ready</strong> for download through the conference portal.</p>

    <div class="info-card" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: white; border: none; text-align: center; padding: 28px 32px;">
        <h3 style="color: rgba(255,255,255,0.75); margin-bottom: 6px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.12em;">Available for Download</h3>
        <div style="font-size: 20px; font-weight: 800; line-height: 1.4;">
            {{ config('conference.short_name') }} {{ config('conference.year') }} Certificates
        </div>
        <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.8;">{{ config('conference.display_dates') }} &mdash; {{ config('conference.city') }}</p>
    </div>

    <div class="info-card">
        <h3>📜 What Certificates Are Available</h3>
        <table class="details-table">
            <tr>
                <th>Attendance</th>
                <td>Certificate of Attendance for all registered participants</td>
            </tr>
            <tr>
                <th>Oral Presentation</th>
                <td>Certificate of Oral Presentation for accepted oral presenters in the programme</td>
            </tr>
            <tr>
                <th>Poster Presentation</th>
                <td>Certificate of Poster Presentation for accepted poster presenters in the programme</td>
            </tr>
        </table>
    </div>

    <div class="info-card" style="border-left: 6px solid #2563eb; background: #eff6ff;">
        <h3 style="color: #1d4ed8;">🔐 How to Download Your Certificate</h3>
        <ol style="margin: 0; padding-left: 20px; color: #1e40af; line-height: 2;">
            <li>Visit the conference portal at <a href="{{ config('app.url') }}" style="color: #2563eb; font-weight: 700;">{{ config('app.url') }}</a></li>
            <li>Click <strong>"Get My Certificate"</strong> and enter your name as it appears on your badge</li>
            <li>Complete the short conference feedback form (takes under a minute)</li>
            <li>Your certificate(s) will download immediately as a PDF</li>
        </ol>
    </div>

    <div class="info-card" style="border-left: 6px solid #10b981; background: #f0fdf4;">
        <h3 style="color: #065f46;">✅ Certificate Verification QR Code</h3>
        <p style="margin: 0; color: #064e3b;">Each certificate includes a unique <strong>QR code</strong> that can be scanned by anyone to verify its authenticity instantly through the {{ config('conference.host_short') }} verification portal. You are encouraged to share your certificate with your institution and include it in your professional records.</p>
    </div>

    <div style="text-align: center; margin: 36px 0;">
        <a href="{{ route('certificate.claim') }}" class="cta-button">Get My Certificate Now</a>
    </div>

    <p>If you experience any difficulty accessing your certificate, or if your name does not appear correctly, please contact us at <a href="mailto:{{ config('conference.contact_email', config('conference.contact_email')) }}" style="color: #2563eb; font-weight: 600;">{{ config('conference.contact_email', config('conference.contact_email')) }}</a> and we will assist you promptly.</p>

    <p>Once again, thank you for your invaluable contribution to science and public health research. We look forward to welcoming you at the next edition of {{ config('conference.short_name') }}.</p>

    <p>Warm regards,<br>
    <strong>The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</strong><br>
    <span style="color: #64748b; font-size: 14px;">{{ config('conference.host') }}</span></p>
@endsection
