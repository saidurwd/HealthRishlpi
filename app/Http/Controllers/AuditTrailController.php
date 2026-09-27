<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use App\Support\Grid;

/**
 * Login history: grid and delete only.
 */
class AuditTrailController extends CrudController
{
    protected string $model = AuditTrail::class;

    protected string $route = 'auditTrail';

    protected string $plural = 'Audit Trails';

    protected string $singular = 'Audit Trail';

    protected function grid(): Grid
    {
        return Grid::for(AuditTrail::query()->with('user'))
            ->compare('id', partial: true)
            ->compare('user_id')
            ->compare('login_time', partial: true)
            ->compare('logout_time', partial: true)
            ->defaultOrder('login_time', 'desc');
    }

    protected function filterData(): array
    {
        return ['users' => User::query()->orderBy('full_name')->pluck('full_name', 'id')];
    }
}
