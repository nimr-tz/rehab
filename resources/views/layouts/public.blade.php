<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('conference.short_name') . ' ' . config('conference.year')) - {{ config('conference.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="font-sans antialiased bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200 h-full">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border-b border-slate-200/50 dark:border-slate-800/50" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-3 group">
                    <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}" class="h-10 w-auto">
                    <div class="hidden sm:block">
                        <span class="block text-lg font-bold text-slate-900 dark:text-white">{{ config('conference.short_name') }} {{ config('conference.year') }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ config('conference.name') }}</span>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ url('/') }}#about" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">About</a>
                    <a href="{{ route('speakers.public') }}" class="px-4 py-2 text-sm font-medium {{ request()->routeIs('speakers.public') ? 'text-brand-600 dark:text-brand-400 bg-slate-50 dark:bg-slate-800' : 'text-slate-600 dark:text-slate-300' }} hover:text-brand-600 dark:hover:text-brand-400 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">Speakers</a>
                    <a href="{{ route('conference-program.view') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">Program</a>
                    <a href="{{ url('/') }}#venue" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">Venue</a>
                </div>

                <!-- Right Side -->
                <div class="flex items-center gap-3">
                    <button onclick="toggleTheme()" class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-brand-600 transition">Log in</a>
                        <a href="{{ route('register') }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <!-- Logo & Info -->
                <div class="flex items-center gap-3">
                    <div class="bg-white p-1.5 rounded-lg">
                        <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}" class="h-8 w-auto">
                    </div>
                    <div>
                        <span class="block text-white font-bold">{{ config('conference.short_name') }} {{ config('conference.year') }}</span>
                        <span class="block text-xs text-slate-500">{{ config('conference.name') }}</span>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-white font-semibold text-sm mb-3">Contact</h3>
                    <a href="mailto:{{ config('conference.contact_email') }}" class="text-slate-400 hover:text-brand-400 transition text-sm">
                        Email: {{ config('conference.contact_email') }}
                    </a>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-white font-semibold text-sm mb-3">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="{{ url('/') }}#about" class="block text-slate-400 hover:text-brand-400 transition text-sm">About</a>
                        <a href="{{ route('speakers.public') }}" class="block text-slate-400 hover:text-brand-400 transition text-sm">Speakers</a>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-8 text-center text-sm">
                &copy; {{ config('conference.year') }} {{ config('conference.host_short') }}. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
</body>
</html>
