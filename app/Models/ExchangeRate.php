<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRate extends Model
{
    protected $fillable = ['from_currency', 'to_currency', 'rate', 'rate_date'];

    protected $casts = [
        'rate' => 'decimal:6',
        'rate_date' => 'date',
    ];

    /**
     * Get rate for a specific date
     */
    public static function getRate(string $from = 'USD', string $to = 'TZS', $date = null)
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : now()->toDateString();

        // Check cache/database
        $cached = self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('rate_date', $date)
            ->first();

        if ($cached) {
            return $cached->rate;
        }

        // If it's today and we don't have it, try to fetch it
        if ($date === now()->toDateString()) {
            return self::fetchTodayRate($from, $to);
        }

        // Fallback to latest available if historical date not found
        $latest = self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $latest ? $latest->rate : 2470.00; // Hard fallback
    }

    /**
     * Fetch today's rate from an external API (or fallback if API fails)
     */
    public static function fetchTodayRate(string $from, string $to)
    {
        try {
            // Using a free API (v6.exchangerate-api.com) - In production you would use a secured key
            // For now, we use a fallback if the API is not configured or fails
            $response = Http::timeout(5)->get("https://open.er-api.com/v6/latest/{$from}");

            if ($response->successful()) {
                $rate = $response->json()['rates'][$to] ?? null;
                if ($rate) {
                    return self::updateOrCreate(
                        ['from_currency' => $from, 'to_currency' => $to, 'rate_date' => now()->toDateString()],
                        ['rate' => $rate]
                    )->rate;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch exchange rate: " . $e->getMessage());
        }

        // Fallback for today if API fails
        return 2470.00;
    }
}
