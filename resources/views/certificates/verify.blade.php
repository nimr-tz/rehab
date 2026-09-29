<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification — {{ config('conference.short_name') }} {{ config('conference.year') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4">

    {{-- Top conference bar --}}
    <div class="w-full max-w-lg mb-6 flex items-center justify-center gap-3">
        <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}" class="h-10">
        <div class="text-center">
            <p class="text-xs font-black uppercase tracking-widest text-slate-500">{{ config('conference.host') }}</p>
            <p class="text-sm font-bold text-slate-700">{{ config('conference.name') }} {{ config('conference.year') }}</p>
        </div>
    </div>

    <div class="max-w-lg w-full">
        @if($valid && $certificate)
            {{-- ✅ Valid --}}
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-blue-100">

                <div class="bg-[#2563eb] px-8 py-7 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight">Certificate Verified</h1>
                    <p class="text-blue-100 text-sm mt-1 font-medium">This certificate is authentic and valid</p>
                </div>

                <div class="p-8">

                    {{-- Certificate type badge --}}
                    <div class="text-center mb-6">
                        <span class="inline-block px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest
                            @if($certificate->type === 'attendance_full') bg-blue-50 text-blue-700 border border-blue-200
                            @elseif($certificate->type === 'oral_presentation') bg-indigo-50 text-indigo-700 border border-indigo-200
                            @elseif($certificate->type === 'poster_presentation') bg-purple-50 text-purple-700 border border-purple-200
                            @else bg-slate-100 text-slate-600 border border-slate-200
                            @endif">
                            {{ $certificate->type_label }}
                        </span>
                    </div>

                    {{-- Holder --}}
                    <div class="text-center mb-6">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Certificate Holder</p>
                        <h2 class="text-2xl font-black text-slate-900">{{ $user->title }} {{ $user->full_name }}</h2>
                        @if(!empty($user->affiliation))
                            <p class="text-slate-500 text-sm mt-1 font-medium">{{ $user->affiliation }}</p>
                        @endif
                    </div>

                    <div class="h-px bg-slate-100 my-6"></div>

                    {{-- Presentation detail (presenter certs only) --}}
                    @if($certificate->isPresenterCertificate() && $certificate->abstract)
                    <div class="mb-6 p-4 bg-blue-50 rounded-2xl border border-blue-100">
                        <p class="text-xs font-black uppercase tracking-widest text-blue-500 mb-2">
                            {{ $certificate->type === 'oral_presentation' ? 'Oral Presentation' : 'Poster Presentation' }}
                        </p>
                        <p class="font-semibold text-slate-900 italic text-sm leading-relaxed">"{{ $certificate->abstract->title }}"</p>
                    </div>
                    @endif

                    {{-- Details rows --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-100">
                                <svg class="w-5 h-5 text-[#2563eb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Conference</p>
                                <p class="font-bold text-slate-900">{{ $conference['edition'] }} {{ $conference['name'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-100">
                                <svg class="w-5 h-5 text-[#2563eb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Venue &amp; Dates</p>
                                <p class="font-bold text-slate-900">{{ $conference['display_dates'] }}</p>
                                <p class="text-sm text-slate-500">{{ $conference['venue'] }}, {{ $conference['city'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-100">
                                <svg class="w-5 h-5 text-[#2563eb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Issue Date</p>
                                <p class="font-bold text-slate-900">{{ $certificate->issued_at->format('F j, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Cert number --}}
                    <div class="mt-6 p-4 bg-slate-50 rounded-2xl text-center border border-slate-100">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Certificate Number</p>
                        <p class="font-mono font-black text-[#2563eb] text-lg tracking-wider">{{ $certificate->certificate_number }}</p>
                    </div>
                </div>

                <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs text-slate-400 font-medium">Verified {{ now()->format('M d, Y') }}</p>
                    <span class="inline-flex items-center gap-1.5 text-xs font-black text-[#2563eb]">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ config('conference.host_short') }} Authenticated
                    </span>
                </div>
            </div>

        @elseif($certificate && $isRevoked)
            {{-- ⚠️ Revoked --}}
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-amber-100">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-7 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-white">Certificate Revoked</h1>
                    <p class="text-amber-100 text-sm mt-1 font-medium">This certificate is no longer valid</p>
                </div>
                <div class="p-8 text-center">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Original Holder</p>
                    <h2 class="text-xl font-black text-slate-900 mb-6">{{ $user->title }} {{ $user->full_name }}</h2>
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 mb-6 text-left">
                        <p class="text-sm text-amber-800 font-semibold"><strong>Reason:</strong> {{ $revokedReason ?? 'No reason provided' }}</p>
                        <p class="text-xs text-amber-600 mt-1">Revoked on {{ $certificate->revoked_at->format('F j, Y') }}</p>
                    </div>
                    <a href="mailto:{{ config('conference.contact_email', config('conference.contact_email')) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-[#2563eb] text-white rounded-2xl font-bold text-sm hover:bg-blue-700 transition-colors">
                        Contact Support
                    </a>
                </div>
            </div>

        @else
            {{-- ❌ Invalid --}}
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-rose-100">
                <div class="bg-gradient-to-r from-rose-500 to-rose-600 px-8 py-7 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black text-white">Verification Failed</h1>
                    <p class="text-rose-100 text-sm mt-1 font-medium">This certificate could not be verified</p>
                </div>
                <div class="p-8 text-center">
                    <p class="text-slate-600 mb-6 font-medium">{{ $message ?? "The certificate number doesn't match any record. Please check the certificate and try again." }}</p>
                    <a href="{{ config('app.url') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-[#2563eb] text-white rounded-2xl font-bold text-sm hover:bg-blue-700 transition-colors">
                        Go to Conference Website
                    </a>
                </div>
            </div>
        @endif

        <p class="text-center text-slate-400 text-xs mt-6 font-medium">{{ config('conference.host') }} &mdash; {{ config('conference.name') }} {{ config('conference.year') }}</p>
    </div>
</body>
</html>
