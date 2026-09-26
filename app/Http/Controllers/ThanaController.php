<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\LegacyModel;
use App\Models\State;
use App\Models\Thana;
use App\Support\Grid;

class ThanaController extends CrudController
{
    protected string $model = Thana::class;

    protected string $route = 'thana';

    protected string $plural = 'Thanas';

    protected string $singular = 'Thana';

    protected function filterData(): array
    {
        return [
            'countries' => Country::query()->orderBy('title')->pluck('title', 'id'),
            'states' => State::query()->orderBy('title')->pluck('title', 'id'),
            'cities' => City::query()->orderBy('title')->pluck('title', 'id'),
            'districts' => District::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(Thana::query()->with('country0', 'state0', 'city0', 'district0'))
            ->compare('id')
            ->compare('country')
            ->compare('state')
            ->compare('city')
            ->compare('district')
            ->compare('title', partial: true)
            ->compare('status', partial: true);
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'countries' => Country::query()->pluck('title', 'id'),
            'states' => State::query()->pluck('title', 'id'),
            'cities' => City::query()->pluck('title', 'id'),
            'districts' => District::query()->pluck('title', 'id'),
        ];
    }
}
