<x-auth.shell title="Create an account" heading="Create your account" :wide="true"
    :intro="'One account for registration, payment and abstracts for the '.$summit->title().'.'">

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-[7rem_1fr_1fr]">
            <x-form.select name="title" label="Title" placeholder="—"
                           :options="array_combine($titles, $titles)" />
            <x-form.input name="first_name" label="First name" autocomplete="given-name" required autofocus />
            <x-form.input name="last_name" label="Last name" autocomplete="family-name" required />
        </div>

        <x-form.input name="email" type="email" label="Email address" icon="mail"
                      placeholder="you@example.com" autocomplete="email" required
                      hint="We send a confirmation link to this address." />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="phone" type="tel" label="Phone number" icon="phone"
                          placeholder="+255 712 345 678" autocomplete="tel" required />
            <x-form.select name="country" label="Country" icon="globe" :options="$countries"
                           placeholder="Select" autocomplete="country" required />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.password name="password" autocomplete="new-password" required
                             hint="Use 8 or more characters with letters and numbers." />
            <x-form.password name="password_confirmation" id="password_confirmation" label="Confirm password"
                             autocomplete="new-password" required />
        </div>

        <div>
            <label class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-600">
                <input type="checkbox" name="terms" value="1" @checked(old('terms')) required
                       class="mt-1 h-4 w-4 shrink-0 rounded border-ink-300 accent-brand-700">
                <span>I confirm that these details are correct. My name appears as entered here on my badge and certificates.</span>
            </label>
            @error('terms')
                <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <x-auth.submit>Create account</x-auth.submit>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">Sign in</a>
    </p>
</x-auth.shell>
