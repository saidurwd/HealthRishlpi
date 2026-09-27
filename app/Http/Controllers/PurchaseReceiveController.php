<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHistory;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveDocument;
use App\Models\PurchaseReceiveParent;
use App\Models\StockSummary;
use App\Models\Store;
use App\Models\StoreDocument;
use App\Models\TransectionStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Goods receives (MRR). Lines are typed in or loaded from approved purchase
 * orders; receiving (status 1) adds them to stock.
 */
class PurchaseReceiveController extends Controller
{
    public function admin(): View
    {
        $grid = Grid::for(PurchaseReceiveParent::query()->withCount('lines')->withSum('lines', 'buy_amount')->withSum('lines', 'total_amount')->with('receiveBy', 'supplier0'))
            ->compare('id')
            ->compare('receive_date', partial: true)
            ->compare('receive_number', partial: true)
            ->compare('receive_by')
            ->compare('supplier')
            ->compare('comments')
            ->compare('status')
            ->compare('created_on', partial: true)
            ->compare('created_by')
            ->defaultOrder('receive_date', 'desc')
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize20'));

        return view('purchase-receive.admin', [
            'grid' => $grid,
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'vendors' => Vendor::query()->orderBy('title')->pluck('title', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::PURCHASE_RECEIVE, userViewOnly: true),
        ]);
    }

