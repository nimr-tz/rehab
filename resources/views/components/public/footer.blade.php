<footer class="bg-brand-900 text-brand-100">
    <div class="wrap pb-8 pt-16">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex rounded-[14px] bg-white p-1.5"><img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="{{ $summit->get('organiser') }}" class="h-11 w-auto"></span>
                    <span class="text-lg font-bold text-white">{{ $summit->shortTitle() }}</span>
                </div>
                <p class="text-[13px] font-bold uppercase tracking-[0.24em] text-sun-400">Transforming lives</p>
            </div>
            <div>
                <h3 class="mb-3.5 text-sm font-bold text-white">Contact</h3>
                <a href="mailto:{{ $summit->get('contact_email') }}" class="mb-2 block text-[15px] hover:text-sun-300">{{ $summit->get('contact_email') }}</a>
                <a href="mailto:{{ $summit->get('abstracts_email') }}" class="block text-[15px] hover:text-sun-300">{{ $summit->get('abstracts_email') }}</a>
            </div>
            <div>
                <h3 class="mb-3.5 text-sm font-bold text-white">Summit</h3>
                <div class="grid gap-2 text-[15px]">
                    <a href="{{ route('home') }}#about" class="hover:text-sun-300">About</a>
                    <a href="{{ route('home') }}#programme" class="hover:text-sun-300">Programme</a>
                    <a href="{{ route('home') }}#speakers" class="hover:text-sun-300">Speakers</a>
                    <a href="{{ route('gallery.index') }}" class="hover:text-sun-300">Gallery</a>
                    <a href="{{ route('home') }}#venue" class="hover:text-sun-300">Venue</a>
                </div>
            </div>
            <div>
                <h3 class="mb-3.5 text-sm font-bold text-white">Participants</h3>
                <div class="grid gap-2 text-[15px]">
                    <a href="{{ route('register') }}" class="hover:text-sun-300">Register</a>
                    <a href="{{ route('login') }}" class="hover:text-sun-300">Log in</a>
                    <a href="{{ route('home') }}#faq" class="hover:text-sun-300">FAQ</a>
                </div>
            </div>
        </div>
        <div class="mt-12 flex flex-wrap justify-between gap-3 border-t border-brand-800 pt-6 text-sm text-brand-200">
            <span>© {{ now()->year }} {{ $summit->get('organiser') }}. All rights reserved.</span>
            <a href="{{ $summit->get('website') }}" class="hover:text-sun-300">{{ preg_replace('#^https?://#', '', $summit->get('website')) }}</a>
        </div>
    </div>
</footer>
