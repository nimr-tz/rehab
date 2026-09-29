@extends('layouts.app')
@section('title', 'Duplicate Watchlist')

@section('content')
<div class="min-h-screen bg-slate-100 dark:bg-gray-950">
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Dashboard
    </a>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Duplicate Watchlist</h1>
        <p class="text-sm text-slate-500 mt-1">Suspicious duplicate accounts and abstracts flagged for admin review.</p>
    </div>

    @if(session('success'))
        <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Duplicate Accounts</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $summary['account_pairs'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Duplicate Abstracts</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $summary['abstract_pairs'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-rose-500 mb-1">High Confidence</p>
            <p class="text-3xl font-black text-rose-600 dark:text-rose-400">{{ $summary['high_confidence'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-amber-500 mb-1">Medium Confidence</p>
            <p class="text-3xl font-black text-amber-600 dark:text-amber-400">{{ $summary['medium_confidence'] }}</p>
        </div>
    </div>

    {{-- Tab navigation --}}
    <div class="flex gap-1 mb-6 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-xl p-1 w-fit shadow-sm">
        <button onclick="showTab('abstracts')" id="tab-abstracts"
                class="tab-btn px-4 py-2 rounded-lg text-sm font-semibold transition-colors bg-indigo-600 text-white">
            Abstract Pairs <span class="ml-1.5 text-xs opacity-70">{{ $abstractFlags->count() }}</span>
        </button>
        <button onclick="showTab('accounts')" id="tab-accounts"
                class="tab-btn px-4 py-2 rounded-lg text-sm font-semibold transition-colors text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
            Account Pairs <span class="ml-1.5 text-xs opacity-70">{{ $accountFlags->count() }}</span>
        </button>
    </div>

    {{-- Abstract duplicate pairs --}}
    <div id="panel-abstracts">
        @forelse($abstractFlags as $flag)
        @php
            $badgeClass = $flag['confidence'] === 'High'
                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden mb-4">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50 flex items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-slate-500">Pair #{{ $loop->iteration }}</span>
                    <p class="text-xs text-slate-400 mt-0.5">{{ implode(' · ', $flag['reasons']) }}</p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badgeClass }}">{{ $flag['confidence'] }}</span>
            </div>

            <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100 dark:divide-gray-800">
                @foreach (['left', 'right'] as $side)
                @php $abs = $flag[$side]; @endphp
                <div class="p-6">
                    {{-- Status badge --}}
                    @php
                        $statusColor = match($abs->status) {
                            'accepted'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'rejected'  => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            'under_review','assigned' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            default     => 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-slate-300',
                        };
                    @endphp
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $statusColor }}">
                            {{ ucwords(str_replace('_', ' ', $abs->status)) }}
                        </span>
                        @if($abs->conference_code)
                            <span class="text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ $abs->conference_code }}</span>
                        @endif
                    </div>

                    <p class="text-sm font-bold text-slate-900 dark:text-white leading-snug mb-1">{{ $abs->title }}</p>
                    <p class="text-xs text-slate-500 mb-4">
                        {{ $abs->author_name }}
                        @if($abs->author_institute) &middot; {{ $abs->author_institute }} @endif
                    </p>

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs mb-5">
                        <div>
                            <dt class="text-slate-400 mb-0.5">Presentation</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ ucwords(str_replace('_', ' ', $abs->presentation_mode ?? '—')) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 mb-0.5">Subtheme</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $abs->subtheme ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 mb-0.5">Avg Score</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $abs->average_score ? number_format($abs->average_score, 1) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 mb-0.5">Submitted</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ optional($abs->submitted_at ?? $abs->created_at)->format('M d, Y') }}</dd>
                        </div>
                    </dl>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.abstracts.view', $abs) }}"
                           class="flex-1 text-center px-3 py-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                            View Full Abstract
                        </a>
                        <button type="button"
                                onclick="openDeleteModal('{{ route('admin.abstracts.destroy', $abs) }}', '{{ addslashes($abs->title) }}')"
                                class="px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 p-12 text-center">
            <p class="text-slate-500 font-semibold">No duplicate abstract pairs detected.</p>
        </div>
        @endforelse
    </div>

    {{-- Account duplicate pairs --}}
    <div id="panel-accounts" class="hidden">
        @forelse($accountFlags as $flag)
        @php
            $badgeClass = $flag['confidence'] === 'High'
                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 shadow-sm overflow-hidden mb-4">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-gray-800 bg-slate-50 dark:bg-gray-800/50 flex items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-slate-500">Account Pair #{{ $loop->iteration }}</span>
                    <p class="text-xs text-slate-400 mt-0.5">{{ implode(' · ', $flag['reasons']) }}</p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badgeClass }}">{{ $flag['confidence'] }}</span>
            </div>

            <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100 dark:divide-gray-800">
                @foreach (['left', 'right'] as $side)
                @php $user = $flag[$side]; @endphp
                <div class="p-6">
                    <p class="text-sm font-bold text-slate-900 dark:text-white mb-0.5">{{ trim($user->first_name . ' ' . $user->last_name) }}</p>
                    <p class="text-xs text-slate-500 mb-4">{{ $user->email }}</p>

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs mb-5">
                        <div>
                            <dt class="text-slate-400 mb-0.5">Phone</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 mb-0.5">Submissions</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->abstract_submissions_count }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-slate-400 mb-0.5">Affiliation</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->affiliation ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 mb-0.5">Registered</dt>
                            <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ optional($user->created_at)->format('M d, Y') }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="block text-center px-3 py-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                        Open User Profile
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-slate-200 dark:border-gray-800 p-12 text-center">
            <p class="text-slate-500 font-semibold">No duplicate account pairs detected.</p>
        </div>
        @endforelse
    </div>

</div>
</div>

{{-- Delete modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200 dark:border-gray-700">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Delete Abstract Permanently</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">This cannot be undone. You are about to permanently delete:</p>
        <p id="deleteModalTitle" class="text-sm font-semibold text-red-600 dark:text-red-400 mb-5 leading-snug"></p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-gray-800 hover:bg-slate-200 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">
                    Yes, Delete Forever
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showTab(tab) {
    ['abstracts', 'accounts'].forEach(t => {
        document.getElementById('panel-' + t).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tab-' + t);
        btn.classList.toggle('bg-indigo-600', t === tab);
        btn.classList.toggle('text-white', t === tab);
        btn.classList.toggle('text-slate-500', t !== tab);
    });
}

function openDeleteModal(action, title) {
    document.getElementById('deleteModalTitle').textContent = title;
    document.getElementById('deleteForm').action = action;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>
@endpush
@endsection
