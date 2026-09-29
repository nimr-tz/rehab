<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue Resolved</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
    <h1 style="margin-bottom: 0;">Issue Resolved</h1>
    <p style="margin-top: 4px; color: #475569;">
        We noticed a recent problem affecting your activity on {{ config('app.name') }} and have now resolved it.
    </p>

    <h2 style="margin-bottom: 8px;">What happened</h2>
    <p style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0;">
        {{ $resolutionSummary }}
    </p>

    @if($actionRequired)
        <h2 style="margin-bottom: 8px;">Action needed from you</h2>
        <p style="background: #fff7ed; padding: 12px; border: 1px solid #fdba74;">
            {{ $actionDetails ?: 'Please try the action again and contact support if the issue persists.' }}
        </p>
    @else
        <h2 style="margin-bottom: 8px;">Do you need to do anything?</h2>
        <p style="background: #ecfdf5; padding: 12px; border: 1px solid #86efac;">
            No further action is required from you at this time.
        </p>
    @endif

    <h2 style="margin-bottom: 8px;">Incident reference</h2>
    <p style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0;">
        Log #{{ $systemLog->id }} · Resolved at {{ $systemLog->resolved_at?->toDateTimeString() ?? now()->toDateTimeString() }}
    </p>
</body>
</html>
