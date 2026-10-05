{{-- A fake bank slip for the demo data only. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #232733; }
        .box { border: 1px solid #c3cad5; border-radius: 6px; padding: 20px 24px; }
        .bank { font-size: 18px; font-weight: bold; color: #1f6f3f; }
        .demo { float: right; color: #b91c1c; font-weight: bold; border: 2px solid #b91c1c; padding: 4px 10px; transform: rotate(-6deg); }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        td { padding: 7px 0; border-bottom: 1px dashed #dfe4eb; }
        td:first-child { color: #6b7386; width: 40%; }
        .amount { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <span class="demo">SAMPLE · DEMO</span>
        <div class="bank">{{ $bank }}</div>
        <div style="color: #6b7386">Deposit / transfer confirmation</div>
        <table>
            <tr><td>Transaction reference</td><td><strong>{{ $payment->transaction_reference }}</strong></td></tr>
            <tr><td>Date</td><td>{{ $payment->paid_on->format('d/m/Y') }} 10:24</td></tr>
            <tr><td>Paid by</td><td>{{ $payment->payer_name }}</td></tr>
            <tr><td>Beneficiary</td><td>Rehab Health</td></tr>
            <tr><td>Narration</td><td>{{ $payment->registration->reference }} summit registration</td></tr>
            <tr><td>Amount</td><td class="amount">{{ $payment->formattedAmount() }}</td></tr>
        </table>
    </div>
</body>
</html>
