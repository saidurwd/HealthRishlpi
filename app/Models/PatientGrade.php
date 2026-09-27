<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Patient Grade (`os_patient_grade`).
 *
 * @property int $id
 * @property string $title
 * @property string|null $remarks
 * @property string $status
 */
#[Table('patient_grade', timestamps: false)]
#[Fillable(['title', 'remarks', 'status'])]
class PatientGrade extends LegacyModel
{
    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Patient Grade', 'remarks' => 'Remarks', 'status' => 'Status'];
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
