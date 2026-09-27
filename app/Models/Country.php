<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Country (`os_country`).
 *
 * @property int $id
 * @property string $title
 * @property string|null $country_2_code
 * @property string|null $country_3_code
 * @property string|null $status
 */
#[Table('country', timestamps: false)]
#[Fillable(['title', 'country_2_code', 'country_3_code', 'status'])]
class Country extends LegacyModel
{
    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Country', 'country_2_code' => 'Code 2', 'country_3_code' => 'Code 3', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:255'],
            'country_2_code' => ['max:2'],
            'country_3_code' => ['max:3'],
            'status' => ['max:8'],
        ];
    }
}
