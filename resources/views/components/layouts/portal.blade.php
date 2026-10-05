@props(['title'])

@php
    $user = auth()->user();
    $roles = $user->getRoleNames()
        ->map(fn ($role) => \App\Enums\Role::tryFrom($role)?->label() ?? $role);

    // Each phase adds its own entries here.
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
    ];
@endphp

<x-layouts.public class="bg-canvas" :title="$title.' · '.$summit->get('organiser').' Events Portal'">
    <div x-data="{ menu: false }" class="min-h-screen md:grid md:grid-cols-[250px_1fr]">

        {{-- Sidebar (drawer on small screens) --}}
        <div x-show="menu" x-cloak @click="menu = false" class="fixed inset-0 z-40 bg-ink-950/30 md:hidden"></div>
        {{-- Hidden off-canvas on small screens; the inline style slides it in when the menu opens. --}}
        <aside :style="menu ? 'translate: 0' : ''"
               class="fixed inset-y-0 left-0 z-50 flex w-[250px] -translate-x-full flex-col border-r border-ink-100 bg-white p-4 transition-[translate] md:static md:translate-x-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-2 pb-6 pt-1">
                <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-9 w-auto">
                <span class="leading-tight">
                    <span class="block text-[13px] font-bold text-ink-900">{{ $summit->get('organiser') }}</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Events Portal</span>
                </span>
            </a>

            <nav class="flex-1 space-y-0.5 text-sm" aria-label="Portal">
                @foreach ($navigation as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           'flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                           'bg-brand-50 font-semibold text-brand-800' => $active,
                           'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! $active,
                       ])>
                        <x-icon :name="$item['icon']" :class="$active ? 'h-5 w-5 text-brand-600' : 'h-5 w-5 text-ink-400'" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-ink-100 pt-4">
                <div class="flex items-center gap-3 px-2">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">
                        {{ mb_substr($user->first_name, 0, 1) }}{{ mb_substr($user->last_name, 0, 1) }}
                    </span>
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-sm font-semibold text-ink-900">{{ $user->name }}</span>
                        <span class="block truncate text-xs text-ink-500">{{ $roles->implode(' · ') }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-ink-600 transition hover:bg-ink-50 hover:text-ink-900">
                        <x-icon name="logout" class="h-5 w-5 text-ink-400" /> Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            {{-- Top bar on small screens --}}
            <div class="sticky top-0 z-30 flex items-center justify-between border-b border-ink-100 bg-white/95 px-4 py-3 backdrop-blur md:hidden">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-8 w-auto">
                    <span class="text-sm font-bold text-ink-900">Events Portal</span>
                </a>
                <button type="button" @click="menu = true" aria-label="Open menu"
                        class="grid h-10 w-10 place-items-center rounded-xl text-ink-700 hover:bg-ink-50">
                    <x-icon name="menu" class="h-6 w-6" />
                </button>
            </div>

            <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-8 sm:py-10">
                @if (session('status'))
                    <x-alert tone="success">{{ session('status') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.public>
