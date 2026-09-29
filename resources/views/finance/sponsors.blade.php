@extends('layouts.app')

@section('title', 'Sponsor Payments')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 font-sans">
    <div class="relative bg-gradient-to-br from-violet-900 via-slate-900 to-cyan-900 py-12 rounded-b-[3rem] shadow-xl mb-8">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-end gap-6">
                <div>
                    <h1 class="text-3xl font-black text-white leading-tight" style="font-family: 'Outfit', sans-serif;">
                        Sponsor <span class="text-cyan-300">Payments.</span>
                    </h1>
                    <p class="text-cyan-100/70 mt-2">Create sponsor invoices, record payments received and track settlement.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl px-4 py-3 text-white">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/50 block mb-1">Draft</span>
                            <span class="text-xl font-black text-slate-200">{{ $stats['pending_count'] }}</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl px-4 py-3 text-white">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/50 block mb-1">Submitted</span>
                            <span class="text-xl font-black text-amber-400">{{ $stats['submitted_count'] }}</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl px-4 py-3 text-white">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/50 block mb-1">Verified</span>
                            <span class="text-xl font-black text-emerald-400">{{ $stats['verified_count'] }}</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl px-4 py-3 text-white">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/50 block mb-1">Rejected</span>
                            <span class="text-xl font-black text-rose-400">{{ $stats['rejected_count'] }}</span>
                        </div>
                    </div>
                    <a href="{{ route('finance.sponsors.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-950/20 transition hover:bg-cyan-300">
                        New Sponsor Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-8 pb-12">
        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm font-bold text-sky-700">{{ session('info') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 w-full md:w-auto">
                    <a href="{{ route('finance.sponsors', ['status' => 'all']) }}" class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $status === 'all' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-gray-700' }}">All</a>
                    <a href="{{ route('finance.sponsors', ['status' => 'pending']) }}" class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $status === 'pending' ? 'bg-slate-700 text-white shadow-lg shadow-slate-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-gray-700' }}">Draft</a>
                    <a href="{{ route('finance.sponsors', ['status' => 'submitted']) }}" class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $status === 'submitted' ? 'bg-amber-500 text-white shadow-lg shadow-amber-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-gray-700' }}">Submitted</a>
                    <a href="{{ route('finance.sponsors', ['status' => 'verified']) }}" class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $status === 'verified' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-gray-700' }}">Verified</a>
                </div>

                <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center">
                    <form action="{{ route('finance.sponsors') }}" method="GET" class="relative w-full md:w-72">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search sponsor or reference..." class="w-full bg-slate-50 dark:bg-gray-900 border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-cyan-500 transition-all">
                        <button type="submit" class="absolute right-3 top-2.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </form>
                    <a href="{{ route('finance.export', ['type' => 'sponsors', 'status' => $status, 'search' => $search]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-cyan-200 transition hover:bg-cyan-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export Excel
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-gray-900/50">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Sponsor</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Contact</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reference</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Updated</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/30 transition-colors align-top">
                                <td class="px-6 py-4">
                                    <p class="max-w-xs font-bold text-slate-900 dark:text-white">{{ $payment->sponsor_name }}</p>
                                    @if($payment->package_name)<p class="text-[10px] font-black uppercase tracking-widest text-cyan-600">{{ $payment->package_name }}</p>@endif
                                    <p class="max-w-xs text-xs text-slate-500 line-clamp-2">{{ $payment->description }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $payment->contact_email }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $payment->contact_phone }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-black text-slate-900 dark:text-white">{{ $payment->currency }} {{ number_format((float) $payment->amount, $payment->currency === 'USD' ? 2 : 0) }}</p>
                                    @if($payment->paid_amount)
                                        <p class="text-[10px] font-bold text-emerald-600">Paid {{ $payment->paid_currency ?: $payment->currency }} {{ number_format((float) $payment->paid_amount, ($payment->paid_currency ?: $payment->currency) === 'USD' ? 2 : 0) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-mono text-xs font-black text-slate-800 dark:text-slate-200">{{ $payment->payment_reference ?: '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span data-sponsor-updated-at class="text-xs text-slate-500 font-medium">{{ optional($payment->updated_at)->format('M d, Y H:i') }}</span>
                                    <span data-sponsor-status-badge class="mt-2 block w-fit rounded-md px-2 py-1 text-[10px] font-black uppercase tracking-widest
                                        @if($payment->isVerified()) bg-emerald-100 text-emerald-700
                                        @elseif($payment->payment_status === 'submitted') bg-amber-100 text-amber-700
                                        @elseif($payment->payment_status === 'rejected') bg-rose-100 text-rose-700
                                        @else bg-slate-100 text-slate-600 @endif">
                                        {{ $payment->isVerified() ? 'verified' : $payment->payment_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($payment->isVerified())
                                        @php $settled = $payment->paymentTransactions()->where('status', 'verified')->first(); @endphp
                                        <span class="rounded-xl bg-emerald-100 px-3 py-2 text-xs font-black uppercase tracking-widest text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Paid</span>
                                        @if($settled)
                                            <p class="mt-2 text-[10px] text-slate-500">{{ $settled->method_label }} · <span class="font-mono">{{ $settled->external_reference }}</span></p>
                                            @if($settled->proof_path)
                                                <a href="{{ route('finance.transactions.proof', $settled) }}" target="_blank" class="text-[10px] font-bold text-indigo-600 hover:underline">View proof</a>
                                            @endif
                                        @endif
                                    @elseif($payment->payment_status === 'rejected')
                                        <span class="rounded-xl bg-rose-100 px-3 py-2 text-xs font-black uppercase tracking-widest text-rose-700">Cancelled</span>
                                    @else
                                        @php $sponsorMethods = app(\App\Payments\PaymentOptions::class)->methodsFor($payment->paymentCurrency()); @endphp
                                        <details class="w-72">
                                            <summary class="cursor-pointer rounded-xl bg-cyan-600 px-3 py-2 text-xs font-black text-white w-fit">Record payment</summary>
                                            <form action="{{ route('finance.sponsors.verify', $payment) }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-2">
                                                @csrf
                                                <select name="method" required class="w-full rounded-lg border-slate-200 text-xs">
                                                    @foreach($sponsorMethods as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="provider" class="w-full rounded-lg border-slate-200 text-xs">
                                                    <option value="">Operator (mobile money only)</option>
                                                    @foreach(app(\App\Payments\PaymentOptions::class)->mobileMoneyProviders($payment->paymentCurrency()) as $key => $provider)
                                                        <option value="{{ $key }}">{{ $provider['label'] }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="external_reference" required placeholder="Bank / transaction reference" class="w-full rounded-lg border-slate-200 text-xs font-mono">
                                                <input name="amount" type="number" step="0.01" min="0" placeholder="Amount received ({{ $payment->currency }})" class="w-full rounded-lg border-slate-200 text-xs">
                                                <input name="paid_on" type="date" max="{{ now()->toDateString() }}" class="w-full rounded-lg border-slate-200 text-xs">
                                                <input name="proof" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">
                                                <textarea name="notes" rows="2" placeholder="Notes (optional)" class="w-full rounded-lg border-slate-200 text-xs"></textarea>
                                                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white" onclick="return confirm('Record this payment as received and verified?')">Verify payment</button>
                                            </form>
                                            <form action="{{ route('finance.sponsors.reject', $payment) }}" method="POST" class="mt-2 space-y-2" onsubmit="return confirm('Cancel this sponsor invoice?')">
                                                @csrf
                                                <input name="notes" required placeholder="Reason for cancelling" class="w-full rounded-lg border-slate-200 text-xs">
                                                <button type="submit" class="w-full rounded-lg border border-rose-200 px-3 py-2 text-xs font-black text-rose-600">Cancel invoice</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">No sponsor payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 dark:border-gray-700">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
