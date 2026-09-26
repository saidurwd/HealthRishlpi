<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Validation\Rule;

/**
 * Unit of measure for products (`os_unit`).
 *
 * @property int $id
 * @property string $formal_name
 * @property string $full_name
 * @property int $decimal_place
 */
#[Table('unit', timestamps: false)]
#[Fillable(['formal_name', 'full_name', 'decimal_place'])]
class Unit extends LegacyModel
{
    protected $attributes = ['decimal_place' => 2];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'formal_name' => 'Formal Name',
            'full_name' => 'Full Name',
            'decimal_place' => 'Decimal Place',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'full_name' => ['required', 'max:100', Rule::unique(self::class)->ignore($model?->getKey())],
            'formal_name' => ['required', 'max:50', Rule::unique(self::class)->ignore($model?->getKey())],
            'decimal_place' => ['integer'],
        ];
    }
}
