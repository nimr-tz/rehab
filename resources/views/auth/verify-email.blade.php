<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Email - {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
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
                    <span class="text-xl">📧</span>
                    <span class="text-sm font-semibold">Verification Required</span>
                </div>

                <h1 class="text-3xl lg:text-4xl font-black leading-tight mb-4">
                    Almost<br>There!
                </h1>
                <p class="text-brand-100/90 leading-relaxed mb-8 max-w-sm text-lg">
                    We just need to verify your email address before you can access your dashboard and start your journey.
                </p>

                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-medium">Check your inbox</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5m-5-5l-5-5"/></svg>
                        </div>
                        <p class="text-sm font-medium">Click the activation link</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm font-medium">Access your account</p>
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
        <div class="w-full lg:w-3/5 lg:ml-[40%] min-h-screen flex flex-col items-center justify-center">
            <!-- Mobile Header -->
            <div class="lg:hidden fixed top-0 left-0 right-0 flex items-center justify-center gap-3 py-6 bg-white border-b z-50">
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-10 w-auto drop-shadow-md">
                <span class="font-bold text-lg text-slate-900">{{ config('conference.short_name') }} <span class="text-brand-500">{{ config('conference.year') }}</span></span>
            </div>

            <div class="w-full max-w-xl px-8 lg:px-12 py-16 flex flex-col items-center text-center">
                <!-- Icon -->
                <div class="mb-8 w-24 h-24 bg-brand-100 rounded-[2.5rem] flex items-center justify-center animate-bounce-subtle">
                    <svg class="w-12 h-12 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>

                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4">Verify Your Email</h2>
                <p class="text-lg text-slate-500 mb-8 leading-relaxed">
                    Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
                </p>

                <!-- Recipient Info -->
                <div class="w-full mb-8 p-6 bg-slate-50 rounded-3xl border-2 border-slate-100 flex flex-col items-center group transition-all hover:border-brand-200">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Awaiting verification for</span>
                    <span class="text-xl font-bold text-slate-700 break-all">{{ Auth::user()->email }}</span>
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="w-full mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-700 font-bold flex items-center justify-center gap-2 animate-fade-in">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        A new verification link has been sent!
                    </div>
                @endif

                <div class="w-full space-y-4">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="w-full py-5 bg-brand-500 hover:bg-brand-600 text-white font-black rounded-2xl transition-all shadow-xl shadow-brand-100 flex items-center justify-center gap-3 text-lg group">
                            Resend Verification Email
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </form>

                    <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-400 hover:text-rose-500 font-bold transition-all text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Log Out
                            </button>
                        </form>
                        <a href="{{ url('/#contact') }}" class="text-brand-500 hover:underline font-bold text-sm">Need Help?</a>
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
            detect_on: 'canvas',
            events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
            modes: { grab: { distance: 140, line_linked: { opacity: 0.5 } }, push: { particles_nb: 4 } }
        },
        retina_detect: true
    });
    </script>
</body>
</html>
