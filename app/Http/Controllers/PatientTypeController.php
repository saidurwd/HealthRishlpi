<?php

namespace App\Http\Controllers;

use App\Models\PatientType;
use App\Support\Grid;

class PatientTypeController extends CrudController
{
    protected string $model = PatientType::class;

    protected string $route = 'patientType';

    protected string $plural = 'Patient Types';

    protected string $singular = 'Patient Type';

    protected function grid(): Grid
    {
        return Grid::for(PatientType::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('remarks', partial: true)
            ->compare('status', partial: true);
    }
}
