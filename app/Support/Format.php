<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Dates, times and money the way each language writes them:
 * "Tuesday, November 10 · 6:45 PM ET" / "mardi 10 novembre · 18 h 45 HE",
 * "$4,250" / "4 250 $" (non-breaking spaces, which every font has).
 */
class Format
{
    private static function french(): bool
    {
        return app()->getLocale() === 'fr';
    }

    public static function day(CarbonInterface $date): string
    {
        return $date->isoFormat(self::french() ? 'dddd D MMMM' : 'dddd, MMMM D');
    }

    public static function date(CarbonInterface $date): string
    {
        return $date->isoFormat(self::french() ? 'D MMMM YYYY' : 'MMMM D, YYYY');
    }

    public static function weekday(CarbonInterface $date): string
    {
        return $date->isoFormat('dddd');
    }

    public static function month(CarbonInterface $date): string
    {
        return rtrim($date->isoFormat('MMM'), '.');
    }

    /** Time of day, by default with the Eastern time zone marker: 6:45 PM ET, 18 h 45 HE, 18 h HE. */
    public static function time(CarbonInterface $date, bool $zone = true): string
    {
        $time = self::french()
            ? $date->format($date->minute ? 'G \h i' : 'G \h')
            : $date->format('g:i A');

        return $zone ? $time.(self::french() ? ' HE' : ' ET') : $time;
    }

    public static function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return self::french()
            ? number_format($amount, 0, ',', "\u{00A0}")."\u{00A0}$"
            : '$'.number_format($amount);
    }
}
