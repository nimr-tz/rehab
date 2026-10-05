<x-auth.shell title="Sign in" heading="Welcome back"
    :intro="'Sign in to the '.$summit->get('organiser').' Events Portal to continue with the '.$summit->get('year').' Summit.'">

    @if (session('status'))
        <x-alert tone="success" class="mb-5">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-form.input name="email" type="email" label="Email address" icon="user"
                      placeholder="you@example.com" autocomplete="email" required autofocus />

        <x-form.password name="password" placeholder="Enter your password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-ink-300 accent-brand-700">
                Keep me signed in
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Forgot password?</a>
        </div>

        <x-auth.submit>Sign in</x-auth.submit>
    </form>

    <div class="my-6 flex items-center gap-4 text-xs text-ink-400">
        <span class="h-px flex-1 bg-ink-200"></span>or<span class="h-px flex-1 bg-ink-200"></span>
    </div>

    <a href="{{ route('register') }}"
       class="flex h-12 w-full items-center justify-center gap-2 rounded-control border border-brand-200 bg-white text-[15px] font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-50">
        <x-icon name="user-plus" />
        New here? Create an account
    </a>

    @if (config('app.demo'))
        <div class="mt-8 rounded-2xl border border-dashed border-sun-400 bg-sun-50 p-4">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-sun-800">Demo · sign in as</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach (\Database\Seeders\DemoSeeder::ACCOUNTS as $email => [$label, $role])
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="password" value="{{ \Database\Seeders\DemoSeeder::PASSWORD }}">
                        <button class="w-full rounded-xl bg-white px-3 py-2 text-left text-xs shadow-soft ring-1 ring-sun-200 transition hover:ring-brand-300">
                            <span class="block font-semibold text-ink-900">{{ $label }}</span>
                            <span class="block text-ink-500">{{ $role }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
</x-auth.shell>
