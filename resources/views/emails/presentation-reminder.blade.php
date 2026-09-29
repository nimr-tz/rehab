<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presentation Upload Reminder</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3969B7 0%, #213c6d 100%); padding: 40px 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">
                                {{ config('conference.short_name') }} {{ config('conference.year') }}
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.8); font-size: 14px;">
                                Africa Joint Scientific Conference
                            </p>
                        </td>
                    </tr>

                    <!-- Alert Banner -->
                    <tr>
                        <td style="background-color: #fef3c7; padding: 20px 40px; border-bottom: 1px solid #fcd34d;">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align: middle; padding-right: 15px;">
                                        <div style="width: 40px; height: 40px; background-color: #f59e0b; border-radius: 10px; text-align: center; line-height: 40px;">
                                            <span style="font-size: 20px;">⚠️</span>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <p style="margin: 0; color: #92400e; font-weight: 700; font-size: 14px;">
                                            ACTION REQUIRED: Presentation Materials Missing
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            @php
                                $normalizedMode = strtolower((string) $presentationMode);
                                $hasFinalMode = !blank($normalizedMode);
                            @endphp
                            <p style="margin: 0 0 20px; color: #334155; font-size: 16px; line-height: 1.6;">
                                Dear <strong>{{ $user->title }} {{ $user->full_name }}</strong>,
                            </p>

                            <p style="margin: 0 0 20px; color: #334155; font-size: 16px; line-height: 1.6;">
                                @if($hasFinalMode)
                                    We noticed that you haven't yet uploaded your <strong style="color: #3969B7;">{{ $normalizedMode }}</strong> materials for the following submission:
                                @else
                                    We noticed that presentation materials are still missing for the following accepted submission:
                                @endif
                            </p>

                            <!-- Abstract Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; border-radius: 12px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <p style="margin: 0 0 10px; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">
                                            Your Submission
                                        </p>
                                        <p style="margin: 0; color: #1e293b; font-size: 18px; font-weight: 700; line-height: 1.4;">
                                            {{ $abstract->title }}
                                        </p>
                                        @if($hasFinalMode)
                                            <p style="margin: 10px 0 0; color: #64748b; font-size: 13px;">
                                                Presentation Mode: <strong style="color: {{ $normalizedMode === 'poster' ? '#f59e0b' : '#3b82f6' }};">{{ ucfirst($normalizedMode) }}</strong>
                                            </p>
                                        @else
                                            <p style="margin: 10px 0 0; color: #64748b; font-size: 13px;">
                                                Presentation materials are still pending for this accepted abstract.
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 25px; color: #334155; font-size: 16px; line-height: 1.6;">
                                @if($hasFinalMode)
                                    Please upload your {{ $normalizedMode === 'poster' ? 'poster file (PDF format recommended)' : 'presentation slides (PowerPoint or PDF)' }} as soon as possible to ensure a smooth conference experience.
                                @else
                                    Please log in to your dashboard and complete your presentation materials as soon as possible to ensure a smooth conference experience.
                                @endif
                            </p>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td style="background: linear-gradient(135deg, #3969B7 0%, #213c6d 100%); border-radius: 12px;">
                                        <a href="{{ $dashboardUrl }}" style="display: inline-block; padding: 18px 40px; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
                                            Upload Your Materials Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 25px 0 0; color: #64748b; font-size: 14px; line-height: 1.6;">
                                If you have any questions about the upload process, please don't hesitate to contact our support team or visit the registration desk at the conference venue.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 30px 40px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 10px; color: #64748b; font-size: 13px; text-align: center;">
                                This reminder was sent by the {{ config('conference.short_name') }} {{ config('conference.year') }} Registration Team.
                            </p>
                            <p style="margin: 0; color: #94a3b8; font-size: 12px; text-align: center;">
                                Africa Joint Scientific Conference | {{ date('Y') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
