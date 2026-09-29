@extends('layouts.app')

@section('title', 'Certificate Management')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-gray-900 pb-20">
    {{-- Header Section --}}
    <div class="bg-white dark:bg-gray-800 border-b border-slate-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Certificate Management</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Issue, track, and verify conference certificates</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.certificates.export', request()->all()) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </a>

                    <button onclick="document.getElementById('bulkIssueModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Bulk Issue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 mt-8">
        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Issued --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Issued</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats['total']) }}</h3>
                    </div>
                </div>
            </div>

            {{-- Presentations --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Presenters</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats['oral_presentations'] + $stats['poster_presentations']) }}</h3>
                    </div>
                </div>
            </div>

            {{-- Revoked --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Revoked</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats['revoked']) }}</h3>
                    </div>
                </div>
            </div>

            {{-- Verifications --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Verifications</p>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats['total_verifications']) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters Section --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm mb-8">
            <form action="{{ route('admin.certificates.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Search --}}
                <div class="col-span-1 md:col-span-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Search</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Email, Cert #" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-gray-900 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- Type Filter --}}
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Type</label>
                    <select name="type" class="w-full py-2.5 bg-slate-50 dark:bg-gray-900 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="">All Types</option>
                        <option value="attendance_full" {{ request('type') == 'attendance_full' ? 'selected' : '' }}>Attendance (Full)</option>
                        <option value="attendance_partial" {{ request('type') == 'attendance_partial' ? 'selected' : '' }}>Participation (Partial)</option>
                        <option value="oral_presentation" {{ request('type') == 'oral_presentation' ? 'selected' : '' }}>Oral Presentation</option>
                        <option value="poster_presentation" {{ request('type') == 'poster_presentation' ? 'selected' : '' }}>Poster Presentation</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Status</label>
                    <select name="status" class="w-full py-2.5 bg-slate-50 dark:bg-gray-900 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="">All Status</option>
                        <option value="valid" {{ request('status') == 'valid' ? 'selected' : '' }}>Valid</option>
                        <option value="revoked" {{ request('status') == 'revoked' ? 'selected' : '' }}>Revoked</option>
                    </select>
                </div>

                {{-- Filter Button --}}
                <div class="flex items-end">
                    <button type="submit" class="w-full py-2.5 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 text-slate-700 dark:text-white rounded-xl text-sm font-bold transition-all">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        {{-- Certificates Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-[2rem] border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-gray-900/50 border-b border-slate-200 dark:border-gray-700">
                            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Certificate Info</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Recipient</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Analytics</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                        @forelse($certificates as $certificate)
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-900/30 transition-all group">
                            <td class="px-6 py-6">
                                <div class="flex flex-col">
                                    <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $certificate->certificate_number }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">Issued: {{ $certificate->issued_at->format('M j, Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($certificate->user->first_name, 0, 1) }}{{ substr($certificate->user->last_name, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ $certificate->user->full_name }}</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[150px]">{{ $certificate->user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 font-medium">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $certificate->type_label }}</span>
                                    @if($certificate->isPresenterCertificate() && $certificate->abstract)
                                        <span class="text-[10px] text-slate-400 line-clamp-1 italic max-w-[200px]" title="{{ $certificate->abstract->title }}">"{{ $certificate->abstract->title }}"</span>
                                    @elseif($certificate->type === 'attendance_partial')
                                        <span class="text-[10px] text-slate-400">{{ $certificate->formatted_attendance_days }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1.5" title="Downloads">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M7 10l5 5m0 0l5-5m-5 5V3"/></svg>
                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ $certificate->download_count }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5" title="Verifications">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ $certificate->verification_count }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                @if($certificate->isRevoked())
                                    <span class="px-2.5 py-1 bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase rounded-lg border border-rose-200 dark:border-rose-500/30">Revoked</span>
                                @else
                                    <span class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase rounded-lg border border-emerald-200 dark:border-emerald-500/30">Valid</span>
                                @endif
                            </td>
                            <td class="px-6 py-6 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                    <a href="{{ route('admin.certificates.preview-user', ['user' => $certificate->user_id, 'type' => $certificate->type]) }}" target="_blank" class="p-2 bg-slate-100 dark:bg-gray-700 hover:bg-indigo-600 hover:text-white rounded-lg transition-all" title="Preview">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    @if($certificate->isRevoked())
                                        <form action="{{ route('admin.certificates.reinstate', $certificate) }}" method="POST" onsubmit="return confirm('Reinstate this certificate?')">
                                            @csrf
                                            <button type="submit" class="p-2 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-lg transition-all" title="Reinstate">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <button onclick="openRevokeModal('{{ $certificate->id }}', '{{ $certificate->certificate_number }}')" class="p-2 bg-rose-50 dark:bg-rose-500/10 text-rose-600 hover:bg-rose-600 hover:text-white rounded-lg transition-all" title="Revoke">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="max-w-xs mx-auto text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="font-bold">No certificates found</p>
                                    <p class="text-xs mt-1">Try adjusting your filters or search terms</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($certificates->hasPages())
            <div class="px-6 py-4 bg-slate-50 dark:bg-gray-900/50 border-t border-slate-200 dark:border-gray-700">
                {{ $certificates->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Bulk Issue Modal --}}
<div id="bulkIssueModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6">
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="p-8">
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Bulk Issue Certificates</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">This will generate certificates for all eligible users who don't have one yet.</p>

                <form action="{{ route('admin.certificates.bulk-issue') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Certificate Type</label>
                            <select name="type" class="w-full py-3 bg-slate-100 dark:bg-gray-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="attendance_any">Any Attendance (Full or Partial)</option>
                                <option value="attendance_full">Full Attendance (3 Days) Only</option>
                                <option value="attendance_partial">Partial Participation Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="document.getElementById('bulkIssueModal').classList.add('hidden')" class="flex-1 py-3 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-white font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-gray-600 transition-all">Cancel</button>
                        <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-2xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all">Start Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Revoke Modal --}}
<div id="revokeModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6">
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="p-8">
                <div class="w-16 h-16 bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Revoke Certificate</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">Revoking certificate <span id="revokeCertNum" class="font-mono font-bold text-slate-900 dark:text-white"></span>. This action can be undone later.</p>

                <form id="revokeForm" action="" method="POST">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Revocation Reason</label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Duplicate issue, attendance verification error..." class="w-full p-4 bg-slate-100 dark:bg-gray-900 border-none rounded-2xl text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="document.getElementById('revokeModal').classList.add('hidden')" class="flex-1 py-3 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-white font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-gray-600 transition-all">Cancel</button>
                        <button type="submit" class="flex-1 py-3 bg-rose-600 text-white font-bold rounded-2xl shadow-lg shadow-rose-500/20 hover:bg-rose-700 transition-all">Revoke Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openRevokeModal(id, certNum) {
        const modal = document.getElementById('revokeModal');
        const form = document.getElementById('revokeForm');
        const span = document.getElementById('revokeCertNum');

        span.textContent = certNum;
        form.action = `/admin/certificates/${id}/revoke`;
        modal.classList.remove('hidden');
    }
</script>
@endpush

@endsection
