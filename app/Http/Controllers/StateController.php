<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\LegacyModel;
use App\Models\State;
use App\Support\Grid;

class StateController extends CrudController
{
    protected string $model = State::class;

    protected string $route = 'state';

    protected string $plural = 'States';

    protected string $singular = 'State';

    protected function filterData(): array
    {
        return [
            'countries' => Country::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(State::query()->with('country0'))
            ->compare('id')
            ->compare('country')
            ->compare('title', partial: true)
            ->compare('state_2_code', partial: true)
            ->compare('state_3_code', partial: true)
            ->compare('status');
    }

    protected function formData(LegacyModel $record): array
    {
        return ['countries' => Country::query()->pluck('title', 'id')];
    }
}
