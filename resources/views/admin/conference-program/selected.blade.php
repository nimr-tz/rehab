@extends('layouts.app')

@section('title', 'Selected Abstracts')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="bg-gradient-to-r from-purple-600 to-pink-700 dark:from-purple-700 dark:to-pink-800 rounded-xl shadow-lg p-8 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    <div>
                        <h1 class="text-3xl font-bold">⭐ Conference Program Selection</h1>
                        <p class="text-purple-100 dark:text-purple-200 mt-1">Abstracts selected for the final conference program • Ready for presentation</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.conference-program.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-colors backdrop-blur-sm">
                        ← Back to Overview
                    </a>
                </div>
            </div>
            <div class="mt-4 flex items-center space-x-6 text-purple-100 dark:text-purple-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Final Program
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ready for Presentation
                </div>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">        <form method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="🔍 Search selected abstracts..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400">
            </div>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition-colors">
                Search
            </button>
            @if(request('search'))
            <a href="{{ route('admin.conference-program.selected') }}" 
               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg transition-colors">
                Clear
            </a>
            @endif
        </form>
        </div>

    @if($abstracts->count() == 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="text-gray-400 dark:text-gray-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Abstracts Selected</h3>
            <p class="text-gray-600 dark:text-gray-400">No abstracts have been selected for the conference program yet.</p>
            <a href="{{ route('admin.conference-program.pending') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition-colors mt-4">
                Start Selecting Abstracts
            </a>
        </div>
    @else
        <!-- Program Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 Program Statistics</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $abstracts->total() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Selected Abstracts</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $abstracts->where('presentation_mode', 'Oral')->count() }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Oral Presentations</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $abstracts->where('presentation_mode', 'Poster')->count() }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Poster Presentations</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        {{ $abstracts->groupBy('subtheme')->count() }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Different Themes</div>
                </div>
            </div>
        </div>

        <!-- Selected Abstracts List -->
        <div class="space-y-4">
            @foreach($abstracts as $abstract)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 border-l-4 border-l-green-500 dark:border-l-green-400">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 pr-4">
                            <!-- Abstract Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                            ⭐ SELECTED
                        </span>
                        <div class="font-mono bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-3 py-1 rounded-md font-medium text-sm">
                            📋 {{ $abstract->conference_code }}
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                            {{ $abstract->presentation_mode === 'Oral' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300' }}">
                            {{ $abstract->presentation_mode === 'Oral' ? '🎤' : '📋' }} {{ $abstract->presentation_mode }}
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        {{ $abstract->title }}
                    </h3>
                                </div>
                            </div>

                            <!-- Abstract Details -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                                <div>
                                    <span class="font-medium">👤 Author:</span><br>
                                    {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}
                                </div>
                                <div>
                                    <span class="font-medium">🏛️ Institution:</span><br>
                                    {{ $abstract->author_institute }}
                                </div>
                                <div>
                                    <span class="font-medium">📚 Theme:</span><br>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        {{ $abstract->subtheme }}
                                    </span>
                                </div>
                            </div>

                            <!-- Committee Information -->
                            @if($abstract->committee_notes)
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-600 rounded-lg p-3">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">💭 Committee Notes:</span><br>
                                    <span class="text-gray-600 dark:text-gray-400">{{ $abstract->committee_notes }}</span>
                                </div>
                            </div>
                            @endif

                            <!-- Selection Info -->
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-4 mt-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">📅 Selected:</span>
                                    {{ $abstract->code_assigned_at->format('M j, Y \a\t g:i A') }}
                                    @if($abstract->codeAssignedBy)
                                        by {{ $abstract->codeAssignedBy->first_name }} {{ $abstract->codeAssignedBy->last_name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('admin.abstracts.view', $abstract) }}" 
                               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm rounded-md transition-colors">
                                👁️ View Details
                            </a>
                            <a href="{{ route('admin.abstracts.view', $abstract) }}#conference-code" 
                               class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-sm rounded-md transition-colors">
                                ✏️ Edit
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $abstracts->withQueryString()->links() }}
            </div>
        @endif

    <!-- Export and Program Generation -->
    @if($abstracts->count() > 0)
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📊 Program Management</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="generateProgram()" 
                    class="flex items-center justify-center px-4 py-3 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg transition-colors">
                🗓️ Generate Program Schedule
            </button>
            <button onclick="exportSelected()" 
                    class="flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition-colors">
                📄 Export Selected List
            </button>
            <button onclick="printProgram()" 
                    class="flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg transition-colors">
                🖨️ Print Program
            </button>
        </div>
    </div>
    @endif
    </div>
</div>

@push('scripts')
<script>
function generateProgram() {
    alert('🗓️ Program generation feature will be implemented based on your scheduling requirements!');
}

function exportSelected() {
    // Export selected abstracts to CSV
    const data = [
        ['Conference Code', 'Title', 'Author', 'Institution', 'Theme', 'Presentation Type', 'Selected Date', 'Notes']
    ];
    
    @foreach($abstracts as $abstract)
    data.push([
        '{{ $abstract->conference_code }}',
        '{{ addslashes($abstract->title) }}',
        '{{ addslashes($abstract->author_name) }}',
        '{{ addslashes($abstract->author_institute) }}',
        '{{ addslashes($abstract->subtheme) }}',
        '{{ $abstract->presentation_mode }}',
        '{{ $abstract->code_assigned_at->format("Y-m-d") }}',
        '{{ addslashes($abstract->committee_notes ?? "") }}'
    ]);
    @endforeach
    
    const csv = data.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'selected_conference_abstracts.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printProgram() {
    window.print();
}
</script>
@endpush
@endsection