    public function view(int $id): View
    {
        return view('purchase-receive.view', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    public function print(int $id): View
    {
        return view('purchase-receive.print', ['parent' => $this->find($id), 'lines' => $this->lines($id)]);
    }

    /**
     * "Buy & Sale Price Comparison": every receive line.
     */
    public function price(): View
    {
        $grid = Grid::for(PurchaseReceive::query()->with('item0', 'batch0'))
            ->compare('id')
            ->compare('parent')
            ->compare('reference')
            ->compare('item')
            ->compare('quantity', partial: true)
            ->compare('rate', partial: true)
            ->compare('total_amount', partial: true)
            ->compare('buy_rate', partial: true)
            ->compare('buy_amount', partial: true)
            ->compare('store')
            ->compare('batch')
            ->defaultOrder('created_on', 'desc')
            ->defaultOrder('item')
            ->paginate(config('legacy.pageSize50'));

        return view('purchase-receive.price', ['grid' => $grid, 'products' => Product::query()->orderBy('title')->pluck('title', 'id')]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->missingMasterData()) {
            return $redirect;
        }

        if ($request->isMethod('post')) {
            if ($this->drafts($request)->where(fn ($query) => $this->withoutStoreOrBatch($query))->exists()) {
                return redirect()->route('purchaseReceive.create')->with('error', 'Store/Batch cannot be blank!');
            }

            $parent = new PurchaseReceiveParent($this->validatedParent($request, ['supplier', 'comments']));

            if (! $this->drafts($request)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            $parent->receive_date = now()->format('Y-m-d G:i:s');
            $parent->receive_number = PurchaseReceiveParent::nextNumber();
            $parent->receive_by = $request->user()->id;
            $parent->status = 0;
            $parent->created_by = $request->user()->id;
            $parent->created_on = now()->format('Y-m-d G:i:s');
            $parent->save();

            $this->drafts($request)->update(['parent' => $parent->id]);
            StoreDocument::storeUploads(StoreDocument::PURCHASE_RECEIVE, $parent->id, $request->file('doc_file', []), $request->user()->id);

            return redirect()->route('purchaseReceive.admin')->with('success', 'Data was saved successfully');
        }

        return view('purchase-receive.form', array_merge($this->formData($request), [
            'parent' => new PurchaseReceiveParent,
            'lines' => Grid::for($this->drafts($request)->with('item0.unit0', 'batch0')->withCount('documents'))->compare('id')->paginate(PHP_INT_MAX),
        ]));
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1) {
            return redirect()->route('purchaseReceive.admin')->with('error', PurchaseOrderController::NOT_AUTHORIZED);
        }

        if ($request->isMethod('post')) {
            if (PurchaseReceive::query()->where('parent', $id)->where(fn ($query) => $this->withoutStoreOrBatch($query))->exists()) {
                return redirect()->route('purchaseReceive.update', $id)->with('error', 'Store/Batch cannot be blank!');
            }

            $parent->fill($this->validatedParent($request, ['status', 'supplier', 'comments']));

            if (! PurchaseReceive::query()->where('parent', $id)->exists()) {
                throw ValidationException::withMessages(['error_message' => PurchaseOrderController::NO_ITEMS]);
            }

            DB::transaction(function () use ($parent) {
                $parent->save();

                // Receiving adds every line to stock and closes its purchase order history
                if ((int) $parent->status === 1) {
                    foreach (PurchaseReceive::query()->where('parent', $parent->id)->get() as $line) {
                        StockSummary::receive($line->store, $line->item, $line->batch, $line->quantity);

                        if ($line->reference) {
                            PurchaseOrderHistory::query()->where('po_number', $line->reference)->where('pr_number', $line->id)->update(['converted' => 1]);
                        }
                    }
                }
            });

            StoreDocument::storeUploads(StoreDocument::PURCHASE_RECEIVE, $parent->id, $request->file('doc_file', []), $request->user()->id);

            return redirect()->route('purchaseReceive.admin')->with('success', 'Data was saved successfully');
        }

        return view('purchase-receive.form', array_merge($this->formData($request), [
            'parent' => $parent,
            'lines' => $this->lines($id),
            'statuses' => TransectionStatus::options(TransectionStatus::PURCHASE_RECEIVE),
        ]));
    }

    /**
     * Special edit of a received MRR (super users only): quantity and rate
     * changes apply at once (adjustmentEdit).
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status !== 1 || ! $request->user()->isSuper()) {
            return redirect()->route('purchaseReceive.admin')->with('error', PurchaseOrderController::NOT_AUTHORIZED);
        }

        return view('purchase-receive.edit', ['parent' => $parent, 'lines' => $this->lines($id)]);
    }

    /**
     * Add a typed-in line (AJAX). The expiry picks the batch with that
     * expiry date, or creates one titled after the date (2030-01-31 →
     * "20300131"). Answers with the line's id.
     */
    public function add(Request $request): Response
    {
        if (collect(['item', 'store', 'quantity', 'buy_rate', 'rate', 'expiry'])->contains(fn ($field) => (string) $request->input($field) === '')) {
            throw ValidationException::withMessages(['line' => ['Please select an Item', 'Please select a Store', 'Please enter Quantity', 'Please enter Buy Rate', 'Please enter Sale Rate', 'Please select Expiry']]);
        }

        $expiry = (string) $request->input('expiry');
        $batch = Batch::query()->where('expiry', $expiry)->first()
            ?? Batch::create(['title' => str_replace('-', '', $expiry), 'expiry' => $expiry]);

        $rules = PurchaseReceive::rules();
        $line = new PurchaseReceive($request->validate($rules, [], PurchaseReceive::labelsFor(array_keys($rules))));
        $line->batch = $batch->id;
        $line->total_amount = round((float) $line->quantity * (float) $line->rate, 6);
        $line->buy_amount = round((float) $line->quantity * (float) $line->buy_rate, 6);
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    /**
     * Add purchase order lines to the receive being written (parent 0) or
     * to receive `prp` (AJAX): ids is one id or a comma-separated list.
     * Each takes the order's untaken quantity, without store, batch or rate.
     */
    public function addpo(Request $request): Response
    {
        $parentId = (int) $request->input('prp', 0);
        $added = [];

        foreach (array_filter(explode(',', (string) $request->input('ids'))) as $orderLineId) {
            $order = PurchaseOrder::query()->find((int) $orderLineId);

            if ($order === null) {
                continue;
            }

            $quantity = $order->availableQuantity();
            $line = new PurchaseReceive;
            $line->forceFill([
                'parent' => $parentId, 'reference' => $order->id, 'item' => $order->item, 'quantity' => $quantity,
                'rate' => 0, 'store' => 0, 'batch' => 0,
                'created_by' => $request->user()->id, 'created_on' => now()->format('Y-m-d G:i:s'),
            ])->save();

            PurchaseOrderHistory::create([
                'po_number' => $order->id, 'pr_number' => $line->id, 'item' => $order->item, 'quantity' => $quantity, 'converted' => 0,
                'created_by' => $request->user()->id, 'created_on' => now()->format('Y-m-d G:i:s'),
            ]);
            $order->refreshConverted(reset: false);
            $added[] = $line->id;
        }

        return response(implode('', $added));
    }

    /**
     * Change a line's store, quantity or rate on a pending receive (AJAX).
     */
    public function adjustment(Request $request): Response
    {
        $line = $this->findLine((int) $request->input('id'));
        $value = $request->input('adjustment');

        match ($request->input('type')) {
            'store' => $line->store = $value,
            'quantity' => $this->applyQuantity($line, $value),
            'rate' => $this->applyRate($line, $value),
            default => null,
        };

        $line->save();

        if ($request->input('type') === 'quantity') {
            $line->syncOrderHistory();
        }

        return response((string) $line->id);
    }

    /**
     * Change a line's quantity or rate on a received MRR: the old quantity
     * leaves stock and the new one is added.
     */
    public function adjustmentEdit(Request $request): Response
    {
        $line = $this->findLine((int) $request->input('id'));
        $value = $request->input('adjustment');

        if ($request->input('type') === 'quantity') {
            $previous = $line->quantity;
            $this->applyQuantity($line, $value);
            $line->save();
            $line->syncOrderHistory();
            StockSummary::issue($line->store, $line->item, $line->batch, $previous);
            StockSummary::receive($line->store, $line->item, $line->batch, $line->quantity);
        }

        if ($request->input('type') === 'rate') {
            $this->applyRate($line, $value);
            $line->save();
        }

        return response((string) $line->id);
    }

    /**
     * Delete a line; a line loaded from a purchase order gives the quantity back to it.
     */
    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        $line = $this->findLine($id);

        if ($line->reference > 0) {
            PurchaseOrderHistory::query()->where('po_number', $line->reference)->where('pr_number', $line->id)->delete();
            $line->order0?->refreshConverted();
        }

        $line->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('purchaseReceive.admin')));
    }

