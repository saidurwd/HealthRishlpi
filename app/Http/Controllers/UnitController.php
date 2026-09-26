<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Support\Grid;

class UnitController extends CrudController
{
    protected string $model = Unit::class;

    protected string $route = 'unit';

    protected string $plural = 'Units';

    protected string $singular = 'Unit';

    protected function grid(): Grid
    {
        return Grid::for(Unit::query())
            ->compare('id')
            ->compare('formal_name', partial: true)
            ->compare('full_name', partial: true)
            ->compare('decimal_place');
    }
}
