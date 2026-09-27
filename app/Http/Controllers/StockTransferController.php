<?php

namespace App\Http\Controllers;

use App\Models\StockSummary;
use App\Models\StockTransfer;
use App\Models\StockTransferParent;
use App\Models\Store;
use App\Models\TransectionStatus;
use App\Models\User;
use App\Support\DocumentNumber;
use App\Support\Grid;
use App\Support\Stock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Store transfers: move stock of an item/batch from one store to another
 * once the transfer is approved (status 1).
 */
class StockTransferController extends Controller
{
    public function admin(): View
    {
        $grid = Grid::for(StockTransferParent::query()->withCount('lines')->withSum('lines', 'total_amount')->with('transferBy'))
            ->compare('id')
            ->compare('transfer_date', partial: true)
            ->compare('transfer_number', partial: true)
            ->compare('transfer_by')
            ->compare('status')
            ->defaultOrder('transfer_date', 'desc')
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize20'));

        return view('stock-transfer.admin', [
            'grid' => $grid,
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::STOCK_TRANSFER, userViewOnly: true),
        ]);
    }

    public function view(int $id): View
    {
        return view('stock-transfer.view', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    public function print(int $id): View
    {
        return view('stock-transfer.print', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parent = new StockTransferParent;

        if ($request->isMethod('post')) {
            $parent->fill($request->validate(['comments' => ['nullable']]));

            if (! $this->drafts($request)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            $parent->transfer_date = now()->format('Y-m-d G:i:s');
            $parent->transfer_by = $request->user()->id;
            $parent->status = 0;
            $parent->created_by = $request->user()->id;
            $parent->created_on = now()->format('Y-m-d G:i:s');
            DocumentNumber::locked('stock_transfer', function () use ($parent) {
                $parent->transfer_number = StockTransferParent::nextNumber();
                $parent->save();
            });

            $this->drafts($request)->update(['parent' => $parent->id]);

            return redirect()->route('stockTransfer.admin')->with('success', 'Data was saved successfully');
        }

        return view('stock-transfer.form', array_merge(StockRequisitionController::lineOptions(), [
            'parent' => $parent,
            'lines' => Grid::for($this->drafts($request)->with('item0.unit0', 'storeFrom0', 'storeTo0', 'batch0'))->compare('id')->paginate(PHP_INT_MAX),
            'storeTree' => Store::treeOptions(8),
        ]));
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1) {
            return redirect()->route('stockTransfer.admin')->with('error', PurchaseOrderController::NOT_AUTHORIZED);
        }

        if ($request->isMethod('post')) {
            $parent->fill($request->validate(StockTransferParent::rules(), [], StockTransferParent::labelsFor(['status', 'comments'])));

            if (! StockTransfer::query()->where('parent', $id)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            DB::transaction(function () use ($parent) {
                $parent->save();

                if ((int) $parent->status === 1) {
                    foreach (StockTransfer::query()->where('parent', $parent->id)->get() as $line) {
                        StockSummary::receive($line->store_to, $line->item, $line->batch, $line->quantity);
                        StockSummary::issue($line->store_from, $line->item, $line->batch, $line->quantity);
                    }
                }
            });

            return redirect()->route('stockTransfer.admin')->with('success', 'Data was saved successfully');
        }

        return view('stock-transfer.form', array_merge(StockRequisitionController::lineOptions(), [
            'parent' => $parent,
            'lines' => $this->lines($id),
            'storeTree' => Store::treeOptions(8),
            'statuses' => TransectionStatus::options(TransectionStatus::STOCK_TRANSFER),
        ]));
    }

    /**
     * Add a line (AJAX) at the item's buy rate in the "from" store (Yii
     * passed too few arguments here and the page failed); the quantity must
     * be on hand there.
     */
    public function add(Request $request): Response
    {
        $input = StockRequisitionController::validatedLine(
            $request,
            StockTransfer::rules(),
            StockTransfer::class,
            ['item', 'store_from', 'quantity', 'batch', 'store_to'],
            ['Please select an Item', 'Please select a From Store', 'Please enter Quantity', 'Please select Lot Number', 'Please select a To Store'],
        );

        $line = new StockTransfer($input);
        $line->rate = StockRequisitionController::rateOrZero(Stock::itemBuyRate($line->item, $line->store_from, $line->batch));
        $line->total_amount = round((float) $line->quantity * (float) $line->rate, 6);
        StockRequisitionController::requireOnHand((float) $line->quantity, StockSummary::availableQty($line->store_from, $line->item, $line->batch));
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        (StockTransfer::query()->find($id) ?? abort(404, 'The requested page does not exist.'))->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('stockTransfer.admin')));
    }

    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        // A model save, so the change reaches the activity log
        StockTransferParent::query()->find($id)?->forceFill(['status' => 2])->save();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect()->route('stockTransfer.admin')->with('success', 'Stock Transfer was deleted successfully.');
    }

    /**
     * @return Builder<StockTransfer>
     */
    private function drafts(Request $request): Builder
    {
        return StockTransfer::query()->where('parent', 0)->where('created_by', $request->user()->id);
    }

    private function lines(int $parentId): Grid
    {
        return Grid::for(StockTransfer::query()->where('parent', $parentId)->with('item0.unit0', 'storeFrom0', 'storeTo0', 'batch0'))->compare('id')->paginate(PHP_INT_MAX);
    }

    private function find(int $id): StockTransferParent
    {
        return StockTransferParent::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
