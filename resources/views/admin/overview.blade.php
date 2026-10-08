@php
    use App\Support\Countries;

    $tzs = $money['TZS'];
    $usd = $money['USD'];
    $millions = fn ($v) => $v >= 1_000_000 ? number_format($v / 1_000_000, 1).'M' : number_format($v);
    $countryMax = max(1, $countries->max() ?? 1);
    $watchStyles = [
        'good' => ['bg-olive-50 border-olive-200', 'text-olive-700', 'Good'],
        'watch' => ['bg-sun-50 border-sun-200', 'text-sun-800', 'Watch'],
        'act' => ['bg-coral-50 border-coral-200', 'text-red-700', 'Act'],
    ];

    // Executives read the summary only: the screens behind it are for admins.
    $admin = auth()->user()->hasRole(\App\Enums\Role::Admin->value);
    $link = fn (string $route, mixed $parameters = []) => $admin ? route($route, $parameters) : null;
@endphp

<x-layouts.portal title="Executive summary">
    <x-slot:header>
        <x-page-header title="Executive summary" description="Registration, revenue and science at a glance">
            @if ($admin)
                <x-button variant="secondary" size="sm" :href="route('admin.settings.edit')" icon="cog">Settings</x-button>
            @endif
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="Registrations" :value="number_format($total)"
            :hint="$target ? round($total / $target * 100).'% of '.number_format($target).' target' : $confirmed.' confirmed'"
            :progress="$target ? $total / $target * 100 : ($total ? $confirmed / $total * 100 : 0)" :href="$link('admin.participants.index')" />
        <x-kpi label="Revenue verified" :value="'TZS '.$millions($tzs['verified'])"
            :hint="($tzs['expected'] ? round($tzs['verified'] / $tzs['expected'] * 100) : 0).'% of '.$millions($tzs['expected']).' expected · USD '.number_format($usd['verified'])"
            :progress="$tzs['expected'] ? $tzs['verified'] / $tzs['expected'] * 100 : 0" bar="#45582e" :href="$link('finance.payments.index', ['status' => 'verified'])" />
        <x-kpi label="Abstracts" :value="number_format($abstracts)" :hint="'+'.$abstractsThisWeek.' this week'" hint-tone="warning" :progress="100" bar="#d69a00" :href="$link('scientific.abstracts.index')" />
        <x-kpi label="Countries" :value="$countries->count()" :hint="$international.'% international'" hint-tone="warning" :progress="$international" bar="#bd520a" />
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <x-card>
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold text-ink-900">Cumulative registrations{{ $pace ? ' vs pace' : '' }}</h2>
                <p class="flex gap-4 text-xs text-ink-600">
                    <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-5 bg-brand-700"></span>Actual · {{ number_format($total) }}</span>
                    @if ($pace)<span class="inline-flex items-center gap-1.5"><span class="h-0 w-5 border-t-2 border-dashed border-ember-600"></span>Pace to {{ number_format($target) }} · {{ number_format($pace) }} today</span>@endif
                </p>
            </div>
            @if ($points)
                <x-chart.trend class="mt-5" :points="$points" :target="$pace" :height="220" />
            @else
                <x-empty icon="chart" title="No registrations yet" class="!py-10" />
            @endif
        </x-card>

        <x-card title="Registrant mix">
            <x-chart.donut :segments="$mix" :total="number_format($total)" caption="registered" :size="170" />
        </x-card>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <x-card>
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold text-ink-900">Paid, waiting and expected</h2>
                <span class="text-xs text-ink-500">By currency</span>
            </div>
            <div class="mt-5 space-y-6">
                @foreach ($money as $currency => $m)
                    @php
                        $expected = max(1, $m['expected']);
                        $unpaid = max(0, $m['expected'] - $m['verified'] - $m['submitted']);
                    @endphp
                    <div>
                        <p class="mb-2 text-sm font-bold text-ink-800">{{ $currency }}</p>
                        <div class="flex h-9 gap-0.5 overflow-hidden rounded-xl">
                            <div class="bg-olive-700" style="width: {{ $m['verified'] / $expected * 100 }}%" title="Verified: {{ $currency }} {{ number_format($m['verified']) }}"></div>
                            <div class="bg-sun-400" style="width: {{ $m['submitted'] / $expected * 100 }}%" title="Awaiting check: {{ $currency }} {{ number_format($m['submitted']) }}"></div>
                            <div class="flex-1 bg-ink-100" title="Not yet paid: {{ $currency }} {{ number_format($unpaid) }}"></div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div class="border-l-[3px] border-olive-700 pl-3"><p class="text-xs font-semibold text-ink-500">Verified</p><p class="text-lg font-extrabold text-ink-900">{{ $millions($m['verified']) }}</p></div>
                            <div class="border-l-[3px] border-sun-400 pl-3"><p class="text-xs font-semibold text-ink-500">Submitted, awaiting check</p><p class="text-lg font-extrabold text-ink-900">{{ $millions($m['submitted']) }}</p></div>
                            <div class="border-l-[3px] border-ink-300 pl-3"><p class="text-xs font-semibold text-ink-500">Expected, not yet paid</p><p class="text-lg font-extrabold text-ink-900">{{ $millions($unpaid) }}</p></div>
                            <div class="border-l-[3px] border-brand-300 pl-3"><p class="text-xs font-semibold text-ink-500">Waived</p><p class="text-lg font-extrabold text-ink-900">{{ $millions($m['waived']) }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="What leadership should watch">
            <ul class="space-y-3">
                @foreach ($watch as [$heading, $text, $level])
                    @php [$box, $tag, $word] = $watchStyles[$level]; @endphp
                    <li class="rounded-2xl border p-4 {{ $box }}">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-bold text-ink-900">{{ $heading }}</p>
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.14em] {{ $tag }}">{{ $word }}</span>
                        </div>
                        <p class="mt-1 text-sm text-ink-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card title="Registrations by country">
            <x-chart.bars :rows="$countries->take(7)->map(fn ($n, $code) => ['label' => Countries::name($code) ?? $code, 'value' => $n, 'color' => $code === 'TZ' ? '#024f6d' : '#d69a00'])->values()->all()" :max="$countryMax" />
        </x-card>

        <x-card title="Top institutions" :padding="false">
            <ol class="divide-y divide-ink-100">
                @foreach ($institutions as $name => $count)
                    <li class="flex items-center gap-4 px-6 py-3.5">
                        <span class="w-6 text-sm font-bold text-ink-400">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="min-w-0 flex-1 truncate font-semibold text-brand-700">{{ $name }}</span>
                        <span class="text-xs text-ink-500">{{ $abstractsByInstitution[$name] ?? 0 }} {{ \Illuminate\Support\Str::plural('abstract', $abstractsByInstitution[$name] ?? 0) }}</span>
                        <span class="w-10 text-right font-extrabold text-ink-900">{{ $count }}</span>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    <x-card title="Latest registrations" :padding="false">
        @if ($admin)
            <x-slot:actions><x-button variant="ghost" size="sm" :href="route('admin.participants.index')">All participants</x-button></x-slot:actions>
        @endif
        <ul class="divide-y divide-ink-100">
            @forelse ($recent as $registration)
                <li>
                    <{{ $admin ? 'a' : 'div' }} @if ($admin) href="{{ route('admin.participants.show', $registration) }}" @endif @class(['flex items-center gap-3 px-6 py-3', 'hover:bg-ink-50' => $admin])>
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">{{ $registration->user->initials() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ink-900">{{ $registration->user->name }}</span>
                            <span class="block text-xs text-ink-500">{{ $registration->category->name }} · {{ $registration->created_at->diffForHumans() }}</span>
                        </span>
                        <x-status :tone="$registration->status->tone()">{{ $registration->status->label() }}</x-status>
                    </{{ $admin ? 'a' : 'div' }}>
                </li>
            @empty
                <li class="px-6 py-5 text-sm text-ink-500">No registrations yet.</li>
            @endforelse
        </ul>
    </x-card>
</x-layouts.portal>
