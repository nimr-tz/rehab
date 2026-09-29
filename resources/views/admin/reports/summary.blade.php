@extends('layouts.app')

@section('title', 'Summary by Institute & Country')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <nav class="flex mb-4 text-slate-500 dark:text-slate-400 text-sm font-medium" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-2">
                    <li><a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Admin</a></li>
                    <li class="flex items-center"><span class="mx-1">/</span></li>
                    <li><a href="{{ route('admin.reports.builder') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Reports</a></li>
                    <li class="flex items-center"><span class="mx-1">/</span></li>
                    <li class="text-slate-900 dark:text-white">Summary by Institute & Country</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Summary by Institute & Country</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">See how many abstracts and registrations come from each institute and country.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Abstracts by Institute --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Abstracts by Institute</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Non-draft submissions, grouped by author institute</p>
                </div>
                <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-gray-800/50 sticky top-0">
                            <tr>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Institute</th>
                                <th class="text-right py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($abstractsByInstitute as $row)
                                <tr class="border-b border-slate-100 dark:border-gray-800 hover:bg-slate-50 dark:hover:bg-gray-800/30">
                                    <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ $row->name }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">{{ $row->count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Abstracts by Country --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Abstracts by Country</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Non-draft submissions, grouped by author country</p>
                </div>
                <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-gray-800/50 sticky top-0">
                            <tr>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Country</th>
                                <th class="text-right py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($abstractsByCountry as $row)
                                <tr class="border-b border-slate-100 dark:border-gray-800 hover:bg-slate-50 dark:hover:bg-gray-800/30">
                                    <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ $row->name }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">{{ $row->count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Registrations by Institute --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Registrations by Institute</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Verified/waived individual + group members, by affiliation/institution</p>
                </div>
                <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-gray-800/50 sticky top-0">
                            <tr>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Institute</th>
                                <th class="text-right py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrationsByInstitute as $row)
                                <tr class="border-b border-slate-100 dark:border-gray-800 hover:bg-slate-50 dark:hover:bg-gray-800/30">
                                    <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ $row->name }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">{{ $row->count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Registrations by Country --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Registrations by Country</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Verified/waived individual + group members, by country</p>
                </div>
                <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-gray-800/50 sticky top-0">
                            <tr>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Country</th>
                                <th class="text-right py-3 px-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrationsByCountry as $row)
                                <tr class="border-b border-slate-100 dark:border-gray-800 hover:bg-slate-50 dark:hover:bg-gray-800/30">
                                    <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ $row->name }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">{{ $row->count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-4">
            <a href="{{ route('admin.reports.builder') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-200 dark:bg-gray-700 text-slate-900 dark:text-white font-bold hover:bg-slate-300 dark:hover:bg-gray-600 transition-colors">
                Report Builder
            </a>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
