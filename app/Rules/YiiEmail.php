<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Port of Yii's CEmailValidator with checkMX: the address must match Yii's
 * pattern and its domain must have an MX record.
 */
class YiiEmail implements ValidationRule
{
    private const PATTERN = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';

    /**
     * MX lookup, replaceable in tests (defaults to checkdnsrr($domain, 'MX')).
     *
     * @var (Closure(string): bool)|null
     */
    public static ?Closure $mxLookup = null;

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (preg_match(self::PATTERN, $value) !== 1 || ! $this->hasMx(substr($value, strpos($value, '@') + 1))) {
            $fail('validation.email')->translate();
        }
    }

    private function hasMx(string $domain): bool
    {
        return self::$mxLookup !== null ? (self::$mxLookup)($domain) : checkdnsrr($domain, 'MX');
    }
}
