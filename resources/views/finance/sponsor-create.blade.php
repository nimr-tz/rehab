@extends('layouts.app')

@section('title', 'Create Sponsor Invoice')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 font-sans">
    <div class="relative bg-gradient-to-br from-violet-900 via-slate-900 to-cyan-900 py-12 rounded-b-[3rem] shadow-xl mb-8">
        <div class="max-w-5xl mx-auto px-6 sm:px-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-3xl font-black text-white leading-tight" style="font-family: 'Outfit', sans-serif;">
                        Sponsor <span class="text-cyan-300">Invoice.</span>
                    </h1>
                    <p class="text-cyan-100/70 mt-2">Create a sponsor invoice. The system issues a payment reference the sponsor quotes when paying.</p>
                </div>
                <a href="{{ route('finance.sponsors') }}" class="inline-flex items-center justify-center rounded-2xl bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:bg-white/20">
                    Back to Sponsors
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-6 sm:px-8 pb-12">
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
                Please check the highlighted fields and try again.
            </div>
        @endif

        <form action="{{ route('finance.sponsors.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-slate-100 dark:border-gray-700 overflow-hidden">
            @csrf
            <div class="grid gap-6 p-6 md:grid-cols-2">
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Sponsor / Organisation</label>
                    <input name="sponsor_name" value="{{ old('sponsor_name') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900">
                    @error('sponsor_name')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Package</label>
                    <input name="package_name" value="{{ old('package_name') }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900" placeholder="e.g. Gold, Student sponsorship">
                    @error('package_name')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Contact Person</label>
                    <input name="contact_person" value="{{ old('contact_person') }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900">
                    @error('contact_person')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900" placeholder="billing@example.com">
                    @error('contact_email')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Contact Phone</label>
                    <input name="contact_phone" value="{{ old('contact_phone') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900" placeholder="255...">
                    @error('contact_phone')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Currency</label>
                    <select name="currency" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900">
                        <option value="TZS" @selected(old('currency', 'TZS') === 'TZS')>TZS</option>
                        <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                    </select>
                    @error('currency')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div x-data="{
                    raw: @js(old('amount', '')),
                    formatAmount(value) {
                        const cleaned = String(value || '').replace(/,/g, '').replace(/[^\d.]/g, '');
                        const parts = cleaned.split('.');
                        const whole = parts[0] || '';
                        const decimals = parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '';
                        this.raw = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + decimals;
                    }
                }" x-init="formatAmount(raw)">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Amount</label>
                    <input type="text" inputmode="decimal" name="amount" x-model="raw" @input="formatAmount($event.target.value)" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900" placeholder="5,000,000">
                    @error('amount')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Description</label>
                    <textarea name="description" rows="4" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900" placeholder="What the sponsorship covers">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Internal Notes</label>
                    <textarea name="admin_notes" rows="3" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold dark:border-gray-700 dark:bg-gray-900" placeholder="Optional finance notes">{{ old('admin_notes') }}</textarea>
                    @error('admin_notes')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 p-6 dark:border-gray-700 dark:bg-gray-900/50 sm:flex-row sm:justify-end">
                <a href="{{ route('finance.sponsors') }}" class="rounded-2xl bg-white px-5 py-3 text-center text-sm font-black text-slate-600 shadow-sm dark:bg-gray-800 dark:text-slate-200">Cancel</a>
                <button type="submit" class="rounded-2xl bg-cyan-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-cyan-200 dark:shadow-none">Create Sponsor Invoice</button>
            </div>
        </form>
    </div>
</div>
@endsection
