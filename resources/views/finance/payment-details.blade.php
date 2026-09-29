@extends('layouts.app')

@section('title', 'Record Details: ' . $user->full_name)

@section('content')
<div class="min-h-screen bg-[#F8F9FC] dark:bg-[#0B1120] font-sans pb-24">
    <!-- Top Navigation / Header -->
    <div class="bg-white dark:bg-[#151C2C] border-b border-slate-200/60 dark:border-slate-800/60 sticky top-0 z-30 backdrop-blur-xl bg-white/80 dark:bg-[#151C2C]/80">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="{{ route('finance.payments') }}" class="group flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Payment Record</h1>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $user->email }}</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide 
                        {{ $user->payment_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : 
                          ($user->payment_status === 'submitted' ? 'bg-amber-100 text-amber-700' : 
                          ($user->payment_status === 'waived' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500')) }}">
                        @if($user->payment_status === 'verified')
                            Settled
                        @elseif($user->payment_status === 'submitted')
                            Awaiting Review
                        @else
                            {{ $user->payment_status ?? 'Outstanding' }}
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 space-y-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- User Profile Card -->
                <div class="bg-white dark:bg-[#151C2C] rounded-2xl p-8 border border-slate-100 dark:border-slate-800 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-6">
                            <div class="h-20 w-20 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 font-black text-2xl">
                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $user->full_name }}</h2>
                                <p class="text-sm text-slate-500 mb-4">{{ $user->email }}</p>
                                <div class="flex items-center gap-4">
                                    <div class="px-3 py-1 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-100 dark:border-slate-700 uppercase tracking-wide">
                                        {{ str_replace('_', ' ', $user->registration_category ?? 'Standard') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                             @php $fee = $user->getRegistrationFee(); @endphp
                             <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Fee Due</p>
                             <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $fee['currency'] }} {{ number_format($fee['amount']) }}</p>
                             <p class="mt-1 font-mono text-xs font-bold text-indigo-600">{{ $user->payment_reference ?? 'No reference yet' }}</p>
                        </div>
                    </div>
                </div>

                @include('finance.partials.transactions', ['transactions' => $transactions])
            </div>

            <!-- Right Column: Actions -->
            <div class="space-y-8">
                <!-- Action Card -->
                <div class="bg-white dark:bg-[#151C2C] rounded-2xl p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wide mb-6">Review</h3>
                    
                    <div class="space-y-3">
                         @if($user->payment_status !== 'verified' && $user->payment_status !== 'waived')
                            @include('finance.partials.review-actions', [
                                'awaiting' => $user->transactionAwaitingReview(),
                                'verifyRoute' => route('finance.verify', $user),
                                'rejectRoute' => route('finance.reject', $user),
                                'payerName' => $user->full_name,
                            ])
                         @endif

                         @if($user->payment_status !== 'waived')
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                                <button onclick="openWaiveModal()" class="w-full py-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-bold text-sm rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                    Grant Official Waiver
                                </button>
                            </div>
                         @else
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                                <form action="{{ route('finance.return-to-payment', $user) }}" method="POST" onsubmit="return confirm('Remove this waiver and return this participant to the payment flow?')">
                                    @csrf
                                    <button type="submit" class="w-full py-3 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 font-bold text-sm rounded-xl hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                                        Return to Payment
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
                    <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wide mb-4">System Meta</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Added On</dt>
                            <dd class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $user->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last Update</dt>
                             <dd class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $user->updated_at->diffForHumans() }}</dd>
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
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Grant Waiver</h3>
        </div>
        <form action="{{ route('finance.waive', $user) }}" method="POST" class="p-6 space-y-4">
            @csrf
            <p class="text-sm text-slate-500">This will bypass payment verification and grant full access.</p>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Justification</label>
                <textarea name="notes" rows="2" required class="w-full rounded-xl border-slate-200 dark:border-slate-700 text-sm bg-slate-50 dark:bg-slate-800 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. VIP Guest, Speaker..."></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('waiveModal')" class="flex-1 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30">Confirm Waiver</button>
            </div>
        </form>
    </div>
</div>

<script>
function openWaiveModal() { document.getElementById('waiveModal').classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>

@endsection
