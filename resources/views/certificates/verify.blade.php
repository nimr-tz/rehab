<x-layouts.public class="bg-white" title="Verify a certificate" description="Check that a certificate of attendance from the Rehabilitation Summit is genuine.">
    <x-public.nav />

    <main class="wrap max-w-2xl py-16">
        <p class="eyebrow">Certificates</p>
        <h1 class="section-title">Verify a certificate</h1>

        @if ($valid)
            @php $edition = $registration->edition; @endphp
            <div class="mt-8 rounded-card border border-emerald-100 bg-emerald-50 p-6">
                <p class="flex items-center gap-2 font-bold text-emerald-800"><x-icon name="check-circle" class="h-6 w-6" /> This certificate is genuine.</p>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-emerald-800/70">Certificate</dt><dd class="font-mono font-semibold text-ink-900">{{ $number }}</dd></div>
                    <div><dt class="text-emerald-800/70">Issued to</dt><dd class="font-semibold text-ink-900">{{ $registration->user->name }}</dd></div>
                    <div><dt class="text-emerald-800/70">Summit</dt><dd class="text-ink-900">{{ trim($edition->ordinal.' '.$edition->name.' '.$edition->year) }}</dd></div>
                    <div><dt class="text-emerald-800/70">Days attended</dt><dd class="text-ink-900">{{ $days->map(fn ($d) => $d->format('j M'))->implode(', ') ?: '—' }}</dd></div>
                </dl>
            </div>
        @else
            <div class="mt-8 rounded-card border border-red-100 bg-red-50 p-6">
                <p class="flex items-center gap-2 font-bold text-red-800"><x-icon name="x-circle" class="h-6 w-6" /> We could not find this certificate.</p>
                <p class="mt-2 text-sm text-red-900">Check the certificate number and code against the certificate. If it still does not match, the certificate was not issued by this portal.</p>
            </div>
        @endif
    </main>

    <x-public.footer />
</x-layouts.public>
