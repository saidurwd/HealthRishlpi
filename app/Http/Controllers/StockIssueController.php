<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIssue;
use App\Models\StockIssueParent;
use App\Models\StockRequisition;
use App\Models\StockRequisitionHistory;
use App\Models\StockSummary;
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
 * Stock issues: lines are typed in or loaded from approved requisitions;
 * issuing (status 1) takes them out of stock.
 */
class StockIssueController extends Controller
{
    public function admin(): View
    {
        $grid = Grid::for(StockIssueParent::query()->withCount('lines')->with('issueBy'))
            ->compare('id')
            ->compare('issue_date', partial: true)
            ->compare('issue_number', partial: true)
            ->compare('issue_by')
            ->compare('total_amount', partial: true)
            ->compare('comments', partial: true)
            ->compare('status')
            ->compare('created_on', partial: true)
            ->compare('created_by')
            ->defaultOrder('issue_date', 'desc')
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize20'));

        return view('stock-issue.admin', [
            'grid' => $grid,
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::STOCK_ISSUE, userViewOnly: true),
        ]);
    }

    public function view(int $id): View
    {
        return view('stock-issue.view', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    public function print(int $id): View
    {
        $parent = $this->find($id);

        // Yii printed the first page of lines only
        return view('stock-issue.print', ['parent' => $parent, 'lines' => $this->lines($id, PHP_INT_MAX), 'total' => StockIssueParent::totalAmount($parent->id)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parent = new StockIssueParent;

        if ($request->isMethod('post')) {
            $parent->fill($request->validate(['comments' => ['nullable']]));

            if (! $this->drafts($request)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            $parent->issue_date = now()->format('Y-m-d G:i:s');
            $parent->issue_by = $request->user()->id;
            $parent->status = 0;
            $parent->created_by = $request->user()->id;
            $parent->created_on = now()->format('Y-m-d G:i:s');
            DocumentNumber::locked('stock_issue', function () use ($parent) {
                $parent->issue_number = StockIssueParent::nextNumber(User::loginName());
                $parent->save();
            });

            $this->drafts($request)->update(['parent' => $parent->id]);
            StockIssueParent::query()->whereKey($parent->id)->update(['total_amount' => StockIssueParent::totalAmount($parent->id)]);

            return redirect()->route('stockIssue.admin')->with('success', 'Data was saved successfully');
        }

        return view('stock-issue.form', array_merge(StockRequisitionController::lineOptions(), [
            'parent' => $parent,
            'lines' => Grid::for($this->drafts($request)->with('item0.unit0', 'store0', 'batch0'))->compare('id')->paginate(config('legacy.pageSize')),
            'requisitions' => $this->loadableRequisitionLines($request),
            'products' => Product::query()->orderBy('title')->get()->mapWithKeys(fn ($p) => [$p->id => $p->title.' - '.$p->product_code]),
            'storeTree' => Store::treeOptions(8),
        ]));
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1) {
            return redirect()->route('stockIssue.admin')->with('error', PurchaseOrderController::NOT_AUTHORIZED);
        }

        if ($request->isMethod('post')) {
            $parent->fill($request->validate(StockIssueParent::rules(), [], StockIssueParent::labelsFor(['status', 'comments'])));

            if (! StockIssue::query()->where('parent', $id)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            // Yii kept the total from creation even after quantity changes
            $parent->total_amount = StockIssueParent::totalAmount($parent->id);

            DB::transaction(function () use ($parent) {
                $parent->save();

                // Issuing takes every line out of stock and closes its requisition history
                if ((int) $parent->status === 1) {
                    foreach (StockIssue::query()->where('parent', $parent->id)->get() as $line) {
                        StockSummary::issue($line->store, $line->item, $line->batch, $line->quantity);

                        if ($line->reference) {
                            StockRequisitionHistory::query()->where('requisition_number', $line->reference)->where('issue_number', $line->id)->update(['converted' => 1]);
                        }
                    }
                }
            });

            return redirect()->route('stockIssue.admin')->with('success', 'Data was saved successfully');
        }

        return view('stock-issue.form', array_merge(StockRequisitionController::lineOptions(), [
            'parent' => $parent,
            'lines' => $this->lines($id),
            'statuses' => TransectionStatus::options(TransectionStatus::STOCK_ISSUE),
        ]));
    }

    /**
     * Special edit of an issued issue (super users only): quantity changes
     * move stock at once (adjustmentEdit).
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status !== 1 || ! $request->user()->isSuper()) {
            return redirect()->route('stockIssue.admin')->with('error', PurchaseOrderController::NOT_AUTHORIZED);
        }

        return view('stock-issue.edit', ['parent' => $parent, 'lines' => $this->lines($id)]);
    }

    /**
     * Add a typed-in line (AJAX) at the item's buy rate; the quantity must be on hand.
     */
    public function add(Request $request): Response
    {
        $line = new StockIssue(StockRequisitionController::validatedLine($request, StockIssue::rules(), StockIssue::class));
        $line->rate = StockRequisitionController::rateOrZero(Stock::itemBuyRate($line->item, $line->store, $line->batch));
        $line->amount = round((float) $line->quantity * (float) $line->rate, 6);
        StockRequisitionController::requireOnHand((float) $line->quantity, StockSummary::availableQty($line->store, $line->item, $line->batch));
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    /**
     * Load a requisition line (AJAX): its untaken quantity at its rate.
     */
    public function addsr(Request $request): Response
    {
        $requisitionLine = StockRequisition::query()->find((int) $request->input('id')) ?? abort(404, 'The requested page does not exist.');
        $quantity = $requisitionLine->availableQuantity();

        StockRequisitionController::requireOnHand($quantity, StockSummary::availableQty($requisitionLine->store, $requisitionLine->item, $requisitionLine->batch));

        $line = new StockIssue;
        $line->forceFill([
            'parent' => 0, 'reference' => $requisitionLine->id, 'item' => $requisitionLine->item, 'quantity' => $quantity,
            'rate' => $requisitionLine->rate, 'amount' => $requisitionLine->amount, 'store' => $requisitionLine->store, 'batch' => $requisitionLine->batch,
            'created_by' => $request->user()->id, 'created_on' => now()->format('Y-m-d G:i:s'),
        ])->save();

        StockRequisitionHistory::create([
            'requisition_number' => $requisitionLine->id, 'issue_number' => $line->id, 'item' => $requisitionLine->item, 'quantity' => $quantity, 'converted' => 0,
            'created_by' => $request->user()->id, 'created_on' => now()->format('Y-m-d G:i:s'),
        ]);
        $requisitionLine->refreshConverted(reset: false);

        return response((string) $line->id);
    }

    /**
     * Change a line's quantity on a pending issue (AJAX).
     */
    public function adjustment(Request $request): Response
    {
        $line = $this->adjustedLine($request);
        $line->save();
        $line->syncRequisitionHistory();

        return response((string) $line->id);
    }

    /**
     * Change a line's quantity on an issued issue: the old quantity goes
     * back to stock and the new one is taken out.
     */
    public function adjustmentEdit(Request $request): Response
    {
        $line = $this->adjustedLine($request);
        $previous = $line->getOriginal('quantity');
        $line->save();
        $line->syncRequisitionHistory();
        StockSummary::receive($line->store, $line->item, $line->batch, $previous);
        StockSummary::issue($line->store, $line->item, $line->batch, $line->quantity);

        return response((string) $line->id);
    }

    /**
     * Delete a line; a line loaded from a requisition gives the quantity back to it.
     */
    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        $line = StockIssue::query()->find($id) ?? abort(404, 'The requested page does not exist.');

        if ($line->reference > 0) {
            StockRequisitionHistory::query()->where('requisition_number', $line->reference)->where('issue_number', $line->id)->delete();
            // Yii called count() on the requisition model here, which fails on PHP 8
            $line->requisition0?->refreshConverted();
        }

        $line->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('stockIssue.admin')));
    }

    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        StockIssueParent::query()->whereKey($id)->update(['status' => 2]);

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect()->route('stockIssue.admin')->with('success', 'Stock Issue was deleted successfully.');
    }

    private function adjustedLine(Request $request): StockIssue
    {
        $line = StockIssue::query()->find((int) $request->input('id')) ?? abort(404, 'The requested page does not exist.');

        if ($request->input('type') === 'quantity') {
            $request->validate(['adjustment' => ['required', 'max:18']], [], ['adjustment' => 'Quantity']);
            $onHand = StockSummary::availableQty($line->store, $line->item, $line->batch) + (float) $line->quantity;
            $line->quantity = $request->input('adjustment');
            $line->amount = (float) $line->quantity * (float) $line->rate;
            StockRequisitionController::requireOnHand((float) $line->quantity, $onHand);
        }

        return $line;
    }

    /**
     * Lines of approved requisitions not yet issued
     * (StockRequisition::search_stock_requisition()), for "Load from SR".
     */
    private function loadableRequisitionLines(Request $request): Grid
    {
        $query = StockRequisition::query()
            ->select('stock_requisition.*')
            ->join('stock_requisition_parent as parent0', 'parent0.id', '=', 'stock_requisition.parent')
            ->where('parent0.status', 1)
            ->where('stock_requisition.converted', 0)
            ->with('parent0', 'item0', 'store0', 'batch0');

        $number = (string) data_get($request->query('StockRequisition'), 'parentRequisitionNumber', '');
        if ($number !== '') {
            $query->where('parent0.requisition_number', 'like', '%'.$number.'%');
        }

        return Grid::for($query)
            ->compare('item', column: 'stock_requisition.item')
            ->compare('store', column: 'stock_requisition.store')
            ->compare('batch', column: 'stock_requisition.batch')
            ->compare('quantity', partial: true, column: 'stock_requisition.quantity')
            ->compare('rate', partial: true, column: 'stock_requisition.rate')
            ->compare('amount', partial: true, column: 'stock_requisition.amount')
            ->compare('created_on', column: 'stock_requisition.created_on')
            ->defaultOrder('parent0.requisition_date', 'desc')
            ->paginate(config('legacy.pageSize20'));
    }

    /**
     * @return Builder<StockIssue>
     */
    private function drafts(Request $request): Builder
    {
        return StockIssue::query()->where('parent', 0)->where('created_by', $request->user()->id);
    }

    private function lines(int $parentId, ?int $perPage = null): Grid
    {
        return Grid::for(StockIssue::query()->where('parent', $parentId)->with('item0.unit0', 'store0', 'batch0', 'requisition0.parent0'))->compare('id')->paginate($perPage ?? config('legacy.pageSize'));
    }

    private function find(int $id): StockIssueParent
    {
        return StockIssueParent::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
