{{-- A fake student ID for the demo data only. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #232733; }
        .card { border: 1px solid #c3cad5; border-radius: 10px; padding: 18px 22px; }
        .school { font-size: 16px; font-weight: bold; color: #1d4e89; }
        .demo { float: right; color: #b91c1c; font-weight: bold; border: 2px solid #b91c1c; padding: 4px 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        td { padding: 6px 0; border-bottom: 1px dashed #dfe4eb; }
        td:first-child { color: #6b7386; width: 40%; }
    </style>
</head>
<body>
    <div class="card">
        <span class="demo">SAMPLE · DEMO</span>
        <div class="school">{{ $user->institution }}</div>
        <div style="color: #6b7386">Student identity card</div>
        <table>
            <tr><td>Name</td><td><strong>{{ $user->first_name }} {{ $user->last_name }}</strong></td></tr>
            <tr><td>Programme</td><td>{{ $user->profession }}</td></tr>
            <tr><td>Registration no.</td><td>{{ $number }}</td></tr>
            <tr><td>Valid until</td><td>{{ $validUntil }}</td></tr>
        </table>
    </div>
</body>
</html>
