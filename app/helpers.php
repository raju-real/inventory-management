<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return Setting::valueOf($key, $default);
    }
}

if (! function_exists('money')) {
    function money($amount): string
    {
        return trim(setting('currency_symbol', '') . ' ' . number_format((float) $amount, 2));
    }
}

if (! function_exists('qty')) {
    /** Format a quantity without trailing zeros: 5.000 => 5, 2.500 => 2.5 */
    function qty($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
    }
}
