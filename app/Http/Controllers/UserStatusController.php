<?php

namespace App\Http\Controllers;

use App\Models\UserStatus;
use App\Support\Grid;

class UserStatusController extends CrudController
{
    protected string $model = UserStatus::class;

    protected string $route = 'userStatus';

    protected string $plural = 'Status';

    protected string $singular = 'Status';

    protected string $savedMessage = 'Status was saved successfully';

    protected function grid(): Grid
    {
        return Grid::for(UserStatus::query())
            ->compare('id')
            ->compare('title', partial: true);
    }
}
