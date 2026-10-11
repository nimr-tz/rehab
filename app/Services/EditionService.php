<?php

namespace App\Services;

use App\Models\Edition;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * One summit a year. Starting the next one leaves the last one and everything
 * in it (registrations, abstracts, payments, attendance) as it was, and makes
 * the new one current. Accounts carry over; registrations do not.
 */
class EditionService
{
    /**
     * @param  array{year: int, name: string, short_name: string, ordinal: string}  $data
     */
    public function startNext(Edition $from, array $data, bool $copyCategories, bool $copyTopics): Edition
    {
        if (Edition::where('year', $data['year'])->exists()) {
            throw new InvalidArgumentException('There is already a '.$data['year'].' summit.');
        }

        return DB::transaction(function () use ($from, $data, $copyCategories, $copyTopics) {
            $next = Edition::create([
                'year' => $data['year'],
                'name' => $data['name'],
                'short_name' => $data['short_name'],
                'ordinal' => $data['ordinal'],
                'country' => $from->country,
                // Everything else (dates, venue, deadlines) is new each year, and nothing opens until the admins open it.
                'registration_open' => false,
                'abstracts_open' => false,
                'is_current' => false,
            ]);

            if ($copyCategories) {
                foreach ($from->categories as $category) {
                    $next->categories()->create($category->only('name', 'currency', 'amount', 'is_student', 'sort'));
                }
            }

            if ($copyTopics) {
                foreach ($from->topics as $topic) {
                    $next->topics()->create($topic->only('name', 'code', 'description', 'sort'));
                }
            }

            $this->makeCurrent($next);

            return $next;
        });
    }

    /** Make an edition the one the portal works on. Exactly one is current. */
    public function makeCurrent(Edition $edition): void
    {
        DB::transaction(function () use ($edition) {
            Edition::whereKeyNot($edition->id)->where('is_current', true)->update(['is_current' => false]);
            $edition->update(['is_current' => true]);
        });
    }

    /** "5th" becomes "6th"; anything unexpected is left for the admin to fill in. */
    public static function nextOrdinal(?string $ordinal): string
    {
        if (! preg_match('/^(\d+)/', (string) $ordinal, $m)) {
            return '';
        }

        $n = (int) $m[1] + 1;
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : match ($n % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };

        return $n.$suffix;
    }
}
