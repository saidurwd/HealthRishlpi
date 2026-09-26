<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Port of Yii's CModel::attributeLabels() / getAttributeLabel().
 *
 * Models list their labels in attributeLabels(); anything missing falls back
 * to Yii's generated label ("full_name" => "Full Name").
 */
trait HasAttributeLabels
{
    /**
     * @return array<string, string>
     */
    public static function attributeLabels(): array
    {
        return [];
    }

    public static function label(string $attribute): string
    {
        return static::attributeLabels()[$attribute]
            ?? ucwords(trim(strtolower(str_replace(['-', '_', '.'], ' ', Str::snake($attribute)))));
    }

    /**
     * Labels for the given attributes, keyed for Validator's $attributes argument.
     *
     * @param  array<int, string>  $attributes
     * @return array<string, string>
     */
    public static function labelsFor(array $attributes): array
    {
        return collect($attributes)->mapWithKeys(fn ($a) => [$a => static::label($a)])->all();
    }
}
