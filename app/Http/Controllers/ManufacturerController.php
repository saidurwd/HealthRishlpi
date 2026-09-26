<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use App\Support\Grid;

class ManufacturerController extends CrudController
{
    protected string $model = Manufacturer::class;

    protected string $route = 'manufacturer';

    protected string $plural = 'Manufacturers';

    protected string $singular = 'Manufacturer';

    protected function grid(): Grid
    {
        return Grid::for(Manufacturer::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('email', partial: true)
            ->compare('phone', partial: true)
            ->compare('mobile', partial: true)
            ->compare('address', partial: true);
    }
}
