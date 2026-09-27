<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\StockIssue;
use App\Models\StockIssueParent;
use App\Models\StockRequisition;
use App\Models\StockRequisitionParent;
use App\Models\StockSummary;
use App\Models\TransectionStatus;
use App\Models\User;
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
 * Stock requisitions: lines are added one by one (AJAX); an approved
 * requisition becomes a stock issue ("Make me issue") or is loaded into one.
 */
class StockRequisitionController extends Controller
{
    // Yii's CActiveDataProvider default: the requisition line grids set no page size
    private const LINES_PER_PAGE = 10;

    public const LINE_FIELDS_MESSAGE = ['Please select an Item.', 'Please select a Store.', 'Please select a Batch.', 'Please enter Quantity.'];

    public function admin(): View
    {
        $grid = Grid::for(StockRequisitionParent::query()->withCount('lines')->with('requisitionBy'))
            ->compare('id')
            ->compare('requisition_date', partial: true)
            ->compare('requisition_number', partial: true)
            ->compare('requisition_by')
            ->compare('comments', partial: true)
            ->compare('status')
            ->compare('created_on', partial: true)
            ->compare('created_by')
            ->defaultOrder('requisition_date', 'desc')
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize20'));

        return view('stock-requisition.admin', [
            'grid' => $grid,
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::STOCK_REQUISITION, userViewOnly: true),
        ]);
    }

    public function view(int $id): View
    {
        $parent = $this->find($id);

        return view('stock-requisition.view', [
            'parent' => $parent,
            'lines' => $this->lines($id),
            'canConvert' => (int) $parent->status === 1 && ! StockRequisition::query()->where('parent', $id)->where('converted', 1)->exists(),
        ]);
    }

    public function print(int $id): View
    {
        // Yii printed the first page of lines only
        return view('stock-requisition.print', ['parent' => $this->find($id), 'lines' => $this->lines($id, PHP_INT_MAX)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parent = new StockRequisitionParent;

        if ($request->isMethod('post')) {
            $parent->fill($request->validate(['comments' => ['nullable']]));

            if (! $this->drafts($request)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            $parent->requisition_date = now()->format('Y-m-d G:i:s');
            $parent->requisition_by = $request->user()->id;
            $parent->requisition_number = StockRequisitionParent::nextNumber(User::loginName());
            $parent->status = 0;
            $parent->created_by = $request->user()->id;
            $parent->created_on = now()->format('Y-m-d G:i:s');
            $parent->save();

            $this->drafts($request)->update(['parent' => $parent->id]);

            return redirect()->route('stockRequisition.view', $parent->id)->with('success', 'Data was saved successfully');
        }

        return view('stock-requisition.form', array_merge(self::lineOptions(), [
            'parent' => $parent,
            'lines' => Grid::for($this->drafts($request)->with('item0.unit0', 'store0', 'batch0'))->compare('id')->paginate(self::LINES_PER_PAGE),
        ]));
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1) {
            return redirect()->route('stockRequisition.admin')->with('error', PurchaseOrderController::NOT_AUTHORIZED);
        }

        if ($request->isMethod('post')) {
            $parent->fill($request->validate(StockRequisitionParent::rules(), [], StockRequisitionParent::labelsFor(['status', 'comments'])));

            if (! StockRequisition::query()->where('parent', $id)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            $parent->save();

            return redirect()->route('stockRequisition.view', $parent->id)->with('success', 'Data was saved successfully');
        }

        return view('stock-requisition.form', array_merge(self::lineOptions(), [
            'parent' => $parent,
            'lines' => $this->lines($id),
            'statuses' => TransectionStatus::options(TransectionStatus::STOCK_REQUISITION),
        ]));
    }

    /**
     * Add a line (AJAX) at the item's buy rate; the quantity must be on hand.
     */
    public function add(Request $request): Response
    {
        $line = new StockRequisition(self::validatedLine($request, StockRequisition::rules(), StockRequisition::class));
        $line->rate = self::rateOrZero(Stock::itemBuyRate($line->item, $line->store, $line->batch));
        $line->amount = round((float) $line->quantity * (float) $line->rate, 6);
        self::requireOnHand((float) $line->quantity, StockSummary::availableQty($line->store, $line->item, $line->batch));
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    /**
     * "Make me issue": a pending stock issue with every unconverted line of
     * this requisition (lines no longer on hand are left out, as Yii's
     * failing validation did); the lines are then marked converted.
     */
    public function convertissue(Request $request, int $id): RedirectResponse
    {
        $issue = DB::transaction(function () use ($request, $id) {
            $issue = new StockIssueParent;
            $issue->forceFill([
                'issue_date' => now()->format('Y-m-d G:i:s'),
                'issue_number' => StockIssueParent::nextNumber(User::loginName()),
                'issue_by' => $request->user()->id,
                'status' => 0,
                'created_by' => $request->user()->id,
                'created_on' => now()->format('Y-m-d G:i:s'),
            ])->save();

            foreach (StockRequisition::query()->where('parent', $id)->where('converted', 0)->get() as $requisitionLine) {
                if ((float) $requisitionLine->quantity > StockSummary::availableQty($requisitionLine->store, $requisitionLine->item, $requisitionLine->batch)) {
                    continue;
                }

                (new StockIssue)->forceFill([
                    'parent' => $issue->id, 'reference' => $requisitionLine->id, 'item' => $requisitionLine->item,
                    'quantity' => $requisitionLine->quantity, 'rate' => $requisitionLine->rate, 'amount' => $requisitionLine->amount,
                    'store' => $requisitionLine->store, 'batch' => $requisitionLine->batch,
                    'created_by' => $request->user()->id, 'created_on' => now()->format('Y-m-d G:i:s'),
                ])->save();
            }

            StockIssueParent::query()->whereKey($issue->id)->update(['total_amount' => StockIssueParent::totalAmount($issue->id)]);
            StockRequisition::query()->where('parent', $id)->update(['converted' => 1]);

            return $issue;
        });

        return redirect()->route('stockIssue.update', $issue->id)->with('success', 'Data was saved successfully');
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        (StockRequisition::query()->find($id) ?? abort(404, 'The requested page does not exist.'))->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('stockRequisition.admin')));
    }

    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        StockRequisitionParent::query()->whereKey($id)->update(['status' => 2]);

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect()->route('stockRequisition.admin')->with('success', 'Stock Requisition was deleted successfully.');
    }

    /**
     * Product / store / batch options of the line forms (the invoice lists).
     *
     * @return array<string, mixed>
     */
    public static function lineOptions(): array
    {
        return [
            'items' => Stock::itemOptions(),
            'stores' => Stock::storeOptions(),
            'batches' => array_map(fn ($b) => $b + ['style' => 'color:'.($b['expired'] ? 'red' : 'green').';'], Stock::batchOptions()),
        ];
    }

    /**
     * The legacy forms refused to post a line without item, store, batch
     * and quantity; then the model rules.
     *
     * @param  array<string, array<int, mixed>>  $rules
     * @param  class-string<LegacyModel>  $model
     * @param  array<int, string>  $needed
     * @param  array<int, string>  $message
     * @return array<string, mixed>
     */
    public static function validatedLine(Request $request, array $rules, string $model, array $needed = ['item', 'store', 'batch', 'quantity'], array $message = self::LINE_FIELDS_MESSAGE): array
    {
        if (collect($needed)->contains(fn ($field) => (string) $request->input($field) === '')) {
            throw ValidationException::withMessages(['line' => $message]);
        }

        return $request->validate($rules, [], $model::labelsFor(array_keys($rules)));
    }

    /**
     * checkAvailability(): the quantity must not exceed what is on hand.
     */
    public static function requireOnHand(float $quantity, float $onHand): void
    {
        if ($quantity > $onHand) {
            throw ValidationException::withMessages(['quantity' => 'Sorry! Request quantity not available!']);
        }
    }

    /**
     * Yii stored a missing LIFO/FIFO rate (false) as 0.
     */
    public static function rateOrZero(mixed $rate): mixed
    {
        return $rate === false ? 0 : $rate;
    }

    /**
     * @return Builder<StockRequisition>
     */
    private function drafts(Request $request): Builder
    {
        return StockRequisition::query()->where('parent', 0)->where('created_by', $request->user()->id);
    }

    private function lines(int $parentId, int $perPage = self::LINES_PER_PAGE): Grid
    {
        return Grid::for(StockRequisition::query()->where('parent', $parentId)->with('item0.unit0', 'store0', 'batch0'))->compare('id')->paginate($perPage);
    }

    private function find(int $id): StockRequisitionParent
    {
        return StockRequisitionParent::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
