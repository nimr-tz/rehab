<x-layouts.public class="min-h-screen bg-canvas" :title="'Sign in · '.$summit->get('organiser').' Events Portal'">
    <x-auth.backdrop />

    <header class="relative z-10 flex items-center justify-between px-5 py-5 sm:px-10">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 lg:invisible">
            <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-9 w-auto">
            <span class="leading-tight">
                <span class="block text-sm font-bold text-ink-900">{{ $summit->get('organiser') }}</span>
                <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700">Events Portal</span>
            </span>
        </a>
        <p class="text-sm text-ink-500">
            <span class="hidden sm:inline">Need help?</span>
            <a href="mailto:{{ $summit->get('contact_email') }}" class="font-semibold text-brand-700 hover:text-brand-800">Contact support</a>
        </p>
    </header>

    <main class="relative z-10 mx-auto grid max-w-7xl items-center gap-10 px-5 pb-14 sm:px-10 lg:min-h-[calc(100vh-88px)] lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:pb-10">

        {{-- Brand panel --}}
        <section class="hidden flex-col items-center text-center lg:flex">
            <a href="{{ route('home') }}" class="relative block aspect-square w-full max-w-[26rem]"
               x-data="brandLogo('{{ asset('images/brand/logo-2027-animated.webp') }}')">
                <div class="absolute inset-[12%] rounded-full bg-white/70 blur-2xl"></div>
                <img src="{{ asset('images/brand/logo-2027-still.webp') }}" alt="{{ $summit->get('organiser') }} {{ $summit->get('year') }} Events Portal"
                     :class="{ 'opacity-0': stillHidden }" class="relative h-full w-full object-contain transition-opacity duration-300">
                <img x-ref="anim" alt="" aria-hidden="true"
                     :class="animated ? 'opacity-100' : 'opacity-0'" class="absolute inset-0 h-full w-full object-contain transition-opacity duration-300">
            </a>

            <p class="mt-8 flex items-center gap-5 text-lg font-semibold tracking-[0.32em] text-brand-800">
                PEOPLE
                <span class="h-2 w-2 rounded-full bg-ember-500"></span>
                CARE
                <span class="h-2 w-2 rounded-full bg-olive-700"></span>
                TOGETHER
            </p>
            <p class="mt-3 text-sm tracking-[0.3em] text-ink-500">A healthier tomorrow, together.</p>
        </section>

        {{-- Sign-in card --}}
        <section class="mx-auto w-full max-w-[30rem]" x-data="{ showPassword: false, notice: false }">
            <div class="rounded-card border border-white bg-white/90 p-7 shadow-lift backdrop-blur sm:p-10">
                <div class="text-center">
                    <a href="{{ route('home') }}"><img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="{{ $summit->get('organiser') }}" class="mx-auto h-20 w-auto"></a>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-ink-900 sm:text-[2.1rem]">Welcome back</h1>
                    <p class="mx-auto mt-2 max-w-xs text-[15px] leading-relaxed text-ink-500">
                        Sign in to the {{ $summit->get('organiser') }} Events Portal to continue with the {{ $summit->get('year') }} Summit.
                    </p>
                </div>

                {{-- Accounts arrive with the authentication step of Phase 0. Until then the
                     form is not sent anywhere. --}}
                <form class="mt-8 space-y-5" @submit.prevent="notice = true">
                    <div x-show="notice" x-cloak role="status"
                         class="rounded-2xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-900">
                        Sign-in is not connected yet. Accounts are set up in the next build step.
                    </div>

                    <div>
                        <label for="email" class="label">Email address</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0A17.9 17.9 0 0 1 12 21.75c-2.68 0-5.22-.58-7.5-1.65Z"/>
                            </svg>
                            <input id="email" name="email" type="email" autocomplete="email" required autofocus
                                   placeholder="you@example.com" class="field pl-12">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" type="password"
                                   autocomplete="current-password" required placeholder="Enter your password" class="field pl-12 pr-12">
                            <button type="button" @click="showPassword = ! showPassword"
                                    :aria-pressed="showPassword.toString()" :aria-label="showPassword ? 'Hide password' : 'Show password'" aria-label="Show password"
                                    class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-ink-400 transition hover:bg-ink-100 hover:text-ink-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40">
                                <svg x-show="! showPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3 9.96 7.18.07.2.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3-9.96-7.18Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 1.93 12c1.3 4.34 5.31 7.5 10.07 7.5.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.76 0 8.77 3.16 10.07 7.5a10.52 10.52 0 0 1-4.29 5.77M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-ink-600">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-ink-300 accent-brand-700">
                            Keep me signed in
                        </label>
                        <a href="#" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Forgot password?</a>
                    </div>

                    <button type="submit"
                            class="group flex h-12 w-full items-center justify-center gap-2 rounded-control bg-gradient-to-r from-brand-700 to-brand-500 text-[15px] font-bold text-white shadow-soft transition hover:from-brand-800 hover:to-brand-600 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30">
                        Sign in
                        <svg class="h-5 w-5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </button>
                </form>

                <div class="my-6 flex items-center gap-4 text-xs text-ink-400">
                    <span class="h-px flex-1 bg-ink-200"></span>or<span class="h-px flex-1 bg-ink-200"></span>
                </div>

                <a href="{{ route('home') }}#register"
                   class="flex h-12 w-full items-center justify-center gap-2 rounded-control border border-brand-200 bg-white text-[15px] font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.13a3.38 3.38 0 1 1-6.75 0 3.38 3.38 0 0 1 6.75 0ZM3 19.24v-.12a6.38 6.38 0 0 1 12.75 0v.12A12.32 12.32 0 0 1 9.37 21c-2.33 0-4.5-.64-6.37-1.76Z"/>
                    </svg>
                    New here? Create an account
                </a>
            </div>

            <p class="mt-6 text-center text-xs text-ink-400">© {{ now()->year }} {{ $summit->get('organiser') }} · Transforming Lives</p>
        </section>
    </main>
</x-layouts.public>
