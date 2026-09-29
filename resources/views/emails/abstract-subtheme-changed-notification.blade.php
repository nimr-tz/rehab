@extends('emails.layout')

@section('content')
<div class="header">
    <h1>Abstract Subtheme Updated</h1>
</div>

<div class="content">
    <p>Dear {{ $abstract->author_name }},</p>

    <p>Your abstract has been moved to a different subtheme by the scientific committee so it can be handled in the most appropriate conference track.</p>

    <div class="highlight">
        <h3>Updated Abstract Details</h3>
        <table class="details-table">
            <tr>
                <th>Abstract ID:</th>
                <td><strong>#{{ $abstract->id }}</strong></td>
            </tr>
            <tr>
                <th>Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Previous Subtheme:</th>
                <td>{{ $oldSubtheme }}</td>
            </tr>
            <tr>
                <th>New Subtheme:</th>
                <td><strong>{{ $newSubtheme }}</strong></td>
            </tr>
            <tr>
                <th>Current Status:</th>
                <td>{{ ucfirst(str_replace('_', ' ', $abstract->status)) }}</td>
            </tr>
        </table>
    </div>

    <p>This update helps place your work in the most suitable scientific stream for review, scheduling, and program alignment.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('abstracts.show', $abstract) }}" class="button">
            View Abstract
        </a>
    </div>

    <p>Best regards,<br>
    <strong>The Conference Scientific Committee</strong></p>
</div>
@endsection
