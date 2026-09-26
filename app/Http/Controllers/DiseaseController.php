<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use App\Support\Grid;

class DiseaseController extends CrudController
{
    protected string $model = Disease::class;

    protected string $route = 'disease';

    protected string $plural = 'Diseases';

    protected string $singular = 'Disease';

    protected function grid(): Grid
    {
        return Grid::for(Disease::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('status', partial: true);
    }
}
