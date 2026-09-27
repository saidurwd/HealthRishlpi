<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Medicine line of a prescription (`os_prescription_medicine`). Lines added
 * before the prescription is saved have parent 0 and belong to their
 * creator until then. `product` holds the product title, not its id.
 *
 * @property int $id
 * @property int $parent
 * @property string|null $product
 * @property string|null $instruction
 * @property int|null $no_of_days
 * @property int $created_by
 */
#[Table('prescription_medicine', timestamps: false)]
#[Fillable(['parent', 'servicetype', 'product', 'instruction', 'no_of_days'])]
class PrescriptionMedicine extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'servicetype' => 'Type',
            'product' => 'Product',
            'instruction' => 'Instruction',
            'no_of_days' => 'No of Days',
            'created_by' => 'Created By',
            'created_on' => 'Created On',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'parent' => ['required', 'integer'],
            'no_of_days' => ['integer'],
            'servicetype' => ['max:8'],
            'product' => ['max:250'],
            'instruction' => ['max:250'],
        ];
    }
}
