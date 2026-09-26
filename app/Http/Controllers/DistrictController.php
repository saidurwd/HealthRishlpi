<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\LegacyModel;
use App\Models\State;
use App\Support\Grid;

class DistrictController extends CrudController
{
    protected string $model = District::class;

    protected string $route = 'district';

    protected string $plural = 'Districts';

    protected string $singular = 'District';

    protected function filterData(): array
    {
        return [
            'countries' => Country::query()->orderBy('title')->pluck('title', 'id'),
            'states' => State::query()->orderBy('title')->pluck('title', 'id'),
            'cities' => City::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(District::query()->with('country0', 'state0', 'city0'))
            ->compare('id')
            ->compare('country')
            ->compare('state')
            ->compare('city')
            ->compare('title', partial: true)
            ->compare('status', partial: true);
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'countries' => Country::query()->pluck('title', 'id'),
            'states' => State::query()->pluck('title', 'id'),
            'cities' => City::query()->pluck('title', 'id'),
        ];
    }
}
