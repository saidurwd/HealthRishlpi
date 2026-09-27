<?php

namespace App\Models;

use App\Models\Concerns\HasTreePath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Billable service (consultation, therapy, ...) (`os_service`). Tree: see HasTreePath.
 *
 * @property int $id
 * @property int|null $parent
 * @property string $title
 * @property string|null $alias
 * @property string|null $path
 * @property string|float $rate
 * @property string $discount
 * @property string $rate_status
 * @property string|null $service_type
 * @property int|null $service_grade
 * @property int|null $ordering
 * @property string $status
 */
#[Table('service', timestamps: false)]
#[Fillable(['parent', 'title', 'alias', 'path', 'rate', 'discount', 'service_type', 'rate_status', 'service_grade', 'ordering', 'status'])]
class Service extends LegacyModel
{
    use HasTreePath;

    protected $attributes = ['rate_status' => 'Auto', 'status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'parent' => 'Parent', 'title' => 'Service', 'alias' => 'Alias', 'path' => 'Path', 'rate' => 'Rate', 'discount' => 'Discount', 'service_type' => 'Service Type', 'service_grade' => 'Grade', 'ordering' => 'Ordering', 'status' => 'Status', 'rate_status' => 'Rate Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:250'],
            'rate' => ['required', 'max:12'],
            'parent' => ['integer'],
            'service_grade' => ['integer'],
            'ordering' => ['integer'],
            'alias' => ['max:250'],
            'path' => ['max:250'],
            'service_type' => ['max:250'],
            'rate_status' => ['max:250'],
            'status' => ['max:8'],
            'discount' => ['max:8'],
        ];
    }
}
