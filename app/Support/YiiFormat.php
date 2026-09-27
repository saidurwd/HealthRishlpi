<?php

namespace App\Support;

/**
 * Display formats the Yii app used everywhere (User::get_date_time(),
 * User::get_date(), User::get_date_ifexist()). Empty and zero dates show
 * nothing.
 */
class YiiFormat
{
    /** "Sep 27, 2026, 3:04:05 PM" */
    public static function dateTime(mixed $date): ?string
    {
        return self::format($date, 'M j, Y, g:i:s A');
    }

    /** "Sep 27, 2026" */
    public static function date(mixed $date): ?string
    {
        return self::format($date, 'M j, Y');
    }

    /** "27-09-2026" */
    public static function dateIfExist(mixed $date): ?string
    {
        return self::format($date, 'd-m-Y');
    }

    /**
     * "1,234.50", negatives in brackets (Product::number_format()).
     * Non-numeric values are returned unchanged.
     */
    public static function number(mixed $value, int $decimals = 2): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $formatted = number_format(abs((float) $value), $decimals, '.', ',');

        return $value < 0 ? '('.$formatted.')' : $formatted;
    }

    /**
     * "৳1,234.50" with the session currency, negatives in brackets
     * (Product::number_format_currency()).
     */
    public static function currency(mixed $value, int $decimals = 2): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $formatted = session('currency').number_format(abs((float) $value), $decimals, '.', ',');

        return $value < 0 ? '('.$formatted.')' : $formatted;
    }

    /**
     * currency() of the value rounded to a whole number first
     * (Product::number_format_currency_round()).
     */
    public static function currencyRound(mixed $value, int $decimals = 0): mixed
    {
        return is_numeric($value) ? self::currency(round((float) $value), $decimals) : $value;
    }

    private static function format(mixed $date, string $format): ?string
    {
        $date = (string) $date;

        if ($date === '' || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return null;
        }

        return date($format, strtotime($date));
    }
}
