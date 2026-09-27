<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * City (`os_city`).
 *
 * @property int $id
 * @property int $country
 * @property int $state
 * @property string $title
 * @property string|null $city_2_code
 * @property string|null $city_3_code
 * @property string|null $status
 */
#[Table('city', timestamps: false)]
#[Fillable(['country', 'state', 'title', 'city_2_code', 'city_3_code', 'status'])]
class City extends LegacyModel
{
    /** @return BelongsTo<Country, $this> */
    public function country0(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /** @return BelongsTo<State, $this> */
    public function state0(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state');
    }

    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'country' => 'Country', 'state' => 'State', 'title' => 'City', 'city_2_code' => 'Code 2', 'city_3_code' => 'Code 3', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'country' => ['required', 'integer'],
            'state' => ['required', 'integer'],
            'title' => ['required', 'max:255'],
            'city_2_code' => ['max:6'],
            'city_3_code' => ['max:9'],
            'status' => ['max:8'],
        ];
    }
}
