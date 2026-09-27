<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceParent;
use App\Models\Patient;
use App\Models\PatientCategory;
use App\Models\PatientCategoryNew;
use App\Models\PatientPrescription;
use App\Models\Service;
use App\Models\StockSummary;
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
 * Patient invoices. Lines are added one by one (AJAX) before the invoice is
 * saved; approving an invoice (status 1) issues its medicines from stock.
 */
class InvoiceController extends Controller
{
    private const NO_ITEMS = 'Please add one or more items to the grid!';

    private const NOT_AUTHORIZED = 'You are not authorized to perform this action!';

    public function admin(): View
    {
        $grid = self::headerGrid(InvoiceParent::query())->paginate(config('legacy.pageSize20'));

        return view('invoice.admin', [
            'grid' => $grid,
            'patients' => Patient::query()->orderBy('name')->get(['id', 'name', 'pat_id'])->mapWithKeys(fn ($p) => [$p->id => $p->name.' ['.$p->pat_id.']']),
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'statuses' => TransectionStatus::options(TransectionStatus::INVOICE, userViewOnly: true),
        ]);
    }

    /**
     * The invoices tab of a patient's page (InvoiceParent::search_patient()).
     */
    public static function patientInvoicesGrid(int $patientId): Grid
    {
        return self::headerGrid(InvoiceParent::query()->where('patient', $patientId))->paginate(config('legacy.pageSize20'));
    }

    public function view(int $id): View
    {
        return view('invoice.view', ['parent' => $this->find($id), 'lines' => $this->linesGrid($id)]);
    }

    public function print(int $id): View
    {
        $parent = $this->find($id);

        return view('invoice.print', [
            'parent' => $parent,
            'lines' => $this->linesGrid($id),
            'total' => Invoice::totalAmount($parent->id),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $parent = new InvoiceParent;

        if ($request->isMethod('post')) {
            $parent->fill($this->validatedParent($request, ['patient', 'prescription', 'payment_status', 'comments']));

            if (! $this->drafts($request)->exists()) {
                throw ValidationException::withMessages(['error_message' => self::NO_ITEMS]);
            }

            $parent->invoice_date = now()->format('Y-m-d G:i:s');
            $parent->invoice_by = $request->user()->id;
            $parent->status = 0;
            $parent->created_by = $request->user()->id;
            $parent->created_on = now()->format('Y-m-d G:i:s');
            DocumentNumber::locked('invoice', function () use ($parent) {
                $parent->invoice_number = InvoiceParent::nextNumber(User::loginName());
                $parent->save();
            });

            $this->drafts($request)->update(['parent' => $parent->id]);
            $patient = Patient::query()->find($parent->patient);
            InvoiceParent::query()->whereKey($parent->id)->update([
                'total_amount' => Invoice::totalAmount($parent->id),
                'patient_category_new' => $patient?->category_new ?: null,
                'patient_category' => $patient?->category ?: null,
            ]);

            return redirect()->route('invoice.update', $parent->id)->with('success', 'Invoice was CREATED successfully.');
        }

        return view('invoice.create', array_merge($this->formData(), [
            'parent' => $parent,
            'lines' => Grid::for($this->drafts($request)->with('item0.unit0', 'service0', 'store0', 'batch0'))->compare('id')->paginate(config('legacy.pageSize100')),
        ]));
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1) {
            return redirect()->route('invoice.admin')->with('error', self::NOT_AUTHORIZED);
        }

        return $this->saveHeader($request, $parent, 'invoice.update');
    }

    /**
     * Restore a deleted invoice; super users only.
     */
    public function rollback(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status === 1 || ! $request->user()->isSuper()) {
            return redirect()->route('invoice.admin')->with('error', self::NOT_AUTHORIZED);
        }

        return $this->saveHeader($request, $parent, 'invoice.rollback');
    }

