<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\LegacyModel;
use App\Models\State;
use App\Support\Grid;

class CityController extends CrudController
{
    protected string $model = City::class;

    protected string $route = 'city';

    protected string $plural = 'Cities';

    protected string $singular = 'City';

    protected function filterData(): array
    {
        return [
            'countries' => Country::query()->orderBy('title')->pluck('title', 'id'),
            'states' => State::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(City::query()->with('country0', 'state0'))
            ->compare('id')
            ->compare('country')
            ->compare('state')
            ->compare('title', partial: true)
            ->compare('city_2_code', partial: true)
            ->compare('city_3_code', partial: true)
            ->compare('status', partial: true);
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'countries' => Country::query()->pluck('title', 'id'),
            'states' => State::query()->pluck('title', 'id'),
        ];
    }
}
