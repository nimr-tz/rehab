@php
    // Anchors on the home page; full links from any other public page.
    $home = request()->routeIs('home') ? '' : route('home');
    $links = [
        $home.'#about' => 'About',
        $home.'#dates' => 'Key dates',
        $home.'#register' => 'Fees',
        $home.'#topics' => 'Topics',
        route('programme') => 'Programme',
        route('gallery.index') => 'Gallery',
        route('awards.index') => 'Awards',
        $home.'#venue' => 'Venue',
        $home.'#faq' => 'FAQ',
    ];
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-ink-100 bg-white/95 backdrop-blur-md">
    <div class="wrap flex min-h-[76px] items-center justify-between gap-3 sm:gap-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
            <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-10 w-auto sm:h-11">
            <span class="flex flex-col leading-tight">
                <span class="whitespace-nowrap text-[15px] font-bold text-brand-700 sm:text-[17px]">{{ $summit->shortTitle() }}</span>
                <span class="whitespace-nowrap text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $summit->get('organiser') }}</span>
            </span>
        </a>

        <div class="hidden items-center gap-6 text-[15px] font-semibold lg:flex">
            @foreach ($links as $href => $label)
                <a href="{{ $href }}" class="text-ink-800 hover:text-ember-600">{{ $label }}</a>
            @endforeach
        </div>

        <div class="flex shrink-0 items-center gap-1 sm:gap-5">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full bg-brand-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-800 sm:px-5 sm:py-3 sm:text-[15px]">My dashboard</a>
            @else
                <a href="{{ route('login') }}" class="hidden text-[15px] font-semibold text-brand-700 hover:text-brand-800 sm:inline">Log in</a>
                <a href="{{ route('register') }}" class="rounded-full bg-brand-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-800 sm:px-5 sm:py-3 sm:text-[15px]">Register</a>
            @endauth
            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-menu"
                    :aria-label="open ? 'Close menu' : 'Open menu'" aria-label="Open menu"
                    class="grid h-11 w-11 place-items-center rounded-full text-brand-700 hover:bg-brand-50 lg:hidden">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <div id="mobile-menu" x-show="open" x-cloak @click="if ($event.target.closest('a')) open = false"
         class="border-t border-ink-100 bg-white lg:hidden">
        <div class="wrap grid gap-1 py-3 text-base font-semibold">
            @foreach ($links as $href => $label)
                <a href="{{ $href }}" class="rounded-xl px-3 py-2.5 hover:bg-brand-50">{{ $label }}</a>
            @endforeach
            <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 text-brand-700 hover:bg-brand-50">Log in</a>
        </div>
    </div>
</nav>
