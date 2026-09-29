@extends('layouts.app')

@section('title', 'Profile Settings | ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-950 py-12 relative">
    {{-- Background Decorative Elements --}}
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        {{-- Modern Header --}}
        <div class="mb-12">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
                Account <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-blue-600">Settings</span>
            </h1>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Manage your conference profile, credentials, and engagement preferences.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            {{-- Navigation Sidebar --}}
            <div class="lg:col-span-4">
                <div class="sticky top-24 space-y-6">
                    {{-- Profile Snapshot Card --}}
                    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-xl border border-slate-200/50 dark:border-slate-800/50 text-center overflow-hidden relative">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-600 to-blue-600"></div>
                        <div class="relative inline-block mb-6">
                            @if(Auth::user()->profile_image)
                                <img src="{{ asset('storage/' . Auth::user()->profile_image) }}"
                                     class="h-28 w-28 rounded-3xl object-cover ring-4 ring-slate-100 dark:ring-slate-800 shadow-lg"
                                     alt="Profile">
                            @else
                                <div class="h-28 w-28 rounded-3xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-500 text-3xl font-bold shadow-inner">
                                    {{ Auth::user()->initials }}
                                </div>
                            @endif
                            <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-500 rounded-xl border-4 border-white dark:border-slate-900 flex items-center justify-center text-white shadow-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1 leading-tight">{{ Auth::user()->title }} {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h2>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">{{ Auth::user()->affiliation ?: config('conference.short_name') . ' Delegate' }}</p>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center justify-center gap-2">
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l3 7-1 4h2l1-3 4-4h4" />
                                </svg>
                                {{ Auth::user()->phone ?: 'No phone on file' }}
                            </span>
                        </p>

                        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-4">
                            <div class="text-left">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1.5">User ID</p>
                                <p class="text-xs font-black text-slate-900 dark:text-white">#{{ str_pad(Auth::id(), 5, '0', STR_PAD_LEFT) }}</p>
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1.5">Account Type</p>
                                <p class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase">{{ Auth::user()->role }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Navigation Tabs --}}
                    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-4 shadow-sm border border-slate-200/50 dark:border-slate-800/50">
                        <nav class="space-y-2">
                            <button onclick="scrollToSection('personal')" class="nav-btn group w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer relative z-20" data-section="personal">
                                <span class="bg-blue-50 dark:bg-blue-500/10 text-blue-600 p-2.5 rounded-xl transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </span>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400 transition-colors text-left">Basic Information</span>
                            </button>
                            <button onclick="scrollToSection('security')" class="nav-btn group w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer relative z-20" data-section="security">
                                <span class="bg-purple-50 dark:bg-purple-500/10 text-purple-600 p-2.5 rounded-xl transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15V3m0 12l-4-4m4 4l4-4"/></svg>
                                </span>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400 transition-colors text-left">Security & Password</span>
                            </button>
                            <button onclick="scrollToSection('journey')" class="nav-btn group w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer relative z-20" data-section="journey">
                                <span class="bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 p-2.5 rounded-xl transition-all shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </span>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400 transition-colors text-left">Engagement Preferences</span>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            {{-- Forms Column --}}
            <div class="lg:col-span-8 space-y-10">
                {{-- Personal Identity --}}
                <div id="personal" class="scroll-mt-24 p-8 md:p-12 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Basic Information</h2>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Basic identification and professional affiliation.</p>
                        </div>
                    </div>
                    @include('profile.partials.update-profile-information-form')
                </div>

                {{-- Access Control --}}
                <div id="security" class="scroll-mt-24 p-8 md:p-12 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 bg-purple-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3m0 12l-4-4m4 4l4-4"/></svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Security Settings</h2>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Keep your account secure with a fresh password.</p>
                        </div>
                    </div>
                    @include('profile.partials.update-password-form')
                </div>

                {{-- Event Roadmap --}}
                <div id="journey" class="scroll-mt-24 p-8 md:p-12 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Engagement Preferences</h2>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Select how you intend to engage with {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>
                        </div>
                    </div>
                    @include('profile.partials.update-journey-intents-form')
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function scrollToSection(id) {
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
            updateActiveNav(id);
        }
    }

    function updateActiveNav(id) {
        document.querySelectorAll('.nav-btn').forEach(btn => {
            if (btn.dataset.section === id) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Observer removed to avoid highlighting
    window.addEventListener('scroll', () => {
        // No highlighting logic needed
    });
</script>

<style>
    .nav-btn {
        background-color: transparent !important;
    }
    .nav-btn span {
        transition: none !important;
    }
</style>
@endsection
