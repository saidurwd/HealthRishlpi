<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LegacyModel;
use App\Support\Grid;

class DepartmentController extends CrudController
{
    protected string $model = Department::class;

    protected string $route = 'department';

    protected string $plural = 'Departments';

    protected string $singular = 'Department';

    protected function filterData(): array
    {
        return [
            'parents' => Department::roots(),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(Department::query()->with('parentRow'))
            ->compare('id')
            ->compare('parent')
            ->compare('code', partial: true)
            ->compare('title', partial: true)
            ->compare('alias', partial: true)
            ->compare('description', partial: true)
            ->compare('path', partial: true)
            ->defaultOrder('path');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'parents' => Department::roots(),
        ];
    }

    /**
     * @param  Department  $record
     */
    protected function saved(LegacyModel $record): void
    {
        $record->updatePath();
    }
}
