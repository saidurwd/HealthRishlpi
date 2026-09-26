<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * District (`os_district`).
 */
#[Table('district', timestamps: false)]
#[Fillable(['country', 'state', 'city', 'title', 'status'])]
class District extends LegacyModel
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

    protected $attributes = ['country' => 18, 'status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'country' => 'Country', 'state' => 'State', 'city' => 'City', 'title' => 'District', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'country' => ['integer'],
            'state' => ['required', 'integer'],
            'city' => ['required', 'integer'],
            'title' => ['required', 'max:150'],
            'status' => ['max:8'],
        ];
    }
}
