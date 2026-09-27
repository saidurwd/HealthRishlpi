<?php

namespace App\Models;

use App\Models\Concerns\HasAttributeLabels;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * User group. Converted from `os_user_group` with the same ids, so
 * `os_user.group_id` still names each user's role (User keeps the two in
 * sync); permissions are route names ("patient.admin").
 *
 * @property int $id
 * @property string|null $details
 */
class Role extends SpatieRole
{
    use HasAttributeLabels;

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'name' => 'Group', 'details' => 'Details'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?self $model = null): array
    {
        return [
            'name' => ['required', 'max:100', Rule::unique(config('permission.table_names.roles'))->where('guard_name', 'web')->ignore($model?->id)],
            'details' => ['nullable'],
        ];
    }
}
