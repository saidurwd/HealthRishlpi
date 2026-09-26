<?php

namespace App\Models;

use App\Models\Concerns\HasTreePath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Staff department (`os_department`). Tree: see HasTreePath.
 */
#[Table('department', timestamps: false)]
#[Fillable(['parent', 'code', 'title', 'alias', 'description', 'path'])]
class Department extends LegacyModel
{
    use HasTreePath;

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'parent' => 'Parent', 'code' => 'Code', 'title' => 'Department', 'alias' => 'Alias', 'description' => 'Description', 'path' => 'Path'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:150'],
            'parent' => ['integer'],
            'code' => ['max:4'],
            'alias' => ['max:250'],
            'path' => ['max:250'],
            'description' => ['nullable'],
        ];
    }
}