    /**
     * Delete a receive: it is only marked deleted (status 2).
     */
    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        PurchaseReceiveParent::query()->whereKey($id)->update(['status' => 2]);

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect()->route('purchaseReceive.admin')->with('success', 'Purchase Receive was deleted successfully.');
    }

    /**
     * The files of one line: list and upload form (loaded into a modal).
     */
    public function upload(int $id): View
    {
        $line = $this->findLine($id);

        return view('purchase-receive._files', [
            'line' => $line,
            'documents' => Grid::for(PurchaseReceiveDocument::query()->where('receive_number', $line->id)->with('createdBy'), 'PurchaseReceiveDocument')->compare('id')->paginate(PHP_INT_MAX),
        ]);
    }

    public function docupload(Request $request): Response
    {
        $rules = PurchaseReceiveDocument::rules();
        $input = $request->validate($rules, [], PurchaseReceiveDocument::labelsFor(array_keys($rules)));

        $document = new PurchaseReceiveDocument(['receive_number' => $input['receive_number'], 'doc_title' => $input['doc_title'] ?? null]);
        if ($request->hasFile('doc_file')) {
            $name = time().'_'.str_replace(' ', '_', strtolower($request->file('doc_file')->getClientOriginalName()));
            $request->file('doc_file')->move(public_path('uploads/store'), $name);
            $document->doc_file = $name;
        }
        $document->created_by = $request->user()->id;
        $document->created_on = now()->format('Y-m-d G:i:s');
        $document->save();

        return response((string) $document->id);
    }

    public function deletefile(Request $request, int $id): RedirectResponse|Response
    {
        $document = PurchaseReceiveDocument::query()->find($id) ?? abort(404, 'The requested page does not exist.');
        $this->deleteUpload((string) $document->doc_file);
        $document->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('purchaseReceive.create')));
    }

    /**
     * Download a line's file.
     */
    public function downloadfile(int $id): BinaryFileResponse|RedirectResponse
    {
        $document = PurchaseReceiveDocument::query()->find($id) ?? abort(404, 'The requested page does not exist.');

        return $this->sendUpload((string) $document->doc_file, (string) $document->doc_title, route('purchaseReceive.admin'));
    }

    /**
     * Download a file attached to the receive itself.
     */
    public function download(int $id): BinaryFileResponse|RedirectResponse
    {
        $document = StoreDocument::query()->find($id) ?? abort(404, 'The requested page does not exist.');

        return $this->sendUpload((string) $document->doc_file, (string) $document->doc_title, route('purchaseReceive.view', (int) $document->transection_id));
    }

    /**
     * All files of a line as <id>.zip.
     */
    public function downloadall(int $id): BinaryFileResponse|RedirectResponse
    {
        $documents = PurchaseReceiveDocument::query()->where('receive_number', $id)->get();

        if ($documents->isEmpty()) {
            // Yii redirected to the view page of the line's id, not its receive's
            return redirect()->route('purchaseReceive.view', (int) (PurchaseReceive::query()->whereKey($id)->value('parent') ?: $id))
                ->with('error', "This Purchase Receive have no document's for download yet!");
        }

        $archive = storage_path('app/'.$id.'.zip');
        $zip = new ZipArchive;
        $zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($documents as $document) {
            if (is_file(public_path('uploads/store/'.$document->doc_file))) {
                $zip->addFile(public_path('uploads/store/'.$document->doc_file), (string) $document->doc_file);
            }
        }
        $zip->close();

        return response()->download($archive, $id.'.zip')->deleteFileAfterSend();
    }

    private function sendUpload(string $file, string $title, string $fallback): BinaryFileResponse|RedirectResponse
    {
        $path = public_path('uploads/store/'.$file);

        if ($file === '' || ! is_file($path)) {
            return redirect($fallback)->with('error', 'The file <strong>'.e($title).'</strong> does not exist');
        }

        return response()->download($path, basename($file));
    }

    private function deleteUpload(string $file): void
    {
        if ($file !== '' && is_file(public_path('uploads/store/'.$file))) {
            unlink(public_path('uploads/store/'.$file));
        }
    }

    /**
     * The new receive page needed products, suppliers and stores to exist.
     */
    private function missingMasterData(): ?RedirectResponse
    {
        return match (true) {
            ! Product::query()->exists() => redirect()->route('product.create')->with('error', 'You have no Product. Please create a Product first.'),
            ! Vendor::query()->exists() => redirect()->route('vendor.create')->with('error', 'You have no Supplier. Please create a Supplier first.'),
            ! Store::query()->exists() => redirect()->route('store.create')->with('error', 'You have no Store. Please create a Store first.'),
            default => null,
        };
    }

    private function applyQuantity(PurchaseReceive $line, mixed $quantity): void
    {
        $line->quantity = $quantity;
        $line->total_amount = (float) $line->quantity * (float) $line->rate;
        // Yii left the buy amount at the old quantity
        $line->buy_amount = (float) $line->quantity * (float) $line->buy_rate;
    }

    private function applyRate(PurchaseReceive $line, mixed $rate): void
    {
        $line->rate = $rate;
        $line->total_amount = (float) $line->quantity * (float) $line->rate;
    }

    /**
     * @param  Builder<PurchaseReceive>  $query
     */
    private function withoutStoreOrBatch(Builder $query): void
    {
        $query->where('batch', 0)->orWhereNull('batch')->orWhere('store', 0)->orWhereNull('store');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        return [
            'items' => PurchaseOrderController::itemOptions(withUnit: true),
            'stores' => Store::treeOptions(8),
            'vendors' => Vendor::query()->orderBy('title')->pluck('title', 'id'),
            'orders' => $this->loadableOrderLines($request),
            'products' => Product::query()->orderBy('title')->get()->mapWithKeys(fn ($p) => [$p->id => $p->title.' - '.$p->product_code]),
        ];
    }

    /**
     * Lines of approved purchase orders not yet fully received
     * (PurchaseOrder::search_purchase_receive()), for "Load from PO".
     */
    private function loadableOrderLines(Request $request): Grid
    {
        $query = PurchaseOrder::query()
            ->select('purchase_order.*')
            ->join('purchase_order_parent as parent0', 'parent0.id', '=', 'purchase_order.parent')
            ->where('parent0.status', 1)
            ->where('purchase_order.converted', 0)
            ->with('parent0.supplier0', 'item0');

        $filters = (array) $request->query('PurchaseOrder', []);
        foreach (['parentOrderNumber' => 'parent0.order_number', 'parentSupplier' => 'parent0.supplier'] as $filter => $column) {
            if (($filters[$filter] ?? '') !== '') {
                $query->where($column, 'like', '%'.$filters[$filter].'%');
            }
        }

        return Grid::for($query)
            ->compare('item', column: 'purchase_order.item')
            ->compare('quantity', partial: true, column: 'purchase_order.quantity')
            ->compare('created_on', partial: true, column: 'purchase_order.created_on')
            ->defaultOrder('parent0.order_date', 'desc')
            ->paginate(PHP_INT_MAX);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function validatedParent(Request $request, array $fields): array
    {
        $rules = array_intersect_key(PurchaseReceiveParent::rules(), array_flip($fields)) + ['doc_file.*' => ['file']];

        return array_intersect_key(
            $request->validate($rules, [], PurchaseReceiveParent::labelsFor(array_keys($rules))),
            array_flip($fields),
        );
    }

    /**
     * @return Builder<PurchaseReceive>
     */
    private function drafts(Request $request): Builder
    {
        return PurchaseReceive::query()->where('parent', 0)->where('created_by', $request->user()->id);
    }

    private function lines(int $parentId): Grid
    {
        return Grid::for(PurchaseReceive::query()->where('parent', $parentId)->with('item0.unit0', 'batch0', 'store0', 'order0.parent0')->withCount('documents'))
            ->compare('id')
            ->paginate(PHP_INT_MAX);
    }

    private function findLine(int $id): PurchaseReceive
    {
        return PurchaseReceive::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }

    private function find(int $id): PurchaseReceiveParent
    {
        return PurchaseReceiveParent::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
