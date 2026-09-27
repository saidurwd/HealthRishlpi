<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Support\Grid;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The Gii-generated admin/create/update/delete controller every Yii master
 * data module used. Subclasses set the model and titles, build the grid, and
 * hook into saving.
 *
 * Views live in resources/views/<kebab route>/: admin.blade.php (the grid
 * columns) and _form.blade.php (the fields). Create/update pages are shared.
 *
 * @template TModel of LegacyModel
 */
abstract class CrudController extends Controller
{
    /** @var class-string<TModel> */
    protected string $model;

    /** Yii controller id, also the route name prefix ("patientCategoryNew") */
    protected string $route;

    /** "Cities" — page heading, breadcrumb and grid title */
    protected string $plural;

    /** "City" — used in "New City" / "Edit City" */
    protected string $singular;

    /** False where the Yii delete action was commented out (it still answered, but deleted nothing) */
    protected bool $deletable = true;

    /** Flash message after a create or update */
    protected string $savedMessage = 'Data was saved successfully';

    /**
     * The grid query with its filters (the model's search() in Yii).
     */
    abstract protected function grid(): Grid;

    public function admin(): View
    {
        return view($this->viewPath('admin'), array_merge([
            'grid' => $this->grid()->paginate($this->pageSize()),
            'page' => $this->page(),
        ], $this->filterData()));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $record = $this->newRecord();

        if ($request->isMethod('post')) {
            $record->fill($this->validated($request));
            $this->saving($record, $request);
            $record->save();
            $this->saved($record);

            return $this->afterSave($record)->with('success', $this->savedMessage);
        }

        return $this->form('create', $record);
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $record = $this->find($id);

        if ($request->isMethod('post')) {
            $record->fill($this->validated($request, $record));
            $this->saving($record, $request);
            $record->save();
            $this->saved($record);

            return $this->afterSave($record)->with('success', $this->savedMessage);
        }

        return $this->form('update', $record);
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        $record = $this->find($id);

        if ($this->deletable) {
            try {
                $record->delete();
            } catch (QueryException $e) {
                return $this->deleteFailed($e);
            }
        }

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route($this->route.'.admin')));
    }

    /**
     * Grid rows per page (the search() pagination in Yii).
     */
    protected function pageSize(): int
    {
        return config('legacy.pageSize');
    }

    /**
     * A new record carrying the column defaults, as a new Yii model did.
     *
     * @return TModel
     */
    protected function newRecord(): LegacyModel
    {
        return new $this->model;
    }

    /**
     * Option lists for the grid's dropdown filters.
     *
     * @return array<string, mixed>
     */
    protected function filterData(): array
    {
        return [];
    }

    /**
     * Extra data the form needs (dropdown options).
     *
     * @return array<string, mixed>
     */
    protected function formData(LegacyModel $record): array
    {
        return [];
    }

    /**
     * Runs after the posted values are filled in, before the save.
     */
    protected function saving(LegacyModel $record, Request $request): void
    {
        //
    }

    /**
     * Runs after the save.
     */
    protected function saved(LegacyModel $record): void
    {
        //
    }

    /**
     * Where a successful create or update goes (the admin grid).
     */
    protected function afterSave(LegacyModel $record): RedirectResponse
    {
        return redirect()->route($this->route.'.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?LegacyModel $record = null): array
    {
        $rules = $this->model::rules($record);

        return $request->validate($rules, [], $this->model::labelsFor(array_keys($rules)));
    }

    /**
     * @return TModel
     */
    protected function find(int $id): LegacyModel
    {
        return $this->model::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }

    private function form(string $action, LegacyModel $record): View
    {
        return view('crud.'.$action, array_merge([
            'record' => $record,
            'page' => $this->page(),
            'form' => $this->viewPath('_form'),
        ], $this->formData($record)));
    }

    /**
     * @return array{route: string, plural: string, singular: string}
     */
    private function page(): array
    {
        return ['route' => $this->route, 'plural' => $this->plural, 'singular' => $this->singular];
    }

    private function viewPath(string $view): string
    {
        return Str::kebab($this->route).'.'.$view;
    }
}
