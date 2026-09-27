<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visitor;
use App\Support\Grid;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Page view statistics: grid, delete and truncate.
 */
class VisitorController extends CrudController
{
    protected string $model = Visitor::class;

    protected string $route = 'visitor';

    protected string $plural = 'Visitors';

    protected string $singular = 'Visitor';

    /**
     * Rows older than a week are pruned every time the grid is opened.
     */
    public function admin(): View
    {
        Visitor::query()->whereRaw('server_time < DATE_SUB(NOW(), INTERVAL 7 DAY)')->delete();

        return parent::admin();
    }

    public function truncate(): RedirectResponse
    {
        Visitor::query()->delete();

        return redirect()->route('visitor.admin')->with('success', 'TRUNCATE all visitors statistics data!');
    }

    protected function grid(): Grid
    {
        return Grid::for(Visitor::query())
            ->compare('id', partial: true)
            ->compare('user_id')
            ->compare('user_name', partial: true)
            ->compare('page_title', partial: true)
            ->compare('page_link', partial: true)
            ->compare('server_time', partial: true)
            ->compare('browser', partial: true)
            ->compare('visitor_ip', partial: true)
            ->defaultOrder('server_time', 'desc');
    }

    protected function filterData(): array
    {
        return ['users' => User::query()->orderBy('full_name')->pluck('full_name', 'id')];
    }
}
