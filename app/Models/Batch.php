<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Stock batch / lot (`os_batch`).
 */
#[Table('batch', timestamps: false)]
#[Fillable(['title', 'manufacturing', 'expiry'])]
class Batch extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Batch', 'manufacturing' => 'Manufacturing', 'expiry' => 'Expiry'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['max:100'],
            'manufacturing' => ['nullable'],
            'expiry' => ['required'],
        ];
    }
}
