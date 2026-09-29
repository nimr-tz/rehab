@extends('layouts.app')

@section('title', 'Assign Conference Codes')

@section('content')
<div class="container mx-auto py-8">
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-yellow-900 dark:text-yellow-100 mb-2">
                    📋 Assign Conference Codes
                </h1>
                <p class="text-yellow-700 dark:text-yellow-300">
                    Assign unique conference codes to accepted abstracts for the program
                </p>
            </div>
            <a href="{{ route('admin.conference-program.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Program
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-dark-800 rounded-lg shadow-sm border border-gray-200 dark:border-dark-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-dark-400">Pending Codes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-200">{{ $pendingAbstracts->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-lg shadow-sm border border-gray-200 dark:border-dark-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-dark-400">Assigned Codes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-200">{{ \App\Models\AbstractSubmission::where('status', 'accepted')->whereNotNull('conference_code')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-lg shadow-sm border border-gray-200 dark:border-dark-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-dark-400">Oral Presentations</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-200">{{ \App\Models\AbstractSubmission::where('status', 'accepted')->where('presentation_mode', 'oral')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-lg shadow-sm border border-gray-200 dark:border-dark-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-dark-400">Poster Presentations</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-200">{{ \App\Models\AbstractSubmission::where('status', 'accepted')->where('presentation_mode', 'poster')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex border-b border-gray-200 dark:border-dark-700 mb-6">
        <a href="{{ route('admin.conference-program.assign-codes.page', ['filter' => 'all']) }}" 
           class="px-6 py-3 border-b-2 font-medium text-sm {{ $filter === 'all' ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            All Accepted
            <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs dark:bg-dark-700 dark:text-gray-300">{{ $stats['all'] }}</span>
        </a>
        <a href="{{ route('admin.conference-program.assign-codes.page', ['filter' => 'pending']) }}" 
           class="px-6 py-3 border-b-2 font-medium text-sm {{ $filter === 'pending' ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            Pending Assignment
            <span class="ml-2 bg-blue-100 text-blue-600 py-0.5 px-2 rounded-full text-xs dark:bg-blue-900/30 dark:text-blue-400">{{ $stats['pending'] }}</span>
        </a>
        <a href="{{ route('admin.conference-program.assign-codes.page', ['filter' => 'assigned']) }}" 
           class="px-6 py-3 border-b-2 font-medium text-sm {{ $filter === 'assigned' ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            Assigned Codes
            <span class="ml-2 bg-green-100 text-green-600 py-0.5 px-2 rounded-full text-xs dark:bg-green-900/30 dark:text-green-400">{{ $stats['assigned'] }}</span>
        </a>
    </div>

    @if($pendingAbstracts->count() > 0)
        <!-- Bulk Assignment Form -->
        <div class="bg-white dark:bg-dark-800 rounded-lg shadow-sm border border-gray-200 dark:border-dark-700 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-dark-200 mb-4">
                {{ $filter === 'assigned' ? 'Update Assigned Codes' : 'Bulk Code Assignment' }}
            </h2>
            
            <form action="{{ route('admin.conference-program.assign-codes.save') }}" method="POST" id="bulkAssignmentForm">
                @csrf
                <div class="space-y-4">
                    @foreach($pendingAbstracts as $abstract)
                        <div class="border {{ $abstract->conference_code ? 'border-green-200 dark:border-green-900/30 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-dark-600' }} rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-300 mb-1">
                                        Abstract #{{ $abstract->id }}
                                        @if($abstract->conference_code)
                                            <span class="ml-2 text-xs font-bold text-green-600">Assigned</span>
                                        @endif
                                    </label>
                                    <p class="text-sm text-gray-600 dark:text-dark-400 font-medium line-clamp-2">{{ $abstract->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-dark-500">by {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}</p>
                                </div>
                                
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-300 mb-1">
                                        Conference Code
                                    </label>
                                    <input type="text" 
                                           name="assignments[{{ $abstract->id }}][conference_code]" 
                                           value="{{ $abstract->conference_code }}"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-600 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-dark-700 dark:text-dark-200 font-mono"
                                           placeholder="e.g., {{ config('conference.short_name') }}-{{ str_pad($abstract->id, 3, '0', STR_PAD_LEFT) }}"
                                           required>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-300 mb-1">
                                        Subtheme
                                    </label>
                                    <select name="assignments[{{ $abstract->id }}][subtheme]" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-600 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-dark-700 dark:text-dark-200 text-sm">
                                        @foreach($subthemes as $theme)
                                            <option value="{{ $theme }}" {{ $abstract->subtheme == $theme ? 'selected' : '' }}>
                                                {{ Illuminate\Support\Str::limit($theme, 40) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-300 mb-1">
                                        Mode
                                    </label>
                                    <select name="assignments[{{ $abstract->id }}][presentation_mode]" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-600 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-dark-700 dark:text-dark-200">
                                        <option value="oral" {{ (strtolower($abstract->presentation_mode) === 'oral') ? 'selected' : '' }}>Oral</option>
                                        <option value="poster" {{ (strtolower($abstract->presentation_mode) === 'poster') ? 'selected' : '' }}>Poster</option>
                                        <option value="audio_poster" {{ (strtolower($abstract->presentation_mode) === 'audio_poster') ? 'selected' : '' }}>Audio Poster</option>
                                    </select>
                                </div>
                            </div>
                            
                            <input type="hidden" name="assignments[{{ $abstract->id }}][abstract_id]" value="{{ $abstract->id }}">
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors shadow-lg shadow-yellow-200 dark:shadow-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $filter === 'assigned' ? 'Update Codes' : 'Save Assignments' }}
                    </button>
                </div>
            </form>
        </div>
    @else
        <!-- No Pending Abstracts -->
        <div class="bg-white dark:bg-dark-800 rounded-lg shadow-sm border border-gray-200 dark:border-dark-700 p-8 text-center">
            <svg class="w-16 h-16 text-gray-400 dark:text-dark-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-dark-200 mb-2">All Done!</h3>
            <p class="text-gray-600 dark:text-dark-400">All accepted abstracts have been assigned conference codes.</p>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate codes based on abstract ID
    const codeInputs = document.querySelectorAll('input[name*="[conference_code]"]');
    codeInputs.forEach((input, index) => {
        const abstractId = input.name.match(/\[(\d+)\]/)[1];
        input.addEventListener('focus', function() {
            if (!this.value) {
                this.value = `{{ config('conference.short_name') }}-${String(abstractId).padStart(3, '0')}`;
            }
        });
    });
});
</script>
@endsection 
