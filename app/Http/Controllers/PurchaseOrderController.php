<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderParent;
use App\Models\TransectionStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Purchase orders: lines are added one by one (AJAX) before the order is
 * saved. Approved orders feed goods receives ("Load from PO").
 */
class PurchaseOrderController extends Controller
{
    public const NO_ITEMS = 'Please add one or more items to the grid!';

    public const NOT_AUTHORIZED = 'You are not authorized to perform this action!';

    public function admin(): View
    {
        $grid = Grid::for(PurchaseOrderParent::query()->withCount('lines')->with('orderBy', 'supplier0'))
            ->compare('id')
            ->compare('order_date', partial: true)
            ->compare('order_number', partial: true)
            ->compare('order_by')
            ->compare('supplier')
            ->compare('comments')
            ->compare('status')
            ->compare('created_on', partial: true)
            ->compare('created_by')
            ->defaultOrder('order_date', 'desc')
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize20'));

        return view('purchase-order.admin', [
            'grid' => $grid,
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'vendors' => Vendor::query()->orderBy('title')->pluck('title', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::PURCHASE_ORDER, userViewOnly: true),
        ]);
    }

    public function view(int $id): View
    {
        return view('purchase-order.view', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    public function print(int $id): View
    {
        return view('purchase-order.print', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parent = new PurchaseOrderParent;

        if ($request->isMethod('post')) {
            $parent->fill($this->validatedParent($request, ['supplier', 'comments']));

            if (! $this->drafts($request)->exists()) {
                throw ValidationException::withMessages(['error_message' => self::NO_ITEMS]);
            }

            $parent->order_date = now()->format('Y-m-d G:i:s');
            $parent->order_number = PurchaseOrderParent::nextNumber();
            $parent->order_by = $request->user()->id;
            $parent->status = 0;
            $parent->created_by = $request->user()->id;
            $parent->created_on = now()->format('Y-m-d G:i:s');
            $parent->save();

            $this->drafts($request)->update(['parent' => $parent->id]);

            return redirect()->route('purchaseOrder.admin')->with('success', 'Data was saved successfully');
        }

        return view('purchase-order.form', [
            'parent' => $parent,
            'lines' => Grid::for($this->drafts($request)->with('item0.unit0'))->compare('id')->paginate(PHP_INT_MAX),
            'items' => self::itemOptions(withUnit: true),
            'vendors' => Vendor::query()->orderBy('title')->pluck('title', 'id'),
        ]);
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1) {
            return redirect()->route('purchaseOrder.admin')->with('error', self::NOT_AUTHORIZED);
        }

        if ($request->isMethod('post')) {
            $parent->fill($this->validatedParent($request, ['status', 'supplier', 'comments']));

            if (! PurchaseOrder::query()->where('parent', $parent->id)->exists()) {
                throw ValidationException::withMessages(['error_message' => self::NO_ITEMS]);
            }

            $parent->save();

            return redirect()->route('purchaseOrder.admin')->with('success', 'Data was saved successfully');
        }

        return view('purchase-order.form', [
            'parent' => $parent,
            'lines' => $this->lines($id),
            'items' => self::itemOptions(withUnit: false),
            'vendors' => Vendor::query()->orderBy('title')->pluck('title', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::PURCHASE_ORDER),
        ]);
    }

    /**
     * Add a line (AJAX); answers with its id.
     */
    public function add(Request $request): Response
    {
        if ((string) $request->input('item') === '' || (string) $request->input('quantity') === '') {
            throw ValidationException::withMessages(['line' => ['Please select an Item.', 'Please enter Quantity.']]);
        }

        $line = new PurchaseOrder($request->validate(PurchaseOrder::rules(), [], PurchaseOrder::labelsFor(array_keys(PurchaseOrder::rules()))));
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    /**
     * Delete a line.
     */
    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        (PurchaseOrder::query()->find($id) ?? abort(404, 'The requested page does not exist.'))->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('purchaseOrder.admin')));
    }

    /**
     * Delete an order: it is only marked deleted (status 2).
     */
    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        PurchaseOrderParent::query()->whereKey($id)->update(['status' => 2]);

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect()->route('purchaseOrder.admin')->with('success', 'Purchase Order was deleted successfully.');
    }

    /**
     * Product options: "Title - code (unit)" on the new order form
     * (Product::getItemList()), "Title - code" on the edit form.
     *
     * @return array<int, string>
     */
    public static function itemOptions(bool $withUnit): array
    {
        return Product::query()->with('unit0')->orderBy('title')->get()
            ->mapWithKeys(fn ($product) => [$product->id => $product->title.' - '.$product->product_code.($withUnit ? ' ('.($product->unit0?->formal_name ?: 'N/A').')' : '')])
            ->all();
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function validatedParent(Request $request, array $fields): array
    {
        $rules = array_intersect_key(PurchaseOrderParent::rules(), array_flip($fields));

        return $request->validate($rules, [], PurchaseOrderParent::labelsFor(array_keys($rules)));
    }

    /**
     * @return Builder<PurchaseOrder>
     */
    private function drafts(Request $request): Builder
    {
        return PurchaseOrder::query()->where('parent', 0)->where('created_by', $request->user()->id);
    }

    private function lines(int $parentId): Grid
    {
        return Grid::for(PurchaseOrder::query()->where('parent', $parentId)->with('item0.unit0', 'item0.category0'))->compare('id')->paginate(PHP_INT_MAX);
    }

    private function find(int $id): PurchaseOrderParent
    {
        return PurchaseOrderParent::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
