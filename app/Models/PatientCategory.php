<?php

namespace App\Models;

use App\Models\Concerns\HasTreePath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Patient sub category (`os_patient_category`). Tree: see HasTreePath.
 *
 * @property int $id
 * @property int|null $parent
 * @property string $title
 * @property string|null $alias
 * @property string|null $path
 * @property string $status
 */
#[Table('patient_category', timestamps: false)]
#[Fillable(['parent', 'title', 'alias', 'path', 'status'])]
class PatientCategory extends LegacyModel
{
    use HasTreePath;

    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'parent' => 'Parent', 'title' => 'Sub Category', 'alias' => 'Alias', 'path' => 'Path', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:250'],
            'parent' => ['integer'],
            'alias' => ['max:250'],
            'path' => ['max:250'],
            'status' => ['max:8'],
        ];
    }
}
