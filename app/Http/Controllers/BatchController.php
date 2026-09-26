<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Support\Grid;

class BatchController extends CrudController
{
    protected string $model = Batch::class;

    protected string $route = 'batch';

    protected string $plural = 'Batches';

    protected string $singular = 'Batch';

    protected function grid(): Grid
    {
        return Grid::for(Batch::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('manufacturing', partial: true)
            ->compare('expiry', partial: true);
    }
}
