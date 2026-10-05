@php
    $dateRange = $summit->dateRange();
    $venueLine = $summit->venueLine();
    $theme = $summit->get('theme');
    $topics = $summit->get('topics', []);
    $contact = $summit->get('contact_email');

    // The four figure colours of the logo, cycled across topics and timelines.
    $accentBorders = ['border-ember-500', 'border-coral-400', 'border-olive-700', 'border-sun-400'];
    $accentDots = ['bg-ember-500', 'bg-coral-400', 'bg-olive-700', 'bg-sun-400'];

    $sessionKinds = [
        'Plenary' => 'text-ember-600',
        'Parallel' => 'text-brand-700',
        'Posters' => 'text-olive-700',
        'Panel' => 'text-sun-700',
    ];
    $programme = [
        ['Day 1 · Opening', [
            ['Plenary', 'Opening ceremony and keynote', 'Welcome and the summit theme'],
            ['Parallel', 'Oral sessions by topic', 'Abstract presentations across halls'],
            ['Posters', 'Poster session', 'Presenters at their posters'],
        ]],
        ['Day 2 · Evidence and practice', [
            ['Plenary', 'Keynote plenary', 'Morning plenary hall'],
            ['Panel', 'Panel discussions', 'Policy, financing and practice'],
            ['Parallel', 'Oral sessions by topic', 'Abstract presentations across halls'],
            ['Posters', 'Poster session', 'Presenters at their posters'],
        ]],
        ['Day 3 · Closing', [
            ['Parallel', 'Oral sessions by topic', 'Abstract presentations across halls'],
            ['Plenary', 'Rapporteur reports', 'Summaries from each session'],
            ['Plenary', 'Closing ceremony', 'Close of the '.$summit->get('edition').' '.$summit->get('name')],
        ]],
    ];

    $speakers = [
        ['Keynote', 'bg-ember-50', 'bg-ember-500'],
        ['Keynote', 'bg-sun-50', 'bg-sun-400'],
        ['Plenary speaker', 'bg-olive-50', 'bg-olive-700'],
        ['Panellist', 'bg-coral-50', 'bg-coral-400'],
    ];

    $faqs = [
        ['How do I create an account?', 'Click "Register" at the top of this page and fill in your details. We send you an email to confirm your address, and then you can sign in. One account covers registration, payment and abstracts.'],
        ['How do I submit an abstract?', 'Sign in and choose "Submit an abstract" on your dashboard while submissions are open. Pick a topic, add your co-authors and write your abstract. You can save a draft and come back to it before the deadline.'],
        ['How does the review work?', 'Abstracts are reviewed double-blind: reviewers do not see who wrote them, and authors do not see who reviewed them. The scientific committee then decides, and you are notified by email and on your dashboard.'],
        ['How do I pay the registration fee?', 'Pay by bank transfer or mobile money using the payment reference shown in your dashboard, then upload the receipt. A finance officer confirms the payment, after which your badge becomes available.'],
        ['I need a visa. Can I get an invitation letter?', 'Yes. Once you have registered, you can download an official invitation letter from your dashboard to support your visa application.'],
        ['Will I receive a CPD certificate?', 'Attendance is recorded at each session you attend. Certificates are issued in the portal after the summit, based on that recorded attendance.'],
    ];
@endphp

