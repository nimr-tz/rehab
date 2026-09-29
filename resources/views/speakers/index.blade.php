@extends('layouts.public')

@section('title', 'Our Speakers')

@section('content')
<div class="pt-24 pb-20 bg-slate-50 dark:bg-slate-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="max-w-3xl mx-auto text-center mb-16">
            <span class="text-teal-600 dark:text-teal-400 font-semibold text-sm uppercase tracking-wider">Meet the Experts</span>
            <h1 class="mt-3 text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white">
                {{ config('conference.short_name') }} {{ config('conference.year') }} Speakers
            </h1>
            <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                A distinguished lineup of world-class researchers, innovators, and health leaders sharing their vision for Africa's health future.
            </p>
        </div>

        <!-- Featured Speakers Section -->
        @if($invitedSpeakers->isNotEmpty())
            <div class="mb-20">
                <div class="flex items-center gap-4 mb-10">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Keynote & Invited Speakers</h2>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-slate-800"></div>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($invitedSpeakers as $speaker)
                        <div class="group bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-100 dark:border-slate-800 flex flex-col" x-data="{ open: false }">
                            <!-- Speaker Image -->
                            <div class="relative h-72 overflow-hidden bg-slate-100 dark:bg-slate-800">
                                <img src="{{ $speaker->photo_url }}" alt="{{ $speaker->name }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-60 group-hover:opacity-80 transition-opacity"></div>

                                <div class="absolute bottom-6 left-6 right-6">
                                    <span class="px-3 py-1 bg-teal-500 text-white text-[10px] font-bold rounded-full uppercase tracking-widest shadow-lg">
                                        {{ $speaker->type }}
                                    </span>
                                </div>
                            </div>

                            <!-- Speaker Content -->
                            <div class="p-8 flex-1 flex flex-col">
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2 group-hover:text-teal-600 transition-colors">{{ $speaker->name }}</h3>
                                <p class="text-teal-600 dark:text-teal-400 font-medium text-sm mb-4">{{ $speaker->position }}</p>
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium mb-6">{{ $speaker->affiliation }}</p>

                                @if($speaker->bio)
                                    <div class="mb-6">
                                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed line-clamp-4" x-show="!open">
                                            {{ $speaker->bio }}
                                        </p>
                                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed" x-show="open" x-cloak>
                                            {{ $speaker->bio }}
                                        </p>
                                        @if(strlen($speaker->bio) > 150)
                                            <button @click="open = !open" class="mt-3 text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">
                                                <span x-show="!open">Read Biography</span>
                                                <span x-show="open">Show Less</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-auto pt-6 border-t border-slate-50 dark:border-slate-800 flex items-center justify-between">
                                    <!-- Social Links -->
                                    <div class="flex items-center gap-3">
                                        @if(isset($speaker->social_links['twitter']))
                                            <a href="https://twitter.com/{{ $speaker->social_links['twitter'] }}" target="_blank" class="text-slate-400 hover:text-blue-400 transition-colors">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                                            </a>
                                        @endif
                                        @if(isset($speaker->social_links['linkedin']))
                                            <a href="{{ $speaker->social_links['linkedin'] }}" target="_blank" class="text-slate-400 hover:text-blue-700 transition-colors">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                            </a>
                                        @endif
                                        @if(isset($speaker->social_links['website']))
                                            <a href="{{ $speaker->social_links['website'] }}" target="_blank" class="text-slate-400 hover:text-teal-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                            </a>
                                        @endif
                                    </div>

                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-full">Invited</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Research Presenters Section -->
        @if($presenters->isNotEmpty())
            <div>
                <div class="flex items-center gap-4 mb-10">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Research Presenters</h2>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-slate-800"></div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                    @foreach($presenters as $presenter)
                        <div class="group bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-100 dark:border-slate-800 text-center hover:shadow-lg transition-all">
                            <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($presenter->author_name) }}&color=0f766e&background=ccfbf1&size=128" alt="{{ $presenter->author_name }}" class="w-full h-full rounded-full">
                            </div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm line-clamp-1 mb-1">{{ $presenter->author_name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ $presenter->author_institute }}</p>
                            <div class="mt-3">
                                <span class="px-2 py-0.5 bg-slate-50 dark:bg-slate-800 text-slate-400 text-[9px] font-bold uppercase rounded-full">Presenter</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Empty State -->
        @if($invitedSpeakers->isEmpty() && $presenters->isEmpty())
            <div class="py-20 text-center">
                <div class="w-20 h-20 mx-auto bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Speakers TBA</h3>
                <p class="text-slate-500 dark:text-slate-400">Our speaker list is currently being finalized. Please check back soon.</p>
            </div>
        @endif
    </div>
</div>
@endsection

