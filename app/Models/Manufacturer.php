<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Manufacturer (`os_manufacturer`).
 *
 * @property int $id
 * @property string $title
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $address
 */
#[Table('manufacturer', timestamps: false)]
#[Fillable(['title', 'email', 'phone', 'mobile', 'address'])]
class Manufacturer extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Manufacturer', 'email' => 'Email', 'phone' => 'Phone', 'mobile' => 'Mobile', 'address' => 'Address'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:150'],
            'email' => ['max:150'],
            'phone' => ['max:100'],
            'mobile' => ['max:100'],
            'address' => ['max:255'],
        ];
    }
}
