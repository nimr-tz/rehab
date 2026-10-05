<x-auth.shell title="Reset your password" heading="Forgot your password?"
    intro="Enter the email address you registered with and we will send you a link to choose a new password.">

    @if (session('status'))
        <x-alert tone="success" class="mb-5">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-form.input name="email" type="email" label="Email address" icon="mail"
                      placeholder="you@example.com" autocomplete="email" required autofocus />

        <x-auth.submit>Send reset link</x-auth.submit>
    </form>

    <a href="{{ route('login') }}" class="mt-6 flex items-center justify-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Back to sign in
    </a>
</x-auth.shell>
