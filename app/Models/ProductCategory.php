<?php

namespace App\Models;

use App\Models\Concerns\HasTreePath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Product category (`os_product_category`). Tree: see HasTreePath.
 *
 * @property int $id
 * @property int|null $parent
 * @property string $title
 * @property string|null $alias
 * @property string|null $description
 * @property string|null $path
 */
#[Table('product_category', timestamps: false)]
#[Fillable(['parent', 'title', 'alias', 'description', 'path'])]
class ProductCategory extends LegacyModel
{
    use HasTreePath;

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'parent' => 'Parent', 'title' => 'Category', 'alias' => 'Alias', 'description' => 'Description', 'path' => 'Path'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:250'],
            'parent' => ['integer'],
            'alias' => ['max:250'],
            'path' => ['max:150'],
            'description' => ['nullable'],
        ];
    }
}
