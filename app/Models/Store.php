<?php

namespace App\Models;

use App\Models\Concerns\HasTreePath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Medicine store / pharmacy (`os_store`). Tree: see HasTreePath.
 */
#[Table('store', timestamps: false)]
#[Fillable(['parent', 'title', 'alias', 'location', 'incharge', 'description', 'path'])]
class Store extends LegacyModel
{
    use HasTreePath;

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'parent' => 'Parent', 'title' => 'Store', 'alias' => 'Alias', 'location' => 'Location', 'incharge' => 'Incharge', 'description' => 'Details', 'path' => 'Path'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:255'],
            'parent' => ['integer'],
            'incharge' => ['integer'],
            'alias' => ['max:255'],
            'location' => ['max:255'],
            'path' => ['max:252'],
            'description' => ['nullable'],
        ];
    }
}
