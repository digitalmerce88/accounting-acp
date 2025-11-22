<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;

class FxConversionService
{
    /**
     * Convert an amount in document currency to base currency using latest (<= date) rate.
     * Assumes stored rate_decimal = number of quote per base (1 USD = 36 THB, base USD quote THB).
     */
    public static function toBase(string $base, string $quote, float $amountQuote, ?string $onDate = null): float
    {
        if ($base === $quote) { return round($amountQuote,2); }
        $d = $onDate ? Carbon::parse($onDate) : Carbon::today();
        $rate = ExchangeRate::where('base_currency',$base)
            ->where('quote_currency',$quote)
            ->where('rate_date','<=',$d->format('Y-m-d'))
            ->orderByDesc('rate_date')
            ->first();
        if(!$rate){ return round($amountQuote,2); } // fallback no conversion
        // amountQuote (quote) / rate_decimal => base
        return round($amountQuote / (float)$rate->rate_decimal, 2);
    }

    public static function latestRate(string $base, string $quote): ?float
    {
        if ($base === $quote) { return 1.0; }
        $rate = ExchangeRate::where('base_currency',$base)
            ->where('quote_currency',$quote)
            ->orderByDesc('rate_date')
            ->first();
        return $rate ? (float)$rate->rate_decimal : null;
    }
}
