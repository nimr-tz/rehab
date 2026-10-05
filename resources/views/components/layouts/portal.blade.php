@props(['title'])

@php
    $user = auth()->user();
    $roles = $user->getRoleNames()->map(fn ($role) => \App\Enums\Role::tryFrom($role)?->label() ?? $role);
    $sections = \App\Support\Navigation::for($user);
    $notifications = $user->notifications()->latest()->limit(8)->get();
    $unread = $user->unreadNotifications()->count();
    $searchHint = \App\Support\Navigation::searchHint($user);
@endphp

<x-layouts.public class="bg-canvas" :title="$title.' · '.$summit->get('organiser').' Events Portal'">
    <div x-data="{ menu: false }" class="min-h-screen lg:grid lg:grid-cols-[264px_minmax(0,1fr)]">

        {{-- Sidebar: a drawer on small screens. The inline style slides it in when the menu opens. --}}
        <div x-show="menu" x-cloak @click="menu = false" class="fixed inset-0 z-40 bg-ink-950/40 lg:hidden"></div>
        <aside :style="menu ? 'translate: 0' : ''"
               class="fixed inset-y-0 left-0 z-50 flex w-[264px] -translate-x-full flex-col bg-brand-700 text-white transition-[translate] lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-5 pb-6 pt-6">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white shadow-soft">
                    <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="" class="h-9 w-auto">
                </span>
                <span class="leading-tight">
                    <span class="block text-[17px] font-bold">{{ $summit->shortTitle() }}</span>
                    <span class="block text-xs text-brand-200">{{ $summit->dateRange() ?? 'Dates to be announced' }}</span>
                </span>
            </a>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-4 text-[15px]" aria-label="Portal">
                @foreach ($sections as $section)
                    <div class="space-y-1">
                        @if ($section['label'])
                            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-300">{{ $section['label'] }}</p>
                        @endif
                        @foreach ($section['items'] as $item)
                            @php $active = request()->routeIs($item['active']) && (! isset($item['query']) || request()->query($item['query'][0]) === $item['query'][1]) && (! isset($item['not_query']) || request()->query($item['not_query'][0]) !== $item['not_query'][1]); @endphp
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif
                               @class([
                                   'group flex items-center gap-3 rounded-xl px-3 py-2.5 transition',
                                   'bg-white/12 font-semibold text-white shadow-[inset_0_0_0_1px_rgb(255_255_255/0.06)]' => $active,
                                   'text-white/80 hover:bg-white/8 hover:text-white' => ! $active,
                               ])>
                                <span @class(['h-1.5 w-1.5 shrink-0 rounded-full', 'bg-sun-400' => $active, 'bg-white/30 group-hover:bg-white/60' => ! $active])></span>
                                <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                @if (! empty($item['count']))
                                    <span class="min-w-6 rounded-full bg-sun-400 px-2 py-0.5 text-center text-[11px] font-bold text-ink-900">{{ $item['count'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-white/10 p-4">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-white/8">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-sun-400 text-sm font-bold text-ink-900">{{ $user->initials() }}</span>
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                        <span class="block truncate text-xs text-brand-200">{{ $roles->implode(' · ') }}</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-white/70 transition hover:bg-white/8 hover:text-white">
                        <x-icon name="logout" class="h-4 w-4" /> Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 border-b border-ink-100 bg-canvas/90 backdrop-blur">
                <div class="mx-auto flex max-w-[1240px] items-center gap-4 px-4 py-4 sm:px-8 lg:py-5">
                    <button type="button" @click="menu = true" aria-label="Open menu"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-ink-700 shadow-soft lg:hidden">
                        <x-icon name="menu" class="h-6 w-6" />
                    </button>

                    <div class="min-w-0 flex-1">
                        {{ $header ?? '' }}
                    </div>

                    <form method="GET" action="{{ route('search') }}" class="relative hidden w-72 md:block" role="search">
                        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                        <input name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="{{ $searchHint }}"
                               aria-label="Search" class="h-11 w-full rounded-control border border-ink-200 bg-white pl-11 pr-4 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                    </form>

                    {{-- Notifications --}}
                    <div x-data="{ open: false }" class="relative shrink-0" @keydown.escape="open = false">
                        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-label="Notifications"
                                class="relative grid h-11 w-11 place-items-center rounded-xl border border-ink-200 bg-white text-brand-700 transition hover:border-brand-300">
                            <x-icon name="bell" class="h-5 w-5" />
                            @if ($unread)
                                <span class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-ember-500 px-1 text-[11px] font-bold text-white">{{ $unread }}</span>
                            @endif
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false"
                             class="absolute right-0 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-lift">
                            <div class="flex items-center justify-between border-b border-ink-100 px-4 py-3">
                                <p class="font-semibold text-ink-900">Notifications</p>
                                @if ($unread)
                                    <form method="POST" action="{{ route('notifications.read') }}">
                                        @csrf
                                        <button class="text-xs font-semibold text-brand-700 hover:underline">Mark all as read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse ($notifications as $notification)
                                    @php
                                        $data = $notification->data;
                                        $tint = match ($data['tone'] ?? 'info') {
                                            'success' => 'bg-emerald-50 text-emerald-700', 'danger' => 'bg-red-50 text-red-700',
                                            'warning' => 'bg-sun-100 text-sun-800', default => 'bg-brand-50 text-brand-700',
                                        };
                                    @endphp
                                    <a href="{{ route('notifications.open', $notification->id) }}" class="flex gap-3 border-b border-ink-100 px-4 py-3 last:border-0 hover:bg-ink-50 {{ $notification->read_at ? '' : 'bg-brand-50/40' }}">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $tint }}"><x-icon :name="$data['icon'] ?? 'info'" class="h-4 w-4" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-ink-900">{{ $data['title'] ?? 'Update' }}</span>
                                            <span class="block truncate text-xs text-ink-500">{{ $data['body'] ?? '' }}</span>
                                            <span class="mt-0.5 block text-[11px] text-ink-400">{{ $notification->created_at->diffForHumans() }}</span>
                                        </span>
                                        @unless ($notification->read_at)<span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-ember-500"></span>@endunless
                                    </a>
                                @empty
                                    <p class="px-4 py-8 text-center text-sm text-ink-500">You are all caught up.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-[1240px] space-y-6 px-4 py-6 sm:px-8 sm:py-8">
                @if (session('status'))
                    <x-alert tone="success">{{ session('status') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.public>
