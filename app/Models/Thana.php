<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Thana (sub-district) (`os_thana`).
 *
 * @property int $id
 * @property int $country
 * @property int $state
 * @property int $city
 * @property int $district
 * @property string $title
 * @property string $status
 */
#[Table('thana', timestamps: false)]
#[Fillable(['country', 'state', 'city', 'district', 'title', 'status'])]
class Thana extends LegacyModel
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

    /** @return BelongsTo<City, $this> */
    public function city0(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city');
    }

    /** @return BelongsTo<District, $this> */
    public function district0(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district');
    }

    protected $attributes = ['country' => 18, 'status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'country' => 'Country', 'state' => 'State', 'city' => 'City', 'district' => 'District', 'title' => 'Thana', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'country' => ['integer'],
            'state' => ['required', 'integer'],
            'city' => ['required', 'integer'],
            'district' => ['required', 'integer'],
            'title' => ['required', 'max:100'],
            'status' => ['max:8'],
        ];
    }
}