    /**
     * "Special edit" of an approved invoice; super users only. Quantities
     * changed here move stock straight away (adjustmentEdit).
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->find($id);

        if ((int) $parent->status !== 1 || ! $request->user()->isSuper()) {
            return redirect()->route('invoice.admin')->with('error', self::NOT_AUTHORIZED);
        }

        if ($request->isMethod('post')) {
            $parent->fill($this->validatedParent($request, ['patient', 'prescription', 'payment_status', 'patient_category_new', 'patient_category', 'comments']));
            $this->requireLines($parent);
            $parent->save();

            return redirect()->route('invoice.admin')->with('success', 'Invoice was UPDATED successfully.');
        }

        return view('invoice.edit', array_merge($this->formData(), ['parent' => $parent, 'lines' => $this->linesGrid($id)]));
    }

    /**
     * Add a line (AJAX): a medicine at the item's current rate less the
     * medicine discount, or a service at its rate (or the typed rate when
     * the service is priced manually) less the typed discount. Answers
     * with the new line's id.
     */
    public function add(Request $request): Response
    {
        // The legacy form refused to post until these were filled, listing all of them
        $needed = $request->input('servicetype') === 'Service'
            ? ['service' => 'Please select a Service.', 'discountamount' => 'Please enter discount Percentage/Cash.', 'quantity' => 'Please enter Quantity.']
            : ['item' => 'Please select an Item.', 'store' => 'Please select a Store.', 'batch' => 'Please select a Batch.', 'quantity' => 'Please enter Quantity.'];

        if (collect(array_keys($needed))->contains(fn ($field) => (string) $request->input($field) === '')) {
            throw ValidationException::withMessages(['line' => array_values($needed)]);
        }

        $rules = Invoice::rules();
        $input = $request->validate($rules, [], Invoice::labelsFor(array_keys($rules)));

        $line = new Invoice;
        $line->parent = (int) ($input['parent'] ?? 0) === 0 ? null : (int) $input['parent'];
        $line->servicetype = $input['servicetype'] ?? null;
        $line->quantity = $input['quantity'];
        $line->note = $input['note'] ?? null;

        if ($line->servicetype === 'Medicine') {
            $line->item = $input['item'] ?? null;
            $line->store = $input['store'] ?? null;
            $line->batch = $input['batch'] ?? null;
            $line->service = null;
            $line->rate = self::rateOrZero(Stock::itemRate($line->item, $line->store, $line->batch));
            $total = round((float) $line->quantity * (float) $line->rate, 6);
            $line->discount = round($total * ((int) config('legacy.discountMedicine') / 100), 6);
            $line->amount = round($total - $line->discount, 6);
        }

        if ($line->servicetype === 'Service') {
            $service = Service::query()->find($input['service'] ?? null);
            $line->service = $input['service'] ?? null;
            $line->item = $line->store = $line->batch = null;
            $line->rate = $service?->rate_status === 'Auto' ? $service->rate : ($input['rate'] ?? null);
            $total = round((float) $line->quantity * (float) $line->rate, 6);
            $discountAmount = (int) ($input['discountamount'] ?? 0);
            $line->discount = match ($input['discounttype'] ?? null) {
                'Percentage' => round($total * ($discountAmount / 100), 6),
                'Cash' => round($discountAmount, 6),
                default => null,
            };
            $line->amount = round($total - (float) $line->discount, 6);
        }

        $this->checkAvailability($line);
        $line->created_by = $request->user()->id;
        $line->created_on = now()->format('Y-m-d G:i:s');
        $line->save();

        return response((string) $line->id);
    }

    /**
     * Change a line's quantity on a draft or pending invoice (AJAX).
     */
    public function adjustment(Request $request): Response
    {
        $line = $this->adjustedLine($request);
        $line->save();

        return response((string) $line->id);
    }

    /**
     * Change a line's quantity on an approved invoice (special edit): the
     * old quantity goes back to stock and the new one is issued.
     */
    public function adjustmentEdit(Request $request): Response
    {
        $line = $this->adjustedLine($request);
        $previous = $line->getOriginal('quantity');
        $line->save();

        if ($line->item > 0) {
            StockSummary::receive($line->store, $line->item, $line->batch, $previous);
            StockSummary::issue($line->store, $line->item, $line->batch, $line->quantity);
        }

        return response((string) $line->id);
    }

