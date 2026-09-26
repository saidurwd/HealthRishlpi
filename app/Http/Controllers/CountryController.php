<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Support\Grid;

class CountryController extends CrudController
{
    protected string $model = Country::class;

    protected string $route = 'country';

    protected string $plural = 'Countries';

    protected string $singular = 'Country';

    protected function grid(): Grid
    {
        return Grid::for(Country::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('country_2_code', partial: true)
            ->compare('country_3_code', partial: true)
            ->compare('status');
    }
}
