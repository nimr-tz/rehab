<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $attendee['name'] }} - {{ config('conference.short_name') }} Badge</title>
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            color: #0f172a;
        }
        .card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .card-header {
            background: #2563eb;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .card-header img {
            height: 3rem;
            width: 3rem;
            border-radius: 50%;
            background: #fff;
            padding: 0.25rem;
            object-fit: contain;
            flex-shrink: 0;
        }
        .card-header-text { color: #fff; }
        .card-header-text .conf-name {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            opacity: 0.85;
        }
        .card-header-text .conf-dates {
            font-size: 0.7rem;
            font-weight: 600;
            opacity: 0.65;
            margin-top: 0.2rem;
        }
        .card-body { padding: 1.75rem 1.5rem; }

        /* Name block */
        .attendee-name {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .attendee-affiliation {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #2563eb;
            margin-top: 0.35rem;
        }
        .attendee-country {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        /* Chips */
        .chips { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.75rem; }
        .chip {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .chip-valid { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
        .chip-pending { background: #fef9c3; color: #a16207; border-color: #fde68a; }
        .chip-presenter { background: #ede9fe; color: #6d28d9; border-color: #ddd6fe; }

        /* Info rows */
        .info-section { margin-top: 1.25rem; }
        .info-row {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
        }
        .info-row-label {
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #94a3b8;
        }
        .info-row-value {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0.2rem;
        }

        /* Presentations */
        .presentation-item {
            padding-top: 0.6rem;
            margin-top: 0.6rem;
            border-top: 1px solid #f1f5f9;
        }
        .presentation-item:first-child { padding-top: 0; margin-top: 0; border-top: none; }
        .presentation-title { font-size: 0.82rem; font-weight: 700; color: #1e293b; line-height: 1.4; }
        .presentation-code {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #2563eb;
            margin-top: 0.25rem;
        }

        /* Divider */
        .divider { height: 1px; background: #f1f5f9; margin: 1.25rem 0; }

        /* Certificate section */
        .cert-section {
            border: 1.5px solid #dbeafe;
            border-radius: 0.75rem;
            padding: 1rem 1.1rem;
            background: #eff6ff;
        }
        .cert-section-label {
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #2563eb;
        }
        .cert-section-note {
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-top: 0.35rem;
            line-height: 1.5;
        }
        .btn-cert {
            display: block;
            width: 100%;
            margin-top: 0.85rem;
            padding: 0.875rem;
            background: #2563eb;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            text-align: center;
            border: none;
            border-radius: 0.625rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-cert:hover { background: #1d4ed8; }

        /* Error */
        .alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #b91c1c;
            margin-top: 0.75rem;
        }

        /* Badge verification footer */
        .verify-box {
            border-radius: 0.75rem;
            padding: 1rem 1.1rem;
            text-align: center;
        }
        .verify-box.valid { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .verify-box.pending { background: #fffbeb; border: 1px solid #fde68a; }
        .verify-box-label {
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }
        .verify-box.valid .verify-box-label { color: #15803d; }
        .verify-box.pending .verify-box-label { color: #a16207; }
        .verify-box-text {
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            margin-top: 0.4rem;
            line-height: 1.5;
        }

        /* Footer */
        .page-footer {
            margin-top: 1.25rem;
            font-size: 0.7rem;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        {{-- Header --}}
        <div class="card-header">
            <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}">
            <div class="card-header-text">
                <div class="conf-name">{{ config('conference.short_name') }} {{ config('conference.year') }}</div>
                <div class="conf-dates">{{ config('conference.display_dates') }}</div>
            </div>
        </div>

        <div class="card-body">
            {{-- Name & affiliation --}}
            <div>
                <div class="attendee-name">{{ $attendee['name'] }}</div>
                @if(!empty($attendee['affiliation']))
                    <div class="attendee-affiliation">{{ $attendee['affiliation'] }}</div>
                @endif
                @if(!empty($attendee['country']))
                    <div class="attendee-country">{{ $attendee['country'] }}</div>
                @endif

                <div class="chips">
                    <span class="chip">{{ $attendee['type'] }}</span>
                    @if($attendee['is_paid'])
                        <span class="chip chip-valid">Valid Badge</span>
                    @else
                        <span class="chip chip-pending">Payment Pending</span>
                    @endif
                    @if($attendee['is_presenter'])
                        <span class="chip chip-presenter">Presenter</span>
                    @endif
                </div>
            </div>

            {{-- Group --}}
            @if(!empty($attendee['group_name']))
                <div class="info-section">
                    @if(!empty($attendee['group_name']))
                        <div class="info-row">
                            <div class="info-row-label">Group</div>
                            <div class="info-row-value">{{ $attendee['group_name'] }}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Presentations --}}
            @if(($attendee['presentations'] ?? collect())->count() > 0)
                <div class="info-section">
                    <div class="info-row">
                        <div class="info-row-label">Accepted Presentations</div>
                        <div style="margin-top: 0.5rem;">
                            @foreach($attendee['presentations'] as $presentation)
                                <div class="presentation-item">
                                    <div class="presentation-title">{{ $presentation->title }}</div>
                                    @if($presentation->conference_code)
                                        <div class="presentation-code">{{ $presentation->conference_code }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="divider"></div>

            {{-- Certificate section --}}
            @php
                $certClaimable = ! empty($attendee['qr_token']);
            @endphp
            @if($certClaimable)
                <div class="cert-section">
                    <div class="cert-section-label">Your Certificate</div>
                    <div class="cert-section-note">
                        We will ask you to share a few words about the conference first —
                        it takes under a minute — then your certificate downloads immediately.
                    </div>
                    @if(session('error'))
                        <div class="alert-error">{{ session('error') }}</div>
                    @endif
                    <form method="POST" action="{{ route('certificate.claim.download') }}">
                        @csrf
                        <input type="hidden" name="code" value="{{ $attendee['qr_token'] }}">
                        <button type="submit" class="btn-cert">Get My Certificate</button>
                    </form>
                </div>
                <div class="divider"></div>
            @endif

            {{-- Badge verification --}}
            <div class="verify-box {{ $attendee['is_paid'] ? 'valid' : 'pending' }}">
                <div class="verify-box-label">
                    {{ $attendee['is_paid'] ? 'Badge Verified' : 'Verification Pending' }}
                </div>
                <div class="verify-box-text">
                    @if($attendee['is_paid'])
                        This badge confirms that the person named above was a registered participant at
                        {{ config('conference.name') }} {{ config('conference.year') }}.
                    @else
                        This credential has been found but registration verification is not yet complete.
                        Please contact the registration desk for assistance.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="page-footer">
        &copy; {{ config('conference.year') }} {{ config('conference.host') }}. All rights reserved.
    </div>
</body>
</html>
