<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Patient Type (`os_patient_type`).
 */
#[Table('patient_type', timestamps: false)]
#[Fillable(['title', 'remarks', 'status'])]
class PatientType extends LegacyModel
{
    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Patient Type', 'remarks' => 'Remarks', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:250'],
            'remarks' => ['max:400'],
            'status' => ['max:8'],
        ];
    }
}
