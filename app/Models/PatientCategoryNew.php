<?php

namespace App\Models;

use App\Models\Concerns\HasTreePath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Patient category (`os_patient_category_new`). Tree: see HasTreePath.
 */
#[Table('patient_category_new', timestamps: false)]
#[Fillable(['parent', 'title', 'alias', 'path', 'status'])]
class PatientCategoryNew extends LegacyModel
{
    use HasTreePath;

    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'parent' => 'Parent', 'title' => 'Category', 'alias' => 'Alias', 'path' => 'Path', 'status' => 'Status'];
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
