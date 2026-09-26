<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\PatientCategory;
use App\Support\Grid;

class PatientCategoryController extends CrudController
{
    protected string $model = PatientCategory::class;

    protected string $route = 'patientCategory';

    protected string $plural = 'Patient Sub Categories';

    protected string $singular = 'Sub Category';

    // PatientCategoryController::actionDelete() had its delete commented out
    protected bool $deletable = false;

    protected function filterData(): array
    {
        return [
            'parents' => PatientCategory::query()->whereNull('parent')->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(PatientCategory::query()->with('parentRow'))
            ->compare('id')
            ->compare('parent')
            ->compare('title', partial: true)
            ->compare('alias', partial: true)
            ->compare('path', partial: true)
            ->compare('status', partial: true)
            ->defaultOrder('path');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'parents' => PatientCategory::query()->whereNull('parent')->orderBy('title')->pluck('title', 'id'),
        ];
    }

    /**
     * @param  PatientCategory  $record
     */
    protected function saved(LegacyModel $record): void
    {
        $record->updatePath();
    }
}
