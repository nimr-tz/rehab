<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Confirm Access - {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
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
                    <span class="text-xl">🔒</span>
                    <span class="text-sm font-semibold">Sensitive Area</span>
                </div>

                <h1 class="text-3xl lg:text-4xl font-black leading-tight mb-4">
                    Identity<br>Confirmation
                </h1>
                <p class="text-brand-100/90 leading-relaxed mb-8 max-w-sm text-lg">
                    You're entering a secure area of the portal. For your protection, please confirm your password before continuing.
                </p>

                <div class="space-y-4">
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <svg class="w-5 h-5 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0012 3c1.268 0 2.39.246 3.411.696m3.666 5.73a10.008 10.008 0 01-4.576 8.351"/></svg>
                        <div>
                            <p class="font-bold text-sm text-white">Advanced Security</p>
                            <p class="text-xs text-brand-200">Session-based validation</p>
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
                    <div class="mb-8 text-center lg:text-left">
                        <h2 class="text-2xl lg:text-3xl font-black text-slate-900 mb-2">Confirm Access</h2>
                        <p class="text-slate-500">This is a secure area. Please confirm your password.</p>
                    </div>

                    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
                        @csrf
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-brand-500 hover:text-brand-600">Forgot?</a>
                                @endif
                            </div>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400 group-focus-within:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input type="password" name="password" id="password" required autofocus
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-4 focus:ring-brand-100 focus:bg-white outline-none transition-all text-slate-900"
                                    placeholder="••••••••">
                            </div>
                            @error('password')<span class="text-xs text-red-500 mt-1 block font-bold">{{ $message }}</span>@enderror
                        </div>

                        <button type="submit" class="w-full py-4 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-brand-100 flex items-center justify-center gap-2 text-lg group">
                            Unlock Content
                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                        <a href="javascript:history.back()" class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-all">
                             Cancel and go back
                        </a>
                    </div>
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
