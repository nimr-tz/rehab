<x-auth.shell title="Confirm your email" heading="Check your email">

    <div class="space-y-5 text-center">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-brand-50 text-brand-700">
            <x-icon name="mail" class="h-8 w-8" />
        </div>

        <p class="text-[15px] leading-relaxed text-ink-600">
            We sent a confirmation link to
            <span class="font-semibold text-ink-900">{{ auth()->user()->email }}</span>.
            Open it to activate your account. If it has not arrived, check your spam folder.
        </p>

        @if (session('status') === 'verification-link-sent')
            <x-alert tone="success" class="text-left">A new confirmation link has been sent.</x-alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-auth.submit>Send the link again</x-auth.submit>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-ink-500 hover:text-ink-800">
                Wrong address? Sign out and register again
            </button>
        </form>
    </div>
</x-auth.shell>
