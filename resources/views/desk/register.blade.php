<x-layouts.portal title="Register a walk-in">
    <x-slot:header>
        <x-page-header eyebrow="Registration desk" title="Register a walk-in" :back="route('desk.index')"
            description="For someone who arrives without a registration. Next, you take the payment by M-Pesa; the badge prints once it is paid." />
    </x-slot:header>

    <form method="POST" action="{{ route('desk.register.store') }}" class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        @csrf

        <x-card title="Their details" description="As they should appear on the badge. If the email already has an account, it is used.">
            <div class="grid gap-5 sm:grid-cols-[8rem_1fr_1fr]">
                <x-form.select name="title" label="Title" placeholder="—" :options="array_combine($titles, $titles)" />
                <x-form.input name="first_name" label="First name" required autofocus />
                <x-form.input name="last_name" label="Last name" required />
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-form.input name="email" type="email" label="Email address" icon="mail" required hint="They get an email to set a password, for their badge and CPD certificate." />
                <x-form.input name="phone" type="tel" label="Phone number" icon="phone" placeholder="+255 712 345 678" required />
                <x-form.input name="institution" label="Institution" required />
                <x-form.input name="profession" label="Profession" placeholder="e.g. Physiotherapist" required />
                <x-form.select name="country" label="Country" icon="globe" :options="$countries" value="TZ" placeholder="Select" required />
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="Category">
                <div class="space-y-2">
                    @foreach ($categories as $category)
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-ink-200 p-4 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="category" value="{{ $category->id }}" @checked((int) old('category', $categories->first()?->id) === $category->id) class="h-4 w-4 accent-brand-700">
                                <span class="font-semibold text-ink-900">{{ $category->name }}</span>
                            </span>
                            <span class="font-bold text-ink-900">{{ $category->formattedAmount() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('category') <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror
            </x-card>

            <x-card title="Needs (optional)">
                <div class="space-y-5">
                    <x-form.input name="dietary_needs" label="Dietary needs" placeholder="e.g. Vegetarian" />
                    <x-form.input name="accessibility_needs" label="Accessibility needs" placeholder="e.g. Wheelchair access" />
                </div>
            </x-card>

            <x-button size="lg" icon="user-plus" class="w-full">Register and take payment</x-button>
        </div>
    </form>
</x-layouts.portal>
