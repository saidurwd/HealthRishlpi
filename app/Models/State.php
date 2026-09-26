<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * State / division (`os_state`).
 */
#[Table('state', timestamps: false)]
#[Fillable(['country', 'title', 'state_2_code', 'state_3_code', 'status'])]
class State extends LegacyModel
{
    /** @return BelongsTo<Country, $this> */
    public function country0(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country');
    }

    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'country' => 'Country', 'title' => 'State', 'state_2_code' => 'Code 2', 'state_3_code' => 'Code 3', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'country' => ['required', 'integer'],
            'title' => ['required', 'max:192'],
            'state_2_code' => ['max:6'],
            'state_3_code' => ['max:9'],
            'status' => ['max:8'],
        ];
    }
}
