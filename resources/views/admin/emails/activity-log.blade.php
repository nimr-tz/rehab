@extends('layouts.app')

@section('title', 'Email Activity Log')

@section('content')
<div x-data="emailLogModal()" class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="max-w-[1700px] mx-auto px-6 py-10">
        <div class="mb-8 flex items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('admin.emails.index') }}" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Email Manager
                    </a>
                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase rounded-md border border-blue-500/20">Full History</span>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Email Activity Log</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mt-1">Browse all email logs directly in the system, with filters instead of exports.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="p-6 bg-white/80 dark:bg-slate-800/80 rounded-[2rem] border border-white dark:border-slate-700 shadow-xl">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Total Logs</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="p-6 bg-white/80 dark:bg-slate-800/80 rounded-[2rem] border border-white dark:border-slate-700 shadow-xl">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-3">Delivered</p>
                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($summary['sent']) }}</p>
            </div>
            <div class="p-6 bg-white/80 dark:bg-slate-800/80 rounded-[2rem] border border-white dark:border-slate-700 shadow-xl">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-500 mb-3">Failed</p>
                <p class="text-3xl font-black text-rose-600 dark:text-rose-400">{{ number_format($summary['failed']) }}</p>
            </div>
            <div class="p-6 bg-white/80 dark:bg-slate-800/80 rounded-[2rem] border border-white dark:border-slate-700 shadow-xl">
                <p class="text-[10px] font-black uppercase tracking-widest text-violet-500 mb-3">Opened</p>
                <p class="text-3xl font-black text-violet-600 dark:text-violet-400">{{ number_format($summary['opened']) }}</p>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-slate-800/80 rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl overflow-hidden mb-8">
            <form method="GET" action="{{ route('admin.emails.activity-log') }}" id="email-log-filter-form" class="p-8 grid lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2 ml-1">Search</label>
                    <input type="text" id="email-log-search" name="search" value="{{ request('search') }}" placeholder="Recipient, subject, abstract, or email type"
                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-900 dark:text-white outline-none focus:ring-4 focus:ring-blue-500/10">
                    <p class="mt-2 ml-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Search updates automatically while typing</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2 ml-1">Status</label>
                    <select name="status" data-auto-submit="true" class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-900 dark:text-white outline-none">
                        <option value="all">All Statuses</option>
                        @foreach(['pending', 'sent', 'failed', 'bounced', 'opened', 'clicked'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2 ml-1">Type</label>
                    <select name="email_type" data-auto-submit="true" class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-900 dark:text-white outline-none">
                        <option value="all">All Types</option>
                        @foreach($typeOptions as $type)
                            <option value="{{ $type }}" {{ request('email_type') === $type ? 'selected' : '' }}>{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-1 xl:grid-cols-2">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2 ml-1">From</label>
                        <input type="date" name="date_from" data-auto-submit="true" value="{{ request('date_from') }}"
                            class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-900 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2 ml-1">To</label>
                        <input type="date" name="date_to" data-auto-submit="true" value="{{ request('date_to') }}"
                            class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-900 dark:text-white outline-none">
                    </div>
                </div>
                <div class="lg:col-span-5 flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.emails.activity-log') }}" class="px-5 py-3 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">Clear</a>
                    <button type="submit" class="px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl">Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="bg-white/80 dark:bg-slate-800/80 rounded-[2.5rem] border border-white dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">All Email Logs</h3>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $logs->total() }} record{{ $logs->total() === 1 ? '' : 's' }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1320px]">
                    <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Recipient</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Type</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Subject</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Abstract</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Status</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Queued / Sent</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Error</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($logs as $log)
                            @php
                                $statusClasses = match($log->status) {
                                    'sent' => 'bg-emerald-100 text-emerald-700',
                                    'opened', 'clicked' => 'bg-violet-100 text-violet-700',
                                    'failed', 'bounced' => 'bg-rose-100 text-rose-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                <td class="px-6 py-5">
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">
                                            {{ $log->user?->full_name ?: $log->recipient_email }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $log->recipient_email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', $log->email_type) }}</span>
                                </td>
                                <td class="px-6 py-5 max-w-[360px]">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2">{{ $log->subject }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    @if($log->abstractSubmission)
                                        <div>
                                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $log->abstractSubmission->conference_code ?: '#' . $log->abstractSubmission->id }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 max-w-[220px]">{{ $log->abstractSubmission->title }}</p>
                                        </div>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusClasses }}">
                                        {{ $log->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ optional($log->sent_at ?? $log->created_at)->format('M d, Y') }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ optional($log->sent_at ?? $log->created_at)->format('H:i') }}</p>
                                </td>
                                <td class="px-6 py-5 max-w-[260px]">
                                    @if($log->error_message)
                                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 line-clamp-2">{{ $log->error_message }}</p>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <button
                                        type="button"
                                        @click='open(@json($log->preview_payload_data))'
                                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 dark:bg-white px-4 py-2 text-[11px] font-black uppercase tracking-widest text-white dark:text-slate-900 shadow-lg"
                                    >
                                        View Email
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-8 py-16 text-center">
                                    <p class="text-lg font-black text-slate-900 dark:text-white">No Email Logs Found</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Try changing the filters or wait for the next outgoing email activity.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-8 py-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
                    {{ $logs->links('components.pagination') }}
                </div>
            @endif
        </div>
    </div>

    <div
        x-show="openState"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-slate-950/70" @click="close()"></div>
        <div class="relative z-10 w-full max-w-4xl rounded-[2rem] border border-white/20 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Email Preview</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900 dark:text-white" x-text="preview.subject || 'Email Details'"></h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        This shows the stored email log details and a rendered preview where the original template can be reconstructed.
                    </p>
                </div>
                <button type="button" @click="close()" class="rounded-xl p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid lg:grid-cols-[1.3fr,0.7fr] gap-0">
                <div class="p-8 border-b lg:border-b-0 lg:border-r border-slate-100 dark:border-slate-800">
                    <template x-if="preview.html">
                        <iframe title="Rendered Email Preview" class="w-full h-[560px] rounded-[1.75rem] border border-slate-200 dark:border-slate-800 bg-white" :srcdoc="preview.html"></iframe>
                    </template>
                    <div x-show="!preview.html" class="rounded-[1.75rem] border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 p-6 space-y-4">
                        <template x-if="!preview.html && preview.body_lines && preview.body_lines.length">
                            <div class="space-y-3">
                                <template x-for="(line, index) in preview.body_lines" :key="index">
                                    <p class="text-sm leading-7 font-medium text-slate-700 dark:text-slate-200" x-text="line"></p>
                                </template>
                            </div>
                        </template>
                        <template x-if="!preview.html && (!preview.body_lines || !preview.body_lines.length)">
                            <p class="text-sm leading-7 font-medium text-slate-500 dark:text-slate-400">
                                No readable preview could be reconstructed from this log entry.
                            </p>
                        </template>
                    </div>
                </div>

                <div class="p-8 space-y-6">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3">Recipient</p>
                        <div class="space-y-1">
                            <p class="text-sm font-black text-slate-900 dark:text-white" x-text="preview.recipient_name || preview.recipient_email || 'Unknown recipient'"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="preview.recipient_email || '—'"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Type</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 break-words" x-text="preview.email_type || '—'"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Status</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200" x-text="preview.status || '—'"></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Sent</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200" x-text="preview.sent_at || '—'"></p>
                        </div>
                    </div>

                    <div x-show="preview.abstract_id">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Abstract</p>
                        <p class="text-sm font-black text-slate-900 dark:text-white" x-text="preview.abstract_title || ('#' + preview.abstract_id)"></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="preview.conference_code || ('#' + preview.abstract_id)"></p>
                    </div>

                    <div x-show="preview.error_message">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-400 mb-2">Error</p>
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400" x-text="preview.error_message"></p>
                    </div>

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Metadata</p>
                        <pre class="max-h-64 overflow-auto rounded-2xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 p-4 text-[11px] leading-6 font-semibold text-slate-600 dark:text-slate-300 whitespace-pre-wrap break-words" x-text="formatMetadata(preview.metadata)"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('email-log-filter-form');
        const searchInput = document.getElementById('email-log-search');

        if (!form || !searchInput) {
            return;
        }

        let debounceTimer = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                form.submit();
            }, 350);
        });

        document.querySelectorAll('[data-auto-submit="true"]').forEach(function (element) {
            element.addEventListener('change', function () {
                form.submit();
            });
        });
    })();

    function emailLogModal() {
        return {
            openState: false,
            preview: {},
            open(payload) {
                this.preview = payload || {};
                this.openState = true;
            },
            close() {
                this.openState = false;
                this.preview = {};
            },
            formatMetadata(metadata) {
                if (!metadata || Object.keys(metadata).length === 0) {
                    return 'No additional metadata saved for this email.';
                }

                try {
                    return JSON.stringify(metadata, null, 2);
                } catch (e) {
                    return 'Metadata could not be formatted.';
                }
            }
        };
    }
</script>
@endsection
