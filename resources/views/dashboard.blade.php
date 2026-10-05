@php
    $user = auth()->user();
    $upcoming = [
        ['ticket', 'bg-sun-100 text-sun-800', 'Register for the '.$summit->title(), 'Choose your registration category and pay by bank transfer or mobile money.'],
        ['document', 'bg-coral-100 text-coral-800', 'Submit an abstract', 'Send your work for double-blind review under one of the summit topics.'],
        ['calendar', 'bg-olive-100 text-olive-800', 'Plan your summit', 'Follow the programme, download your badge and request an invitation letter.'],
    ];
@endphp

<x-layouts.portal title="Dashboard">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">{{ $summit->title() }}</p>
            <h1 class="mt-1 text-2xl font-bold text-ink-900">Karibu, {{ $user->title ? $user->title.' '.$user->last_name : $user->first_name }}</h1>
        </div>
        <a href="{{ route('home') }}#programme" class="inline-flex h-9 items-center rounded-control border border-ink-200 bg-white px-3.5 text-[13px] font-semibold text-ink-800 transition hover:border-ink-300 hover:bg-ink-50">
            View programme
        </a>
    </div>

    <div class="rounded-card border border-ink-100 bg-white p-6 shadow-soft">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
                <x-icon name="check-circle" class="h-6 w-6" />
            </div>
            <div class="flex-1">
                <p class="text-lg font-semibold text-ink-900">Your account is ready</p>
                <p class="text-sm text-ink-500">
                    Registration and abstract submission for {{ $summit->get('year') }} open here soon. We will email you at
                    <span class="font-medium text-ink-700">{{ $user->email }}</span> when they do.
                </p>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-lg font-semibold text-ink-900">Coming to your dashboard</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ($upcoming as [$icon, $tint, $heading, $body])
                <div class="rounded-card border border-ink-100 bg-white p-5 shadow-soft">
                    <div class="grid h-10 w-10 place-items-center rounded-xl {{ $tint }}">
                        <x-icon :name="$icon" class="h-5 w-5" />
                    </div>
                    <p class="mt-4 font-semibold text-ink-900">{{ $heading }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $body }}</p>
                    <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-ink-400"></span>Opening soon
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.portal>
