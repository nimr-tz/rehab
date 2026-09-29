@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Conference Feedback | ' . config('conference.short_name') . ' ' . config('conference.year'))

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-20 overflow-hidden" x-data="feedbackForm()">
    {{-- Progress Bar --}}
    <div class="fixed top-16 left-0 w-full h-2 bg-slate-200 dark:bg-slate-800 z-50">
        <div class="h-full bg-indigo-600 transition-all duration-500 ease-out" :style="{ width: progress + '%' }"></div>
    </div>

    {{-- High-Impact Header --}}
    <div class="relative bg-slate-900 py-12 md:py-16 lg:py-20 overflow-hidden @guest pt-28 md:pt-32 @endguest">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-indigo-500 rounded-full blur-[120px] -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500 rounded-full blur-[120px] translate-x-1/2 translate-y-1/2"></div>
        </div>

        <div class="max-w-4xl mx-auto px-6 relative z-10 text-center">
            <template x-if="step === 1">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-6 tracking-tight animate-fade-in-up">
                    About You & Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-blue-400">Experience.</span>
                </h1>
            </template>
            <template x-if="step === 2">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-6 tracking-tight animate-fade-in-up">
                    Scientific <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400">Programme.</span>
                </h1>
            </template>
            <template x-if="step === 3">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-6 tracking-tight animate-fade-in-up">
                    Venue & <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-400">Services.</span>
                </h1>
            </template>
            <template x-if="step === 4">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-6 tracking-tight animate-fade-in-up">
                    Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-sky-400">Role.</span>
                </h1>
            </template>
            <template x-if="step === 5">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-6 tracking-tight animate-fade-in-up">
                    Road to <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">{{ config('conference.short_name') }} 2027.</span>
                </h1>
            </template>

            <p class="text-lg text-slate-400 font-medium max-w-2xl mx-auto leading-relaxed" x-text="stepDescription"></p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-6 -mt-10 relative z-20">
        <form action="{{ route('feedback.store') }}" method="POST" id="main-feedback-form">
            @csrf
            @if(request('badge'))
                <input type="hidden" name="badge_code" value="{{ request('badge') }}">
            @endif

            {{-- ============================================================ --}}
            {{-- Step 1: General Information + Overall Satisfaction           --}}
            {{-- ============================================================ --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-10" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8">
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] md:rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-700/50 p-6 md:p-10 lg:p-12">
                    <div class="flex items-center gap-4 mb-8 md:mb-10">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xl md:text-2xl">
                            👋
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight">About You</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">A few details so we can read your feedback in context.</p>
                        </div>
                    </div>

                    <div class="space-y-10">
                        @guest
                        <div class="grid md:grid-cols-2 gap-8 p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-800">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Your Name <span class="text-slate-400 font-normal normal-case">(optional)</span></label>
                                <input type="text" name="guest_name" class="w-full h-14 px-6 rounded-2xl bg-white dark:bg-slate-900 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900 dark:text-white" placeholder="e.g. Dr. Jane Smith">
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Email <span class="text-slate-400 font-normal normal-case">(optional)</span></label>
                                <input type="email" name="guest_email" class="w-full h-14 px-6 rounded-2xl bg-white dark:bg-slate-900 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900 dark:text-white" placeholder="your@email.com">
                            </div>
                        </div>
                        @endguest

                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">I participated as</label>
                                <select name="participant_type" x-model="participantType" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900 dark:text-white appearance-none">
                                    <option value="attendee">Attendee</option>
                                    <option value="presenter">Presenter (oral or poster)</option>
                                    <option value="chair_rapporteur">Session Chair / Rapporteur</option>
                                    <option value="reviewer">Abstract Reviewer</option>
                                    <option value="organizer">Organizing Team / Staff</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Field / Institution</label>
                                <input type="text" name="field_discipline" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900 dark:text-white" placeholder="e.g. Epidemiology — {{ config('conference.host_short') }} Mwanza">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Was this your first {{ config('conference.short_name') }}?</label>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach(['first_time' => 'My First ' . config('conference.short_name'), 'returning' => 'Attended Before'] as $val => $lbl)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="attendance_history" value="{{ $val }}" class="peer sr-only">
                                            <div class="py-4 px-4 rounded-xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 text-center text-xs font-black text-slate-600 dark:text-slate-300 transition-all">{{ $lbl }}</div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">How did you hear about {{ config('conference.short_name') }} {{ config('conference.year') }}?</label>
                                <select name="how_heard" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900 dark:text-white appearance-none">
                                    <option value="">Select...</option>
                                    <option value="colleague">Colleague / word of mouth</option>
                                    <option value="email">{{ config('conference.host_short') }} / {{ config('conference.short_name') }} email</option>
                                    <option value="institution">My institution</option>
                                    <option value="social_media">Social media</option>
                                    <option value="website">{{ config('conference.host_short') }} / {{ config('conference.short_name') }} website</option>
                                    <option value="previous_attendance">I attended a previous {{ config('conference.short_name') }}</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-2 text-center mt-6">Overall Conference Experience</label>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter text-center mb-6">Scale: 1 (Very Poor) to 5 (Outstanding)</p>
                            <div class="flex justify-between items-center gap-1 md:gap-3">
                                @foreach([1 => '😫', 2 => '😕', 3 => '🙂', 4 => '🤩', 5 => '💎'] as $val => $emoji)
                                    <label class="relative flex-1 cursor-pointer group">
                                        <input type="radio" name="overall_rating" value="{{ $val }}" class="peer sr-only" required>
                                        <div class="h-16 md:h-24 rounded-2xl md:rounded-3xl border-2 border-slate-100 dark:border-slate-700 flex flex-col items-center justify-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:bg-slate-50">
                                            <span class="text-2xl md:text-4xl mb-1 md:mb-2 transition-transform group-hover:scale-125">{{ $emoji }}</span>
                                            <span class="text-[9px] md:text-[10px] font-black text-slate-400 peer-checked:text-indigo-600 hidden sm:block">
                                                @if($val == 1) Poor @elseif($val == 5) Excellent @else {{ $val }}/5 @endif
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Did {{ config('conference.short_name') }} {{ config('conference.year') }} meet your expectations?</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach(['exceeded' => 'Exceeded Them', 'fully_met' => 'Fully Met', 'partially_met' => 'Partially Met', 'not_met' => 'Not Met'] as $val => $lbl)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="met_expectations" value="{{ $val }}" class="peer sr-only">
                                        <div class="py-4 px-4 rounded-xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 text-center text-xs font-black text-slate-600 dark:text-slate-300 transition-all">{{ $lbl }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Would you attend {{ config('conference.short_name') }} 2027? 📅</label>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach(['definitely' => 'Definitely', 'probably' => 'Probably', 'maybe' => 'Maybe', 'no' => 'No'] as $val => $lbl)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="likely_to_attend_next" value="{{ $val }}" class="peer sr-only">
                                            <div class="py-3 px-4 rounded-xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 text-center text-xs font-black text-slate-600 dark:text-slate-300">{{ $lbl }}</div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Recommend {{ config('conference.short_name') }} to colleagues? 🗣️</label>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach(['definitely' => 'Definitely', 'probably' => 'Probably', 'maybe' => 'Maybe', 'no' => 'No'] as $val => $lbl)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="likely_to_recommend" value="{{ $val }}" class="peer sr-only">
                                            <div class="py-3 px-4 rounded-xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 text-center text-xs font-black text-slate-600 dark:text-slate-300">{{ $lbl }}</div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Overall Comments</label>
                            <textarea name="overall_comments" rows="3" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-indigo-500 font-medium" placeholder="Your general impressions of {{ config('conference.short_name') }} {{ config('conference.year') }}..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Step 2: Scientific Content + Sessions & Programme            --}}
            {{-- ============================================================ --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-10" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8">
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] md:rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-700/50 p-6 md:p-10 lg:p-12">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-2xl">
                            🔬
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Scientific &amp; Academic Content</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">The research, the speakers, the sessions. <span class="font-bold text-emerald-600">(1: Poor – 5: Excellent)</span></p>
                        </div>
                    </div>

                    {{-- Which parts did they actually attend — lets us segment every answer below --}}
                    <div class="mb-10">
                        <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Which parts did you take part in?</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['Keynote & plenary sessions', 'Oral presentation sessions', 'Poster sessions'] as $part)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="sessions_attended[]" value="{{ $part }}" class="peer sr-only">
                                    <div class="px-4 py-2.5 rounded-xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 text-xs font-black text-slate-600 dark:text-slate-300 transition-all">{{ $part }}</div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @php
                            $scienceMetrics = [
                                ['id' => 'speaker_quality_rating', 'label' => 'Relevance & quality of keynote presentations', 'emoji' => '🎤'],
                                ['id' => 'content_quality_rating', 'label' => 'Quality & diversity of research presented', 'emoji' => '📚'],
                                ['id' => 'session_variety_rating', 'label' => 'Depth of topics covered across the 3 days', 'emoji' => '🎭'],
                                ['id' => 'scientific_discussion_rating', 'label' => 'Opportunities for scientific discussion & debate', 'emoji' => '💬'],
                                ['id' => 'poster_session_rating', 'label' => 'Poster sessions', 'emoji' => '🖼️'],
                                ['id' => 'learning_value_rating', 'label' => 'Learning value — what you took home', 'emoji' => '🎓'],
                            ];
                        @endphp

                        @foreach($scienceMetrics as $metric)
                            <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl space-y-4 transition-all hover:shadow-lg">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ $metric['emoji'] }}</span>
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $metric['label'] }}</label>
                                </div>
                                <div class="flex gap-1.5">
                                    @for($i=1; $i<=5; $i++)
                                        <label class="relative flex-1 cursor-pointer">
                                            <input type="radio" name="{{ $metric['id'] }}" value="{{ $i }}" class="peer sr-only">
                                            <div class="h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 peer-checked:bg-emerald-500 peer-checked:text-white flex items-center justify-center font-black text-xs">
                                                {{ $i }}
                                            </div>
                                        </label>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-2">Were emerging &amp; priority research areas adequately addressed?</label>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter mb-4">e.g. AI &amp; digital health, AMR, climate &amp; health, UHC</p>
                        <div class="grid grid-cols-3 gap-3 max-w-md">
                            @foreach(['yes' => 'Yes', 'partially' => 'Partially', 'no' => 'No'] as $val => $lbl)
                                <label class="cursor-pointer">
                                    <input type="radio" name="emerging_areas_covered" value="{{ $val }}" class="peer sr-only">
                                    <div class="py-3 px-4 rounded-xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 text-center text-xs font-black text-slate-600 dark:text-slate-300">{{ $lbl }}</div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Sessions & Programme structure --}}
                    <div class="mt-12 pt-10 border-t border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-1">Sessions &amp; Programme Structure</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-8">How the programme was put together and run.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @php
                                $programmeMetrics = [
                                    ['id' => 'session_balance_rating', 'label' => 'Balance between plenary, parallel & poster sessions', 'emoji' => '⚖️'],
                                    ['id' => 'session_pacing_rating', 'label' => 'Session pacing & time allocated per presenter', 'emoji' => '⏱️'],
                                    ['id' => 'session_organization_rating', 'label' => 'Quality of session moderation & chairing', 'emoji' => '🪑'],
                                    ['id' => 'networking_rating', 'label' => 'Networking opportunities — breaks & side meetings', 'emoji' => '🤝'],
                                ];
                            @endphp

                            @foreach($programmeMetrics as $metric)
                                <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl space-y-4 transition-all hover:shadow-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">{{ $metric['emoji'] }}</span>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $metric['label'] }}</label>
                                    </div>
                                    <div class="flex gap-1.5">
                                        @for($i=1; $i<=5; $i++)
                                            <label class="relative flex-1 cursor-pointer">
                                                <input type="radio" name="{{ $metric['id'] }}" value="{{ $i }}" class="peer sr-only">
                                                <div class="h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 peer-checked:bg-emerald-500 peer-checked:text-white flex items-center justify-center font-black text-xs">
                                                    {{ $i }}
                                                </div>
                                            </label>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Step 3: Logistics, Venue, Communications & Materials         --}}
            {{-- ============================================================ --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-10" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8">
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] md:rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-700/50 p-6 md:p-10 lg:p-12">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 text-2xl">
                            🏨
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Logistics &amp; Organization</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">From registration to your last coffee at {{ config('conference.venue') }}. <span class="font-bold text-orange-600">(1: Poor – 5: Excellent)</span></p>
                        </div>
                    </div>

                    <div class="space-y-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @php
                                $logisticsMetrics = [
                                    ['id' => 'venue_rating', 'label' => config('conference.venue') . ' venue — space, accessibility & facilities', 'emoji' => '🏛️'],
                                    ['id' => 'registration_process_rating', 'label' => 'Registration, payment & badge check-in', 'emoji' => '🎫'],
                                    ['id' => 'session_timing_rating', 'label' => 'Conference schedule & time management', 'emoji' => '🕙'],
                                    ['id' => 'organization_rating', 'label' => 'Overall organisation & daily flow', 'emoji' => '🧭'],
                                    ['id' => 'service_quality_rating', 'label' => 'Signage, directions & staff helpfulness', 'emoji' => '🛎️'],
                                    ['id' => 'accommodation_transport_rating', 'label' => 'Accommodation & transport around ' . config('conference.city'), 'emoji' => '🚌'],
                                ];
                            @endphp

                            @foreach($logisticsMetrics as $metric)
                                <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl space-y-4 transition-all hover:shadow-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">{{ $metric['emoji'] }}</span>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $metric['label'] }}</label>
                                    </div>
                                    <div class="flex gap-1.5">
                                        @for($i=1; $i<=5; $i++)
                                            <label class="relative flex-1 cursor-pointer">
                                                <input type="radio" name="{{ $metric['id'] }}" value="{{ $i }}" class="peer sr-only">
                                                <div class="h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 peer-checked:bg-orange-500 peer-checked:text-white flex items-center justify-center font-black text-xs">
                                                    {{ $i }}
                                                </div>
                                            </label>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-2">Meals, Breaks &amp; Catering</label>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter mb-4">Tea breaks, lunches and overall catering quality</p>
                            <div class="flex gap-2 max-w-lg">
                                @foreach([1 => '🤢', 2 => '😐', 3 => '😋', 4 => '🥘', 5 => '👨‍🍳'] as $val => $emoji)
                                    <label class="relative flex-1 cursor-pointer group">
                                        <input type="radio" name="food_quality_rating" value="{{ $val }}" class="peer sr-only">
                                        <div class="h-16 rounded-2xl bg-slate-50 dark:bg-slate-900 border-2 border-transparent peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/20 flex items-center justify-center text-2xl filter grayscale peer-checked:grayscale-0 transition-all">
                                            {{ $emoji }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Communications & Materials --}}
                        <div class="pt-8 border-t border-slate-100 dark:border-slate-700">
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-1">App, Communications &amp; Materials</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-8">The {{ config('conference.short_name') }} website, mobile app, emails and printed materials.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @php
                                    $commsMetrics = [
                                        ['id' => 'platform_usability', 'label' => config('conference.short_name') . ' website & mobile app usefulness', 'emoji' => '📱'],
                                        ['id' => 'communication_rating', 'label' => 'Clarity of pre-conference communications', 'emoji' => '📧'],
                                        ['id' => 'materials_rating', 'label' => 'Programme booklet, abstract book & materials', 'emoji' => '📖'],
                                    ];
                                @endphp

                                @foreach($commsMetrics as $metric)
                                    <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl space-y-4 transition-all hover:shadow-lg">
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl">{{ $metric['emoji'] }}</span>
                                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $metric['label'] }}</label>
                                        </div>
                                        <div class="flex gap-1.5">
                                            @for($i=1; $i<=5; $i++)
                                                <label class="relative flex-1 cursor-pointer">
                                                    <input type="radio" name="{{ $metric['id'] }}" value="{{ $i }}" class="peer sr-only">
                                                    <div class="h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 peer-checked:bg-orange-500 peer-checked:text-white flex items-center justify-center font-black text-xs">
                                                        {{ $i }}
                                                    </div>
                                                </label>
                                            @endfor
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Catering / Hospitality Comments</label>
                                <textarea name="hospitality_comments" rows="3" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-orange-500 font-medium" placeholder="Food variety, queues, dietary options, staff..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-4">Website / App Issues</label>
                                <textarea name="platform_feedback" rows="3" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-orange-500 font-medium" placeholder="Anything that didn't work — payment, programme, check-in, notifications..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Step 4: Role-Specific — everyone sees it, opts into blocks   --}}
            {{-- ============================================================ --}}
            <div x-show="step === 4" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-10" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8">

                {{-- Role selector — multi-select, since people held several roles --}}
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] md:rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-700/50 p-6 md:p-10 lg:p-12">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center text-cyan-600 dark:text-cyan-400 text-2xl">
                            🙋
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Did any of these apply to you?</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Tick all that apply — extra questions appear for each. None? Just hit Next.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @php
                            $roles = [
                                ['value' => 'presented', 'label' => 'I presented', 'sub' => 'Oral or poster presentation', 'emoji' => '🎤'],
                                ['value' => 'chaired', 'label' => 'I chaired / reported', 'sub' => 'Session chair or rapporteur', 'emoji' => '🪑'],
                            ];
                        @endphp
                        @foreach($roles as $role)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="roles_held[]" value="{{ $role['value'] }}" x-model="rolesHeld" class="peer sr-only">
                                <div class="p-5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 dark:peer-checked:bg-cyan-900/20 transition-all h-full">
                                    <div class="text-2xl mb-2">{{ $role['emoji'] }}</div>
                                    <div class="text-sm font-black text-slate-900 dark:text-white">{{ $role['label'] }}</div>
                                    <div class="text-xs text-slate-400 font-medium mt-1">{{ $role['sub'] }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <p class="mt-6 text-sm text-slate-400 font-medium text-center" x-show="rolesHeld.length === 0">
                        Attended only? That's most people — click <span class="font-black text-slate-600 dark:text-slate-300">Next Section</span> to continue. 👍
                    </p>
                </div>

                {{-- Speaker / presenter / chair block --}}
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] md:rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-700/50 p-6 md:p-10 lg:p-12"
                     x-show="isSpeaker">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center text-cyan-600 dark:text-cyan-400 text-2xl">
                            🎤
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">On the Podium — Speakers &amp; Chairs</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Your experience on stage and behind the scenes. <span class="font-bold text-cyan-600">(1: Poor – 5: Excellent)</span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @php
                            $speakerMetrics = [
                                ['id' => 'presenter_support_rating', 'label' => 'Support received before & during the conference', 'emoji' => '🤲'],
                                ['id' => 'av_quality_rating', 'label' => 'AV & technical equipment — sound, projector, slides', 'emoji' => '🎛️'],
                                ['id' => 'audience_engagement_rating', 'label' => 'Audience engagement & Q&A quality', 'emoji' => '🙋'],
                            ];
                        @endphp

                        @foreach($speakerMetrics as $metric)
                            <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl space-y-4 transition-all hover:shadow-lg">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ $metric['emoji'] }}</span>
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $metric['label'] }}</label>
                                </div>
                                <div class="flex gap-1.5">
                                    @for($i=1; $i<=5; $i++)
                                        <label class="relative flex-1 cursor-pointer">
                                            <input type="radio" name="{{ $metric['id'] }}" value="{{ $i }}" class="peer sr-only">
                                            <div class="h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 peer-checked:bg-cyan-500 peer-checked:text-white flex items-center justify-center font-black text-xs">
                                                {{ $i }}
                                            </div>
                                        </label>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Abstract & review pipeline — presenters only --}}
                    <div class="mt-10 p-6 md:p-8 bg-cyan-50/60 dark:bg-cyan-900/10 rounded-3xl border border-cyan-100 dark:border-cyan-900/40 space-y-6"
                         x-show="didPresent">
                        <div>
                            <h3 class="text-sm font-black text-cyan-700 dark:text-cyan-400 uppercase tracking-widest">Abstract Submission &amp; Review</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">From submitting on the website to receiving your decision.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @php
                                $presenterMetrics = [
                                    ['id' => 'submission_ease_rating', 'label' => 'Submitting your abstract on the website', 'emoji' => '💻'],
                                    ['id' => 'review_time_rating', 'label' => 'Speed of the review decision', 'emoji' => '⏳'],
                                    ['id' => 'reviewer_comments_quality', 'label' => 'Usefulness of reviewer feedback', 'emoji' => '💬'],
                                    ['id' => 'abstract_review_rating', 'label' => 'Overall quality of the review process', 'emoji' => '📝'],
                                ];
                            @endphp
                            @foreach($presenterMetrics as $metric)
                                <div class="p-5 bg-white dark:bg-slate-900/50 rounded-2xl space-y-3">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">{{ $metric['emoji'] }}</span>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $metric['label'] }}</label>
                                    </div>
                                    <div class="flex gap-1.5">
                                        @for($i=1; $i<=5; $i++)
                                            <label class="relative flex-1 cursor-pointer">
                                                <input type="radio" name="{{ $metric['id'] }}" value="{{ $i }}" class="peer sr-only">
                                                <div class="h-10 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 peer-checked:bg-cyan-500 peer-checked:text-white flex items-center justify-center font-black text-xs">
                                                    {{ $i }}
                                                </div>
                                            </label>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest mb-3">Was the double-blind review fair?</label>
                            <div class="grid grid-cols-3 gap-3 max-w-md">
                                @foreach(['yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not Sure'] as $val => $lbl)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="review_fair" value="{{ $val }}" class="peer sr-only">
                                        <div class="py-3 px-4 rounded-xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 dark:peer-checked:bg-cyan-900/20 text-center text-xs font-black text-slate-600 dark:text-slate-300">{{ $lbl }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest mb-3">Anything about submission or review we should fix?</label>
                            <textarea name="review_process_comments" rows="2" class="w-full px-5 py-3 rounded-2xl bg-white dark:bg-slate-900 border-none focus:ring-2 focus:ring-cyan-500 font-medium text-sm" placeholder="e.g. unclear guidelines, late decision emails, confusing revision process..."></textarea>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ============================================================ --}}
            {{-- Step 5: Open-Ended Feedback + Demographics                   --}}
            {{-- ============================================================ --}}
            <div x-show="step === 5" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-10" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8">
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] md:rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-700/50 p-6 md:p-10 lg:p-12">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-2xl">
                            🎬
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">In Your Own Words</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">The most valuable part — be specific and be honest.</p>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">What worked well? ✅</label>
                                <textarea name="what_worked_well" rows="3" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-medium" placeholder="The things we should definitely keep for 2027..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">What needs improvement? 🔧</label>
                                <textarea name="what_needs_improvement" rows="3" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-medium" placeholder="Be honest — queues, rooms, timing, anything..."></textarea>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">Most Valuable Session 💎</label>
                                <input type="text" name="most_valuable_session" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-bold" placeholder="Which session or speaker stood out?">
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">Least Valuable Session 📉</label>
                                <input type="text" name="least_valuable_session" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-bold" placeholder="What didn't earn its slot?">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">Suggested themes or topics for {{ config('conference.short_name') }} 2027 🔭</label>
                            <textarea name="topics_want_to_see" rows="2" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-medium" placeholder="Research areas, subthemes, keynote speakers, workshop ideas..."></textarea>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8 pt-2">
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">Age Group</label>
                                <select name="age_group" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-bold text-slate-900 dark:text-white appearance-none">
                                    <option value="">Prefer not to say</option>
                                    @foreach(['18-25', '26-35', '36-45', '46-55', '56-65', '65+'] as $age)
                                        <option value="{{ $age }}">{{ $age }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">Career Stage</label>
                                <select name="experience_level" class="w-full h-14 px-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-bold text-slate-900 dark:text-white appearance-none">
                                    <option value="">Prefer not to say</option>
                                    <option value="student">Student</option>
                                    <option value="early_career">Early Career Researcher</option>
                                    <option value="mid_career">Mid-Career Researcher</option>
                                    <option value="senior">Senior Researcher / Leadership</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-4">Any other comments?</label>
                            <textarea name="additional_comments" rows="3" class="w-full px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border-none focus:ring-2 focus:ring-purple-500 font-medium" placeholder="Anything else you want the organizing committee to know..."></textarea>
                        </div>

                        <div class="flex items-center justify-between p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800"
                             x-show="didPresent">
                             <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Would you like to present your research again in 2027?</span>
                             <label class="relative inline-flex items-center cursor-pointer">
                                 <input type="checkbox" name="would_present_again" value="1" class="sr-only peer">
                                 <div class="w-14 h-8 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                             </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Controls --}}
            <div class="mt-8 md:mt-12 flex justify-between items-center bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl md:rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700">
                <button type="button"
                        x-show="step > 1"
                        @click="prevStep()"
                        class="px-8 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-black text-sm uppercase tracking-widest transition-all">
                    Back
                </button>
                <div x-show="step === 1" class="w-8"></div> {{-- Spacer --}}

                <div class="flex gap-4">
                    <button type="button"
                            x-show="step < totalSteps"
                            @click="nextStep()"
                            class="px-10 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                        Next Section
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <button type="submit"
                            x-show="step === totalSteps"
                            @click="submitForm($event)"
                            class="px-12 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-base uppercase tracking-widest shadow-2xl hover:scale-105 transition-all flex items-center gap-4">
                        Finalize & Submit
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    function feedbackForm() {
        return {
            step: 1,
            totalSteps: 5,
            progress: 20,
            participantType: 'attendee',
            rolesHeld: [],
            descriptions: [
                "Who you are and the overall pulse of your {{ config('conference.short_name') }} {{ config('conference.year') }} experience.",
                "Keynotes, research quality, posters and how the programme was structured.",
                "{{ config('conference.venue') }} venue, registration & badges, catering, transport, the app and communications.",
                "Did you present or chair a session? Tell us about that — or skip ahead.",
                "What to keep, what to fix, and your wishlist for {{ config('conference.short_name') }} 2027."
            ],
            init() {
                // Pre-tick step 4 roles from the primary role chosen on step 1;
                // people can still adjust since many held several roles.
                this.$watch('participantType', (v) => {
                    const map = {
                        presenter: ['presented'],
                        chair_rapporteur: ['chaired'],
                    };
                    this.rolesHeld = map[v] || [];
                });
            },
            get stepDescription() {
                return this.descriptions[this.step - 1];
            },
            get didPresent() {
                return this.rolesHeld.includes('presented');
            },
            get didChair() {
                return this.rolesHeld.includes('chaired');
            },
            get isSpeaker() {
                return this.didPresent || this.didChair;
            },
            nextStep() {
                if (this.step < this.totalSteps) {
                    this.step++;
                    this.updateProgress();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },
            prevStep() {
                if (this.step > 1) {
                    this.step--;
                    this.updateProgress();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },
            submitForm(e) {
                // Required fields on hidden steps won't surface browser validation
                // errors — jump back to the step containing the first invalid field.
                const form = document.getElementById('main-feedback-form');
                if (!form.checkValidity()) {
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        for (let s = 1; s <= 4; s++) {
                            if (firstInvalid.closest(`[x-show="step === ${s}"]`)) { this.step = s; break; }
                        }
                        this.updateProgress();
                        setTimeout(() => form.reportValidity(), 100);
                        return;
                    }
                }
                form.submit();
            },
            updateProgress() {
                this.progress = (this.step / this.totalSteps) * 100;
            }
        }
    }
</script>
@endsection
