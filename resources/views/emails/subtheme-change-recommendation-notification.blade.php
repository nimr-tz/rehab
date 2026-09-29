@extends('emails.layout')

@section('title', 'Subtheme Change Suggested')

@section('content')
<p style="font-size: 16px; margin-top: 0;">
    A reviewer has recommended moving this abstract to a different subtheme.
</p>

<div style="margin: 24px 0; padding: 18px 20px; border-radius: 14px; background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 100%); border: 1px solid #fdba74;">
    <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #c2410c; margin-bottom: 8px;">
        Recommendation
    </div>
    <div style="font-size: 20px; font-weight: 800; color: #9a3412; margin-bottom: 6px;">
        {{ $abstract->subtheme }}
    </div>
    <div style="font-size: 14px; color: #9a3412; margin-bottom: 6px;">Current subtheme</div>
    <div style="font-size: 22px; font-weight: 800; color: #1d4ed8; margin-top: 14px; margin-bottom: 6px;">
        {{ $suggestedSubtheme }}
    </div>
    <div style="font-size: 14px; color: #1e3a8a;">Suggested subtheme</div>
</div>

<div class="highlight">
    <h3>Abstract Snapshot</h3>
    <table class="details-table">
        <tr>
            <th>Abstract ID</th>
            <td>#{{ $abstract->id }}</td>
        </tr>
        <tr>
            <th>Title</th>
            <td>{{ $abstract->title }}</td>
        </tr>
        <tr>
            <th>Author</th>
            <td>{{ $abstract->author_name }}</td>
        </tr>
    </table>
</div>

<div class="highlight">
    <h3>Reviewer Details</h3>
    <table class="details-table">
        <tr>
            <th>Reviewer</th>
            <td>{{ $reviewer->first_name }} {{ $reviewer->last_name }}</td>
        </tr>
        <tr>
            <th>Reviewer Slot</th>
            <td>{{ $reviewerPosition === 1 ? 'Reviewer A' : 'Reviewer B' }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $reviewer->email }}</td>
        </tr>
        <tr>
            <th>Time</th>
            <td>{{ now()->format('M d, Y H:i') }}</td>
        </tr>
    </table>
</div>

<p style="margin-bottom: 24px;">
    Please review the abstract and decide whether the subtheme should be updated before the final decision flow continues.
</p>

<p>
    <a href="{{ route('admin.abstracts.view', $abstract) }}" class="button">Open Abstract Review</a>
</p>
@endsection
