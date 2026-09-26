<?php

namespace App\Http\Controllers;

use App\Models\PatientGrade;
use App\Support\Grid;

class PatientGradeController extends CrudController
{
    protected string $model = PatientGrade::class;

    protected string $route = 'patientGrade';

    protected string $plural = 'Patient Grades';

    protected string $singular = 'Patient Grade';

    protected function grid(): Grid
    {
        return Grid::for(PatientGrade::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('remarks', partial: true)
            ->compare('status', partial: true);
    }
}
