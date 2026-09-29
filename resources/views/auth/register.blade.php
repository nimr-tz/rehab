<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <style>
        .step { display: none; }
        .step.active { display: flex; flex-direction: column; }
        .step-indicator { transition: all 0.3s ease; }
        .step-indicator.completed { background: #10b981; border-color: #10b981; }
        .step-indicator.active { background: #3a86ff; border-color: #3a86ff; transform: scale(1.1); }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .animate-shake { animation: shake 0.4s ease-in-out; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-[Outfit]">
    @php($countries = config('countries', []))

    <div class="min-h-screen flex">
        <!-- Left Side: Join Panel (Fixed) -->
        <div class="hidden lg:flex lg:w-2/5 bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 flex-col items-center justify-between fixed left-0 top-0 bottom-0 overflow-hidden py-16 px-10">
            <div id="particles-js" class="absolute inset-0 z-0"></div>

            <!-- Big centred logo + welcome text -->
            <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center text-white gap-8">
                <div>
                    <h2 class="text-2xl font-black tracking-wide">{{ config('conference.short_name') }} {{ config('conference.year') }}</h2>
                    <p class="text-brand-100/70 text-sm">{{ config('conference.edition') }} Edition</p>
                </div>
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-56 w-auto drop-shadow-2xl">

                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full mb-5">
                        <span class="text-lg">✨</span>
                        <span class="text-sm font-semibold">Get Started</span>
                    </div>
                    <h1 class="text-4xl font-black leading-tight mb-3">
                        Join<br>Us
                    </h1>
                    <p class="text-brand-100/80 text-sm leading-relaxed max-w-xs mx-auto">
                        Create an account to submit abstracts, register, and connect with researchers.
                    </p>
                </div>
            </div>

            <!-- Bottom: steps + copyright -->
            <div class="relative z-10 text-center text-white w-full">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-4">Your Journey</p>

                <div class="space-y-2 mb-6" id="step-progress">
                    <div class="flex items-center gap-3 step-item justify-center" data-step="1">
                        <div class="w-7 h-7 rounded-full border-2 border-white/50 flex items-center justify-center text-xs font-bold step-indicator active">1</div>
                        <p class="text-sm font-medium">Personal Information</p>
                    </div>
                    <div class="flex items-center gap-3 step-item justify-center" data-step="2">
                        <div class="w-7 h-7 rounded-full border-2 border-white/30 flex items-center justify-center text-xs font-bold step-indicator">2</div>
                        <p class="text-sm font-medium text-white/60">Affiliation & Contact</p>
                    </div>
                    <div class="flex items-center gap-3 step-item justify-center" data-step="3">
                        <div class="w-7 h-7 rounded-full border-2 border-white/30 flex items-center justify-center text-xs font-bold step-indicator">3</div>
                        <p class="text-sm font-medium text-white/60">Registration Category</p>
                    </div>
                    <div class="flex items-center gap-3 step-item justify-center" data-step="4">
                        <div class="w-7 h-7 rounded-full border-2 border-white/30 flex items-center justify-center text-xs font-bold step-indicator">4</div>
                        <p class="text-sm font-medium text-white/60">Journey Selection</p>
                    </div>
                    <div class="flex items-center gap-3 step-item justify-center" data-step="5">
                        <div class="w-7 h-7 rounded-full border-2 border-white/30 flex items-center justify-center text-xs font-bold step-indicator">5</div>
                        <p class="text-sm font-medium text-white/60">Create Password</p>
                    </div>
                </div>

                <p class="text-brand-100/50 text-xs mb-3">&copy; {{ config('conference.year') }} {{ config('conference.host_short') }}. All rights reserved.</p>
            </div>
        </div>

        <!-- Right Side: Registration Form -->
        <div class="w-full lg:w-3/5 lg:ml-[40%] min-h-screen flex flex-col">
            <!-- Mobile Header -->
            <div class="lg:hidden flex items-center justify-center gap-3 py-6 bg-white border-b">
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-10 w-auto drop-shadow-md">
                <span class="font-bold text-lg text-slate-900">{{ config('conference.short_name') }} <span class="text-brand-500">{{ config('conference.year') }}</span></span>
            </div>

            <div class="flex-1 flex flex-col px-4 sm:px-8 lg:px-24 py-8 sm:py-16 w-full">
                <!-- Header -->
                <div class="mb-10">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900">Create Account</h2>
                        <span class="text-[10px] sm:text-sm text-slate-400 font-medium bg-slate-100 px-3 py-1 rounded-full whitespace-nowrap">Step <span id="current-step">1</span>/5</span>
                    </div>
                    <p class="text-base sm:text-lg text-slate-500" id="step-description">Let's start with your personal details</p>
                    <div class="mt-6 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div id="progress-bar" class="h-full bg-brand-500 rounded-full transition-all duration-500" style="width: 20%"></div>
                    </div>
                </div>

                @if ($errors->any())
                <div class="mb-8 p-5 bg-red-50 border border-red-100 rounded-2xl">
                    <p class="text-sm font-semibold text-red-600 mb-2">Please fix the following:</p>
                    <ul class="text-sm text-red-500 space-y-1">@foreach ($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
                </div>
                @endif

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" id="registration-form" class="flex-1 flex flex-col" novalidate>
                    @csrf
                    <input type="hidden" name="current_step" id="current-step-input" value="1">
                    <div id="steps-container" class="flex-1 flex flex-col">

                    <!-- Step 1: Personal Information -->
                    <div class="step active flex-1" data-step="1">

                        <div class="flex-1 flex flex-col justify-center space-y-12 mb-12">
                            <div>
                                <label class="block text-base font-bold text-slate-800 mb-3">What's your title?</label>
                                <div class="flex flex-wrap gap-3">
                                    @foreach(['Dr.', 'Prof.', 'Mr.', 'Mrs.', 'Ms.'] as $t)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="title" value="{{ $t }}" {{ old('title') == $t ? 'checked' : '' }} required class="peer hidden">
                                        <span class="inline-block px-6 py-3 rounded-xl border-2 border-slate-200 text-slate-600 font-semibold peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-600 hover:border-slate-300 transition-all">{{ $t }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-lg sm:text-xl font-bold text-slate-800 mb-3 sm:mb-4">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full px-4 py-3 sm:px-6 sm:py-5 bg-white border-2 border-slate-200 rounded-xl sm:rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-lg sm:text-xl transition-all shadow-sm" placeholder="Enter your first name">
                            </div>
                            <div>
                                <label class="block text-lg sm:text-xl font-bold text-slate-800 mb-3 sm:mb-4">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full px-4 py-3 sm:px-6 sm:py-5 bg-white border-2 border-slate-200 rounded-xl sm:rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-lg sm:text-xl transition-all shadow-sm" placeholder="Enter your last name">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Affiliation & Contact -->
                    <div class="step flex-1" data-step="2">
                        <div class="flex-1 flex flex-col justify-center space-y-12 mb-12">
                            <div>
                                <label class="block text-lg sm:text-xl font-bold text-slate-800 mb-3 sm:mb-4">Affiliation</label>
                                <input type="text" name="affiliation" value="{{ old('affiliation') }}" required class="w-full px-4 py-3 sm:px-6 sm:py-5 bg-white border-2 border-slate-200 rounded-xl sm:rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-lg sm:text-xl transition-all shadow-sm" placeholder="Your institution or organization">
                                <p class="text-sm sm:text-base text-slate-400 mt-2 sm:mt-3 font-medium">Please use the full name, avoid abbreviations</p>
                            </div>
                            <div>
                                <label class="block text-lg sm:text-xl font-bold text-slate-800 mb-3 sm:mb-4">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-3 sm:px-6 sm:py-5 bg-white border-2 border-slate-200 rounded-xl sm:rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-lg sm:text-xl transition-all shadow-sm" placeholder="Enter your email address">
                            </div>
                            <div>
                                <label class="block text-xl font-bold text-slate-800 mb-4">Country</label>
                                <select name="country" id="country-select" required class="w-full px-6 py-5 bg-white border-2 border-slate-200 rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-xl transition-all shadow-sm">
                                    <option value="">Select your country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country['name'] }}" data-iso="{{ $country['iso'] }}" data-code="{{ $country['code'] }}" data-digits="{{ $country['digits'] }}" {{ old('country') == $country['name'] ? 'selected' : '' }}>{{ $country['name'] }}</option>
                                    @endforeach
                                    <option value="Other" data-iso="" data-code="" data-digits="12" {{ old('country') == 'Other' ? 'selected' : '' }}>Other / Not Listed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-lg sm:text-xl font-bold text-slate-800 mb-3 sm:mb-4">Phone Number</label>
                                <div class="flex gap-2 sm:gap-4">
                                    <div class="w-32 sm:w-40 flex-shrink-0">
                                        <div id="country-code-display" class="w-full px-2 sm:px-4 py-3 sm:py-5 bg-slate-50 border-2 border-slate-200 rounded-xl sm:rounded-2xl text-lg sm:text-xl text-center font-bold text-slate-600 shadow-sm flex items-center justify-center gap-1 sm:gap-2 h-[54px] sm:h-[74px]">
                                            <span id="display-flag"></span>
                                            <span id="display-code"></span>
                                        </div>
                                        <input type="hidden" name="country_code" id="country-code-val">
                                    </div>
                                    <div class="flex-1">
                                        <input type="tel" name="phone" id="phone-input" value="{{ old('phone') }}" required class="w-full px-4 py-3 sm:px-6 sm:py-5 bg-white border-2 border-slate-200 rounded-xl sm:rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-lg sm:text-xl transition-all shadow-sm" placeholder="Enter phone number">
                                    </div>
                                </div>
                                <p class="text-sm mt-3 flex items-center justify-between font-medium">
                                    <span id="phone-hint" class="text-slate-400">Select a country first</span>
                                    <span id="phone-counter" class="text-slate-400">0/10 digits</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Registration Category -->
                    <div class="step flex-1" data-step="3">
                        <div class="flex-1 flex flex-col justify-center space-y-12 mb-12">
                            <div>
                                <label class="block text-xl font-bold text-slate-800 mb-2">Registration category</label>
                                <p class="text-base text-slate-400 font-medium mb-6">Choose the category that best describes you.</p>

                                <div class="grid grid-cols-2 gap-4">
                                    {{-- Participant (East Africa) --}}
                                    <label class="cursor-pointer block">
                                        <input type="radio" name="registration_category" value="professional_local" {{ old('registration_category') == 'professional_local' ? 'checked' : '' }} required class="peer hidden">
                                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 bg-white transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/50 hover:border-slate-300 hover:shadow-sm text-center">
                                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center mb-4 shadow-lg shadow-slate-200">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <p class="font-bold text-slate-800 text-base">Participant</p>
                                            <p class="text-xs text-slate-400 mt-1">East Africa</p>
                                            {{-- Selected checkmark --}}
                                            <div class="absolute top-3 right-3 w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-all scale-75 peer-checked:scale-100">
                                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            </div>
                                        </div>
                                    </label>

                                    {{-- Participant (International) --}}
                                    <label class="cursor-pointer block">
                                        <input type="radio" name="registration_category" value="professional_international" {{ old('registration_category') == 'professional_international' ? 'checked' : '' }} required class="peer hidden">
                                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 bg-white transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/50 hover:border-slate-300 hover:shadow-sm text-center">
                                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mb-4 shadow-lg shadow-blue-100">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <p class="font-bold text-slate-800 text-base">Participant</p>
                                            <p class="text-xs text-slate-400 mt-1">Other Countries</p>
                                            <div class="absolute top-3 right-3 w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-all scale-75 peer-checked:scale-100">
                                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            </div>
                                        </div>
                                    </label>

                                    {{-- Student (East Africa) --}}
                                    <label class="cursor-pointer block">
                                        <input type="radio" name="registration_category" value="student_local" {{ old('registration_category') == 'student_local' ? 'checked' : '' }} required class="peer hidden">
                                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 bg-white transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/50 hover:border-slate-300 hover:shadow-sm text-center">
                                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center mb-4 shadow-lg shadow-amber-100">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            </div>
                                            <p class="font-bold text-slate-800 text-base">Student</p>
                                            <p class="text-xs text-slate-400 mt-1">East Africa</p>
                                            <div class="absolute top-3 right-3 w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-all scale-75 peer-checked:scale-100">
                                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            </div>
                                        </div>
                                    </label>

                                    {{-- Student (International) --}}
                                    <label class="cursor-pointer block">
                                        <input type="radio" name="registration_category" value="student_international" {{ old('registration_category') == 'student_international' ? 'checked' : '' }} required class="peer hidden">
                                        <div class="relative p-6 rounded-2xl border-2 border-slate-200 bg-white transition-all peer-checked:border-brand-500 peer-checked:bg-brand-50/50 hover:border-slate-300 hover:shadow-sm text-center">
                                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center mb-4 shadow-lg shadow-teal-100">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            </div>
                                            <p class="font-bold text-slate-800 text-base">Student</p>
                                            <p class="text-xs text-slate-400 mt-1">Other Countries</p>
                                            <div class="absolute top-3 right-3 w-6 h-6 rounded-full bg-brand-500 flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-all scale-75 peer-checked:scale-100">
                                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            </div>
                                        </div>
                                    </label>

                                </div>
                            </div>
                            <!-- Student Verification Section (Hidden by default) -->
                            <div id="student-doc-wrapper" class="hidden p-6 bg-slate-50 rounded-2xl border-2 border-dashed border-brand-300">
                                <input type="hidden" name="student_status" id="student_status" value="no">

                                <h4 class="text-base font-bold text-brand-700 mb-2">Student Verification Required</h4>
                                <p class="text-sm text-slate-500 mb-4">Please upload a valid student ID. This is required to process your discounted registration rate.</p>

                                <div id="student-doc-section">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Upload Student ID <span class="text-red-500">*</span></label>
                                    <input type="file" name="student_document" class="w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-brand-500 file:text-white hover:file:bg-brand-600 file:cursor-pointer transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Journey Selection -->
                    <div class="step flex-1" data-step="4">
                        <div class="flex-1 flex flex-col justify-center space-y-6 mb-12">

                            {{-- Heading --}}
                            <div>
                                <label class="block text-xl font-bold text-slate-800 mb-2">Your conference participation</label>
                                <p class="text-base text-slate-400 font-medium">Everyone attends the full conference. Select any additional roles below only if they apply to you.</p>
                            </div>

                            {{-- Conference Attendee — always included --}}
                            <input type="hidden" name="intent_attendee" value="1">
                            <div class="px-6 py-5 rounded-2xl bg-emerald-50/70 border-2 border-emerald-200 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">Conference Attendee</p>
                                        <p class="text-sm text-slate-500">All sessions, keynotes, networking, and workshops</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-100 border border-emerald-300 px-3 py-1 rounded-full flex-shrink-0">Included</span>
                            </div>

                            {{-- Optional additional roles --}}
                            <div class="pt-2">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Additional roles (optional)</p>

                                <div class="space-y-3">
                                    {{-- Presenter --}}
                                    <label class="cursor-pointer block">
                                        <input type="checkbox" name="intent_presenter" value="1" class="peer hidden" checked>
                                        <div class="px-6 py-4 rounded-2xl border-2 border-slate-200 bg-white transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 hover:border-slate-300 flex items-center justify-between gap-4">
                                            <div>
                                                <p class="font-bold text-slate-800">Scientific Presenter</p>
                                                <p class="text-sm text-slate-400">Submit and present an abstract (oral or poster)</p>
                                            </div>
                                        </div>
                                    </label>

                                    {{-- Group Coordinator --}}
                                    <label class="cursor-pointer block">
                                        <input type="checkbox" name="intent_group_leader" id="intent_group_leader" value="1" class="peer hidden">
                                        <div class="px-6 py-4 rounded-2xl border-2 border-slate-200 bg-white transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 hover:border-slate-300 flex items-center justify-between gap-4">
                                            <div>
                                                <p class="font-bold text-slate-800">Group Coordinator</p>
                                                <p class="text-sm text-slate-400">Register and manage a team of 3+ attendees</p>
                                            </div>
                                        </div>
                                    </label>

                                </div>
                            </div>

                            {{-- Group info (shown when Group Coordinator is checked) --}}
                            <div id="group-reg-info" class="hidden">
                                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
                                    <p class="font-semibold mb-1">Group registration note</p>
                                    <p class="text-amber-700">You'll be able to add team members and generate a single invoice from your dashboard. Presenters in your group still need to register individually.</p>
                                </div>
                            </div>

                            <p class="text-sm text-slate-400">You can change these later in your profile settings.</p>
                        </div>
                    </div>

                    <!-- Step 5: Password -->
                    <div class="step flex-1" data-step="5">
                        <div class="flex-1 flex flex-col justify-center space-y-12 mb-12">
                            <div class="text-center mb-4">
                                <div class="w-20 h-20 mx-auto bg-brand-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800">Secure your account</h3>
                                <p class="text-slate-500">Create a strong password to protect your account</p>
                            </div>
                            <div>
                                <label class="block text-xl font-bold text-slate-800 mb-4">Password</label>
                                <input type="password" name="password" id="password_input" required class="w-full px-6 py-5 bg-white border-2 border-slate-200 rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-xl transition-all shadow-sm" placeholder="Create a strong password">
                                <ul class="mt-3 space-y-1">
                                    <li id="rule_length" class="text-sm text-red-500 font-medium flex items-center gap-2 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        At least 8 characters
                                    </li>
                                    <li id="rule_case" class="text-sm text-red-500 font-medium flex items-center gap-2 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Contains uppercase & lowercase letters
                                    </li>
                                    <li id="rule_number" class="text-sm text-red-500 font-medium flex items-center gap-2 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Contains at least one number
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <label class="block text-xl font-bold text-slate-800 mb-4">Confirm Password</label>
                                <input type="password" name="password_confirmation" required class="w-full px-6 py-5 bg-white border-2 border-slate-200 rounded-2xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 outline-none text-xl transition-all shadow-sm" placeholder="Type your password again">
                            </div>
                        </div>
                    </div>
                    </div> <!-- End of steps-container -->

                    <!-- Navigation -->
                    <div class="mt-auto pt-6 sm:pt-10 border-t-2 border-slate-100 flex items-center justify-between relative z-50 gap-4">
                        <button type="button" id="prev-btn" style="display: none;" class="px-6 py-3 sm:px-10 sm:py-5 border-2 border-slate-200 text-slate-600 font-bold rounded-xl sm:rounded-2xl hover:bg-slate-50 transition-all flex items-center gap-2 text-lg sm:text-xl">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </button>
                        <button type="button" id="next-btn" class="ml-auto px-8 py-3 sm:px-12 sm:py-5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl sm:rounded-2xl transition-all shadow-xl shadow-brand-200 flex items-center gap-3 text-lg sm:text-xl">
                            Continue
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                        <button type="button" id="submit-btn" style="display: none;" class="ml-auto px-8 py-3 sm:px-12 sm:py-5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl sm:rounded-2xl transition-all shadow-xl shadow-brand-200 flex items-center gap-3 text-lg sm:text-xl">
                            Create Account
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-slate-500">Already have an account? <a href="{{ route('login') }}" class="font-bold text-brand-500 hover:text-brand-600">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
    particlesJS('particles-js', {
        particles: {
            number: { value: 80, density: { enable: true, value_area: 700 } },
            color: { value: '#ffffff' },
            shape: { type: 'circle' },
            opacity: { value: 0.6, random: true, anim: { enable: true, speed: 1.5, opacity_min: 0.2, sync: false } },
            size: { value: 5, random: true, anim: { enable: true, speed: 3, size_min: 1, sync: false } },
            line_linked: { enable: true, distance: 130, color: '#ffffff', opacity: 0.4, width: 1.5 },
            move: { enable: true, speed: 3, direction: 'none', random: true, straight: false, out_mode: 'out', attract: { enable: true, rotateX: 600, rotateY: 1200 } }
        },
        interactivity: {
            events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
            modes: { grab: { distance: 180, line_linked: { opacity: 0.8 } }, push: { particles_nb: 5 } }
        },
        retina_detect: true
    });

    const steps = document.querySelectorAll('.step');
    const stepItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const submitBtn = document.getElementById('submit-btn');
    const currentStepEl = document.getElementById('current-step');
    const progressBar = document.getElementById('progress-bar');
    const stepDescription = document.getElementById('step-description');

    const descriptions = ["Let's start with your personal details", "Tell us about your affiliation and contact", "Select your registration category", "Customize your {{ config('conference.short_name') }} {{ config('conference.year') }} experience", "Secure your account with a password"];
    let currentStep = 1;

    function updateStep(step) {
        currentStep = parseInt(step); // Sync the internal variable
        steps.forEach(s => s.classList.remove('active'));
        document.querySelector(`.step[data-step="${step}"]`).classList.add('active');

        stepItems.forEach((item, index) => {
            const indicator = item.querySelector('.step-indicator');
            const text = item.querySelector('p');
            indicator.classList.remove('active', 'completed');
            text.classList.remove('text-white', 'text-white/60');

            if (index + 1 < step) {
                indicator.classList.add('completed');
                indicator.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                text.classList.add('text-white');
            } else if (index + 1 === step) {
                indicator.classList.add('active');
                indicator.textContent = step;
                text.classList.add('text-white');
            } else {
                indicator.textContent = index + 1;
                text.classList.add('text-white/60');
            }
        });

        currentStepEl.textContent = step;
        document.getElementById('current-step-input').value = step;
        stepDescription.textContent = descriptions[step - 1];
        progressBar.style.width = `${step * 20}%`;

        prevBtn.style.display = step === 1 ? 'none' : 'flex';
        nextBtn.style.display = step === 5 ? 'none' : 'flex';
        submitBtn.style.display = step === 5 ? 'flex' : 'none';
    }

    function validateStep(step) {
        console.log("Validating step:", step);
        if (step === 4) return true;
        const currentStepEl = document.querySelector(`.step[data-step="${step}"]`);
        if (!currentStepEl) return true;

        const inputs = currentStepEl.querySelectorAll('input[required], select[required]');
        let valid = true;
        let firstInvalid = null;

        inputs.forEach(input => {
            let isInputValid = true;
            if (input.type === 'radio') {
                const name = input.name;
                const container = input.closest('div');
                const checked = currentStepEl.querySelector(`input[name="${name}"]:checked`);
                if (!checked) {
                    isInputValid = false;
                    if (container) container.classList.add('ring-2', 'ring-red-300', 'rounded-2xl', 'p-2');
                } else {
                    if (container) container.classList.remove('ring-2', 'ring-red-300');
                }
            } else {
                if (!input.value || !input.checkValidity()) {
                    isInputValid = false;
                    input.classList.add('border-red-500', 'bg-red-50');
                } else {
                    input.classList.remove('border-red-500', 'bg-red-50');
                }
            }

            if (!isInputValid) {
                valid = false;
                if (!firstInvalid) firstInvalid = input;
                console.warn("Validation failed for field:", input.name || input.id);
            }
        });

        if (!valid) {
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus({ preventScroll: true });
            }
            // Add a temporary shake effect to current section
            currentStepEl.classList.add('animate-shake');
            setTimeout(() => currentStepEl.classList.remove('animate-shake'), 500);
        }

        return valid;
    }

    nextBtn.addEventListener('click', () => {
        if (validateStep(currentStep) && currentStep < 5) {
            currentStep++;
            updateStep(currentStep);
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStep(currentStep);
        }
    });

    // Handle manual submission to bypass browser hidden-field validation issues
    submitBtn.addEventListener('click', (e) => {
        e.preventDefault();

        // Ensure we are on the final step
        if (currentStep !== 5) return;

        if (validateStep(currentStep)) {
            // Provide immediate feedback
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating Account...';

            // Submit form
            document.getElementById('registration-form').submit();
        } else {
            // If validation failed, re-enable button
            submitBtn.disabled = false;
        }
    });



    // Auto-detect student status based on category (New Logic)
    const docWrapper = document.getElementById('student-doc-wrapper');
    const studentStatusInput = document.getElementById('student_status');
    const fileInput = document.querySelector('input[name="student_document"]');

    document.querySelectorAll('input[name="registration_category"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const isStudent = this.value.includes('student');

            if (isStudent) {
                // Determine status as YES
                studentStatusInput.value = 'yes';

                // Show upload section
                docWrapper.classList.remove('hidden');

                // Make file required
                fileInput.required = true;

                // Optional: Scroll to it
                docWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                studentStatusInput.value = 'no';
                docWrapper.classList.add('hidden');
                fileInput.required = false;
            }
        });
    });

    // Phone number with country code
    const countrySelect = document.getElementById('country-select');
    const displayFlag = document.getElementById('display-flag');
    const displayCode = document.getElementById('display-code');
    const countryCodeVal = document.getElementById('country-code-val');
    const phoneInput = document.getElementById('phone-input');
    const phoneCounter = document.getElementById('phone-counter');
    const phoneHint = document.getElementById('phone-hint');

    // Group Registration Info Logic
    const groupLeaderCheckbox = document.getElementById('intent_group_leader');
    const groupRegInfo = document.getElementById('group-reg-info');

    if (groupLeaderCheckbox) {
        groupLeaderCheckbox.addEventListener('change', function() {
            if (this.checked) {
                groupRegInfo.classList.remove('hidden');
                groupRegInfo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                groupRegInfo.classList.add('hidden');
            }
        });
    }

    let expectedDigits = 0;

    function updateCountryCode() {
        const selected = countrySelect.options[countrySelect.selectedIndex];
        const code = selected.dataset.code || '';
        const iso = selected.dataset.iso || '';
        const flag = selected.dataset.flag || '';
        expectedDigits = parseInt(selected.dataset.digits) || 0;

        if (iso) {
            displayFlag.innerHTML = `<img src="https://flagcdn.com/w40/${iso.toLowerCase()}.png" class="h-5 w-auto object-cover rounded-sm" alt="${flag}" onerror="this.remove()">`;
        } else {
            displayFlag.textContent = flag;
        }

        displayCode.textContent = code;
        countryCodeVal.value = code;

        if (code && expectedDigits > 0) {
            phoneHint.textContent = `Enter ${expectedDigits} digits (local number)`;
            phoneInput.placeholder = '7XX XXX XXX';
            phoneInput.setAttribute('maxlength', expectedDigits + 2);
        } else if (code) {
            phoneHint.textContent = 'Enter local phone number';
            phoneInput.placeholder = 'Phone number';
            phoneInput.removeAttribute('maxlength');
        } else if (countrySelect.value === 'Other') {
            phoneHint.textContent = 'Enter full phone number with country code';
            phoneInput.placeholder = '+XXX XXX XXX XXX';
            expectedDigits = 10;
        } else {
            phoneHint.textContent = 'Select a country first';
            phoneInput.placeholder = 'Enter phone number';
            displayCode.textContent = '+';
        }

        updatePhoneCounter();
    }

    function updatePhoneCounter() {
        const digits = phoneInput.value.replace(/\D/g, '').length;
        phoneCounter.textContent = expectedDigits > 0 ? digits + '/' + expectedDigits + ' digits' : digits + ' digits';

        if (expectedDigits > 0 && digits === expectedDigits) {
            phoneCounter.classList.remove('text-slate-400', 'text-red-500');
            phoneCounter.classList.add('text-green-600');
            phoneInput.classList.remove('border-red-300');
            phoneInput.classList.add('border-green-400');
        } else if (expectedDigits > 0 && digits > 0) {
            phoneCounter.classList.remove('text-slate-400', 'text-green-600');
            phoneCounter.classList.add('text-red-500');
            phoneInput.classList.remove('border-green-400');
            phoneInput.classList.add('border-red-300');
        } else if (digits > 0) {
            phoneCounter.classList.remove('text-red-500', 'text-green-600');
            phoneCounter.classList.add('text-slate-400');
            phoneInput.classList.remove('border-red-300', 'border-green-400');
        } else {
            phoneCounter.classList.remove('text-red-500', 'text-green-600');
            phoneCounter.classList.add('text-slate-400');
            phoneInput.classList.remove('border-red-300', 'border-green-400');
        }
    }

    countrySelect.addEventListener('change', updateCountryCode);
    phoneInput.addEventListener('input', updatePhoneCounter);

    // Password Validation
    const passwordInput = document.getElementById('password_input');
    const ruleLength = document.getElementById('rule_length');
    const ruleCase = document.getElementById('rule_case');
    const ruleNumber = document.getElementById('rule_number');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;

            // Length Check
            if (val.length >= 8) {
                ruleLength.classList.remove('text-red-500');
                ruleLength.classList.add('text-emerald-600', 'font-bold');
            } else {
                ruleLength.classList.add('text-red-500');
                ruleLength.classList.remove('text-emerald-600', 'font-bold');
            }

            // Case Check (Upper & Lower)
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) {
                ruleCase.classList.remove('text-red-500');
                ruleCase.classList.add('text-emerald-600', 'font-bold');
            } else {
                ruleCase.classList.add('text-red-500');
                ruleCase.classList.remove('text-emerald-600', 'font-bold');
            }

            // Number Check
            if (/\d/.test(val)) {
                ruleNumber.classList.remove('text-red-500');
                ruleNumber.classList.add('text-emerald-600', 'font-bold');
            } else {
                ruleNumber.classList.add('text-red-500');
                ruleNumber.classList.remove('text-emerald-600', 'font-bold');
            }
        });
    }

    // Initialize on page load
    updateCountryCode();
    updateStep(1); // Ensure buttons are properly displayed

    @if ($errors->any())
        // Keep the wizard on the current step if there are errors, or default to 1
        updateStep({{ old('current_step', 1) }});
    @endif
    </script>
</body>
</html>
