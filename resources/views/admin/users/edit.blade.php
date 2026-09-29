@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="relative max-w-[1600px] mx-auto px-6 py-10 text-slate-900 dark:text-white">

        {{-- Executive Header System --}}
        <div class="relative overflow-hidden mb-12">
            {{-- Abstract Background Elements --}}
            <div class="absolute inset-0 opacity-40 pointer-events-none">
                <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-purple-200 dark:bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8 z-10">
                <div class="flex items-center gap-6">
                    <div class="p-5 bg-slate-900 dark:bg-white rounded-[2rem] shadow-2xl shadow-slate-200/50 dark:shadow-none transition-all group">
                        <svg class="w-10 h-10 text-white dark:text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Edit User</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase rounded-md border border-blue-500/20">User Profile</span>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium tracking-wide">Editing the profile of <span class="text-slate-900 dark:text-white font-black">{{ $user->first_name }} {{ $user->last_name }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.users') }}" class="px-6 py-4 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-black text-xs uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back to Users
                    </a>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-10">

            {{-- Left Column: Entity Intelligence --}}
            <div class="lg:col-span-4 space-y-8">

                {{-- Profile Stat Card --}}
                <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:scale-125 transition-transform duration-700">
                        <svg class="w-48 h-48 text-slate-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    </div>

                    <div class="relative z-10 text-center">
                        <div class="relative inline-block mb-6">
                            @if($user->profile_image)
                                <img src="{{ asset('storage/' . $user->profile_image) }}" alt=""
                                     class="h-32 w-32 rounded-[2.5rem] object-cover mx-auto shadow-2xl border-4 border-white dark:border-slate-700" />
                            @else
                                <div class="h-32 w-32 rounded-[2.5rem] bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center font-black text-4xl shadow-2xl border-4 border-white dark:border-slate-700">
                                    {{ $user->initials }}
                                </div>
                            @endif
                            <div class="absolute -bottom-2 -right-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 p-3 rounded-2xl shadow-xl border-2 border-white dark:border-slate-700">
                                @php
                                    $roleIcons = [
                                        'admin' => '👑',
                                        'scientific_admin' => '🎓',
                                        'reviewer' => '🔍',
                                        'finance_officer' => '💰',
                                        'registration_officer' => '📋',
                                        'chief_rapporteur' => '🗂️',
                                        'author' => '✏️',
                                    ];
                                @endphp
                                <span class="text-xl leading-none">{{ $roleIcons[$user->role] ?? '👤' }}</span>
                            </div>
                        </div>

                        <h2 class="text-2xl font-black text-slate-900 dark:text-white leading-tight px-4">{{ $user->title ?? '' }} {{ $user->first_name }} {{ $user->last_name }}</h2>
                        <p class="text-sm font-bold text-slate-400 mt-2">{{ $user->email }}</p>

                        <div class="mt-8 flex flex-wrap justify-center gap-3 px-2">
                            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700 flex-1 min-w-[120px]">
                                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest leading-none mb-1 text-center">Submissions</p>
                                <p class="text-xl font-black text-slate-900 dark:text-white text-center">{{ $userStats['total_submissions'] }}</p>
                            </div>
                            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700 flex-1 min-w-[120px]">
                                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest leading-none mb-1 text-center">Reviews</p>
                                <p class="text-xl font-black text-slate-900 dark:text-white text-center">{{ $userStats['reviews_completed'] }}</p>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Account Created</p>
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mt-1">Member Since: {{ $userStats['member_since'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Access Protocol Panel --}}
                <div class="p-8 bg-slate-900 rounded-[3rem] border border-slate-800 shadow-2xl relative overflow-hidden group text-white">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-blue-500/20 rounded-2xl text-blue-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.586 21h14.828a2 2 0 001.965-1.515L22 17"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black tracking-tight leading-none uppercase tracking-tighter">Current Roles</h3>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">Primary Role</p>
                            </div>
                        </div>

                        <div class="p-6 bg-slate-800/50 rounded-[2.5rem] border border-slate-700 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                @php
                                    $roleIconsFull = [
                                        'admin' => '👑',
                                        'scientific_admin' => '🎓',
                                        'reviewer' => '🔍',
                                        'finance_officer' => '💰',
                                        'registration_officer' => '📋',
                                        'chief_rapporteur' => '🗂️',
                                        'author' => '✏️',
                                    ];
                                @endphp
                                <span class="text-4xl">{{ $roleIconsFull[$user->role] ?? '👤' }}</span>
                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Role</p>
                                    <p class="text-2xl font-black">{{ ucfirst($user->role) }}</p>
                                </div>
                            </div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-ping"></div>
                        </div>
                    </div>
                </div>
                {{-- Payment Status Card --}}
                @php
                    $paymentStatusMeta = [
                        'verified'           => ['label' => 'Verified',           'color' => 'emerald', 'icon' => '✅'],
                        'waived'             => ['label' => 'Waived',             'color' => 'purple',  'icon' => '🎁'],
                        'submitted'          => ['label' => 'Submitted',          'color' => 'amber',   'icon' => '⏳'],
                        'rejected'           => ['label' => 'Rejected',           'color' => 'rose',    'icon' => '❌'],
                        'pending'            => ['label' => 'Pending',            'color' => 'slate',   'icon' => '⏸️'],
                    ];
                    $currentMeta = $paymentStatusMeta[$user->payment_status] ?? $paymentStatusMeta['pending'];
                @endphp
                <div class="p-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 bg-emerald-500/10 rounded-2xl text-emerald-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white leading-none">Payment Status</h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Override Registration Payment</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl text-emerald-700 dark:text-emerald-400 text-xs font-black">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-5 px-4 py-3 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-700 flex items-center gap-3">
                        <span class="text-xl">{{ $currentMeta['icon'] }}</span>
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Current</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $currentMeta['label'] }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.users.set-payment-status', $user) }}" class="space-y-4">
                        @csrf
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Set Status</label>
                            <div class="relative">
                                <select name="payment_status" class="w-full h-12 pl-4 pr-10 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl appearance-none font-black text-sm text-slate-900 dark:text-white focus:ring-4 focus:ring-blue-500/10 outline-none cursor-pointer">
                                    @foreach($paymentStatusMeta as $val => $meta)
                                        <option value="{{ $val }}" {{ $user->payment_status === $val ? 'selected' : '' }}>
                                            {{ $meta['icon'] }} {{ $meta['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Reason / Notes (optional)</label>
                            <textarea name="payment_notes" rows="2" placeholder="e.g. Reason for manual status change"
                                      class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm text-slate-900 dark:text-white resize-none focus:ring-4 focus:ring-blue-500/10 outline-none">{{ $user->payment_notes }}</textarea>
                        </div>
                        <button type="submit" class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest rounded-2xl hover:-translate-y-0.5 transition-all shadow-lg">
                            Update Payment Status
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right Column: Optimization Engine --}}
            <div class="lg:col-span-8">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    {{-- Role Matrix --}}
                    <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Roles & Permissions</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Assign Roles</p>
                            </div>
                            <div class="px-4 py-1.5 bg-blue-50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 rounded-full">
                                <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Permissions</span>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4 mb-8">
                            @foreach($availableRoles as $roleValue => $roleLabel)
                                @php
                                    $hasRole = $user->hasRole($roleValue);
                                    $isPrimary = $user->primaryRole() && $user->primaryRole()->name === $roleValue;
                                @endphp
                                <label class="group relative flex items-start p-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 rounded-[2rem] cursor-pointer transition-all hover:border-blue-500/30">
                                    <div class="flex items-center h-6 mt-1">
                                        <input type="checkbox" name="roles[]" value="{{ $roleValue }}"
                                               {{ $hasRole ? 'checked' : '' }}
                                               class="w-5 h-5 text-blue-600 border-slate-300 dark:border-slate-700 rounded-lg focus:ring-blue-500 bg-white dark:bg-slate-800">
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-black text-slate-900 dark:text-white uppercase text-xs tracking-widest">
                                                {{ $roleIcons[$roleValue] ?? '👤' }} {{ $roleLabel }}
                                            </span>
                                            @if($isPrimary)
                                                <span class="text-[8px] font-black bg-blue-600 text-white px-2 py-0.5 rounded-full">PRINCIPAL</span>
                                            @endif
                                        </div>
                                        <span class="text-slate-400 text-[10px] font-bold mt-1 block leading-relaxed">
                                            @if($roleValue === 'admin')
                                                Full access to all system features.
                                            @elseif($roleValue === 'scientific_admin')
                                                Scientific program oversight, abstract and reviewer coordination.
                                            @elseif($roleValue === 'reviewer')
                                                Can review and grade abstracts.
                                            @elseif($roleValue === 'finance_officer')
                                                Can manage payments and registrations.
                                            @elseif($roleValue === 'registration_officer')
                                                Can check-in users.
                                            @elseif($roleValue === 'chief_rapporteur')
                                                Reviews, edits, and approves session reports; compiles the final report.
                                            @elseif($roleValue === 'author')
                                                Can submit abstracts.
                                            @endif
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="p-8 bg-blue-500/5 rounded-[2.5rem] border border-blue-500/10">
                            <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] mb-4">Select Primary Role (Default Login)</label>
                            <div class="relative group">
                                <select name="primary_role" id="primary_role" required
                                        class="w-full h-14 pl-6 pr-12 bg-white dark:bg-slate-900 border border-blue-500/20 rounded-2xl appearance-none cursor-pointer focus:ring-4 focus:ring-blue-500/10 text-sm font-black text-slate-900 dark:text-white outline-none transition-all">
                                    @foreach($availableRoles as $roleValue => $roleLabel)
                                        @php
                                            $isPrimary = $user->primaryRole() && $user->primaryRole()->name === $roleValue;
                                        @endphp
                                        <option value="{{ $roleValue }}" {{ $isPrimary ? 'selected' : '' }}>
                                            {{ $roleIcons[$roleValue] ?? '👤' }} {{ $roleLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 text-blue-400 pointer-events-none">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Data Fields --}}
                    <div class="p-10 bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl">
                        <div class="flex items-center justify-between mb-10">
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">User Information</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Personal Details</p>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8 mb-8">
                            {{-- Title --}}
                            <div class="space-y-2">
                                <label for="title" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Title</label>
                                <select name="title" id="title" class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white appearance-none cursor-pointer">
                                    <option value="">N/A</option>
                                    @foreach(['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'] as $t)
                                        <option value="{{ $t }}" {{ $user->title === $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Email --}}
                            <div class="space-y-2">
                                <label for="email" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Email Address <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                       class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white">
                                @error('email') <p class="text-[10px] font-black text-rose-500 mt-1 uppercase">{{ $message }}</p> @enderror
                            </div>

                            {{-- First Name --}}
                            <div class="space-y-2">
                                <label for="first_name" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">First Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                       class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white">
                            </div>

                            {{-- Last Name --}}
                            <div class="space-y-2">
                                <label for="last_name" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Last Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                       class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white">
                            </div>

                            {{-- Institute --}}
                            <div class="space-y-2 md:col-span-1">
                                <label for="affiliation" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Institution</label>
                                <input type="text" name="affiliation" id="affiliation" value="{{ old('affiliation', $user->affiliation) }}"
                                       class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white">
                            </div>

                            {{-- Registration Category --}}
                            <div class="space-y-2 md:col-span-1">
                                <label for="registration_category" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Registration Category</label>
                                <div class="relative">
                                    <select name="registration_category" id="registration_category"
                                            class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white appearance-none cursor-pointer">
                                        <option value="">Select Category...</option>
                                        <option value="professional_local" {{ $user->registration_category === 'professional_local' ? 'selected' : '' }}>Participant (East Africa)</option>
                                        <option value="professional_international" {{ $user->registration_category === 'professional_international' ? 'selected' : '' }}>Participant (International)</option>
                                        <option value="student_local" {{ $user->registration_category === 'student_local' ? 'selected' : '' }}>Student (East Africa)</option>
                                        <option value="student_international" {{ $user->registration_category === 'student_international' ? 'selected' : '' }}>Student (International)</option>
                                    </select>
                                    <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                        @if($user->hasRole('reviewer'))
                            <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700">
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Reviewer Preferences</h3>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Manage Review Assignments & Subthemes</p>
                                    </div>
                                    <div class="px-4 py-1.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 rounded-full">
                                        <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Reviewer Engine</span>
                                    </div>
                                </div>
                                
                                <div class="space-y-8">
                                    <div class="space-y-2 md:w-1/2">
                                        <label for="reviewer_max_load" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Maximum Review Load</label>
                                        <div class="relative">
                                            <input type="number" name="reviewer_max_load" id="reviewer_max_load" min="1" max="50" value="{{ old('reviewer_max_load', $user->reviewer_max_load ?? 10) }}"
                                                class="w-full h-16 px-6 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-black text-slate-900 dark:text-white">
                                            <div class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xs uppercase tracking-widest pointer-events-none">Abstracts</div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <label class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest flex items-center gap-2">
                                            Selected Subthemes 
                                            <span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-md">{{ count($selectedSubthemes ?? []) }}</span>
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto custom-scrollbar pr-2 p-2 bg-slate-50/50 dark:bg-slate-900/30 rounded-3xl border border-slate-100 dark:border-slate-800">
                                            @foreach(($subthemes ?? []) as $code => $name)
                                                <label class="flex items-start p-4 bg-white dark:bg-slate-800 border {{ in_array($code, ($selectedSubthemes ?? [])) ? 'border-blue-500 dark:border-blue-500 shadow-sm shadow-blue-500/10 ring-1 ring-blue-500/20' : 'border-slate-100 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700' }} rounded-2xl cursor-pointer transition-all">
                                                    <input type="checkbox" name="subthemes[]" value="{{ $code }}"
                                                        {{ in_array($code, ($selectedSubthemes ?? [])) ? 'checked' : '' }}
                                                        class="mt-0.5 w-5 h-5 text-blue-600 bg-slate-50 border-slate-200 rounded-md focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-2 dark:bg-slate-900 dark:border-slate-600 transition-all">
                                                    <div class="ml-3">
                                                        <span class="block text-xs font-black text-slate-900 dark:text-white">{{ $code }}</span>
                                                        <span class="block mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-tight">{{ $name }}</span>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="text-[10px] text-slate-400 ml-1">The system will automatically assign matching abstracts to this reviewer based on these selected subthemes.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Form Actions --}}
                        <div class="flex items-center justify-between pt-8 mt-8 border-t border-slate-100 dark:border-slate-700">
                            <a href="{{ route('admin.users') }}" class="text-xs font-black uppercase text-slate-400 hover:text-slate-600 tracking-widest leading-none">Cancel</a>
                            <button type="submit"
                                    class="px-12 py-6 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest rounded-[2rem] shadow-2xl hover:-translate-y-1 transition-all flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
</style>
@endsection
