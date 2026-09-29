<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <script>
        // CRITICAL: Initialize theme immediately to prevent FOUC
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('conference.short_name') }} {{ config('conference.year') }} - {{ config('conference.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">



    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&family=Playfair+Display:ital,wght@0,400;0,700;1,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        [x-cloak] { display: none !important; }
        @keyframes badge-pulse-subtle {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }
        .badge-pulse {
            animation: badge-pulse-subtle 3s infinite ease-in-out;
        }
    </style>
</head>

@php
    $sidebarData = $sidebarData ?? [];
    $user = auth()->user();
    $sessionRole = session('active_role');
    $primaryRole = $user?->primary_role_name ?? ($user?->role ?? 'author');
    $activeRole = ($user && $sessionRole && $user->hasRole($sessionRole)) ? $sessionRole : $primaryRole;
    $submissionWindowStatus = app(\App\Services\SubmissionWindowService::class)->status();
    $submissionWindowOpen = $submissionWindowStatus['is_open'];
    $submissionWindowOverrideActive = $submissionWindowStatus['override_active'];
    $submissionDeadlineText = $submissionWindowStatus['deadline']->timezone(config('app.timezone'))->format('F d, Y \a\t H:i');
    $submissionOverrideText = $submissionWindowStatus['override_until']
        ? $submissionWindowStatus['override_until']->timezone(config('app.timezone'))->format('F d, Y \a\t H:i')
        : null;

    // Define role-specific classes to ensure JIT scanning and dynamic logic
    $logoBg = match($activeRole) {
        'admin' => 'bg-admin-600 shadow-admin-500/20',
        'scientific_admin' => 'bg-blue-600 shadow-blue-500/20',
        'reviewer' => 'bg-reviewer-600 shadow-reviewer-500/20',
        'finance_officer' => 'bg-teal-600 shadow-teal-500/20',
        'registration_officer' => 'bg-violet-600 shadow-violet-500/20',
        default => 'bg-author-600 shadow-author-500/20',
    };

    $logoText = match($activeRole) {
        'admin' => 'text-admin-600 dark:text-admin-400',
        'scientific_admin' => 'text-blue-600 dark:text-blue-400',
        'reviewer' => 'text-reviewer-600 dark:text-reviewer-400',
        'finance_officer' => 'text-teal-600 dark:text-teal-400',
        'registration_officer' => 'text-violet-600 dark:text-violet-400',
        default => 'text-author-600 dark:text-author-400',
    };

    $sidebarBorder = match($activeRole) {
        'admin' => 'border-admin-100 dark:border-admin-900/50',
        'scientific_admin' => 'border-blue-100 dark:border-blue-900/50',
        'reviewer' => 'border-reviewer-100 dark:border-reviewer-900/50',
        'finance_officer' => 'border-teal-100 dark:border-teal-900/50',
        'registration_officer' => 'border-violet-100 dark:border-violet-900/50',
        default => 'border-author-100 dark:border-author-900/50',
    };

    $roleGradient = match($activeRole) {
        'admin' => 'from-admin-500 via-admin-600 to-indigo-600',
        'scientific_admin' => 'from-blue-500 via-blue-600 to-indigo-600',
        'reviewer' => 'from-reviewer-500 via-reviewer-600 to-indigo-600',
        'finance_officer' => 'from-teal-500 via-teal-600 to-indigo-600',
        'registration_officer' => 'from-violet-500 via-violet-600 to-indigo-600',
        default => 'from-author-500 via-author-600 to-indigo-600',
    };

    // Sidebar chrome and header gradients (keep role-specific, but don't affect other roles' menus).
    $sidebarShellBg = match($activeRole) {
        'admin' => 'bg-gradient-to-b from-white via-white to-admin-50/40 dark:from-slate-900 dark:via-slate-900 dark:to-admin-950/10',
        'scientific_admin' => 'bg-gradient-to-b from-white via-white to-blue-50/30 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/10',
        default => '',
    };

    $roleHeaderGradient = match($activeRole) {
        'admin' => 'from-admin-700 via-admin-600 to-indigo-700',
        'scientific_admin' => 'from-blue-700 via-blue-600 to-indigo-700',
        'reviewer' => 'from-reviewer-700 via-reviewer-600 to-emerald-700',
        'finance_officer' => 'from-teal-700 via-teal-600 to-sky-700',
        'registration_officer' => 'from-violet-700 via-violet-600 to-indigo-700',
        default => 'from-indigo-700 via-indigo-600 to-blue-600',
    };

    $roleHeaderBorder = match($activeRole) {
        'admin' => 'border-admin-500/70',
        'scientific_admin' => 'border-blue-500/70',
        'reviewer' => 'border-reviewer-500/70',
        'finance_officer' => 'border-teal-500/70',
        'registration_officer' => 'border-violet-500/70',
        default => 'border-indigo-500/70',
    };

    $roleHeaderEyebrow = match($activeRole) {
        'admin' => 'text-admin-100',
        'scientific_admin' => 'text-blue-100',
        'reviewer' => 'text-reviewer-100',
        'finance_officer' => 'text-teal-100',
        'registration_officer' => 'text-violet-100',
        default => 'text-indigo-100',
    };
@endphp

<body class="bg-slate-50 dark:bg-slate-950 font-sans antialiased h-full transition-colors duration-300"
    data-role="{{ $activeRole }}">

    <div class="flex min-h-screen h-full" x-data="{ sidebarOpen: false, sidebarCollapsed: false }" x-init="sidebarCollapsed = window.innerWidth < 1440">

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/80 z-40 lg:hidden"></div>

        <!-- Side Navigation -->
        <aside x-bind:class="{
            'translate-x-0': sidebarOpen,
            '-translate-x-full': !sidebarOpen,
            'lg:w-20': sidebarCollapsed,
            'lg:w-80': !sidebarCollapsed
        }"
               class="fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 {{ $sidebarShellBg }} border-r border-slate-200 dark:border-slate-800 shadow-xl lg:static lg:translate-x-0 transition-all duration-300 flex flex-col overflow-hidden">

            <!-- Logo Section -->
            <div class="h-24 flex items-center border-b border-slate-100 dark:border-slate-800" :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-8'">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 flex items-center justify-center flex-shrink-0">
                        <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="Logo" class="w-10 h-10 object-contain" />
                    </div>
                    <div x-show="!sidebarCollapsed" class="lg:block hidden">
                        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            <span class="text-indigo-600">{{ config('conference.short_name') }}</span>
                            <span class="text-slate-400 font-light">{{ config('conference.year') }}</span>
                        </h1>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="ml-auto lg:hidden text-slate-400 hover:text-slate-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation -->
            @php
                // Premium Sidebar Tokens (all roles)
                $navHeaderClass = "px-6 py-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 mt-6";

                $navPillBase = "group relative flex items-center gap-3 px-3 py-2.5 text-[13px] font-semibold rounded-2xl transition-all duration-200 border border-transparent hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900";
                $navPillInactive = "text-slate-700/90 dark:text-slate-200/90 hover:bg-white/70 dark:hover:bg-slate-900/45 hover:shadow-soft";

                $navIconWrapBase = "flex items-center justify-center w-9 h-9 rounded-xl border transition-all duration-200";
                $navIconWrapInactive = "bg-slate-50/80 border-slate-200 text-slate-500 dark:bg-slate-800/70 dark:border-slate-700 dark:text-slate-300 group-hover:bg-white group-hover:text-slate-700 dark:group-hover:bg-slate-800";

                $navChevron = "w-4 h-4 ml-auto opacity-0 group-hover:opacity-60 transition-opacity duration-200 text-slate-400 dark:text-slate-500";

                $navPillActive = match($activeRole) {
                    'admin' => "bg-white/90 dark:bg-slate-900/65 border-admin-200/70 dark:border-admin-800/50 text-admin-900 dark:text-admin-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-admin-600",
                    'scientific_admin' => "bg-white/90 dark:bg-slate-900/65 border-blue-200/70 dark:border-blue-800/50 text-blue-900 dark:text-blue-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-blue-600",
                    'reviewer' => "bg-white/90 dark:bg-slate-900/65 border-reviewer-200/70 dark:border-reviewer-800/50 text-reviewer-900 dark:text-reviewer-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-reviewer-600",
                    'finance_officer' => "bg-white/90 dark:bg-slate-900/65 border-teal-200/70 dark:border-teal-800/50 text-teal-900 dark:text-teal-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-teal-600",
                    'registration_officer' => "bg-white/90 dark:bg-slate-900/65 border-violet-200/70 dark:border-violet-800/50 text-violet-900 dark:text-violet-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-violet-600",
                    default => "bg-white/90 dark:bg-slate-900/65 border-author-200/70 dark:border-author-800/50 text-author-900 dark:text-author-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-author-600",
                };

                $navIconWrapActive = match($activeRole) {
                    'admin' => "bg-admin-50 border-admin-200 text-admin-700 shadow-sm shadow-admin-500/10 dark:bg-admin-900/25 dark:border-admin-800/60 dark:text-admin-200",
                    'scientific_admin' => "bg-blue-50 border-blue-200 text-blue-700 shadow-sm shadow-blue-500/10 dark:bg-blue-900/25 dark:border-blue-800/60 dark:text-blue-200",
                    'reviewer' => "bg-reviewer-50 border-reviewer-200 text-reviewer-700 shadow-sm shadow-reviewer-500/10 dark:bg-reviewer-900/25 dark:border-reviewer-800/60 dark:text-reviewer-200",
                    'finance_officer' => "bg-teal-50 border-teal-200 text-teal-700 shadow-sm shadow-teal-500/10 dark:bg-teal-900/25 dark:border-teal-800/60 dark:text-teal-200",
                    'registration_officer' => "bg-violet-50 border-violet-200 text-violet-700 shadow-sm shadow-violet-500/10 dark:bg-violet-900/25 dark:border-violet-800/60 dark:text-violet-200",
                    default => "bg-author-50 border-author-200 text-author-700 shadow-sm shadow-author-500/10 dark:bg-author-900/25 dark:border-author-800/60 dark:text-author-200",
                };
            @endphp

            {{-- Role switcher — only for people who actually hold more than one
                 role; with a single role there is nothing to switch to. Modelled on
                 the shadcn team switcher: a quiet trigger showing the capacity you
                 are acting in, opening an inline menu rather than a full-screen
                 modal. --}}
            @if($user && $user->roles->count() > 1)
                @php
                    $rsInitials = strtoupper(mb_substr($user->first_name ?? '', 0, 1).mb_substr($user->last_name ?? '', 0, 1)) ?: 'U';
                    $rsCurrent  = $user->roles->firstWhere('name', $activeRole);
                    $rsLabel    = $rsCurrent?->display_name ?: ucwords(str_replace('_', ' ', $activeRole));

                    $rsAccent = match($activeRole) {
                        'admin'                => 'from-indigo-500 to-indigo-700',
                        'scientific_admin'     => 'from-blue-500 to-blue-700',
                        'reviewer'             => 'from-emerald-500 to-emerald-700',
                        'finance_officer'      => 'from-amber-500 to-amber-700',
                        'registration_officer' => 'from-violet-500 to-violet-700',
                        default                => 'from-slate-500 to-slate-700',
                    };
                @endphp

                <div class="px-3 pt-3 pb-1" x-data="{ roleMenu: false }" @keydown.escape.window="roleMenu = false">
                    <div class="relative">
                        <button type="button"
                                @click="roleMenu = !roleMenu"
                                :aria-expanded="roleMenu"
                                class="w-full group flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-left transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900/10 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/70"
                                :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                                title="Switch role">
                            {{-- Initials tile, tinted by the active capacity --}}
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gradient-to-br {{ $rsAccent }} text-[11px] font-semibold tracking-wide text-white shadow-sm">
                                {{ $rsInitials }}
                            </span>

                            <span x-show="!sidebarCollapsed" class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium leading-tight text-slate-900 dark:text-slate-100">
                                    {{ $rsLabel }}
                                </span>
                                <span class="block truncate text-[11px] leading-tight text-slate-500 dark:text-slate-400">
                                    {{ $user->roles->count() }} roles available
                                </span>
                            </span>

                            <svg x-show="!sidebarCollapsed"
                                 class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                                 :class="roleMenu ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Menu --}}
                        <div x-show="roleMenu"
                             x-cloak
                             @click.outside="roleMenu = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
                             class="absolute left-0 right-0 z-50 mt-1.5 origin-top overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-lg shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900"
                             :class="sidebarCollapsed ? 'w-56' : ''">
                            <p class="px-2 py-1.5 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                Switch role
                            </p>

                            @foreach($user->roles as $availableRole)
                                @php
                                    $rsIsCurrent = $availableRole->name === $activeRole;
                                    $rsName = $availableRole->display_name ?: ucwords(str_replace('_', ' ', $availableRole->name));
                                @endphp

                                @if($rsIsCurrent)
                                    <div class="flex items-center gap-2 rounded-md bg-slate-100 px-2 py-2 text-sm font-medium text-slate-900 dark:bg-slate-800 dark:text-slate-100">
                                        <span class="truncate">{{ $rsName }}</span>
                                        <svg class="ml-auto h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                @else
                                    <form action="{{ route('switch-role') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $availableRole->name }}">
                                        <button type="submit"
                                                class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                                            <span class="truncate">{{ $rsName }}</span>
                                        </button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <nav class="flex-1 overflow-y-auto custom-scrollbar pt-6" :class="sidebarCollapsed ? 'px-2' : ''">
                <!-- Navigation Items -->
                @php
                    $user = auth()->user();
                    if ($user) $user = $user->fresh() ?? $user;

                    /*
                     | Proceedings phase — focused navigation.
                     |
                     | The conference and the abstract book are finished; the only work
                     | left is the Conference Proceedings volume. The sidebar is therefore
                     | cut back to what that task needs:
                     |
                     |   authors           Dashboard only (their proceedings entries are
                     |                     listed on it, each linking to its correction form)
                     |   admin / sci-admin Dashboard + Scientific Program (where the
                     |                     proceedings and abstract book are downloaded)
                     |
                     | Every other item is hidden rather than deleted. Set this to false to
                     | bring the full navigation back in one step.
                     */
                    $focusedNav = true;
                @endphp

                {{-- Focused navigation (shadcn-style): quiet, compact, and sized for
                     the handful of pages this phase actually needs. --}}
                @if($focusedNav)
                    @php
                        $fDash = match($activeRole) {
                            'admin', 'scientific_admin' => 'admin.dashboard',
                            'reviewer'                  => 'reviewer.dashboard',
                            'finance_officer'           => 'finance.dashboard',
                            'registration_officer'      => 'registration.dashboard',
                            default                     => 'user.dashboard',
                        };
                        $fIsStaff = in_array($activeRole, ['admin', 'scientific_admin']);

                        $fItem   = 'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors duration-150';
                        $fIdle   = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-100';
                        $fActive = 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-50';

                        $fLinks = [[
                            'route'  => $fDash,
                            'href'   => route($fDash),
                            'label'  => 'Dashboard',
                            'active' => request()->routeIs($fDash) || request()->routeIs('dashboard'),
                            'icon'   => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        ]];

                        if ($fIsStaff) {
                            $fLinks[] = [
                                'href'   => route('admin.conference-program.index'),
                                'label'  => 'Scientific Program',
                                'active' => request()->routeIs('admin.conference-program.*'),
                                'icon'   => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                            ];
                            $fLinks[] = [
                                'href'   => route('admin.proceedings.entries'),
                                'label'  => 'Proceedings Entries',
                                'active' => request()->routeIs('admin.proceedings.*') || request()->routeIs('abstracts.proceedings.*'),
                                'icon'   => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                            ];
                            $fLinks[] = [
                                'href'   => route('admin.emails.index'),
                                'label'  => 'Email Manager',
                                'active' => request()->routeIs('admin.emails.*'),
                                'icon'   => 'M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                            ];
                        }
                    @endphp

                    <div class="px-3 pt-1">
                        <p x-show="!sidebarCollapsed"
                           class="px-3 pb-3 text-[11px] font-medium tracking-wide text-slate-400 dark:text-slate-500">
                            Menu
                        </p>
                        <div class="space-y-1.5">
                            @foreach($fLinks as $fLink)
                                <a href="{{ $fLink['href'] }}"
                                   title="{{ $fLink['label'] }}"
                                   class="{{ $fItem }} {{ $fLink['active'] ? $fActive : $fIdle }}"
                                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fLink['icon'] }}"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">{{ $fLink['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    {{-- 1. Discovery Section --}}
                    <div class="space-y-0.5">

                        @php
                            $dashboardRoute = match($activeRole) {
                                'admin' => 'admin.dashboard',
                                'scientific_admin' => 'admin.dashboard',
                                'reviewer' => 'reviewer.dashboard',
                                'finance_officer' => 'finance.dashboard',
                                'registration_officer' => 'registration.dashboard',
                                'executive' => 'executive.dashboard',
                                default => 'user.dashboard',
                            };
                            $isActive = request()->routeIs($dashboardRoute) || request()->routeIs('dashboard');
                        @endphp

                        @if(!$focusedNav && !in_array($activeRole, ['finance_officer', 'registration_officer']))
                            @php $isActiveDash = request()->routeIs($dashboardRoute) || request()->routeIs('dashboard'); @endphp
                            <a href="{{ route($dashboardRoute) }}" class="mx-3 {{ $navPillBase }} {{ $isActiveDash ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Dashboard">
                                <span class="{{ $navIconWrapBase }} {{ $isActiveDash ? $navIconWrapActive : $navIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="tracking-wide truncate">Dashboard</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif

                        @if(!$focusedNav && in_array($activeRole, ['admin', 'scientific_admin']))
                            @php $isActiveExec = request()->routeIs('executive.dashboard'); @endphp
                            <a href="{{ route('executive.dashboard') }}" class="mx-3 {{ $navPillBase }} {{ $isActiveExec ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Management Dashboard">
                                <span class="{{ $navIconWrapBase }} {{ $isActiveExec ? $navIconWrapActive : $navIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="tracking-wide truncate">Management Dashboard</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif


                        @if(!$focusedNav && !in_array($activeRole, ['admin', 'scientific_admin', 'reviewer', 'finance_officer', 'registration_officer']))
                            {{-- Conference ending — Payments, Chair/Rapporteur applications and Invitation letters hidden --}}

                            @php $isActive = request()->routeIs('certificate.*'); @endphp
                            <a href="{{ route('certificate.index') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Certificates">
                                <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-2.06 3.42 3.42 0 014.438 0c.643.511 1.341.875 1.946 2.06a3.42 3.42 0 003.535 2.152c.813-.083 1.62.247 2.152.883a3.42 3.42 0 010 4.438c-.636.536-.966 1.339-.883 2.152a3.42 3.42 0 002.152 3.535 3.42 3.42 0 010 4.438c-.531.636-1.339.966-2.152.883a3.42 3.42 0 00-3.535 2.152 3.42 3.42 0 01-4.438 0a3.42 3.42 0 00-1.946-2.06a3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-2.06 3.42 3.42 0 010-4.438c.636-.536.966-1.339.883-2.152a3.42 3.42 0 00-2.152-3.535 3.42 3.42 0 010-4.438c.531-.636 1.339-.966 2.152-.883z" />
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Certificates</span>
                                @php $certReleaseAt = \Carbon\Carbon::parse(config('conference.certificates_release_at'), config('conference.timezone')); @endphp
                                @if(now()->lt($certReleaseAt))
                                    <span x-show="!sidebarCollapsed" data-cert-countdown data-release="{{ $certReleaseAt->toIso8601String() }}" class="ml-auto px-1.5 py-0.5 text-[8px] font-black bg-indigo-500 text-white rounded-md tracking-tight badge-pulse tabular-nums whitespace-nowrap">&hellip;</span>
                                @endif
                            </a>
                        @endif
                    </div>

                    {{-- Conference over — Submit Abstract, My Presentations, Rapporteur Report and
                         the Services portals stay hidden. My Abstracts is back: the proceedings
                         phase needs authors to reach their accepted work to correct how it appears
                         in print, and it is the only route to that form. --}}
                    @if(!$focusedNav && !in_array($activeRole, ['admin', 'scientific_admin', 'reviewer', 'finance_officer', 'registration_officer']))
                        @php
                            $isActiveAbstracts = request()->routeIs('abstracts.my')
                                || request()->routeIs('abstracts.show')
                                || request()->routeIs('abstracts.proceedings.*');
                            $correctionsOpen = app(\App\Services\ProceedingsCorrectionService::class)->isOpen();
                            $correctableCount = $correctionsOpen && auth()->check()
                                ? \App\Models\AbstractSubmission::where('user_id', auth()->id())
                                    ->where('status', 'accepted')
                                    ->whereNotNull('conference_code')
                                    ->count()
                                : 0;
                        @endphp
                        <div class="space-y-0.5">
                            <a href="{{ route('abstracts.my') }}" class="mx-3 {{ $navPillBase }} {{ $isActiveAbstracts ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="My Abstracts">
                                <span class="{{ $navIconWrapBase }} {{ $isActiveAbstracts ? $navIconWrapActive : $navIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">My Abstracts</span>
                                @if($correctableCount > 0)
                                    <span x-show="!sidebarCollapsed" class="ml-auto px-1.5 py-0.5 text-[8px] font-black bg-violet-500 text-white rounded-md tracking-tight badge-pulse whitespace-nowrap" title="Proceedings corrections are open">
                                        REVIEW
                                    </span>
                                @else
                                    <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                @endif
                            </a>
                        </div>
                    @endif

                    @if (!$focusedNav && now()->isAfter(\Carbon\Carbon::parse(config('conference.end_date', '2026-06-11'))->startOfDay()))
                        @php $isActive = request()->routeIs('feedback.*'); @endphp
                        <div class="space-y-0.5">
                            <a href="{{ route('feedback.create') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Conference Feedback">
                                <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Conference Feedback</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    @endif

                    {{-- 3. Management Panels (Role Specific) --}}
                @if ($activeRole === 'reviewer')
                    {{-- Review phase closed — all reviewer nav items hidden --}}
                @endif

                {{-- Conference over — Session Lead assignments hidden --}}


                @if (!$focusedNav && in_array($activeRole, ['admin', 'scientific_admin']))
                    @php
                        // Premium flat-list styling for admin/scientific_admin only (no grouping).
                        $panelNavLinkBase = "group relative flex items-center gap-3 px-3 py-2.5 text-[13px] font-semibold rounded-2xl transition-all duration-200 border border-transparent hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900";
                        $panelNavLinkInactive = "text-slate-700/90 dark:text-slate-200/90 hover:bg-white/70 dark:hover:bg-slate-900/45 hover:shadow-soft";

                        $panelNavIconWrapBase = "flex items-center justify-center w-9 h-9 rounded-xl border transition-all duration-200";
                        $panelNavIconWrapInactive = "bg-slate-50/80 border-slate-200 text-slate-500 dark:bg-slate-800/70 dark:border-slate-700 dark:text-slate-300 group-hover:bg-white group-hover:text-slate-700 dark:group-hover:bg-slate-800";

                        $panelNavChevron = "w-4 h-4 ml-auto opacity-0 group-hover:opacity-60 transition-opacity duration-200 text-slate-400 dark:text-slate-500";

                        if ($activeRole === 'admin') {
                            $panelNavLinkActive = "bg-white/90 dark:bg-slate-900/65 border-admin-200/70 dark:border-admin-800/50 text-admin-900 dark:text-admin-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-admin-600";
                            $panelNavIconWrapActive = "bg-admin-50 border-admin-200 text-admin-700 shadow-sm shadow-admin-500/10 dark:bg-admin-900/25 dark:border-admin-800/60 dark:text-admin-200";
                        } else {
                            $panelNavLinkActive = "bg-white/90 dark:bg-slate-900/65 border-blue-200/70 dark:border-blue-800/50 text-blue-900 dark:text-blue-100 shadow-soft before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-7 before:w-1 before:rounded-full before:bg-blue-600";
                            $panelNavIconWrapActive = "bg-blue-50 border-blue-200 text-blue-700 shadow-sm shadow-blue-500/10 dark:bg-blue-900/25 dark:border-blue-800/60 dark:text-blue-200";
                        }
                    @endphp
                    <div class="space-y-1 pb-8">
                        <div class="mx-3 rounded-3xl bg-gradient-to-b from-white/70 to-slate-50/40 dark:from-slate-900/40 dark:to-slate-950/20 border border-slate-200/70 dark:border-slate-800/60 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] backdrop-blur-sm p-2">
                        @unless($focusedNav)
                        @if($activeRole === 'scientific_admin')
                        {{-- Flat list for scientific admin (no categories), simplified neutral styling --}}
                            @php $isActive = request()->routeIs('admin.session-role-applications.*'); @endphp
                            <a href="{{ route('admin.session-role-applications.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Chair and Rapporteur Applications">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-4M9 20H4v-2a4 4 0 015-4m4-6a4 4 0 11-8 0 4 4 0 018 0zm6 4a3 3 0 100-6 3 3 0 000 6z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Chair/Rapporteur Applications</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.students.*'); @endphp
                            <a href="{{ route('admin.students.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Student Verification">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Student Verification</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.conference-program.*'); @endphp
                            <a href="{{ route('admin.conference-program.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Conference Program">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 00-2 2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Scientific Program</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.reports.*'); @endphp
                            <a href="{{ route('admin.reports.builder') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Report Engine">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Report Engine</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.users*'); @endphp
                            <a href="{{ route('admin.users') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="User Management">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">User Management</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.feedback.*'); @endphp
                            <a href="{{ route('admin.feedback.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Participant Feedback">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L3.577 10.1c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Participant Feedback</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif

                        {{-- Admin flat list: system and operational tools only --}}
                        @if($activeRole === 'admin')
                            @php $isActive = request()->routeIs('admin.speakers.*'); @endphp
                            <a href="{{ route('admin.speakers.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Speakers">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 11-3-3 3 3 0 013 3z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Speakers</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.abstracts.presentations'); @endphp
                            <a href="{{ route('admin.abstracts.presentations') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Presentation Repository">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Presentation Repository</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.announcements.*'); @endphp
                            <a href="{{ route('admin.announcements.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Announcements">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Announcements</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.emails.*'); @endphp
                            <a href="{{ route('admin.emails.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Email Manager">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Email Manager</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.invitations.*'); @endphp
                            <a href="{{ route('admin.invitations.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Invitations">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Invitations</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            @php $isActive = request()->routeIs('admin.feedback.*'); @endphp
                            <a href="{{ route('admin.feedback.index') }}" class="{{ $panelNavLinkBase }} {{ $isActive ? $panelNavLinkActive : $panelNavLinkInactive }}" :class="sidebarCollapsed ? 'justify-center px-0' : ''" title="Participant Feedback">
                                <span class="{{ $panelNavIconWrapBase }} {{ $isActive ? $panelNavIconWrapActive : $panelNavIconWrapInactive }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L3.577 10.1c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </span>
                                <span x-show="!sidebarCollapsed" class="truncate">Participant Feedback</span>
                                <svg x-show="!sidebarCollapsed" class="{{ $panelNavChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif
                        @endunless
                        </div>
                    </div>
                @endif

                @if (!$focusedNav && $user && $user->hasRole('chief_rapporteur'))
                    <div class="space-y-0.5">
                        @php $isActive = request()->routeIs('chief-rapporteur.*'); @endphp
                        <a href="{{ route('chief-rapporteur.index') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Chief Rapporteur">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Chief Rapporteur</span>
                            <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                @endif

                @if ($activeRole === 'finance_officer')
                    <div class="space-y-0.5">

                        @php $isActive = request()->routeIs('finance.dashboard'); @endphp
                        <a href="{{ route('finance.dashboard') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Dashboard">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Dashboard</span>
                            <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        @php $isActive = request()->routeIs('finance.payments') || request()->routeIs('finance.show'); @endphp
                        <a href="{{ route('finance.payments') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Payments">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">All Payments</span>
                            <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        @php $isActive = request()->query('status') === 'submitted'; @endphp
                        <a href="{{ route('finance.payments', ['status' => 'submitted']) }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Pending Verification">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Pending</span>
                            @php $pendingFinanceCount = data_get($sidebarData, 'pendingFinanceCount', 0); @endphp
                            @if($pendingFinanceCount > 0)
                                <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-black shadow-lg shadow-amber-500/20" x-show="!sidebarCollapsed">
                                    {{ $pendingFinanceCount }}
                                </span>
                            @endif
                        </a>

                        @php $isActive = request()->routeIs('finance.sponsors*'); @endphp
                        <a href="{{ route('finance.sponsors') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Sponsor Payments">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Sponsors</span>
                            @php $pendingSponsorFinanceCount = data_get($sidebarData, 'pendingSponsorFinanceCount', 0); @endphp
                            @if($pendingSponsorFinanceCount > 0)
                                <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-cyan-500 text-slate-950 shadow-lg shadow-cyan-500/20" x-show="!sidebarCollapsed">
                                    {{ $pendingSponsorFinanceCount }}
                                </span>
                            @else
                                <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            @endif
                        </a>
                    </div>
                @endif

                @if ($activeRole === 'registration_officer')
                    <div class="space-y-0.5">

                        @php $isActive = request()->routeIs('registration.dashboard') || request()->routeIs('registration.attendees'); @endphp
                        <a href="{{ route('registration.dashboard') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Reception">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Reception</span>
                            <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        @php $isActive = request()->routeIs('registration.onsite.*'); @endphp
                        <a href="{{ route('registration.onsite.index') }}" class="mx-3 {{ $navPillBase }} {{ $isActive ? $navPillActive : $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Walk-In Visitors">
                            <span class="{{ $navIconWrapBase }} {{ $isActive ? $navIconWrapActive : $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Walk-Ins</span>
                            <svg x-show="!sidebarCollapsed" class="{{ $navChevron }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        <a href="{{ route('registration.export') }}" class="mx-3 {{ $navPillBase }} {{ $navPillInactive }}" :class="sidebarCollapsed ? '!mx-2 justify-center' : ''" title="Export">
                            <span class="{{ $navIconWrapBase }} {{ $navIconWrapInactive }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate">Export</span>
                        </a>
                    </div>
                @endif
                </div>
            </nav>

            {{-- Sidebar footer (Settings / Sign Out) removed — both remain in the header avatar dropdown --}}
        </aside>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col relative min-w-0 overflow-hidden">

            @php
                $isProfile = request()->routeIs('profile.*');
            @endphp
            <!-- Header -->
            <header class="h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-50 sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
                <!-- Top accent line based on role -->
                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r {{ $roleGradient }}"></div>

                <!-- Left: Mobile Menu & Title -->
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden transition-colors text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" title="Open Sidebar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden lg:flex transition-colors text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" title="Toggle Sidebar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    <!-- Breadcrumbs / Page Title (Simplified) -->
                    <h2 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">
                        @if (request()->routeIs('dashboard') || request()->routeIs('user.dashboard'))
                            Dashboard
                        @elseif(request()->routeIs('abstracts.*'))
                            Abstracts
                        @elseif(request()->routeIs('reviewer.dashboard'))
                            Reviewer Panel
                        @elseif(request()->routeIs('reviewer.preferences.*'))
                            Expert Onboarding
                        @elseif(request()->routeIs('presentations.*'))
                            Scientific
                        @elseif(request()->routeIs('profile.*'))
                            Profile
                        @elseif(request()->routeIs('admin.advanced-analytics.*'))
                            Analytics
                        @else
                            {{ config('conference.short_name') }} {{ config('conference.year') }}
                        @endif
                    </h2>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="p-2 transition-colors text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="p-2 transition-colors relative text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">
                            <span class="sr-only">Notifications</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if(data_get($sidebarData, 'unreadNotificationsCount', 0) > 0)
                                <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-400 rounded-full ring-2 ring-white dark:ring-slate-950 animate-pulse"></span>
                            @endif
                        </button>

                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-100 dark:border-gray-700 py-1 z-[100] max-h-96 overflow-y-auto">

                            <div class="px-4 py-3 border-b border-slate-100 dark:border-gray-700 flex justify-between items-center">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h3>
                                @if($user && $user->notifications()->unread()->count() > 0)
                                    <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Mark all read</button>
                                    </form>
                                @endif
                            </div>

                            @forelse(($user?->notifications()->unread()->latest()->take(5)->get() ?? collect()) as $notification)
                                <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-gray-700/50 border-b border-slate-50 dark:border-gray-700/50 last:border-0 group">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            @if($notification->type === 'review_assignment')
                                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </div>
                                            @elseif($notification->type === 'status_change')
                                                <div class="w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 bg-slate-100 text-slate-600 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $notification->title ?? 'Notification' }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ Str::limit($notification->message ?? '', 60) }}</p>
                                            <div class="flex items-center justify-between mt-1">
                                                <p class="text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                                @if($notification->action_url)
                                                    <a href="{{ $notification->action_url }}" class="text-[10px] mobile-touch-target font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">View</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                    <p class="text-sm">No new notifications</p>
                                </div>
                            @endforelse

                            <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-xs text-center text-blue-600 hover:text-blue-800 dark:text-blue-400 border-t border-slate-100 dark:border-gray-700 font-medium">
                                View all notifications
                            </a>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                            @if ($user?->profile_image)
                                <img src="{{ asset('storage/' . $user->profile_image) }}" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-gray-600" alt="Profile">
                            @else
                                <div class="w-8 h-8 rounded-full {{ $activeRole === 'admin' ? 'bg-admin-100 dark:bg-admin-900/30 text-admin-600 dark:text-admin-400' : ($activeRole === 'reviewer' ? 'bg-reviewer-100 dark:bg-reviewer-900/30 text-reviewer-600 dark:text-reviewer-400' : 'bg-author-100 dark:bg-author-900/30 text-author-600 dark:text-author-400') }} flex items-center justify-center font-bold text-xs">
                                    {{ $user?->initials ?? 'AJ' }}
                                </div>
                            @endif
                        </button>

                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-100 dark:border-gray-700 py-1 z-[100]">

                            <div class="px-4 py-2 border-b border-slate-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $user?->first_name ?? 'Guest' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $user?->email ?? '' }}</p>
                            </div>

                            @if($user)
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-700/50">Profile Settings</a>
                            @endif


                            @if($user)
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">Sign Out</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 font-semibold">Sign In</a>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <!-- Toast Notifications (Top Center, Larger) -->
            <div id="toast-container" class="fixed top-6 left-1/2 transform -translate-x-1/2 z-[100] space-y-4 min-w-[320px] max-w-md"></div>
            @if (session('success'))
                <script>
                    window.__toasts = window.__toasts || [];
                    window.__toasts.push({ type: 'success', message: @json(session('success')) });
                </script>
            @endif
            @if (session('error'))
                <script>
                    window.__toasts = window.__toasts || [];
                    window.__toasts.push({ type: 'error', message: @json(session('error')) });
                </script>
            @endif
            @if (session('warning'))
                <script>
                    window.__toasts = window.__toasts || [];
                    window.__toasts.push({ type: 'warning', message: @json(session('warning')) });
                </script>
            @endif
            @if ($errors->any())
                <script>
                    window.__toasts = window.__toasts || [];
                    @foreach ($errors->all() as $error)
                        window.__toasts.push({ type: 'error', message: @json($error) });
                    @endforeach
                </script>
            @endif
            @if (session('info'))
                <script>
                    window.__toasts = window.__toasts || [];
                    window.__toasts.push({ type: 'info', message: @json(session('info')) });
                </script>
            @endif

            <!-- Submission Deadline Banner -->
            @php
                $showDeadlineBanner = !in_array($activeRole, ['admin', 'scientific_admin', 'reviewer', 'finance_officer', 'registration_officer']);
                $publicSubmissionWindowOpen = $submissionWindowOpen && !$submissionWindowOverrideActive;
                $topAnnouncements = collect();

                if($user && $showDeadlineBanner) {
                    $topAnnouncements->push([
                        'label' => 'Feedback',
                        'message' => 'The conference has ended — share your ' . config('conference.short_name') . ' ' . config('conference.year') . ' experience.',
                        'url' => route('feedback.create'),
                        'tone' => 'indigo',
                    ]);
                }
            @endphp

            @if($showDeadlineBanner && $publicSubmissionWindowOpen)
                <div class="bg-gradient-to-r from-[#fffbeb] via-[#fef3c7] via-[#fde68a] via-[#fef3c7] to-[#fffbeb] text-amber-900 py-1.5 overflow-hidden whitespace-nowrap relative z-30 shadow-sm border-b border-amber-200">
                    <div class="flex animate-marquee-slow items-center gap-12">
                        <span class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.2em] text-amber-800">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Submission window open
                        </span>
                        <span class="text-xs font-semibold text-amber-700/80">Share your abstract while the portal is still open.</span>
                        <span class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.2em] text-amber-800">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Submission window open
                        </span>
                        <span class="text-xs font-semibold text-amber-700/80">Share your abstract while the portal is still open.</span>
                    </div>
                </div>
            @elseif($showDeadlineBanner && $topAnnouncements->isNotEmpty())
                <div class="bg-gradient-to-r from-indigo-50 via-white to-sky-50 text-slate-900 py-1.5 overflow-hidden whitespace-nowrap relative z-30 shadow-sm border-b border-indigo-100">
                    <div class="flex animate-marquee-slow items-center gap-12">
                        @foreach($topAnnouncements->concat($topAnnouncements) as $announcement)
                            @php
                                $announcementTone = [
                                    'indigo' => ['dot' => 'bg-indigo-500', 'text' => 'text-indigo-700', 'hover' => 'hover:text-indigo-700'],
                                    'sky' => ['dot' => 'bg-sky-500', 'text' => 'text-sky-700', 'hover' => 'hover:text-sky-700'],
                                    'teal' => ['dot' => 'bg-teal-500', 'text' => 'text-teal-700', 'hover' => 'hover:text-teal-700'],
                                ][$announcement['tone'] ?? 'indigo'];
                            @endphp
                            <a href="{{ $announcement['url'] }}" class="inline-flex items-center gap-3 text-xs sm:text-sm font-semibold text-slate-600 {{ $announcementTone['hover'] }} transition-colors">
                                <span class="w-2 h-2 rounded-full {{ $announcementTone['dot'] }}"></span>
                                <span class="font-black uppercase tracking-[0.18em] {{ $announcementTone['text'] }}">{{ $announcement['label'] }}</span>
                                <span>{{ $announcement['message'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <style>
                @keyframes marquee-slow {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                .animate-marquee-slow {
                    display: inline-flex;
                    animation: marquee-slow 30s linear infinite;
                    width: max-content;
                }
                .animate-marquee-slow:hover {
                    animation-play-state: paused;
                }
            </style>

            <!-- Page Content -->
            <div class="flex-1 overflow-auto relative z-0 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
                @yield('content')
            </div>

            {{-- Move Universal Account Section HERE, before final script --}}
            @if(!in_array($activeRole, ['admin', 'reviewer', 'finance_officer', 'registration_officer']))
                {{-- This part is already in the nav for non-admin roles usually, but let's make it a dedicated block if needed. --}}
                {{-- Actually, the account section is already INSIDE the <nav>. I just need to make sure it's at the bottom of the nav. --}}
            @endif
        </main>

    <!-- Final Account Section for ALL Roles at the bottom of Sidebar -->
    <script>
        // Ensure the Account section is at the bottom of the nav
        document.addEventListener('DOMContentLoaded', function() {
            const nav = document.querySelector('nav');
            const accountSection = document.getElementById('sidebar-account-section');
            if (nav && accountSection) {
                nav.appendChild(accountSection);
            }
        });
    </script>

    <!-- Role Switching Portal -->
    </div>

    <script>
        // Dark Mode Toggle Function
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            if (newTheme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
            localStorage.setItem('theme', newTheme);
        }

        // System theme detection (optional enhancement)
        function detectSystemTheme() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            return 'light';
        }

        // Initialize theme on page load (if no saved preference, use system preference)
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = detectSystemTheme();
            const theme = savedTheme || systemTheme;

            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Listen for system theme changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                    if (!localStorage.getItem('theme')) {
                        if (e.matches) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    }
                });
            }
        });

        // Toast system
        (function() {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const queue = window.__toasts || [];

            function showToast({ type = 'success', message = '' }) {
                const colors = {
                    success: { bg: 'bg-emerald-600', icon: 'M5 13l4 4L19 7' },
                    error: { bg: 'bg-rose-600', icon: 'M6 18L18 6M6 6l12 12' },
                    warning: { bg: 'bg-amber-500', icon: 'M12 9v2m0 4h.01M4.938 19h14.124c1.54 0 2.502-1.667 1.732-2.5L13.732 5c-.77-.833-1.732-.833-2.464 0L3.206 16.5c-.77.833.192 2.5 1.732 2.5z' }
                };

                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center shadow-lg rounded-xl overflow-hidden text-white animate-fade-in-up';

                const color = colors[type] || colors.success;
                wrapper.innerHTML = `
                    <div class="${color.bg} p-3 flex items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${color.icon}" />
                        </svg>
                    </div>
                    <div class="bg-gray-900/90 dark:bg-gray-800/90 backdrop-blur px-4 py-3 text-sm">${message}</div>
                    <button class="px-3 py-3 bg-gray-900/90 hover:bg-gray-800/90 text-white">✕</button>
                `;

                const closeBtn = wrapper.querySelector('button');
                function removeToast() {
                    wrapper.style.opacity = '0';
                    wrapper.style.transform = 'translateY(-6px)';
                    setTimeout(() => wrapper.remove(), 150);
                }
                closeBtn.addEventListener('click', removeToast);

                container.appendChild(wrapper);
                setTimeout(removeToast, 4000);
            }

            queue.forEach(showToast);
            window.__toasts = { push: showToast };
        })();
    </script>

    {{-- Compact certificate-release countdown badges (sidebar, dashboard) --}}
    <script>
        (function() {
            const badges = document.querySelectorAll('[data-cert-countdown]');
            if (!badges.length) return;
            const pad = n => String(n).padStart(2, '0');
            const tick = () => {
                let allDone = true;
                badges.forEach(el => {
                    const diff = new Date(el.dataset.release).getTime() - Date.now();
                    if (diff <= 0) {
                        el.textContent = 'Ready';
                        return;
                    }
                    allDone = false;
                    const h = Math.floor(diff / 3600000);
                    el.textContent = h + 'h ' + pad(Math.floor(diff / 60000) % 60) + 'm ' + pad(Math.floor(diff / 1000) % 60) + 's';
                });
                if (allDone) clearInterval(timer);
            };
            const timer = setInterval(tick, 1000);
            tick();
        })();
    </script>

    @stack('scripts')
    @stack('end-scripts')
    

</body>
</html>
