<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\PatientCategoryNew;
use App\Support\Grid;

class PatientCategoryNewController extends CrudController
{
    protected string $model = PatientCategoryNew::class;

    protected string $route = 'patientCategoryNew';

    protected string $plural = 'Patient Categories';

    protected string $singular = 'Category';

    // PatientCategoryNewController::actionDelete() had its delete commented out
    protected bool $deletable = false;

    protected function filterData(): array
    {
        return [
            'parents' => PatientCategoryNew::query()->whereNull('parent')->orderBy('title')->pluck('title', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(PatientCategoryNew::query()->with('parentRow'))
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
            'parents' => PatientCategoryNew::query()->whereNull('parent')->orderBy('title')->pluck('title', 'id'),
        ];
    }

    /**
     * @param  PatientCategoryNew  $record
     */
    protected function saved(LegacyModel $record): void
    {
        $record->updatePath();
    }
}
