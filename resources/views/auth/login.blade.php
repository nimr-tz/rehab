<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 min-h-screen font-[Outfit]">
    <div class="min-h-screen flex">
        <!-- Left Side: Welcome Back Panel -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-800 via-brand-600 to-brand-500 flex-col items-center justify-between relative overflow-hidden py-16 px-12">
            <div id="particles-js" class="absolute inset-0 z-0"></div>

            <!-- Big centred logo + welcome text -->
            <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center text-white gap-8">
                <div class="space-y-2">
                    <h2 class="text-2xl font-black tracking-wide">{{ config('conference.short_name') }} {{ config('conference.year') }}</h2>
                    <p class="text-brand-100/70 text-sm">{{ config('conference.edition') }} Edition</p>
                    <div class="flex flex-col items-center gap-2 pt-2">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/10">
                            <svg class="w-4 h-4 text-brand-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-bold">{{ config('conference.display_dates') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/10">
                            <svg class="w-4 h-4 text-brand-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            <span class="text-sm font-bold">{{ config('conference.venue') }}, {{ config('conference.city') }}</span>
                        </div>
                    </div>
                </div>
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-56 w-auto drop-shadow-2xl">

                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full mb-5">
                        <span class="text-lg">👋</span>
                        <span class="text-sm font-semibold">Good to see you!</span>
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-black leading-tight mb-3">
                        Welcome<br>Back
                    </h1>
                    <p class="text-brand-100/80 text-base leading-relaxed max-w-xs mx-auto">
                        Sign in to access your dashboard, manage your submissions, and stay updated.
                    </p>
                </div>
            </div>

            <!-- Bottom: conference info + copyright -->
            <div class="relative z-10 text-center text-white w-full">
                <p class="text-brand-100/50 text-xs mb-3">&copy; {{ config('conference.year') }} {{ config('conference.host_short') }}. All rights reserved.</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white">
            <div class="w-full h-full flex flex-col justify-center px-10 lg:px-20 xl:px-32 py-16">
                <div class="lg:hidden flex items-center justify-center gap-3 mb-10">
                    <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Animated Logo" class="h-10 w-auto drop-shadow-md">
                    <span class="font-bold text-lg text-slate-900">{{ config('conference.short_name') }} <span class="text-brand-500">{{ config('conference.year') }}</span></span>
                </div>

                <div class="w-full">
                    <div class="mb-8">
                        <h2 class="text-2xl lg:text-3xl font-black text-slate-900 mb-2">Sign In</h2>
                        <p class="text-slate-500">Enter your credentials to continue</p>
                    </div>

                    <x-auth-session-status class="mb-6" :status="session('status')" />

                    @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl">
                        <p class="text-sm font-semibold text-red-600">Please check your credentials and try again.</p>
                    </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-2 focus:ring-brand-100 focus:bg-white outline-none transition-all text-slate-900" placeholder="yourname@email.com">
                            @error('email')<span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-2 focus:ring-brand-100 focus:bg-white outline-none transition-all text-slate-900 pr-12" placeholder="••••••••">
                                <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"><svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                                <span class="ml-2 text-sm text-slate-600">Remember me</span>
                            </label>
                            @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-500 hover:text-brand-600">Forgot password?</a>
                            @endif
                        </div>
                        <button type="submit" class="w-full py-3.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-brand-100 flex items-center justify-center gap-2">
                            Sign In
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>

                    <div class="my-8 flex items-center">
                        <div class="flex-1 border-t border-slate-200"></div>
                        <span class="px-4 text-sm text-slate-400">New here?</span>
                        <div class="flex-1 border-t border-slate-200"></div>
                    </div>

                    <a href="{{ route('register') }}" class="w-full py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Create an Account
                    </a>
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
    particlesJS('particles-js', {
        particles: {
            number: {
                value: 80,
                density: {
                    enable: true,
                    value_area: 700
                }
            },
            color: {
                value: '#ffffff'
            },
            shape: {
                type: 'circle',
                stroke: {
                    width: 0,
                    color: '#000000'
                }
            },
            opacity: {
                value: 0.6,
                random: true,
                anim: {
                    enable: true,
                    speed: 1.5,
                    opacity_min: 0.2,
                    sync: false
                }
            },
            size: {
                value: 5,
                random: true,
                anim: {
                    enable: true,
                    speed: 3,
                    size_min: 1,
                    sync: false
                }
            },
            line_linked: {
                enable: true,
                distance: 130,
                color: '#ffffff',
                opacity: 0.4,
                width: 1.5
            },
            move: {
                enable: true,
                speed: 3,
                direction: 'none',
                random: true,
                straight: false,
                out_mode: 'out',
                bounce: false,
                attract: {
                    enable: true,
                    rotateX: 600,
                    rotateY: 1200
                }
            }
        },
        interactivity: {
            detect_on: 'canvas',
            events: {
                onhover: {
                    enable: true,
                    mode: 'grab'
                },
                onclick: {
                    enable: true,
                    mode: 'push'
                },
                resize: true
            },
            modes: {
                grab: {
                    distance: 180,
                    line_linked: {
                        opacity: 0.8
                    }
                },
                push: {
                    particles_nb: 5
                },
                repulse: {
                    distance: 200,
                    duration: 0.4
                }
            }
        },
        retina_detect: true
    });

    function togglePassword(){
        const input=document.getElementById('password');
        const icon=document.getElementById('eyeIcon');
        if(input.type==='password'){
            input.type='text';
            icon.innerHTML='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
        }else{
            input.type='password';
            icon.innerHTML='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
        }
    }
    </script>
</body>
</html>
