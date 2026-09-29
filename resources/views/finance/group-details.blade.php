@extends('layouts.app')

@section('title', 'Group Details: ' . ($groupRegistration->group_name ?: 'Group #' . $groupRegistration->id))

@section('content')
<div class="min-h-screen bg-[#F8F9FC] dark:bg-[#0B1120] font-sans pb-24">
    <!-- Top Navigation / Header -->
    <div class="bg-white dark:bg-[#151C2C] border-b border-slate-200/60 dark:border-slate-800/60 sticky top-0 z-30 backdrop-blur-xl bg-white/80 dark:bg-[#151C2C]/80">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="{{ route('finance.groups') }}" class="group flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Group Record</h1>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $groupRegistration->organization ?: 'No Org.' }}</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                     <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide 
                        {{ $groupRegistration->payment_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : 
                          ($groupRegistration->payment_status === 'submitted' ? 'bg-amber-100 text-amber-700' : 
                          ($groupRegistration->payment_status === 'waived' ? 'bg-indigo-100 text-indigo-700' : 
                          ($groupRegistration->payment_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500'))) }}">
                        {{ $groupRegistration->payment_status === 'verified' ? 'Settled' : ($groupRegistration->payment_status === 'submitted' ? 'Awaiting Review' : ($groupRegistration->payment_status ?? 'Outstanding')) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 space-y-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Group Profile Card -->
                <div class="bg-white dark:bg-[#151C2C] rounded-2xl p-8 border border-slate-100 dark:border-slate-800 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-6">
                            <div class="h-20 w-20 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-2xl">
                                {{ substr($groupRegistration->group_name ?: 'G', 0, 1) }}
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $groupRegistration->group_name ?: 'Group #' . $groupRegistration->id }}</h2>
                                <p class="text-sm text-slate-500 mb-4">{{ $groupRegistration->organization ?: 'Personal Group' }}</p>
                                <div class="flex items-center gap-4">
                                     <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                                            {{ $groupRegistration->member_count }}
                                        </div>
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Members</span>
                                     </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                             <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Due</p>
                             <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $groupRegistration->formatted_total }}</p>
                        </div>
                    </div>
                </div>

                @include('finance.partials.transactions', ['transactions' => $transactions])

                <!-- Members List -->
                <div class="bg-white dark:bg-[#151C2C] rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wide">Group Members</h3>
                    </div>
                    <div class="divide-y divide-slate-50 dark:divide-slate-800">
                        @foreach($groupRegistration->members as $member)
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 text-xs font-bold">
                                        {{ substr($member->full_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $member->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $member->category_label }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $member->formatted_fee }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column: Actions -->
            <div class="space-y-8">
                <!-- Action Card -->
                <div class="bg-white dark:bg-[#151C2C] rounded-2xl p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wide mb-6">Review</h3>
                    
                    <div class="space-y-3">
                         @if($groupRegistration->payment_status !== 'verified' && $groupRegistration->payment_status !== 'waived')
                            @include('finance.partials.review-actions', [
                                'awaiting' => $groupRegistration->transactionAwaitingReview(),
                                'verifyRoute' => route('finance.group.verify', $groupRegistration),
                                'rejectRoute' => route('finance.group.reject', $groupRegistration),
                                'payerName' => $groupRegistration->group_name ?: 'this group',
                            ])
                         @endif

                         @if($groupRegistration->payment_status !== 'waived')
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                                <button onclick="openWaiveModal()" class="w-full py-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-bold text-sm rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                    Grant Group Waiver
                                </button>
                            </div>
                         @else
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                                <form action="{{ route('finance.group.return-to-payment', $groupRegistration) }}" method="POST" onsubmit="return confirm('Remove this group waiver and return this group to the payment flow?')">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 font-bold text-sm rounded-xl hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                                        Return Group to Payment
                                    </button>
                                </form>
                            </div>
                         @endif
                    </div>
                    <p class="text-[10px] text-slate-400 mt-4 leading-relaxed text-center">
                        Verify only after matching the reference and amount against the bank statement or mobile money report.
                    </p>
                </div>

                <!-- Meta Info -->
                <div class="bg-slate-50 dark:bg-[#151C2C] rounded-2xl p-6 border border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wide mb-4">Group Meta</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Created On</dt>
                            <dd class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $groupRegistration->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last Update</dt>
                            <dd class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $groupRegistration->updated_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Waive Modal -->
<div id="waiveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Grant Group Waiver</h3>
        </div>
        <form action="{{ route('finance.group.waive', $groupRegistration) }}" method="POST" class="p-6 space-y-4">
            @csrf
            <p class="text-sm text-slate-500">This will waiver the fee for all members in this group.</p>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Justification</label>
                <textarea name="notes" rows="2" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 text-sm bg-slate-50 dark:bg-slate-800 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. Sponsored Group..."></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('waiveModal')" class="flex-1 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openWaiveModal() { document.getElementById('waiveModal').classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
// Simple close handler for the specific modal
document.getElementById('waiveModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>

@endsection
