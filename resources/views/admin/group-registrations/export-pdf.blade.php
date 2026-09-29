<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Group Registrations Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; margin: 0; padding: 0; }
        h1 { font-size: 14px; font-weight: bold; margin: 0 0 4px 0; color: #0f172a; }
        .meta { font-size: 8px; color: #64748b; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #1e293b; color: #fff; }
        th { padding: 6px 8px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 20px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-verified { background: #d1fae5; color: #065f46; }
        .badge-waived   { background: #e0e7ff; color: #3730a3; }
        .badge-pending  { background: #f1f5f9; color: #475569; }
        .badge-submitted{ background: #fef3c7; color: #92400e; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .date-col { white-space: nowrap; }
        .type-paid   { color: #059669; font-weight: bold; font-size: 7px; display: block; }
        .type-waived { color: #4f46e5; font-weight: bold; font-size: 7px; display: block; }
        .footer { margin-top: 16px; font-size: 7px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Group Registrations</h1>
    <div class="meta">
        Generated: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; Total groups: {{ $groups->count() }}
        &nbsp;|&nbsp; Verified: {{ $groups->where('payment_status', 'verified')->count() }}
        &nbsp;|&nbsp; Waived: {{ $groups->where('payment_status', 'waived')->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Group / Organization</th>
                <th>Leader</th>
                <th style="text-align:center">Members</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Verified By</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
            <tr>
                <td>{{ $group->id }}</td>
                <td>
                    <strong>{{ $group->group_name ?? 'Group #' . $group->id }}</strong>
                    @if($group->organization)
                        <br><span style="color:#64748b">{{ $group->organization }}</span>
                    @endif
                </td>
                <td>
                    {{ $group->leader->full_name ?? 'N/A' }}<br>
                    <span style="color:#64748b">{{ $group->leader->email ?? '' }}</span>
                </td>
                <td style="text-align:center">{{ $group->members_count }}</td>
                <td>
                    <span class="badge badge-{{ $group->payment_status }}">
                        @switch($group->payment_status)
                            @case('verified') Settled @break
                            @case('waived')   Waived  @break
                            @case('submitted') Awaiting @break
                            @case('rejected')  Rejected @break
                            @default Pending
                        @endswitch
                    </span>
                </td>
                <td class="date-col">
                    @if($group->payment_verified_at && in_array($group->payment_status, ['verified', 'waived']))
                        {{ $group->payment_verified_at->format('d M Y') }}
                        <span class="{{ $group->payment_status === 'waived' ? 'type-waived' : 'type-paid' }}">
                            {{ $group->payment_status === 'waived' ? 'Waived' : 'Paid' }}
                        </span>
                    @else
                        <span style="color:#94a3b8">—</span>
                    @endif
                </td>
                <td>{{ $group->verifier->full_name ?? '—' }}</td>
                <td class="date-col">{{ $group->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; color:#94a3b8; padding: 20px;">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">{{ config('conference.short_name') }} Conference Management System &mdash; Confidential</div>
</body>
</html>
