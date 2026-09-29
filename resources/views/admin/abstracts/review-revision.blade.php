@extends('layouts.app')

@section('content')
<div class="container max-w-2xl mx-auto py-8">
    <h2 class="text-2xl font-bold mb-4">Review Submitted Revision</h2>
    <div class="mb-6 p-4 bg-blue-100 border-l-4 border-blue-500">
        <strong>Author:</strong> {{ $abstract->user->first_name }} {{ $abstract->user->last_name }}<br>
        <strong>Title:</strong> {{ $abstract->title }}<br>
        <strong>Revision Round:</strong> {{ $abstract->revision_round ?? 1 }}<br>
        <strong>Submitted At:</strong> {{ $abstract->revision_submitted_at ? $abstract->revision_submitted_at->format('Y-m-d H:i') : 'N/A' }}
    </div>
    <div class="mb-6 p-4 bg-gray-50 border border-gray-200">
        <strong>Author Response:</strong>
        <div class="mt-2 prose max-w-none">{!! $abstract->revision_feedback !!}</div>
    </div>
    <div class="mb-6">
        <a href="{{ asset('storage/' . $abstract->revision_file) }}" target="_blank" class="text-blue-700 underline">Download Revised Abstract</a>
    </div>
    <form method="POST" action="{{ route('admin.processRevision', $abstract->id) }}" class="space-y-6">
        @csrf
        <div>
            <label for="decision" class="block font-medium">Decision <span class="text-red-500">*</span></label>
            <select name="decision" id="decision" required class="mt-1 block w-full border rounded p-2">
                <option value="accept_revision">Accept Revision</option>
                <option value="needs_more_revision">Request Further Revision</option>
                <option value="reject_revision">Reject</option>
            </select>
            @error('decision')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label for="admin_notes" class="block font-medium">Admin Notes (optional)</label>
            <textarea name="admin_notes" id="admin_notes" rows="4" class="mt-1 block w-full border rounded p-2">{{ old('admin_notes') }}</textarea>
            @error('admin_notes')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Submit Decision</button>
    </form>
</div>
@endsection
