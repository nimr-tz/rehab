<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Read-only view of the current summit edition for the public pages.
 *
 * Reads config/summit.php for now. Phase 0 points it at the `editions` table,
 * and the views keep working unchanged.
 */
class Summit
{
    public function get(string $key, mixed $default = null): mixed
    {
        return config("summit.{$key}", $default);
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
        return collect($this->get('fees', []))->every(fn (array $fee) => filled($fee['amount']));
    }

    private function date(string $key): ?CarbonImmutable
    {
        $value = $this->get($key);

        return $value ? CarbonImmutable::parse($value) : null;
    }
}
