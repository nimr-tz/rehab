@extends('emails.layout')

@section('title', 'Review Submitted')

@section('content')
<p>A reviewer has submitted their review for consideration.</p>

<div class="highlight">
    <h3>Abstract Information</h3>
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
        <tr>
            <th>Institution</th>
            <td>{{ $abstract->author_institute }}</td>
        </tr>
    </table>
</div>

<div class="highlight">
    <h3>Reviewer Information</h3>
    <table class="details-table">
        <tr>
            <th>Reviewer</th>
            <td>{{ $reviewer->first_name }} {{ $reviewer->last_name }}</td>
        </tr>
        <tr>
            <th>Position</th>
            <td>{{ $reviewerPosition === 1 ? 'Reviewer A' : 'Reviewer B' }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $reviewer->email }}</td>
        </tr>
        <tr>
            <th>Submitted At</th>
            <td>{{ now()->format('M d, Y H:i') }}</td>
        </tr>
    </table>
</div>

<p>This review is now available for committee follow-up.</p>

<p>
    <a href="{{ route('admin.abstracts.view', $abstract) }}" class="button">View Abstract Details</a>
</p>
@endsection
