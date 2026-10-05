@php
    use App\Support\Palette;

    $channelTotal = max(1, $channels->sum());
@endphp

<x-layouts.portal title="Finance desk">
    <x-slot:header>
        <x-page-header title="Finance operations desk" :description="$submitted->count().' '.\Illuminate\Support\Str::plural('payment', $submitted->count()).' waiting for verification'">
            <x-button variant="secondary" size="sm" :href="route('finance.payments.export')" icon="download">Export CSV</x-button>
        </x-page-header>
    </x-slot:header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="Verified" :value="number_format($verifiedCount)" tint="bg-olive-50"
            :hint="'TZS '.number_format($verifiedTotals['TZS'] ?? 0).' · USD '.number_format($verifiedTotals['USD'] ?? 0)" :href="route('finance.payments.index', ['status' => 'verified'])" />
        <x-kpi label="Awaiting verification" :value="$submitted->count()" tint="bg-ember-50"
            :hint="$olderThan3.' older than 3 days'" :hint-tone="$olderThan3 ? 'danger' : 'muted'" :href="route('finance.payments.index')" />
        <x-kpi label="Not yet paid" :value="$notPaid" hint="Reference issued, no payment yet" />
        <x-kpi label="Rejected" :value="$rejected" hint="Asked to resubmit" :href="route('finance.payments.index', ['status' => 'rejected'])" />
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <x-card :padding="false">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 pb-4 pt-6">
                <h2 class="text-lg font-bold text-ink-900">Verification queue</h2>
                <div class="flex gap-1.5 text-xs font-bold">
                    <a href="{{ route('finance.payments.index') }}" class="rounded-full bg-brand-700 px-3 py-1.5 text-white">All {{ $submitted->count() }}</a>
                    <a href="{{ route('finance.payments.index', ['method' => 'mobile_money']) }}" class="rounded-full bg-ink-100 px-3 py-1.5 text-ink-700 hover:bg-ink-200">Mobile money {{ $mobileCount }}</a>
                    <a href="{{ route('finance.payments.index', ['method' => 'bank_transfer']) }}" class="rounded-full bg-ink-100 px-3 py-1.5 text-ink-700 hover:bg-ink-200">Bank {{ $bankCount }}</a>
                </div>
            </div>
            @if ($submitted->isEmpty())
                <x-empty icon="check-circle" title="Queue is clear" class="!py-8">Every submitted payment has been reviewed.</x-empty>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-y border-ink-100 text-[11px] font-bold uppercase tracking-[0.14em] text-ink-500">
                            <tr><th class="px-6 py-3">Payer</th><th class="px-3 py-3">Amount</th><th class="px-3 py-3">Method</th><th class="px-3 py-3">Submitted</th><th class="px-6 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($submitted->take(7) as $payment)
                                @php $age = (int) $payment->created_at->diffInDays(now()); @endphp
                                <tr>
                                    <td class="px-6 py-3.5">
                                        <p class="font-semibold text-ink-900">{{ $payment->registration->user->name }}</p>
                                        <p class="font-mono text-xs text-ink-500">{{ $payment->registration->reference }} · {{ $payment->transaction_reference }}</p>
                                    </td>
                                    <td class="px-3 py-3.5 font-extrabold text-ink-900">{{ $payment->formattedAmount() }}</td>
                                    <td class="px-3 py-3.5 text-ink-700">{{ $payment->channel() }}</td>
                                    <td class="px-3 py-3.5 font-semibold {{ $age > 3 ? 'text-red-700' : 'text-ink-600' }}">{{ $payment->created_at->diffForHumans() }}</td>
                                    <td class="px-6 py-3.5 text-right"><x-button size="sm" :href="route('finance.payments.show', $payment)">Verify</x-button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Payment methods" description="All submitted payments">
            <x-chart.bars :rows="$channels->map(fn ($n, $label) => ['label' => $label, 'value' => $n, 'display' => $n.' · '.round($n / $channelTotal * 100).'%'])->values()->all()" color="#1b7fa3" />
        </x-card>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card title="Collection to date">
            <div class="space-y-6">
                @foreach ($collection as $currency => $c)
                    @php $pct = $c['expected'] ? $c['collected'] / $c['expected'] * 100 : 0; @endphp
                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm font-bold text-ink-700">{{ $currency }}</span>
                            <span class="text-xs text-ink-500">{{ round($pct) }}% collected</span>
                        </div>
                        <p class="mt-1 text-2xl font-extrabold tracking-tight text-brand-700">{{ number_format($c['collected']) }} <span class="text-sm font-semibold text-ink-500">of {{ number_format($c['expected']) }}</span></p>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-ink-100"><div class="h-full rounded-full {{ $currency === 'TZS' ? 'bg-brand-700' : 'bg-sun-500' }}" style="width: {{ $pct }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Daily pulse" description="Payments verified per day, last 5 weeks">
            <div class="grid grid-cols-[1.5rem_repeat(7,minmax(0,1fr))] gap-1 text-center text-[11px]">
                <span></span>
                @foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $d)<span class="font-semibold text-ink-500">{{ $d }}</span>@endforeach
                @foreach ($pulse->chunk(7) as $w => $week)
                    <span class="self-center font-semibold text-ink-500">W{{ $w + 1 }}</span>
                    @foreach ($week as $day)
                        @php [$bg, $fg] = $day['future'] ? ['#f6f8fa', '#c3cad5'] : Palette::heat($day['count'], $pulseMax); @endphp
                        <span class="grid h-8 place-items-center rounded-md font-bold" style="background: {{ $bg }}; color: {{ $fg }}" title="{{ $day['date']->format('D j M') }}: {{ $day['count'] }} verified">{{ $day['future'] ? '' : $day['count'] }}</span>
                    @endforeach
                @endforeach
            </div>
        </x-card>

        <x-card title="Exports" description="Spreadsheets for reconciliation">
            <div class="space-y-2">
                @foreach (['all' => 'All payments', 'verified' => 'Verified payments', 'submitted' => 'Awaiting verification', 'rejected' => 'Rejected payments'] as $status => $label)
                    <a href="{{ route('finance.payments.export', ['status' => $status]) }}" class="flex items-center justify-between rounded-xl border border-ink-100 px-4 py-3 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:bg-brand-50">
                        {{ $label }} <x-icon name="download" class="h-4 w-4 text-brand-700" />
                    </a>
                @endforeach
            </div>
        </x-card>
    </div>
</x-layouts.portal>
