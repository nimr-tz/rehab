<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 min-h-screen font-[Outfit]">
    <div class="min-h-screen flex">
        <!-- Left Side: Visual Panel -->
        <div class="hidden lg:flex lg:w-2/5 bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 p-10 flex-col justify-between fixed left-0 top-0 bottom-0 overflow-hidden">
            <div id="particles-js" class="absolute inset-0 z-0"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4">
                    <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-14 w-auto drop-shadow-md">
                    <div>
                        <span class="text-white font-bold text-xl">{{ config('conference.short_name') }} {{ config('conference.year') }}</span>
                        <p class="text-brand-200 text-xs font-semibold">{{ config('conference.edition') }} Edition</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-white">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full mb-6">
                    <span class="text-xl">🔑</span>
                    <span class="text-sm font-semibold">Account Recovery</span>
                </div>

                <h1 class="text-3xl lg:text-4xl font-black leading-tight mb-4">
                    Password<br>Forgotten?
                </h1>
                <p class="text-brand-100/90 leading-relaxed mb-8 max-w-sm text-lg">
                    Don't worry! It happens. Just enter your email address and we'll send you a link to reset your password and get you back into the portal.
                </p>

                <div class="space-y-4">
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <svg class="w-5 h-5 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <div>
                            <p class="font-bold text-sm text-white">Secure Link</p>
                            <p class="text-xs text-brand-200">Sent directly to your inbox</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10">
                <p class="text-white/50 text-xs font-bold uppercase tracking-widest leading-relaxed">
                    © {{ config('conference.year') }} {{ config('conference.host_short') }}. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Right Side: Content Area -->
        <div class="w-full lg:w-3/5 lg:ml-[40%] min-h-screen flex flex-col items-center justify-center p-6">
            <!-- Mobile Header -->
            <div class="lg:hidden fixed top-0 left-0 right-0 flex items-center justify-center gap-3 py-6 bg-white border-b z-50">
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-10 w-auto drop-shadow-md">
                <span class="font-bold text-lg text-slate-900">{{ config('conference.short_name') }} <span class="text-brand-500">{{ config('conference.year') }}</span></span>
            </div>

            <div class="w-full max-w-md">
                <div class="bg-white rounded-3xl shadow-xl p-8 lg:p-10 border border-slate-100">
                    <div class="mb-8">
                        <h2 class="text-2xl lg:text-3xl font-black text-slate-900 mb-2">Forgot Password</h2>
                        <p class="text-slate-500">Provide your email to receive a reset link</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-700 text-sm font-bold flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400 group-focus-within:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/>
                                    </svg>
                                </div>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 focus:bg-white outline-none transition-all text-slate-900"
                                    placeholder="yourname@email.com">
                            </div>
                            @error('email')<span class="text-xs text-red-500 mt-1 block font-bold">{{ $message }}</span>@enderror
                        </div>

                        <button type="submit" class="w-full py-4 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-brand-100 flex items-center justify-center gap-2 text-lg group">
                            Send Reset Link
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-center">
                        <a href="{{ route('login') }}" class="text-sm font-bold text-slate-400 hover:text-brand-500 flex items-center gap-2 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Sign In
                        </a>
                    </div>
                </div>

                <div class="mt-8 text-center lg:hidden">
                    <p class="text-slate-500 text-sm font-medium">© {{ config('conference.year') }} {{ config('conference.host_short') }}</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
    particlesJS('particles-js', {
        particles: {
            number: { value: 60, density: { enable: true, value_area: 800 } },
            color: { value: '#ffffff' },
            shape: { type: 'circle' },
            opacity: { value: 0.4, random: true, anim: { enable: true, speed: 1.5, opacity_min: 0.1, sync: false } },
            size: { value: 4, random: true, anim: { enable: true, speed: 2, size_min: 0.1, sync: false } },
            line_linked: { enable: true, distance: 150, color: '#ffffff', opacity: 0.2, width: 1 },
            move: { enable: true, speed: 2, direction: 'none', random: true, straight: false, out_mode: 'out', bounce: false }
        },
        interactivity: {
            events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
            modes: { grab: { distance: 140, line_linked: { opacity: 0.5 } }, push: { particles_nb: 4 } }
        },
        retina_detect: true
    });
    </script>
</body>
</html>
