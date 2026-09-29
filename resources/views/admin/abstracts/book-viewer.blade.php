@extends('layouts.app')

@section('title', 'Abstract Book Viewer')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Header Bar -->
    <div class="bg-white dark:bg-gray-800 shadow-lg border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Title -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            📚 Abstract Book
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ config('conference.short_name') }} {{ config('conference.year') }} Official Publication</p>
                    </div>
                </div>

                <!-- Stats & Actions -->
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-6 text-sm">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['total'] }}</div>
                            <div class="text-xs text-gray-500">Abstracts</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['themes'] }}</div>
                            <div class="text-xs text-gray-500">Themes</div>
                        </div>
                    </div>

                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden md:block"></div>

                    <!-- Download Button -->
                    <form action="{{ route('admin.abstracts.book.generate') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span class="hidden sm:inline">Download PDF</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Viewer Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
            <!-- PDF Embed -->
            <div class="relative" style="height: calc(100vh - 180px); min-height: 600px;">
                <iframe 
                    src="{{ route('admin.abstracts.book.stream') }}" 
                    class="w-full h-full border-0"
                    title="Abstract Book PDF Viewer"
                ></iframe>
                
                <!-- Fallback for browsers that don't support PDF embedding -->
                <noscript>
                    <div class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900">
                        <div class="text-center p-8">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">PDF Viewer Not Available</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Your browser doesn't support embedded PDF viewing.</p>
                            <form action="{{ route('admin.abstracts.book.generate') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                                    Download PDF Instead
                                </button>
                            </form>
                        </div>
                    </div>
                </noscript>
            </div>
        </div>

        <!-- Quick Stats Footer -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
                <div class="text-xs text-gray-500 uppercase tracking-wide">Oral Presentations</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['oral'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
                <div class="text-xs text-gray-500 uppercase tracking-wide">Poster Presentations</div>
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['poster'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
                <div class="text-xs text-gray-500 uppercase tracking-wide">Scheduled Sessions</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['scheduled'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
                <div class="text-xs text-gray-500 uppercase tracking-wide">Generated</div>
                <div class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ now()->format('M j, Y') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
