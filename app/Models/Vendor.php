<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Supplier (`os_vendor`).
 */
#[Table('vendor', timestamps: false)]
#[Fillable(['title', 'email', 'phone', 'mobile', 'address'])]
class Vendor extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Vendor', 'email' => 'Email', 'phone' => 'Phone', 'mobile' => 'Mobile', 'address' => 'Address'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:255'],
            'email' => ['max:150'],
            'phone' => ['max:100'],
            'mobile' => ['max:100'],
            'address' => ['max:255'],
        ];
    }
}
