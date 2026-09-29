@php
    $letterConfig = config('conference.invitation_letter', []);
    $startDate = \Carbon\Carbon::parse(config('conference.start_date'));
    $endDate = \Carbon\Carbon::parse(config('conference.end_date'));
    $sameMonth = $startDate->format('F Y') === $endDate->format('F Y');
    $participantName = trim(($letter->title ? $letter->title . ' ' : '') . ($letter->passport_name ?: ($user->full_name ?? 'Participant Name')));
    $participantAddress = $letter->institute ?: ($user->institute ?: ($user->affiliation ?: 'Participant Address'));
    $conferenceName = config('conference.name');
    $conferenceShort = config('conference.short_name') . ' ' . config('conference.year');
    $conferenceEdition = config('conference.edition');
    $conferenceDates = $sameMonth
        ? $startDate->format('jS') . ' to ' . $endDate->format('jS F Y')
        : $startDate->format('jS F Y') . ' to ' . $endDate->format('jS F Y');
    $conferenceVenue = implode(', ', array_filter([config('conference.venue'), config('conference.city'), config('conference.country')]));
    $conferenceTheme = config('conference.theme');
    $refNo = implode('/', array_filter([
        $letterConfig['reference_prefix'] ?? 'INV',
        config('conference.year'),
        $letter->id ? str_pad((string) $letter->id, 4, '0', STR_PAD_LEFT) : null,
    ]));
    $hasAbstract = !empty($abstract);
    $signaturePath = filled($letterConfig['signature_path'] ?? null) ? public_path($letterConfig['signature_path']) : null;
    $logoPath = public_path(config('conference.logo_path'));
    $contactName = $letterConfig['contact_name'] ?? null;
    $contactEmail = $letterConfig['contact_email'] ?? config('conference.contact_email');
    $contactPhone = $letterConfig['contact_phone'] ?? null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invitation Letter - {{ $conferenceShort }}</title>
    <style>
        @page { margin: 26px 51px 22px 51px; }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #000;
            font-size: 13.1px;
            line-height: 1.18;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 110px; }
        .letterhead { text-align: right; }
        .letterhead .host {
            font-family: "Times New Roman", Times, serif;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: .1px;
        }
        .letterhead .conference { font-size: 13px; margin-top: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 21px; }
        .meta-table td { vertical-align: top; }
        .reply { font-style: italic; }
        .ref { margin-top: 2px; }
        .date { text-align: right; padding-top: 22px; }
        .to-line { margin: 0 0 20px 0; }
        .copy-line { margin: 0 0 35px 0; }
        .copy-line .copy-name { font-style: italic; }
        .subject {
            font-family: "Times New Roman", Times, serif;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            font-size: 14.2px;
            line-height: 1.1;
            margin: 0 50px 25px 50px;
        }
        .paragraph { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .paragraph td { vertical-align: top; }
        .num { width: 48px; padding-right: 14px; }
        .body-text { text-align: justify; }
        .indent-only { margin-left: 78px; margin-bottom: 18px; }
        a, .link { color: #0066cc; text-decoration: none; }
        .closing { text-align: center; margin-top: 15px; margin-bottom: 14px; }
        .signature { height: 38px; margin: 3px auto 0 auto; display: block; }
        .signatory-name { font-size: 14px; }
        .signatory-title {
            font-family: "Times New Roman", Times, serif;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            border-top: 1px solid #222;
            padding-top: 6px;
            text-align: center;
            font-size: 9px;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                @if(file_exists($logoPath))
                    <img class="logo" src="{{ $logoPath }}" alt="{{ config('conference.host') }}">
                @endif
            </td>
            <td class="letterhead" style="width: 70%;">
                <div class="host">{{ strtoupper(config('conference.host')) }}</div>
                <div class="conference">{{ $conferenceEdition }} {{ $conferenceName }} ({{ $conferenceShort }})</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <div class="reply">In reply please quote;</div>
                <div class="ref">Ref. No: {{ $refNo }}</div>
            </td>
            <td class="date">{{ now()->format('jS F, Y') }}</td>
        </tr>
    </table>

    <p class="to-line">To: {{ $participantName }}</p>
    <p class="copy-line">Copy to: <span class="copy-name">{{ $participantAddress }}</span></p>

    <div class="subject">
        RE: INVITATION TO PARTICIPATE IN THE {{ strtoupper($conferenceEdition . ' ' . $conferenceName) }}<br>
        ({{ strtoupper($conferenceShort) }})
    </div>

    <div class="indent-only">Refer to the heading above.</div>

    <table class="paragraph">
        <tr>
            <td class="num">2.</td>
            <td class="body-text">
                @if($hasAbstract)
                    On behalf of the Scientific Committee, we are pleased to inform you that you have been selected to participate in the {{ $conferenceEdition }} {{ $conferenceName }} ({{ $conferenceShort }}) organised by {{ config('conference.host') }}. You are invited to deliver a presentation entitled &ldquo;<strong>{{ $abstract->title }}</strong>&rdquo; during the conference.
                @else
                    On behalf of the Organising Committee, we are pleased to invite you to participate in the {{ $conferenceEdition }} {{ $conferenceName }} ({{ $conferenceShort }}) organised by {{ config('conference.host') }}.
                @endif
            </td>
        </tr>
    </table>

    <table class="paragraph">
        <tr>
            <td class="num">3.</td>
            <td class="body-text">
                The conference will be held from {{ $conferenceDates }} at the {{ $conferenceVenue }}@if(filled($conferenceTheme)), under the theme &ldquo;{{ $conferenceTheme }}&rdquo;@endif.
            </td>
        </tr>
    </table>

    <table class="paragraph">
        <tr>
            <td class="num">4.</td>
            <td class="body-text">
                @if($hasAbstract)
                    As a selected presenter, you will be part of the scientific programme and discussions contributing to knowledge exchange and policy-relevant dialogue at the national and international levels.
                @else
                    As an invited participant, you will have the opportunity to take part in scientific sessions, discussions and policy-relevant dialogue with practitioners, researchers and stakeholders from national and international institutions.
                @endif
            </td>
        </tr>
    </table>

    <table class="paragraph">
        <tr>
            <td class="num">5.</td>
            <td class="body-text">
                Should you require any clarification or further assistance, please contact
                @if(filled($contactName)){{ $contactName }} via email at @else us at @endif
                <span class="link">{{ $contactEmail }}</span>@if(filled($contactPhone)) or by phone at {{ $contactPhone }}@endif.
            </td>
        </tr>
    </table>

    <table class="paragraph">
        <tr>
            <td class="num">6.</td>
            <td class="body-text">
                Additional conference updates are available at <span class="link">{{ config('conference.website') }}</span>.
            </td>
        </tr>
    </table>

    <table class="paragraph">
        <tr>
            <td class="num">7.</td>
            <td class="body-text">
                @if($hasAbstract)
                    Congratulations on your selection. We look forward to your valuable contribution and participation in {{ $conferenceShort }}.
                @else
                    We look forward to your valuable participation in {{ $conferenceShort }}.
                @endif
            </td>
        </tr>
    </table>

    <div class="closing">
        <div>Yours sincerely,</div>
        @if($signaturePath && file_exists($signaturePath))
            <img class="signature" src="{{ $signaturePath }}" alt="Signature">
        @else
            <div style="height: 46px;"></div>
        @endif
        @if(filled($letterConfig['signatory_name'] ?? null))
            <div class="signatory-name">{{ $letterConfig['signatory_name'] }}</div>
        @endif
        <div class="signatory-title">{{ $letterConfig['signatory_title'] ?? 'Organising Committee' }}</div>
    </div>

    <div class="footer">
        <strong>{{ config('conference.host') }}</strong><br>
        @if(filled($letterConfig['address'] ?? null)){{ $letterConfig['address'] }}<br>@endif
        Email: {{ config('conference.contact_email') }} &middot; Website: {{ config('conference.website') }}
    </div>
</body>
</html>
