<x-auth.shell title="Choose a new password" heading="Choose a new password"
    intro="Pick a password you have not used here before.">

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.input name="email" type="email" label="Email address" icon="mail"
                      :value="$request->email" autocomplete="email" required />

        <x-form.password name="password" label="New password" placeholder="At least 8 characters"
                         autocomplete="new-password" required autofocus
                         hint="Use 8 or more characters with letters and numbers." />

        <x-form.password name="password_confirmation" id="password_confirmation" label="Confirm new password"
                         placeholder="Repeat your password" autocomplete="new-password" required />

        <x-auth.submit>Save new password</x-auth.submit>
    </form>
</x-auth.shell>
