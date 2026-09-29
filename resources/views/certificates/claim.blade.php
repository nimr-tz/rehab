<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Your Certificate - {{ $conference['short_name'] }} {{ $conference['year'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('conference.logo_mark_path')) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 flex items-center justify-center p-4">
    <div class="max-w-lg w-full">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-center">
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}" class="h-14 mx-auto mb-3 bg-white rounded-full p-1.5">
                <h1 class="text-2xl font-bold text-white">Claim Your Certificate</h1>
                <p class="text-blue-100 text-sm mt-1">{{ $conference['edition'] }} {{ $conference['name'] }}</p>
            </div>

            <div class="p-8">
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm font-medium">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl text-sm font-medium">
                        {{ session('success') }}
                    </div>
                @endif

                @if(!$released)
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl text-sm font-medium">
                        Certificates unlock on {{ $releaseAt->format('F j, Y \a\t H:i') }}. Please check back then.
                    </div>
                @endif

                <p class="text-slate-500 text-sm leading-relaxed mb-1">
                    Before your certificate is issued, we will ask you to fill in a short conference
                    feedback form. It takes under a minute, and your certificate downloads immediately after.
                </p>

                <div class="mt-6">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Enter the code printed under the QR code on your badge</p>

                    <form method="POST" action="{{ route('certificate.claim.download') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="text-xs font-bold text-slate-500 uppercase tracking-widest">Badge code</label>
                            <input id="code" name="code" type="text" required autocomplete="off"
                                   value="{{ old('code') }}"
                                   placeholder="e.g. {{ config('conference.qr_prefix') }}-XXXXXXXXXXXX"
                                   class="mt-2 w-full h-14 px-5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm uppercase focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none">
                            @error('code')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="w-full h-14 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl font-bold text-sm uppercase tracking-widest transition-all shadow-lg shadow-blue-500/25">
                            Get My Certificate
                        </button>
                    </form>
                    <p class="mt-4 text-xs text-slate-400">Lost your badge? Contact the organizers with your registration details.</p>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-400">
                        Registered with your own account?
                        <a href="{{ route('certificate.index') }}" class="text-blue-600 hover:underline font-bold">Log in and download here</a>
                    </p>
                </div>
            </div>
        </div>

        <p class="text-center text-blue-200/60 text-xs mt-6">
            Questions? Contact <a href="mailto:{{ config('conference.contact_email') }}" class="underline">{{ config('conference.contact_email') }}</a>
        </p>
    </div>
</body>
</html>
