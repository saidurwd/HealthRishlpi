<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Account status names (`os_user_status`).
 *
 * @property int $id
 * @property string $title
 */
#[Table('user_status', timestamps: false)]
#[Fillable(['title'])]
class UserStatus extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return ['title' => ['required', 'max:100']];
    }
}