    /**
     * Delete a line.
     */
    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        (Invoice::query()->find($id) ?? abort(404, 'The requested page does not exist.'))->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('invoice.admin')));
    }

    /**
     * Delete an invoice: it is only marked deleted (status 2).
     */
    public function remove(Request $request, int $id): RedirectResponse|Response
    {
        InvoiceParent::query()->whereKey($id)->update(['status' => 2]);

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect()->route('invoice.admin')->with('success', 'Invoice was deleted successfully.');
    }

    /**
     * Update / rollback: save the header with the recomputed total; on
     * approval issue every medicine line from stock.
     */
    private function saveHeader(Request $request, InvoiceParent $parent, string $view): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $parent->fill($this->validatedParent($request, ['patient', 'prescription', 'status', 'payment_status', 'patient_category_new', 'patient_category', 'comments']));
            $parent->total_amount = Invoice::totalAmount($parent->id);
            $this->requireLines($parent);

            DB::transaction(function () use ($parent) {
                $parent->save();

                if ((int) $parent->status === 1) {
                    foreach (Invoice::query()->where('parent', $parent->id)->where('item', '>', 0)->get() as $line) {
                        StockSummary::issue($line->store, $line->item, $line->batch, $line->quantity);
                    }
                }
            });

            return redirect()->route('invoice.admin')->with('success', 'Invoice was UPDATED successfully.');
        }

        return view($view, array_merge($this->formData(), ['parent' => $parent, 'lines' => $this->linesGrid($parent->id)]));
    }

    private function adjustedLine(Request $request): Invoice
    {
        $line = Invoice::query()->with('service0')->find((int) $request->input('id')) ?? abort(404, 'The requested page does not exist.');

        if ($request->input('type') === 'quantity') {
            $request->validate(['adjustment' => ['required', 'max:18']], [], ['adjustment' => 'Quantity']);
            $line->applyQuantity($request->input('adjustment'));
            $this->checkAvailability($line);
        }

        return $line;
    }

    /**
     * Medicines can only take what is free in the store/batch (checkAvailability()).
     */
    private function checkAvailability(Invoice $line): void
    {
        if ($line->servicetype === 'Medicine' && (float) $line->quantity > $line->availableQuantity()) {
            throw ValidationException::withMessages(['quantity' => 'Sorry! Request quantity not available!']);
        }
    }

    private function requireLines(InvoiceParent $parent): void
    {
        if (! Invoice::query()->where('parent', $parent->id)->exists()) {
            throw ValidationException::withMessages(['error_message' => self::NO_ITEMS]);
        }
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function validatedParent(Request $request, array $fields): array
    {
        $rules = array_intersect_key(InvoiceParent::rules(), array_flip($fields));

        return $request->validate($rules, [], InvoiceParent::labelsFor(array_keys($rules)));
    }

    /**
     * Lines added by this user that are not on an invoice yet.
     *
     * @return Builder<Invoice>
     */
    private function drafts(Request $request): Builder
    {
        return Invoice::query()
            ->where(fn ($query) => $query->whereNull('parent')->orWhere('parent', 0))
            ->where('created_by', $request->user()->id);
    }

    private function linesGrid(int $parentId): Grid
    {
        return Grid::for(Invoice::query()->where('parent', $parentId)->with('item0.unit0', 'service0', 'store0', 'batch0'))
            ->compare('id')
            ->paginate(config('legacy.pageSize100'));
    }

    /**
     * InvoiceParent::search() filters.
     *
     * @param  Builder<InvoiceParent>  $query
     */
    private static function headerGrid(Builder $query): Grid
    {
        return Grid::for($query->withCount('lines')->with('patient0', 'invoiceBy'))
            ->compare('id')
            ->compare('patient')
            ->compare('prescription')
            ->compare('invoice_date', partial: true)
            ->compare('invoice_number', partial: true)
            ->compare('invoice_by')
            ->compare('total_amount', partial: true)
            ->compare('patient_category_new')
            ->compare('patient_category')
            ->compare('comments', partial: true)
            ->compare('status')
            ->compare('payment_status')
            ->compare('created_on', partial: true)
            ->compare('created_by')
            ->defaultOrder('invoice_date', 'desc')
            ->defaultOrder('id', 'desc');
    }

    /**
     * Options of the line and header forms.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $services = Service::query()->orderBy('ordering')->orderBy('path')->get(['id', 'parent', 'title', 'rate_status']);
        $children = $services->where('parent', '>', 0)->groupBy('parent');

        return [
            // Top-level services are optgroups of their children (Service::getServiceCategory())
            'services' => $services->filter(fn ($s) => (int) $s->parent === 0)
                ->mapWithKeys(fn ($root) => [$root->title => ($children[$root->id] ?? collect())->pluck('title', 'id')->all()])
                ->all(),
            'manualServices' => $services->where('rate_status', 'Manual')->pluck('id')->all(),
            'items' => Stock::itemOptions(),
            'stores' => Stock::storeOptions(),
            'batches' => array_map(fn ($b) => $b + ['style' => 'color:'.($b['expired'] ? 'red' : 'green').';'], Stock::batchOptions()),
            'patients' => Patient::query()->orderByDesc('id')->get(['id', 'name', 'pat_id'])->mapWithKeys(fn ($p) => [$p->id => $p->name.' ['.$p->pat_id.']']),
            'prescriptions' => PatientPrescription::query()->orderByDesc('created_on')->get(['id', 'patient', 'pre_number'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => (string) $p->pre_number, 'chain' => (string) $p->patient])->all(),
            'statuses' => TransectionStatus::options(TransectionStatus::INVOICE),
            'categoriesNew' => PatientController::twoLevelOptions(PatientCategoryNew::class),
            'categories' => PatientController::twoLevelOptions(PatientCategory::class),
        ];
    }

    /**
     * Yii stored a missing LIFO/FIFO rate (false) as 0.
     */
    private static function rateOrZero(mixed $rate): mixed
    {
        return $rate === false ? 0 : $rate;
    }

    private function find(int $id): InvoiceParent
    {
        return InvoiceParent::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
