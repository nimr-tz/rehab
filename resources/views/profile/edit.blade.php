<x-layouts.portal title="Profile">
    <x-slot:header>
        <x-page-header title="Profile" description="Your details as they appear on badges, certificates and letters." />
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <x-card title="Personal details">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-[7rem_1fr_1fr]">
                    <x-form.select name="title" label="Title" placeholder="—" :options="array_combine($titles, $titles)" :value="$user->title" />
                    <x-form.input name="first_name" label="First name" :value="$user->first_name" required />
                    <x-form.input name="last_name" label="Last name" :value="$user->last_name" required />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="phone" type="tel" label="Phone" icon="phone" :value="$user->phone" required />
                    <x-form.select name="country" label="Country" icon="globe" :options="$countries" :value="$user->country" required />
                    <x-form.input name="institution" label="Institution" :value="$user->institution" />
                    <x-form.input name="profession" label="Profession" :value="$user->profession" />
                </div>
                <div>
                    <span class="label">Email address</span>
                    <p class="rounded-control border border-ink-100 bg-ink-50 px-4 py-3 text-[15px] text-ink-600">{{ $user->email }}</p>
                    <p class="mt-1.5 text-xs text-ink-500">To change your email, contact {{ $summit->get('contact_email') }}.</p>
                </div>
                <x-button icon="check">Save details</x-button>
            </form>
        </x-card>

        <x-card title="Change password">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <x-form.password name="current_password" label="Current password" autocomplete="current-password" required />
                <x-form.password name="password" label="New password" autocomplete="new-password" required hint="8 or more characters with letters and numbers." />
                <x-form.password name="password_confirmation" id="password_confirmation" label="Confirm new password" autocomplete="new-password" required />
                <x-button variant="secondary" icon="lock">Change password</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.portal>
