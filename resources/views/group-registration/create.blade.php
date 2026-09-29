@extends('layouts.app')

@section('title', 'Create Group Registration')

@section('content')
@php
    $countries = collect(config('countries', []))->pluck('name')->all();
    $registrationFees = collect($fees)->only([
        'professional_local',
        'professional_international',
        'student_local',
        'student_international',
    ]);
@endphp
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900 relative font-sans">
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-900 py-16 rounded-b-[4rem] shadow-2xl overflow-hidden mb-8">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 relative z-10">
            <div class="space-y-3">
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('group-registration.index') }}" class="text-teal-200 hover:text-white transition-colors flex items-center gap-1 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to Groups
                    </a>
                </div>
                <h1 class="text-3xl md:text-4xl font-black text-white leading-tight tracking-tight" style="font-family: 'Outfit', sans-serif;">
                    Create Group Registration
                </h1>
                <p class="text-teal-200 text-sm font-medium">
                    Add at least 3 members to register as a group
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-6 sm:px-8 pb-12">
        @if($errors->any())
            <div class="mb-6 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4">
                <ul class="text-sm text-rose-700 dark:text-rose-300 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            </div>
        @endif

        <!-- Warning for Authors -->
        <div class="mb-6 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl p-4 flex gap-3">
            <svg class="w-6 h-6 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p class="font-bold text-amber-800 dark:text-amber-400 text-sm mb-1">Attention Abstract Authors:</p>
                <p class="text-sm text-amber-700 dark:text-amber-500">
                    If any member of your group has submitted an abstract for presentation, they should <span class="font-bold underline">register individually</span> using their own account. Registering via a group may prevent their abstract from being correctly linked to their registration status.
                </p>
            </div>
        </div>

        <form id="group-form" action="{{ route('group-registration.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Group Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-100 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Group Information
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Group Name</label>
                        <input type="text" name="group_name" value="{{ old('group_name') }}" placeholder="e.g., {{ config('conference.host_short') }} Research Team" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Organization <span class="text-rose-500">*</span></label>
                        <input type="text" name="organization" value="{{ old('organization') }}" required placeholder="e.g., {{ config('conference.host') }}" class="w-full px-4 py-3 bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
                    </div>
                </div>
            </div>

            <!-- Fee Reference Card -->
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl p-6 mb-6 text-white">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Registration Fees
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    @foreach($registrationFees as $key => $fee)
                        <div class="bg-white/10 rounded-lg p-3">
                            <p class="text-slate-300 text-xs">{{ $fee['label'] }}</p>
                            <p class="font-bold text-lg text-white">
                                {{ $fee['currency'] === 'USD' ? '$' . number_format($fee['amount']) : 'TZS ' . number_format($fee['amount']) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Members Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Group Members
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Minimum 3 members required</p>
                    </div>
                    <button type="button" id="add-member-btn" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-bold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Member
                    </button>
                </div>
                <div class="p-6">
                    <div id="members-container" class="space-y-4">
                        <!-- Members will be added here dynamically -->
                    </div>
                    <p id="member-count-warning" class="hidden mt-4 text-sm text-amber-600 dark:text-amber-400 font-medium">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        Add at least 3 members to create a group registration
                    </p>
                </div>
            </div>

            <!-- Total and Submit -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Total Amount</p>
                        <p id="total-amount" class="text-3xl font-black text-slate-900 dark:text-white">TZS 0</p>
                        <p class="text-xs text-slate-400 mt-1"><span id="member-count">0</span> member(s)</p>
                    </div>
                    <button type="submit" id="submit-btn" disabled class="inline-flex items-center gap-2 px-8 py-4 bg-teal-600 hover:bg-teal-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-teal-200 dark:shadow-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Create Group Registration
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Member Template -->
<template id="member-template">
    <div class="member-card bg-slate-50 dark:bg-gray-700/30 rounded-xl p-5 border border-slate-200 dark:border-gray-600 relative">
        <button type="button" class="remove-member-btn absolute -top-2 -right-2 w-6 h-6 bg-rose-500 hover:bg-rose-600 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-lg transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="members[INDEX][full_name]" required placeholder="Enter full name" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Email <span class="text-rose-500">*</span></label>
                <input type="email" name="members[INDEX][email]" required placeholder="email@example.com" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Phone</label>
                <input type="text" name="members[INDEX][phone]" placeholder="+255..." class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Institution <span class="text-rose-500">*</span></label>
                <input type="text" name="members[INDEX][institution]" required placeholder="Organization name" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Country <span class="text-rose-500">*</span></label>
                <select name="members[INDEX][country]" required class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ $country === 'Tanzania' ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Category <span class="text-rose-500">*</span></label>
                <select name="members[INDEX][registration_category]" required class="category-select w-full px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
                    <option value="">Select category...</option>
                    @foreach($registrationFees as $key => $fee)
                        <option value="{{ $key }}" data-amount="{{ $fee['amount'] }}" data-currency="{{ $fee['currency'] }}">
                            {{ $fee['label'] }} - {{ $fee['currency'] === 'USD' ? '$' . number_format($fee['amount']) : 'TZS ' . number_format($fee['amount']) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="student-doc-container hidden lg:col-span-3">
                <label class="block text-xs font-semibold text-rose-600 dark:text-rose-400 mb-1.5 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Student ID / Verification Document <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <input type="file" name="members[INDEX][student_document]" accept=".pdf,.jpg,.jpeg,.png" class="student-doc-input w-full px-3 py-2 bg-white dark:bg-gray-800 border border-rose-100 dark:border-rose-900/30 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/20">
                    <p class="text-[10px] text-slate-400 flex-shrink-0">PDF, JPG, PNG (Max 5MB)</p>
                </div>
            </div>
        </div>
        <div class="mt-3 text-right">
            <span class="member-fee text-sm font-bold text-teal-600">Fee: --</span>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('members-container');
    const template = document.getElementById('member-template');
    const addBtn = document.getElementById('add-member-btn');
    const submitBtn = document.getElementById('submit-btn');
    const totalEl = document.getElementById('total-amount');
    const countEl = document.getElementById('member-count');
    const warningEl = document.getElementById('member-count-warning');

    let memberIndex = 0;

    function addMember(data = null) {
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.member-card');

        // Update field names with current index
        card.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace('INDEX', memberIndex);
        });

        // Pre-fill data if provided
        if (data) {
            if (data.full_name) card.querySelector('[name$="[full_name]"]').value = data.full_name;
            if (data.email) card.querySelector('[name$="[email]"]').value = data.email;
            if (data.phone) card.querySelector('[name$="[phone]"]').value = data.phone;
            if (data.institution) card.querySelector('[name$="[institution]"]').value = data.institution;
            if (data.country) card.querySelector('[name$="[country]"]').value = data.country;
            if (data.registration_category) {
                const select = card.querySelector('.category-select');
                select.value = data.registration_category;
                // Trigger change to update fee display
                setTimeout(() => select.dispatchEvent(new Event('change')), 10);
            }

            // If it's the leader (first member), maybe show a indicator
            if (memberIndex === 0) {
                const label = card.querySelector('label');
                label.innerHTML += ' <span class="text-[10px] bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded ml-2 font-bold uppercase">Group Leader</span>';
            }
        }

        // Remove button handler
        card.querySelector('.remove-member-btn').addEventListener('click', function() {
            card.remove();
            updateTotals();
        });

        // Category change handler
        card.querySelector('.category-select').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const feeSpan = card.querySelector('.member-fee');
            const studentDocContainer = card.querySelector('.student-doc-container');
            const studentDocInput = card.querySelector('.student-doc-input');

            if (selected.value) {
                const amount = selected.dataset.amount;
                const currency = selected.dataset.currency;
                feeSpan.textContent = 'Fee: ' + (currency === 'USD' ? '$' + parseInt(amount).toLocaleString() : 'TZS ' + parseInt(amount).toLocaleString());

                // Show/hide student doc
                if (selected.value.includes('student')) {
                    studentDocContainer.classList.remove('hidden');
                    studentDocInput.required = true;
                } else {
                    studentDocContainer.classList.add('hidden');
                    studentDocInput.required = false;
                    studentDocInput.value = ''; // Clear if hidden
                }
            } else {
                feeSpan.textContent = 'Fee: --';
                studentDocContainer.classList.add('hidden');
                studentDocInput.required = false;
            }
            updateTotals();
        });

        container.appendChild(clone);
        memberIndex++;
        updateTotals();
    }

    function updateTotals() {
        const cards = container.querySelectorAll('.member-card');
        let totalTZS = 0;
        let totalUSD = 0;
        let validMembers = 0;

        cards.forEach(card => {
            const select = card.querySelector('.category-select');
            const selected = select.options[select.selectedIndex];
            if (selected.value) {
                validMembers++;
                const amount = parseInt(selected.dataset.amount);
                const currency = selected.dataset.currency;
                if (currency === 'USD') {
                    totalUSD += amount;
                } else {
                    totalTZS += amount;
                }
            }
        });

        // Display total
        if (totalUSD > 0 && totalTZS > 0) {
            totalEl.textContent = 'TZS ' + totalTZS.toLocaleString() + ' + $' + totalUSD.toLocaleString();
        } else if (totalUSD > 0) {
            totalEl.textContent = '$' + totalUSD.toLocaleString();
        } else {
            totalEl.textContent = 'TZS ' + totalTZS.toLocaleString();
        }

        countEl.textContent = cards.length;

        // Validate minimum 3 members
        const hasEnoughMembers = cards.length >= 3;
        const allCategoriesSelected = validMembers >= 3;

        submitBtn.disabled = !(hasEnoughMembers && allCategoriesSelected);
        warningEl.classList.toggle('hidden', hasEnoughMembers);
    }

    addBtn.addEventListener('click', () => addMember());

    addMember({
        full_name: "{{ $user->full_name }}",
        email: "{{ $user->email }}",
        phone: "{{ $user->phone }}",
        institution: "{{ $user->affiliation }}",
        country: "{{ $user->country }}",
        registration_category: "{{ $user->registration_category }}"
    });

    addMember();
    addMember();
});
</script>
@endpush
@endsection
