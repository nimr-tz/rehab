<?php

namespace App\Support;

use App\Models\Edition;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The current summit edition, as the views see it.
 *
 * Edition details come from the `editions` table (edited by admins in Summit
 * settings). Organisation details such as contact emails come from
 * config/summit.php, which also supplies placeholders when no edition exists
 * yet, so a fresh install still renders every public page.
 */
class Summit
{
    private ?Edition $edition = null;

    private bool $loaded = false;

    public function edition(): ?Edition
    {
        if (! $this->loaded) {
            $this->edition = Edition::current()?->load(['categories', 'topics']);
            $this->loaded = true;
        }

        return $this->edition;
    }

    /** Forget the cached edition, after settings change. */
    public function refresh(): void
    {
        $this->loaded = false;
        $this->edition = null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $edition = $this->edition();

        if (! $edition) {
            return config("summit.{$key}", $default);
        }

        return match ($key) {
            'year', 'name', 'short_name', 'theme', 'venue', 'city', 'country', 'registration_open' => $edition->{$key},
            'edition' => $edition->ordinal,
            'start_date', 'end_date' => $edition->{$key}?->toDateString(),
            'abstracts_open' => $edition->acceptsAbstracts(),
            'days' => $edition->start_date && $edition->end_date
                ? (int) $edition->start_date->diffInDays($edition->end_date) + 1
                : config('summit.days', $default),
            'topics' => $edition->topics->pluck('code', 'name')->all(),
            'fees' => $edition->categories->map(fn ($category) => [
                'label' => $category->name,
                'currency' => $category->currency,
                'amount' => $category->amount,
            ])->all(),
            'key_dates' => [
                ['label' => 'Abstract submission deadline', 'date' => $edition->abstract_deadline?->toDateString()],
                ['label' => 'Session chair and rapporteur applications close', 'date' => $edition->session_role_deadline?->toDateString()],
                ['label' => 'Presentation upload deadline', 'date' => $edition->presentation_deadline?->toDateString()],
            ],
            default => config("summit.{$key}", $default),
        };
    }

    public function title(): string
    {
        return $this->get('name').' '.$this->get('year');
    }

    public function shortTitle(): string
    {
        return $this->get('short_name').' '.$this->get('year');
    }

    /** "16–18 September 2027", or null while the dates are not announced. */
    public function dateRange(): ?string
    {
        $start = $this->date('start_date');
        $end = $this->date('end_date') ?? $start;

        if (! $start) {
            return null;
        }

        if ($start->isSameDay($end)) {
            return $start->format('j F Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->format('j').'–'.$end->format('j F Y');
        }

        return $start->format('j M').' – '.$end->format('j M Y');
    }

    /** "JNICC, Dar es Salaam", or null while the venue is not announced. */
    public function venueLine(): ?string
    {
        $parts = array_filter([$this->get('venue'), $this->get('city')]);

        return $parts ? implode(', ', $parts) : null;
    }

    /** @return list<array{label: string, date: ?string}> */
    public function keyDates(): array
    {
        return array_map(fn (array $item) => [
            'label' => $item['label'],
            'date' => $item['date'] ? CarbonImmutable::parse($item['date'])->format('j M Y') : null,
        ], $this->get('key_dates', []));
    }

    public function feesConfirmed(): bool
    {
        $fees = $this->get('fees', []);

        return $fees !== [] && collect($fees)->every(fn (array $fee) => filled($fee['amount']));
    }

    public static function formatDate(?CarbonInterface $date): string
    {
        return $date ? $date->format('j M Y') : 'To be announced';
    }

    private function date(string $key): ?CarbonImmutable
    {
        $value = $this->get($key);

        return $value ? CarbonImmutable::parse($value) : null;
    }
}
