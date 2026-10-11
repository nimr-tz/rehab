{{-- The hero's words: tagline, title, theme, actions and the key facts. $dark for white text on a photo. --}}
@php $dark ??= false; @endphp

<div>
    <p @class(['flex flex-wrap items-center gap-x-3.5 gap-y-2 text-[13px] font-bold uppercase tracking-[0.16em]', 'text-brand-700' => ! $dark, 'text-sun-300' => $dark])>
        People <span class="h-[7px] w-[7px] rounded-full bg-ember-500"></span>
        Care <span @class(['h-[7px] w-[7px] rounded-full', 'bg-olive-700' => ! $dark, 'bg-olive-300' => $dark])></span>
        Together
    </p>
    <h1 @class(['mt-6 text-[clamp(3rem,6.4vw,5.6rem)] font-extrabold leading-[0.98] tracking-[-0.035em]', 'text-brand-700' => ! $dark, 'text-white' => $dark])>
        {{ $summit->get('name') }} <span @class(['text-ember-500' => ! $dark, 'text-sun-400' => $dark])>{{ $summit->get('year') }}</span>
    </h1>
    <p @class(['mt-6 max-w-xl text-[clamp(1.15rem,1.6vw,1.35rem)] leading-normal', 'text-ink-600' => ! $dark, 'text-brand-100' => $dark])>
        <span @class(['font-bold', 'text-ink-900' => ! $dark, 'text-white' => $dark])>Theme:</span>
        @if ($theme)
            {{ $theme }}
        @else
            <span class="tba">to be announced with the call for abstracts</span>
        @endif
    </p>

    <div class="mt-9 flex flex-wrap gap-3">
        @if ($dark)
            <a href="#register" class="btn-pill bg-sun-400 text-ink-900 hover:bg-sun-300 focus-visible:ring-sun-300/50">Register now →</a>
        @else
            <a href="#register" class="btn-pill bg-brand-700 text-white shadow-[0_10px_24px_-10px_rgb(2_79_109/0.6)] hover:bg-brand-800 focus-visible:ring-brand-500/30">
                Register now <span class="text-sun-400">→</span>
            </a>
        @endif
        @if ($summit->get('abstracts_open'))
            <a href="{{ route('login') }}" @class(['btn-pill border-[1.5px]', 'border-brand-700 bg-white text-brand-700 hover:bg-brand-700 hover:text-white focus-visible:ring-brand-500/30' => ! $dark, 'border-white/60 text-white hover:bg-white/10 focus-visible:ring-white/30' => $dark])>
                Submit an abstract
            </a>
        @endif
    </div>

    <dl @class(['mt-12 grid grid-cols-2 gap-5 border-t pt-6 sm:grid-cols-3', 'border-brand-100' => ! $dark, 'border-white/20' => $dark])>
        @php $dt = $dark ? 'text-brand-200' : 'text-ink-500'; $dd = $dark ? 'text-white' : 'text-ink-900'; @endphp
        <div>
            <dt class="text-xs font-bold uppercase tracking-[0.14em] {{ $dt }}">Dates</dt>
            <dd class="mt-1.5 text-lg font-bold {{ $dd }}">
                @if ($dateRange)
                    {{ $dateRange }}
                @else
                    {{ $summit->get('year') }}<span class="block text-sm tba">Exact dates to be announced</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-[0.14em] {{ $dt }}">Venue</dt>
            <dd class="mt-1.5 text-lg {{ $venueLine ? 'font-bold '.$dd : 'tba' }}">{{ $venueLine ?? 'To be announced' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-[0.14em] {{ $dt }}">Edition</dt>
            <dd class="mt-1.5 text-lg font-bold {{ $dd }}">{{ $summit->get('edition') }}</dd>
        </div>
    </dl>
</div>