<x-layouts.public class="bg-white"
    :title="$summit->title().' · '.$summit->get('organiser')"
    :description="'The '.$summit->get('edition').' '.$summit->get('name').', organised by '.$summit->get('organiser').'. Register, submit an abstract and follow the programme.'">

    {{-- Announcement --}}
    <div class="bg-brand-700 text-white">
        <div class="wrap flex flex-wrap items-center justify-center gap-x-4 gap-y-1 py-2.5 text-center text-sm font-semibold">
            <span class="h-2 w-2 rounded-full bg-sun-400"></span>
            @if ($dateRange && $venueLine)
                <span>{{ $summit->title() }} · {{ $dateRange }} · {{ $venueLine }}</span>
            @else
                <span>Save the date: the {{ $summit->title() }}. Dates and venue will be announced soon.</span>
            @endif
            <a href="{{ route('register') }}" class="text-sun-300 hover:text-sun-200">Create your account →</a>
        </div>
    </div>

    <x-public.nav />

    {{-- Hero --}}
    <header class="bg-gradient-to-b from-white to-brand-50">
        <div class="wrap grid items-center gap-12 pb-20 pt-14 lg:grid-cols-[1.15fr_1fr] lg:pt-16">
            <div>
                <p class="flex flex-wrap items-center gap-x-3.5 gap-y-2 text-[13px] font-bold uppercase tracking-[0.16em] text-brand-700">
                    People <span class="h-[7px] w-[7px] rounded-full bg-ember-500"></span>
                    Care <span class="h-[7px] w-[7px] rounded-full bg-olive-700"></span>
                    Together
                </p>
                <h1 class="mt-6 text-[clamp(3rem,6.4vw,5.6rem)] font-extrabold leading-[0.98] tracking-[-0.035em] text-brand-700">
                    {{ $summit->get('name') }} <span class="text-ember-500">{{ $summit->get('year') }}</span>
                </h1>
                <p class="mt-6 max-w-xl text-[clamp(1.15rem,1.6vw,1.35rem)] leading-normal text-ink-600">
                    <span class="font-bold text-ink-900">Theme:</span>
                    @if ($theme)
                        {{ $theme }}
                    @else
                        <span class="tba">to be announced with the call for abstracts</span>
                    @endif
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="#register" class="btn-pill bg-brand-700 text-white shadow-[0_10px_24px_-10px_rgb(2_79_109/0.6)] hover:bg-brand-800 focus-visible:ring-brand-500/30">
                        Register now <span class="text-sun-400">→</span>
                    </a>
                    @if ($summit->get('abstracts_open'))
                        <a href="{{ route('login') }}" class="btn-pill border-[1.5px] border-brand-700 bg-white text-brand-700 hover:bg-brand-700 hover:text-white focus-visible:ring-brand-500/30">
                            Submit an abstract
                        </a>
                    @endif
                </div>

                <dl class="mt-12 grid grid-cols-2 gap-5 border-t border-brand-100 pt-6 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Dates</dt>
                        <dd class="mt-1.5 text-lg font-bold text-ink-900">
                            @if ($dateRange)
                                {{ $dateRange }}
                            @else
                                {{ $summit->get('year') }}<span class="block text-sm tba">Exact dates to be announced</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Venue</dt>
                        <dd class="mt-1.5 text-lg {{ $venueLine ? 'font-bold text-ink-900' : 'tba' }}">{{ $venueLine ?? 'To be announced' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Edition</dt>
                        <dd class="mt-1.5 text-lg font-bold text-ink-900">{{ $summit->get('edition') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="relative mx-auto flex w-full max-w-[32rem] items-center justify-center">
                <div class="absolute aspect-square w-[88%] rounded-full bg-[radial-gradient(circle,#fff_0%,#fff_55%,rgb(255_255_255/0)_72%)]"></div>
                <span class="absolute right-[10%] top-[6%] h-[22px] w-[22px] rounded-full bg-sun-400"></span>
                <span class="absolute bottom-[14%] left-[4%] h-4 w-4 rounded-full bg-coral-400"></span>
                <span class="absolute left-[2%] top-[30%] h-2.5 w-2.5 rounded-full bg-brand-700"></span>
                <div x-data="brandLogo('{{ asset('images/brand/logo-2027-animated.webp') }}')" class="relative aspect-square w-full">
                    <img src="{{ asset('images/brand/logo-2027-still.webp') }}" alt="{{ $summit->get('organiser') }} {{ $summit->get('year') }} Events Portal"
                         :class="{ 'opacity-0': stillHidden }" class="h-full w-full object-contain">
                    <img x-ref="anim" alt="" aria-hidden="true" style="display: none" :style="{ display: animated ? 'block' : 'none' }"
                         class="absolute inset-0 h-full w-full object-contain">
                </div>
            </div>
        </div>
    </header>

    {{-- About --}}
    <section id="about" class="wrap pt-28">
        <div class="grid gap-x-16 gap-y-8 lg:grid-cols-3">
            <p class="eyebrow">About the Summit</p>
            <div class="lg:col-span-2">
                <p class="text-[clamp(1.6rem,3vw,2.5rem)] font-semibold leading-tight tracking-tight text-brand-700">
                    The {{ $summit->get('edition') }} {{ $summit->get('name') }} is organised by {{ $summit->get('organiser') }}@if ($summit->get('co_organiser')) in collaboration with the {{ $summit->get('co_organiser') }}@endif.
                </p>
                <p class="mt-6 max-w-3xl text-xl leading-relaxed text-ink-600">
                    It brings together practitioners, researchers, policy makers and partners to share evidence and practice in rehabilitation.
                </p>
            </div>
        </div>
        <div class="mt-16 grid gap-4 md:grid-cols-3">
            <div class="rounded-[20px] bg-ember-50 p-7">
                <p class="text-[2.75rem] font-extrabold tracking-tight text-ember-600">{{ $summit->get('days') }} days</p>
                <p class="mt-1.5 text-[17px] leading-snug text-ink-600">of plenaries, parallel sessions and posters</p>
            </div>
            <div class="rounded-[20px] bg-sun-50 p-7">
                <p class="text-[2.75rem] font-extrabold tracking-tight text-brand-700">{{ count($topics) }} topics</p>
                <p class="mt-1.5 text-[17px] leading-snug text-ink-600">for abstracts, review and sessions</p>
            </div>
            <div class="rounded-[20px] bg-olive-50 p-7">
                <p class="text-[2.75rem] font-extrabold tracking-tight text-olive-700">CPD</p>
                <p class="mt-1.5 text-[17px] leading-snug text-ink-600">attendance recorded per session, with certificates after the summit</p>
            </div>
        </div>
    </section>

    {{-- Key dates --}}
    <section id="dates" class="wrap pt-28">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Key dates</p>
                <h2 class="section-title">On the road to {{ $summit->get('year') }}</h2>
            </div>
            <a href="{{ route('login') }}" class="text-base font-bold text-brand-700 hover:text-brand-800">Apply to chair or report a session →</a>
        </div>
        <ol class="mt-12 grid gap-y-8 sm:grid-cols-2 lg:grid-cols-5">
            <li class="border-t-[3px] border-brand-700 pr-5">
                <span class="-mt-2.5 block h-4 w-4 rounded-full bg-brand-700 ring-[5px] ring-white"></span>
                <p class="mt-4 text-[22px] font-bold tracking-tight text-brand-700">Open now</p>
                <p class="mt-2 text-base leading-snug text-ink-600">Create your portal account</p>
            </li>
            @foreach ($summit->keyDates() as $i => $item)
                <li class="border-t-[3px] {{ $accentBorders[$i % 4] }} pr-5">
                    <span class="-mt-2.5 block h-4 w-4 rounded-full {{ $accentDots[$i % 4] }} ring-[5px] ring-white"></span>
                    <p class="mt-4 text-[22px] {{ $item['date'] ? 'font-bold tracking-tight text-brand-700' : 'tba' }}">{{ $item['date'] ?? 'To be announced' }}</p>
                    <p class="mt-2 text-base leading-snug text-ink-600">{{ $item['label'] }}</p>
                </li>
            @endforeach
            <li class="border-t-[3px] border-sun-400 pr-5">
                <span class="-mt-2.5 block h-4 w-4 rounded-full bg-sun-400 ring-[5px] ring-white"></span>
                <p class="mt-4 text-[22px] {{ $dateRange ? 'font-bold tracking-tight text-brand-700' : 'tba' }}">{{ $dateRange ?? 'To be announced' }}</p>
                <p class="mt-2 text-base leading-snug text-ink-600">The {{ $summit->title() }}</p>
            </li>
        </ol>
    </section>

    {{-- How to register --}}
    <section id="register" class="mt-28 bg-brand-700 text-white">
        <div class="wrap grid gap-14 py-24 lg:grid-cols-2">
            <div>
                <p class="text-[13px] font-bold uppercase tracking-[0.16em] text-sun-400">How to register</p>
                <h2 class="mt-3 text-[clamp(2.1rem,4vw,3.4rem)] font-extrabold leading-[1.05] tracking-tight">Three steps to your badge</h2>
                <ol class="mt-10 grid gap-7">
                    @foreach ([
                        ['bg-sun-400', 'Create your account', 'Register on the portal and verify your email. Authors submit abstracts from the same account.'],
                        ['bg-coral-400', 'Pay and upload proof', 'Pay by bank transfer or mobile money using your personal payment reference, then upload the receipt.'],
                        ['bg-olive-300', 'Get verified', 'Our finance team confirms your payment. Your badge and invitation letter are then ready in your dashboard.'],
                    ] as $n => [$colour, $step, $body])
                        <li class="grid grid-cols-[52px_1fr] items-start gap-4">
                            <span class="grid h-[52px] w-[52px] place-items-center rounded-full {{ $colour }} text-xl font-extrabold text-ink-900">{{ $n + 1 }}</span>
                            <div>
                                <p class="mt-1 text-xl font-bold">{{ $step }}</p>
                                <p class="mt-1.5 text-[17px] leading-relaxed text-brand-100">{{ $body }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-pill bg-sun-400 text-ink-900 hover:bg-sun-300 focus-visible:ring-sun-300/50">Create your account →</a>
                    {{-- Group registration arrives in Phase 2; the leader starts with their own account. --}}
                    <a href="{{ route('register') }}" class="btn-pill border-[1.5px] border-white/50 text-white hover:bg-white/10 focus-visible:ring-white/30">Register a group</a>
                </div>
            </div>

            <div class="self-start rounded-card bg-white p-8 text-ink-800 shadow-lift">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h3 class="text-2xl font-bold text-brand-700">Registration fees</h3>
                    @unless ($summit->feesConfirmed())
                        <span class="text-[13px] font-bold text-ember-600">To be confirmed</span>
                    @endunless
                </div>
                <div class="mt-5 divide-y divide-ink-100 border-t border-ink-100">
                    @foreach ($summit->get('fees', []) as $fee)
                        <div class="flex items-center justify-between gap-4 py-4">
                            <span class="text-[17px] font-semibold">{{ $fee['label'] }}</span>
                            <span class="text-[17px] font-semibold {{ filled($fee['amount']) ? 'text-ink-900' : 'text-ink-400' }}">
                                {{ $fee['currency'] }} {{ filled($fee['amount']) ? number_format((float) $fee['amount']) : '—' }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <p class="mb-2.5 mt-6 text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Pay by</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($summit->get('payment_methods', []) as $method)
                        <span class="whitespace-nowrap rounded-full bg-brand-50 px-3.5 py-2 text-sm font-semibold text-brand-700">{{ $method }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Topics --}}
    <section id="topics" class="bg-brand-50">
        <div class="wrap py-24">
            <p class="eyebrow !text-ember-700">Topics</p>
            <h2 class="section-title">{{ ucfirst(\Illuminate\Support\Number::spell(count($topics))) }} topics, one theme</h2>
            <p class="mt-3.5 text-lg text-ink-600">Abstracts are submitted and reviewed under these topics.</p>
            <ol class="mt-12 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($topics as $name => $code)
                    <li class="flex min-h-[150px] flex-col justify-between gap-7 rounded-[20px] border-t-[5px] bg-white p-6 {{ $accentBorders[$loop->index % 4] }}">
                        <div class="flex items-center justify-between">
                            <span class="text-[15px] font-bold text-ink-500">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="rounded-md bg-brand-50 px-2.5 py-1 text-[13px] font-bold tracking-[0.08em] text-brand-700">{{ $code }}</span>
                        </div>
                        <p class="text-xl font-bold leading-tight text-ink-900">{{ $name }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Programme --}}
    <section id="programme" class="wrap pt-28" x-data="{ day: 0 }">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Programme</p>
                <h2 class="section-title">{{ ucfirst(\Illuminate\Support\Number::spell($summit->get('days'))) }} days of the Summit</h2>
            </div>
            <a href="#programme" class="text-base font-bold text-brand-700 hover:text-brand-800">View the full programme →</a>
        </div>
        <div role="tablist" aria-label="Summit days" class="mt-10 flex flex-wrap gap-2">
            @foreach ($programme as $i => [$label])
                <button type="button" role="tab" @click="day = {{ $i }}" :aria-selected="(day === {{ $i }}).toString()"
                        :class="day === {{ $i }} ? 'bg-brand-700 text-white' : 'bg-white text-brand-700 hover:bg-brand-50'"
                        class="whitespace-nowrap rounded-full border-[1.5px] border-brand-700 px-5 py-3 text-base font-bold transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        @foreach ($programme as $i => [, $sessions])
            <div role="tabpanel" x-show="day === {{ $i }}" @if ($i > 0) x-cloak @endif class="mt-6 overflow-hidden rounded-card border border-ink-100">
                @foreach ($sessions as [$kind, $sessionTitle, $note])
                    <div class="grid gap-x-8 gap-y-2 px-7 py-6 sm:grid-cols-[180px_1fr] sm:items-baseline {{ $loop->first ? '' : 'border-t border-ink-100' }}">
                        <span class="text-sm font-bold uppercase tracking-[0.12em] {{ $sessionKinds[$kind] }}">{{ $kind }}</span>
                        <div>
                            <p class="text-xl font-bold text-ink-900">{{ $sessionTitle }}</p>
                            <p class="mt-1.5 text-base text-ink-500">{{ $note }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
        <p class="mt-3.5 text-sm text-ink-500">Indicative outline. Times and halls are published in the programme.</p>
    </section>

    {{-- Speakers --}}
    <section id="speakers" class="wrap pt-28">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Speakers</p>
                <h2 class="section-title">Who you will hear from</h2>
            </div>
        </div>
        <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($speakers as [$role, $tint, $dot])
                <div class="rounded-card border border-ink-100 bg-ink-50/60 p-3.5 sm:p-6">
                    <div class="flex aspect-square items-end rounded-[14px] p-3 sm:rounded-[18px] sm:p-4 {{ $tint }}">
                        <span class="h-8 w-8 rounded-full sm:h-11 sm:w-11 {{ $dot }}"></span>
                    </div>
                    <p class="mt-4 text-xs font-bold uppercase tracking-[0.14em] text-ink-500">{{ $role }}</p>
                    <p class="mt-1.5 text-base font-bold text-ink-900 sm:text-xl">To be announced</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Venue --}}
    <section id="venue" class="wrap pt-28">
        <div class="grid overflow-hidden rounded-[28px] bg-brand-50 lg:grid-cols-2">
            <div class="p-8 sm:p-11">
                <p class="eyebrow !text-ember-700">Venue</p>
                @if ($venueLine)
                    <h2 class="mt-3 text-[clamp(1.9rem,3.4vw,2.75rem)] font-extrabold leading-[1.1] tracking-tight text-brand-700">{{ $summit->get('venue') }}</h2>
                    <p class="mt-4 text-lg text-ink-600">{{ collect([$summit->get('city'), $summit->get('country')])->filter()->implode(', ') }}</p>
                @else
                    <h2 class="mt-3 text-[clamp(1.9rem,3.4vw,2.75rem)] font-extrabold leading-[1.1] tracking-tight text-brand-700">Venue to be announced</h2>
                    <p class="mt-4 max-w-md text-lg leading-relaxed text-ink-600">
                        The venue and travel information will be published together with the summit dates.
                        International participants can request an invitation letter for their visa once registered.
                    </p>
                @endif
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($venueLine)
                        <a href="https://maps.google.com/?q={{ urlencode($venueLine.', '.$summit->get('country')) }}" target="_blank" rel="noopener"
                           class="inline-flex whitespace-nowrap rounded-full bg-brand-700 px-6 py-3.5 text-base font-bold text-white transition hover:bg-brand-800">Open in Google Maps →</a>
                    @endif
                    <a href="{{ route('login') }}" class="inline-flex whitespace-nowrap rounded-full border-[1.5px] border-brand-700 px-6 py-3.5 text-base font-bold text-brand-700 transition hover:bg-brand-700 hover:text-white">Request an invitation letter</a>
                </div>
            </div>
            @if ($venueLine)
                <iframe title="Map of {{ $summit->get('venue') }}" class="block h-full min-h-[380px] w-full border-0" loading="lazy"
                        src="https://maps.google.com/maps?q={{ urlencode($venueLine.', '.$summit->get('country')) }}&output=embed"></iframe>
            @else
                <div class="relative hidden min-h-[380px] overflow-hidden bg-gradient-to-br from-brand-100 to-brand-200 lg:block" aria-hidden="true">
                    <div class="absolute inset-0 bg-[radial-gradient(#7cc0d7_1.4px,transparent_1.4px)] [background-size:18px_18px] opacity-60"></div>
                    <div class="absolute left-1/2 top-1/2 grid h-28 w-28 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white shadow-lift">
                        <svg class="h-12 w-12 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    </div>
                    <span class="absolute left-[18%] top-[22%] h-4 w-4 rounded-full bg-sun-400"></span>
                    <span class="absolute bottom-[20%] right-[16%] h-5 w-5 rounded-full bg-coral-400"></span>
                    <span class="absolute bottom-[30%] left-[26%] h-3 w-3 rounded-full bg-ember-500"></span>
                </div>
            @endif
        </div>
    </section>

    {{-- Organisers --}}
    <section id="partners" class="wrap pt-28">
        <p class="eyebrow">Organisers</p>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="flex items-center gap-5 rounded-card border border-ink-100 p-7">
                <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="{{ $summit->get('organiser') }}" class="h-[72px] w-auto">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Organised by</p>
                    <p class="mt-1 text-[22px] font-bold text-brand-700">{{ $summit->get('organiser') }}</p>
                </div>
            </div>
            @if ($summit->get('co_organiser'))
                <div class="flex items-center gap-5 rounded-card border border-ink-100 p-7">
                    <div class="h-[72px] w-[72px] shrink-0 rounded-full border-[1.5px] border-dashed border-brand-300"></div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink-500">In collaboration with</p>
                        <p class="mt-1 text-[22px] font-bold text-brand-700">{{ $summit->get('co_organiser') }}</p>
                    </div>
                </div>
            @endif
            <div class="flex flex-col justify-center gap-2 rounded-card bg-sun-50 p-7">
                <p class="text-xl font-bold text-ink-900">Partner with the Summit</p>
                <a href="mailto:{{ $contact }}" class="text-base font-bold text-brand-700 hover:text-brand-800">{{ $contact }} →</a>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="wrap py-28">
        <div class="grid gap-x-16 gap-y-10 lg:grid-cols-3">
            <div>
                <p class="eyebrow">FAQ</p>
                <h2 class="section-title">Questions, answered</h2>
                <p class="mt-4 text-lg leading-relaxed text-ink-600">
                    Can't find what you need? Email
                    <a href="mailto:{{ $contact }}" class="font-bold text-brand-700 hover:text-brand-800">{{ $contact }}</a>.
                </p>
            </div>
            <div class="border-t border-brand-100 lg:col-span-2">
                @foreach ($faqs as [$question, $answer])
                    <details class="group border-b border-brand-100" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-6 text-left text-xl font-semibold text-ink-900 [&::-webkit-details-marker]:hidden">
                            <span>{{ $question }}</span>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-50 text-[22px] font-medium text-brand-700 transition group-open:rotate-45 group-open:bg-brand-700 group-open:text-white" aria-hidden="true">+</span>
                        </summary>
                        <p class="pb-6 pr-14 text-[17px] leading-relaxed text-ink-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Closing call to action --}}
    <section class="bg-sun-400">
        <div class="wrap flex flex-wrap items-center justify-between gap-7 py-16 sm:py-[72px]">
            <h2 class="max-w-3xl text-[clamp(1.9rem,3.6vw,3rem)] font-extrabold leading-[1.08] tracking-tight text-ink-900">
                @if ($dateRange && $venueLine)
                    Join us in {{ $summit->get('city') ?? $summit->get('venue') }}, {{ $dateRange }}.
                @else
                    Join us at the {{ $summit->title() }}.
                @endif
            </h2>
            <a href="#register" class="btn-pill bg-brand-700 text-white hover:bg-brand-800 focus-visible:ring-brand-700/30">Register now →</a>
        </div>
    </section>

    <x-public.footer />
</x-layouts.public>
