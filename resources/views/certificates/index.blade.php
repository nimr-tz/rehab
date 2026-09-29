@extends('layouts.app')

@section('title', 'My Certificates')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900 pb-20">
    {{-- Hero Header --}}
    <div class="relative bg-gradient-to-br from-indigo-600 via-blue-700 to-slate-800 py-10 md:py-12 lg:py-16 overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.03]"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="max-w-6xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-2.5 md:p-3 bg-white/10 backdrop-blur-sm rounded-2xl">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white tracking-tight">My Certificates</h1>
                    <p class="text-blue-100/70 text-xs md:text-sm">{{ $conference['edition'] }} {{ $conference['short_name'] }} {{ $conference['year'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 lg:px-8 -mt-6 md:-mt-8 relative z-20">
        {{-- Flash Messages --}}
        @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
        @endif

        @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if($eligibility['can_download'])
            {{-- Eligible: Show Available Certificates --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-200 dark:border-gray-700 overflow-hidden">

                {{-- Attendance Section --}}
                <div class="p-6 md:p-8 border-b border-slate-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2.5 bg-amber-100 dark:bg-amber-500/20 rounded-xl">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Attendance Certificate</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Based on your conference attendance records</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-gray-900/50 rounded-2xl p-6">
                        @if($eligibility['attendance']['type'] === 'full')
                            {{-- Full Attendance --}}
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    <div class="p-3 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl shadow-lg shadow-amber-500/20">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Certificate of Attendance</h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Full Conference Attendance</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" onclick="openPreview('{{ route('certificate.preview') }}')" class="cursor-pointer inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-slate-700 dark:text-slate-200 rounded-xl font-semibold transition-all duration-300 hover:bg-slate-50 dark:hover:bg-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Preview
                                    </button>
                                    <a href="{{ route('certificate.download.attendance') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl font-semibold transition-all duration-300 hover:shadow-lg hover:shadow-amber-500/25 hover:-translate-y-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Download PDF
                                    </a>
                                </div>
                            </div>
                        @else
                            {{-- Partial Attendance --}}
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    <div class="p-3 bg-gradient-to-br from-slate-400 to-slate-600 rounded-xl shadow-lg shadow-slate-500/20">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Certificate of Participation</h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Partial Conference Attendance</p>
                                        <div class="flex flex-col gap-2">
                                            <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium italic">
                                                * This certificate will clearly list the specific dates you were present.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" onclick="openPreview('{{ route('certificate.preview') }}')" class="cursor-pointer inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-slate-700 dark:text-slate-200 rounded-xl font-semibold transition-all duration-300 hover:bg-slate-50 dark:hover:bg-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Preview
                                    </button>
                                    <a href="{{ route('certificate.download.attendance') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-slate-500 to-slate-600 hover:from-slate-600 hover:to-slate-700 text-white rounded-xl font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Download PDF
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Oral Presentations Section --}}
                @if(count($eligibility['oral_presentations']) > 0)
                <div class="p-6 md:p-8 border-b border-slate-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2.5 bg-blue-100 dark:bg-blue-500/20 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Oral Presentation Certificates</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ count($eligibility['oral_presentations']) }} presentation(s) found</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach($eligibility['oral_presentations'] as $presentation)
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 border border-blue-100 dark:border-blue-800/50">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-0.5 bg-blue-600 text-white text-xs font-bold rounded uppercase">Oral</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $presentation['session'] }}</span>
                                    </div>
                                    <h4 class="font-semibold text-slate-900 dark:text-white line-clamp-2">{{ $presentation['title'] }}</h4>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openPreview('{{ route('certificate.preview') }}?abstract_id={{ $presentation['id'] }}')" class="cursor-pointer p-2.5 bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/40 transition-all" title="Preview">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <a href="{{ route('certificate.download.presentation', $presentation['id']) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Poster Presentations Section --}}
                @if(count($eligibility['poster_presentations']) > 0)
                <div class="p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2.5 bg-purple-100 dark:bg-purple-500/20 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Poster Presentation Certificates</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ count($eligibility['poster_presentations']) }} poster(s) found</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach($eligibility['poster_presentations'] as $presentation)
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-0.5 bg-purple-600 text-white text-xs font-bold rounded uppercase">Poster</span>
                                        <span class="px-2 py-0.5 bg-purple-200 dark:bg-purple-800 text-purple-700 dark:text-purple-300 text-xs font-bold rounded">{{ $presentation['poster_id'] }}</span>
                                    </div>
                                    <h4 class="font-semibold text-slate-900 dark:text-white line-clamp-2">{{ $presentation['title'] }}</h4>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openPreview('{{ route('certificate.preview') }}?abstract_id={{ $presentation['id'] }}')" class="cursor-pointer p-2.5 bg-white dark:bg-gray-800 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/40 transition-all" title="Preview">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <a href="{{ route('certificate.download.presentation', $presentation['id']) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- No Presentations --}}
                @if(count($eligibility['oral_presentations']) === 0 && count($eligibility['poster_presentations']) === 0)
                <div class="p-6 md:p-8">
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">No Presentation Certificates</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                            You don't have any accepted presentations. If you believe this is an error, please contact the conference organizers.
                        </p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Already Issued Certificates --}}
            @if($issuedCertificates->count() > 0)
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-slate-100 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Previously Issued Certificates</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Track your downloaded certificates</p>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-gray-700">
                    @foreach($issuedCertificates as $cert)
                    <div class="p-4 md:p-6 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                @if($cert->type === 'attendance_full') bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400
                                @elseif($cert->type === 'attendance_partial') bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400
                                @elseif($cert->type === 'oral_presentation') bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400
                                @else bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400
                                @endif">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $cert->type_label }}</p>
                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ $cert->certificate_number }}</span>
                                    <span>•</span>
                                    <span>Issued {{ $cert->issued_at->format('M j, Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($cert->isRevoked())
                            <span class="px-3 py-1 bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 text-xs font-bold rounded-full">Revoked</span>
                            @else
                            <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-full">Valid</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        @else
            {{-- Not Eligible Yet --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="p-8 md:p-12 text-center">

                    @if(!($eligibility['released'] ?? true))
                        {{-- Countdown to release --}}
                        <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Certificates Unlock In</h2>

                        <div class="flex items-center justify-center gap-3 md:gap-4 my-8" id="certCountdown" data-release="{{ $eligibility['release_at'] }}">
                            <div class="bg-slate-900 dark:bg-black text-white rounded-2xl px-4 py-3 md:px-6 md:py-4 min-w-[72px]">
                                <p class="text-3xl md:text-4xl font-black tabular-nums" data-unit="hours">--</p>
                                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">Hours</p>
                            </div>
                            <span class="text-2xl font-black text-slate-300 dark:text-slate-600">:</span>
                            <div class="bg-slate-900 dark:bg-black text-white rounded-2xl px-4 py-3 md:px-6 md:py-4 min-w-[72px]">
                                <p class="text-3xl md:text-4xl font-black tabular-nums" data-unit="minutes">--</p>
                                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">Minutes</p>
                            </div>
                            <span class="text-2xl font-black text-slate-300 dark:text-slate-600">:</span>
                            <div class="bg-slate-900 dark:bg-black text-white rounded-2xl px-4 py-3 md:px-6 md:py-4 min-w-[72px]">
                                <p class="text-3xl md:text-4xl font-black tabular-nums" data-unit="seconds">--</p>
                                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mt-1">Seconds</p>
                            </div>
                        </div>

                        @if(!($eligibility['feedback_submitted'] ?? false))
                            <div class="max-w-lg mx-auto bg-amber-50 dark:bg-amber-900/15 border border-amber-200 dark:border-amber-800/40 rounded-2xl p-6 text-left flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-amber-900 dark:text-amber-200 mb-1">Feedback unlocks your certificate</p>
                                    <p class="text-xs text-amber-800/80 dark:text-amber-300/70 leading-relaxed mb-4">
                                        To help us improve, certificates can only be downloaded after you've shared your conference feedback. Submit it now so you're ready the moment the timer hits zero.
                                    </p>
                                    <a href="{{ route('feedback.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                                        Submit Feedback Now
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="max-w-lg mx-auto bg-emerald-50 dark:bg-emerald-900/15 border border-emerald-200 dark:border-emerald-800/40 rounded-2xl p-5 flex items-center gap-3 text-left">
                                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-sm text-emerald-800 dark:text-emerald-300 font-medium">
                                    Feedback received — thank you! Your certificate will be ready to download as soon as the countdown ends.
                                </p>
                            </div>
                        @endif

                    @elseif(!($eligibility['feedback_submitted'] ?? true))
                        {{-- Released, but feedback not yet submitted --}}
                        <div class="w-20 h-20 bg-amber-50 dark:bg-amber-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">One Step Left</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-lg mx-auto mb-8">
                            Your certificates are ready — to help us improve, they unlock once you've shared your conference feedback. It only takes a couple of minutes.
                        </p>
                        <a href="{{ route('feedback.create') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl font-semibold transition-all duration-300 hover:shadow-lg hover:shadow-amber-500/25 hover:-translate-y-0.5">
                            Submit Feedback to Unlock
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>

                    @else
                        {{-- Released + feedback done, but other requirements unmet --}}
                        <div class="w-20 h-20 bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Almost There</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-lg mx-auto mb-8">
                            Your certificates will be available for download once all eligibility requirements below are met.
                        </p>

                        {{-- Eligibility Checklist --}}
                        <div class="inline-block text-left bg-slate-50 dark:bg-gray-900/50 rounded-2xl p-6 max-w-sm mx-auto">
                            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-4">Eligibility Requirements</h3>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center {{ $eligibility['has_attendance'] ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-gray-700' }}">
                                        @if($eligibility['has_attendance'])
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        @endif
                                    </div>
                                    <span class="text-sm {{ $eligibility['has_attendance'] ? 'text-slate-700 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' }}">
                                        Attend at least one day of the conference
                                    </span>
                                </div>
                                <div class="pl-9 mb-2">
                                    <p class="text-[11px] text-slate-400 leading-tight">
                                        Participants with a verified or waived registration are automatically counted as attendees. If your attendance was scanned on specific days, your certificate will state those dates.
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center {{ $eligibility['conference_ended'] ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-gray-700' }}">
                                        @if($eligibility['conference_ended'])
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        @endif
                                    </div>
                                    <span class="text-sm {{ $eligibility['conference_ended'] ? 'text-slate-700 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' }}">
                                        Conference must conclude ({{ $conference['display_dates'] }})
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center bg-emerald-500">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">
                                        Submit your conference feedback
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Help Section --}}
        <div class="mt-8 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Questions about your certificates?
                <a href="mailto:{{ config('conference.contact_email', config('conference.contact_email')) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">Contact us</a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live countdown to certificate release; reloads to unlock the page at zero
    (function() {
        const el = document.getElementById('certCountdown');
        if (!el) return;
        const release = new Date(el.dataset.release).getTime();
        const units = {
            hours: el.querySelector('[data-unit="hours"]'),
            minutes: el.querySelector('[data-unit="minutes"]'),
            seconds: el.querySelector('[data-unit="seconds"]'),
        };
        const pad = n => String(n).padStart(2, '0');
        const tick = () => {
            const diff = release - Date.now();
            if (diff <= 0) {
                clearInterval(timer);
                window.location.reload();
                return;
            }
            units.hours.textContent = pad(Math.floor(diff / 3600000));
            units.minutes.textContent = pad(Math.floor(diff / 60000) % 60);
            units.seconds.textContent = pad(Math.floor(diff / 1000) % 60);
        };
        const timer = setInterval(tick, 1000);
        tick();
    })();

    function openPreview(url) {
        const modal = document.getElementById('previewModal');
        const iframe = document.getElementById('previewIframe');
        const loader = document.getElementById('previewLoader');

        // Show modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Reset iframe and show loader
        iframe.classList.add('hidden');
        loader.classList.remove('hidden');

        // Set URL
        iframe.src = url;

        // Hide loader when iframe is loaded
        iframe.onload = function() {
            loader.classList.add('hidden');
            iframe.classList.remove('hidden');
        };

        // Disable scroll
        document.body.style.overflow = 'hidden';
    }

    function closePreview() {
        const modal = document.getElementById('previewModal');
        const iframe = document.getElementById('previewIframe');

        // Hide modal
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Clear iframe source
        iframe.src = 'about:blank';

        // Enable scroll
        document.body.style.overflow = '';
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closePreview();
        }
    });
</script>
@endpush

{{-- Preview Modal --}}
<div id="previewModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 md:p-8">
    <div class="relative w-full max-w-6xl max-h-full bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-gray-800 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Certificate Preview</h3>
            </div>
            <button onclick="closePreview()" class="p-2 hover:bg-slate-100 dark:hover:bg-gray-800 rounded-full transition-colors text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 overflow-auto bg-slate-100 dark:bg-gray-950 p-4 md:p-12 min-h-[500px] flex items-center justify-center relative">
            {{-- Loader --}}
            <div id="previewLoader" class="flex flex-col items-center gap-4">
                <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                <p class="text-slate-500 font-medium">Generating your preview...</p>
            </div>

            {{-- Iframe --}}
            <iframe id="previewIframe" src="about:blank" class="w-full h-full min-h-[70vh] rounded-lg shadow-xl hidden bg-white"></iframe>
        </div>

        {{-- Modal Footer --}}
        <div class="p-6 border-t border-slate-100 dark:border-gray-800 text-center shrink-0">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                This is a preview of your official certificate. You can download the high-resolution PDF for printing.
            </p>
        </div>
    </div>
</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection

