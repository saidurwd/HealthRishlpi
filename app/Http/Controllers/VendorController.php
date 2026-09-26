<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Support\Grid;

class VendorController extends CrudController
{
    protected string $model = Vendor::class;

    protected string $route = 'vendor';

    protected string $plural = 'Vendors';

    protected string $singular = 'Vendor';

    protected function grid(): Grid
    {
        return Grid::for(Vendor::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('email', partial: true)
            ->compare('phone', partial: true)
            ->compare('mobile', partial: true)
            ->compare('address', partial: true);
    }
}
