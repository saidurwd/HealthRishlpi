<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Instruction (`os_instruction`).
 */
#[Table('instruction', timestamps: false)]
#[Fillable(['title', 'status'])]
class Instruction extends LegacyModel
{
    protected $attributes = ['status' => 'Active'];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'title' => 'Instruction', 'status' => 'Status'];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'title' => ['required', 'max:150'],
            'status' => ['max:8'],
        ];
    }
}
