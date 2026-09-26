<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\PatientGrade;
use App\Models\Service;
use App\Support\Grid;

class ServiceController extends CrudController
{
    protected string $model = Service::class;

    protected string $route = 'service';

    protected string $plural = 'Services';

    protected string $singular = 'Service';

    protected function filterData(): array
    {
        return [
            'parents' => Service::roots(),
            'grades' => PatientGrade::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(Service::query()->with('parentRow'))
            ->compare('id')
            ->compare('parent')
            ->compare('title', partial: true)
            ->compare('alias', partial: true)
            ->compare('path', partial: true)
            ->compare('rate', partial: true)
            ->compare('discount')
            ->compare('service_type')
            ->compare('rate_status')
            ->compare('service_grade')
            ->compare('ordering')
            ->compare('status', partial: true)
            ->defaultOrder('path');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'parents' => Service::roots(),
            'grades' => PatientGrade::query()->pluck('title', 'id'),
        ];
    }

    /**
     * @param  Service  $record
     */
    protected function saved(LegacyModel $record): void
    {
        $record->updatePath();
    }
}
