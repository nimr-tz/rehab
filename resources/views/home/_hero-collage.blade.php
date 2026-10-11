{{-- The hero: the words beside a photo of the summit stage, with the logo as a badge. --}}
<header class="overflow-hidden bg-gradient-to-b from-white to-brand-50">
    <div class="wrap grid items-center gap-14 pb-24 pt-14 lg:grid-cols-[1.05fr_1fr] lg:pt-16">
        @include('home._hero-text')

        <div class="relative mx-auto w-full max-w-[36rem] pb-10 pl-6 sm:pb-14 sm:pl-10">
            <span class="absolute right-[4%] top-[-4%] h-[22px] w-[22px] rounded-full bg-sun-400"></span>
            <span class="absolute bottom-[4%] right-[30%] h-4 w-4 rounded-full bg-coral-400"></span>
            <span class="absolute left-0 top-[18%] h-2.5 w-2.5 rounded-full bg-brand-700"></span>

            <img src="{{ asset('images/home/hero-stage.webp') }}" width="1400" height="934" fetchpriority="high"
                 alt="Speakers and guests on the stage of the 2026 summit, under the Rehabilitation Summit banner"
                 class="aspect-[4/3] w-full rounded-[28px] object-cover shadow-lift">
            <img src="{{ asset('images/home/hero-entrance.webp') }}" width="900" height="600" loading="lazy"
                 alt="The summit's entrance arch at the convention centre"
                 class="absolute -right-4 bottom-0 hidden aspect-[3/2] w-[44%] rounded-[20px] border-[6px] border-white object-cover shadow-lift sm:block">
            <div class="absolute bottom-0 left-0 w-[34%] rounded-full bg-white p-2.5 shadow-lift">
                @include('home._hero-logo-mark')
            </div>
        </div>
    </div>
</header>
