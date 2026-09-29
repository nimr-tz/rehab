@extends('layouts.public')

@section('title', config('conference.short_name') . ' ' . config('conference.year'))

@php
    $tz = config('conference.timezone');
    $start = \Carbon\Carbon::parse(config('conference.start_date'), $tz);
    $end = \Carbon\Carbon::parse(config('conference.end_date'), $tz)->endOfDay();
    $deadline = \Carbon\Carbon::parse(config('conference.submission_deadline'), $tz)->endOfDay();
    $now = now($tz);
    $conferenceOver = $now->gt($end);
    $submissionsOpen = $now->lte($deadline);
    $sessionRolesOpen = ! $conferenceOver && $now->lte(\App\Models\SessionRoleApplication::deadline());
    $topics = \App\Support\ConferenceTopics::names();
    $venueLine = implode(', ', array_filter([config('conference.venue'), config('conference.city'), config('conference.country')]));
    $mapQuery = urlencode($venueLine);
@endphp

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 pt-16 text-white">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px;"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-200">
                    {{ config('conference.edition') }} Edition &middot; {{ config('conference.display_dates') }}
                </p>

                <h1 class="text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                    {{ config('conference.name') }} {{ config('conference.year') }}
                </h1>

                @if(filled(config('conference.theme')))
                    <p class="mt-6 text-lg leading-relaxed text-brand-100 sm:text-xl">
                        <span class="font-semibold text-white">Theme:</span> {{ config('conference.theme') }}
                    </p>
                @endif

                <p class="mt-4 flex items-start gap-2 text-brand-100">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ $venueLine }}</span>
                </p>

                <div class="mt-10 flex flex-wrap gap-3">
                    @if($conferenceOver)
                        <a href="{{ route('certificate.index') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition hover:bg-brand-50">Get your certificate</a>
                        <a href="{{ route('feedback.create') }}" class="rounded-lg border border-white/40 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Share feedback</a>
                        <a href="{{ route('public.abstract-book.download') }}" class="rounded-lg border border-white/40 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Abstract book</a>
                    @else
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition hover:bg-brand-50">Go to your dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition hover:bg-brand-50">Register</a>
                            @if($submissionsOpen)
                                <a href="{{ route('register') }}" class="rounded-lg border border-white/40 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Submit an abstract</a>
                            @endif
                            <a href="{{ route('login') }}" class="rounded-lg border border-white/40 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Log in</a>
                        @endauth
                        <a href="{{ route('conference-program.view') }}" class="rounded-lg border border-white/40 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Programme</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- About & key dates --}}
    <section id="about" class="bg-white py-16 dark:bg-slate-950 sm:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="lg:col-span-2">
                <h2 class="text-3xl font-black text-slate-900 dark:text-white">About the Summit</h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600 dark:text-slate-300">
                    The {{ config('conference.edition') }} {{ config('conference.name') }} is organised by {{ config('conference.host') }}@if(filled(config('conference.co_organiser'))) in collaboration with the {{ config('conference.co_organiser') }}@endif.
                    It brings together practitioners, researchers, policy makers and partners to share evidence and practice in rehabilitation.
                </p>
                @if(($abstractsCount ?? 0) > 0)
                    <p class="mt-4 text-slate-500 dark:text-slate-400">{{ number_format($abstractsCount) }} abstracts submitted so far.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-sm font-bold uppercase tracking-widest text-brand-700 dark:text-brand-300">Key dates</h3>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Abstract submission deadline</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $deadline->format('j F Y') }}</dd>
                    </div>
                    @if($sessionRolesOpen)
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Session chair / rapporteur applications close</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ \App\Models\SessionRoleApplication::deadline()->format('j F Y') }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Conference</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ config('conference.display_dates') }}</dd>
                    </div>
                </dl>
                @if($sessionRolesOpen)
                    <a href="{{ Auth::check() ? route('session-role-applications.create') : route('register') }}" class="mt-6 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-900 dark:text-brand-300">
                        Apply to chair or report a session &rarr;
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Topics --}}
    @if(count($topics) > 0)
        <section id="program" class="bg-slate-50 py-16 dark:bg-slate-900 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white">Topics</h2>
                        <p class="mt-2 text-slate-600 dark:text-slate-300">Abstracts are submitted and reviewed under these topics.</p>
                    </div>
                    <a href="{{ route('conference-program.view') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900 dark:text-brand-300">View the programme &rarr;</a>
                </div>

                <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($topics as $i => $topic)
                        <li class="flex gap-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white">{{ $i + 1 }}</span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $topic }}</p>
                                @if($description = \App\Support\ConferenceTopics::description($topic))
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $description }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- Venue --}}
    <section id="venue" class="bg-white py-16 dark:bg-slate-950 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-slate-900 dark:text-white">Venue</h2>
            <p class="mt-2 text-lg text-slate-600 dark:text-slate-300">{{ $venueLine }}</p>
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                <iframe title="Map of {{ config('conference.venue') }}" class="h-80 w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    src="https://maps.google.com/maps?q={{ $mapQuery }}&output=embed"></iframe>
            </div>
            <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener" class="mt-4 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-900 dark:text-brand-300">
                Open in Google Maps &rarr;
            </a>
        </div>
    </section>
@endsection
