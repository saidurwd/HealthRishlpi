<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * User group (`os_user_group`); `os_acl` rows grant access per group.
 *
 * @property int $id
 * @property string $title
 * @property string|null $details
 */
#[Table('user_group', timestamps: false)]
#[Fillable(['title', 'details'])]
class UserGroup extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Group', 'details' => 'Details'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:100'],
            'details' => ['nullable'],
        ];
    }
}
