<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Disease;
use App\Models\Patient;
use App\Models\PatientCategory;
use App\Models\PatientCategoryNew;
use App\Models\PatientGrade;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\StockSummary;
use App\Models\Store;
use App\Support\Reports;
use App\Support\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The Reports menu. Each report has a screen (filter form + table; the
 * form posts back to the same URL) and a printable page ("...print") that
 * takes the filters from the query string.
 */
class ReportController extends Controller
{
    public function stocksummary(Request $request): View
    {
        return $this->stockSummaryReport($request, false);
    }

    public function stocksummaryprint(Request $request): View
    {
        return $this->stockSummaryReport($request, true);
    }

    public function stockreceive(Request $request): View
    {
        return $this->stockReceiveReport($request, false);
    }

    public function stockreceiveprint(Request $request): View
    {
        return $this->stockReceiveReport($request, true);
    }

    public function sales(Request $request): View
    {
        return $this->salesReport($request, false);
    }

    public function salesprint(Request $request): View
    {
        return $this->salesReport($request, true);
    }

    public function expiration(Request $request): View
    {
        return $this->expirationReport($request, false);
    }

    public function expirationprint(Request $request): View
    {
        return $this->expirationReport($request, true);
    }

    public function register(Request $request): View
    {
        return $this->registerReport($request, false);
    }

    public function registerprint(Request $request): View
    {
        return $this->registerReport($request, true);
    }

    public function disease(Request $request): View
    {
        return $this->diseaseReport($request, false);
    }

    public function diseaseprint(Request $request): View
    {
        return $this->diseaseReport($request, true);
    }

    public function category(Request $request): View
    {
        return $this->categoryReport($request, false);
    }

    public function categoryprint(Request $request): View
    {
        return $this->categoryReport($request, true);
    }

    public function medicine(Request $request): View
    {
        return $this->billReport($request, false, 'medicine');
    }

    public function medicineprint(Request $request): View
    {
        return $this->billReport($request, true, 'medicine');
    }

    public function service(Request $request): View
    {
        return $this->billReport($request, false, 'service');
    }

    public function serviceprint(Request $request): View
    {
        return $this->billReport($request, true, 'service');
    }

    public function mincome(Request $request): View
    {
        return $this->incomeReport($request, false);
    }

    public function mincomeprint(Request $request): View
    {
        return $this->incomeReport($request, true);
    }

    public function periodstock(Request $request): View
    {
        return $this->periodStockReport($request, false);
    }

    public function periodstockprint(Request $request): View
    {
        return $this->periodStockReport($request, true);
    }

    public function patinvoice(Request $request): View
    {
        return $this->patientInvoiceReport($request, false);
    }

    public function patinvoiceprint(Request $request): View
    {
        return $this->patientInvoiceReport($request, true);
    }

    public function registerphysio(Request $request): View
    {
        return $this->registerPhysioReport($request, false);
    }

    public function registerphysioprint(Request $request): View
    {
        return $this->registerPhysioReport($request, true);
    }

    public function prescription(Request $request): View
    {
        return $this->prescriptionReport($request, false);
    }

    public function prescriptionprint(Request $request): View
    {
        return $this->prescriptionReport($request, true);
    }

    public function contactregister(Request $request): View
    {
        return $this->contactRegisterReport($request, false);
    }

    public function contactregisterprint(Request $request): View
    {
        return $this->contactRegisterReport($request, true);
    }

    /**
     * "All Services" printout (the PRINT button of the Service grid).
     */
    public function allserviceprint(): View
    {
        return view('report.allserviceprint', [
            'services' => Service::query()->with('parentRow')->orderBy('path')->limit(config('legacy.pageSize1000'))->get(),
            'grades' => PatientGrade::query()->pluck('title', 'id'),
        ]);
    }

    private function stockSummaryReport(Request $request, bool $print): View
    {
        $f = $print
            ? ['cid' => $request->query('cid'), 'itemid' => $request->query('itemid'), 'store' => $request->query('store')]
            : ['cid' => $request->input('categoryid') ?: '', 'itemid' => $request->input('itemid') ?: '', 'store' => $request->input('store') ?: 0];

        return $this->report('stocksummary', $print, $f, [
            'rows' => Reports::stockSummary($f['cid'], $f['itemid'], $f['store']),
            'printQuery' => ['cid' => (int) $f['cid'], 'itemid' => (int) $f['itemid'], 'store' => $f['store']],
        ] + $this->productFilters());
    }

    private function stockReceiveReport(Request $request, bool $print): View
    {
        [$weekStart, $weekEnd] = self::thisWeek();
        $f = $print
            ? ['cid' => $request->query('cid'), 'itemid' => $request->query('itemid'), 'start_date' => (string) $request->query('start_date'), 'end_date' => (string) $request->query('end_date')]
            : ['cid' => $request->input('categoryid') ?: 0, 'itemid' => $request->input('itemid') ?: 0, 'start_date' => $request->input('start_date') ?: $weekStart, 'end_date' => $request->input('end_date') ?: $weekEnd];

        $rows = Reports::stockReceive($f['cid'], $f['itemid'], $f['start_date'], $f['end_date']);

        return $this->report('stockreceive', $print, $f, [
            'rows' => $rows,
            'stores' => self::storePaths(array_column($rows, 'storeid')),
            'units' => self::units(array_column($rows, 'itemid')),
            'printQuery' => ['cid' => (int) $f['cid'], 'itemid' => (int) $f['itemid'], 'start_date' => $f['start_date'], 'end_date' => $f['end_date']],
        ] + $this->productFilters());
    }

    private function salesReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-d'), date('Y-m-d'), ['type', 'service', 'product', 'category', 'status']);
        $rows = Reports::sales($f['start_date'], $f['end_date'], $f['type'], $f['service'], $f['product'], $f['category'], $f['status']);

        return $this->report('sales', $print, $f, [
            'rows' => $rows,
            'patients' => Patient::query()->whereKey(array_unique(array_column($rows, 'patient')))->pluck('pat_id', 'id'),
            'products' => Product::query()->whereKey(array_unique(array_column($rows, 'item')))->pluck('title', 'id'),
            'services' => Service::query()->whereKey(array_unique(array_column($rows, 'service')))->pluck('title', 'id'),
            'serviceOptions' => self::serviceGroups(),
            'productOptions' => self::productGroups(),
            'subCategoryOptions' => PatientController::twoLevelOptions(PatientCategory::class),
        ]);
    }

    private function expirationReport(Request $request, bool $print): View
    {
        $f = [
            'item' => $request->input('item') ?: '',
            'store' => $request->input('store') ?: '',
            'expiry' => $request->input('expiry') ?: 0,
        ];
        $rows = Reports::expiration($f['item'], $f['store'], $f['expiry']);

        return $this->report('expiration', $print, $f, [
            'rows' => $rows,
            'batches' => Batch::query()->whereKey(array_unique(array_column($rows, 'batch')))->pluck('title', 'id'),
            'stores' => self::storePaths(array_column($rows, 'storeid')),
            'products' => Product::query()->orderBy('title')->pluck('title', 'id'),
            'storeOptions' => Store::query()->orderBy('title')->pluck('title', 'id'),
            'printQuery' => ['item' => (int) $f['item'], 'store' => (int) $f['store'], 'expiry' => $f['expiry']],
        ]);
    }

    private function registerReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'), ['category' => 0, 'category_new' => 0]);
        $rows = Reports::patientRegister($f['start_date'], $f['end_date'], $f['category'], $f['category_new']);

        return $this->report('register', $print, $f, ['rows' => $rows] + $this->patientLookups($rows) + $this->categoryFilters());
    }

    private function diseaseReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'));

        return $this->report('disease', $print, $f, [
            'rows' => Reports::disease($f['start_date'], $f['end_date']),
            'diseases' => Disease::query()->pluck('title', 'id'),
        ]);
    }

    private function categoryReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'));
        $empty = ['AGE_GROUP_1' => 0, 'AGE_GROUP_2' => 0, 'AGE_GROUP_3' => 0, 'AGE_GROUP_4' => 0, 'total' => 0];
        $ages = Reports::patientAttendanceAge($f['start_date'], $f['end_date']);

        // Yii took the first row as male and the second as female, but the
        // enum sorts Female first, so it showed the two columns swapped
        $bySex = collect($ages)->keyBy('sex');

        return $this->report('category', $print, $f, [
            'male' => $bySex->get('Male', $empty),
            'female' => $bySex->get('Female', $empty),
            'sexes' => Reports::patientAttendanceSex($f['start_date'], $f['end_date']),
        ]);
    }

    private function billReport(Request $request, bool $print, string $kind): View
    {
        $f = $this->dated($request, $print, date('Y-m-d'), date('Y-m-d'), ['category', 'category_new', 'service', 'status']);
        $rows = $kind === 'medicine'
            ? Reports::medicineBill($f['start_date'], $f['end_date'], $f['category'], $f['category_new'], $f['service'], $f['status'])
            : Reports::serviceBill($f['start_date'], $f['end_date'], $f['category'], $f['category_new'], $f['service'], $f['status']);

        return $this->report($kind, $print, $f, [
            'rows' => $rows,
            'aliasesNew' => PatientCategoryNew::query()->pluck('alias', 'id'),
            'aliases' => PatientCategory::query()->pluck('alias', 'id'),
            'grades' => PatientGrade::query()->pluck('title', 'id'),
            'serviceTree' => self::serviceTree(),
            'serviceTitle' => $f['service'] ? Service::query()->whereKey($f['service'])->value('title') : null,
        ] + $this->categoryFilters());
    }

    private function incomeReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-d'), date('Y-m-d'), ['product']);
        $rows = array_map(function ($row) {
            // Buy rate of the item over all stores and batches (genarateItemBuyRate($item, 0, 0))
            $row['buy_amount'] = (float) Stock::itemBuyRate($row['item'], 0, 0) * (float) $row['quantity'];
            $row['income'] = (float) $row['sale_amount'] - $row['buy_amount'];

            return $row;
        }, Reports::medicineIncome($f['start_date'], $f['end_date'], $f['product']));

        return $this->report('mincome', $print, $f, [
            'rows' => $rows,
            'products' => Product::query()->pluck('title', 'id'),
            'productOptions' => self::productGroups(),
        ]);
    }

    private function periodStockReport(Request $request, bool $print): View
    {
        $f = $print
            ? ['cid' => $request->query('cid'), 'itemid' => $request->query('itemid'), 'store' => $request->query('store'), 'start_date' => (string) $request->query('start_date'), 'end_date' => (string) $request->query('end_date')]
            : ['cid' => $request->input('categoryid') ?: '', 'itemid' => $request->input('itemid') ?: '', 'store' => $request->input('store') ?: 0, 'start_date' => $request->input('start_date') ?: date('Y-m-01'), 'end_date' => $request->input('end_date') ?: date('Y-m-d')];

        return $this->report('periodstock', $print, $f, [
            'rows' => Reports::periodWiseStock($f['cid'], $f['itemid'], $f['store'], $f['start_date'], $f['end_date']),
            'printQuery' => ['cid' => (int) $f['cid'], 'itemid' => (int) $f['itemid'], 'store' => $f['store'], 'start_date' => $f['start_date'], 'end_date' => $f['end_date']],
        ] + $this->productFilters());
    }

    private function patientInvoiceReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'), ['patient', 'status']);

        return $this->report('patinvoice', $print, $f, [
            'rows' => Reports::patientInvoice($f['start_date'], $f['end_date'], $f['patient'], $f['status']),
            'patientOptions' => self::patientOptions(),
            'patientName' => $f['patient'] ? Patient::query()->whereKey($f['patient'])->value('name') : null,
        ]);
    }

    private function registerPhysioReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'), ['category' => 0, 'category_new' => 0]);
        $rows = Reports::patientRegisterPhysio($f['start_date'], $f['end_date'], $f['category'], $f['category_new']);

        return $this->report('registerphysio', $print, $f, [
            'rows' => $rows,
            'grades' => PatientGrade::query()->pluck('title', 'id'),
        ] + $this->patientLookups($rows) + $this->categoryFilters());
    }

    private function prescriptionReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'), ['patient', 'type' => 0]);

        return $this->report('prescription', $print, $f, [
            'rows' => Reports::invoiceByPrescription($f['start_date'], $f['end_date'], $f['patient'], $f['type']),
            'patientOptions' => self::patientOptions(),
            'patientName' => $f['patient'] ? Patient::query()->whereKey($f['patient'])->value('name') : null,
        ]);
    }

    private function contactRegisterReport(Request $request, bool $print): View
    {
        $f = $this->dated($request, $print, date('Y-m-01'), date('Y-m-t'), ['category' => 0, 'category_new' => 0, 'admission']);
        $rows = Reports::patientContactRegister($f['start_date'], $f['end_date'], $f['category'], $f['category_new'], $f['admission']);

        return $this->report('contactregister', $print, $f, ['rows' => $rows] + $this->patientLookups($rows) + $this->categoryFilters());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $data
     */
    private function report(string $name, bool $print, array $filters, array $data): View
    {
        return view('report.'.$name, $data + [
            'print' => $print,
            'report' => $name,
            'f' => $filters,
            'printQuery' => $filters,
        ]);
    }

    /**
     * Date range plus other filters. The screen reads the posted form
     * (empty values fall back to the defaults), the print page its query
     * string as the screen's print link wrote it.
     *
     * @param  array<int|string, mixed>  $others  name, or name => default (null when omitted)
     * @return array<string, mixed>
     */
    private function dated(Request $request, bool $print, string $start, string $end, array $others = []): array
    {
        $f = [
            'start_date' => (string) ($print ? $request->query('start_date') : ($request->input('start_date') ?: $start)),
            'end_date' => (string) ($print ? $request->query('end_date') : ($request->input('end_date') ?: $end)),
        ];

        foreach ($others as $key => $default) {
            [$name, $default] = is_int($key) ? [$default, null] : [$key, $default];
            $f[$name] = $print ? $request->query($name) : ($request->input($name) ?: $default);
        }

        return $f;
    }

    /**
     * Sunday to Saturday of the current week (the stock receive default).
     *
     * @return array{0: string, 1: string}
     */
    private static function thisWeek(): array
    {
        $today = strtotime(date('Y-m-d'));
        $start = date('w', $today) == 0 ? $today : strtotime('last sunday', $today);

        return [date('Y-m-d', $start), date('Y-m-d', strtotime('next saturday', $start))];
    }

    /**
     * Category tree and products (chained to their category) for the stock reports.
     *
     * @return array<string, mixed>
     */
    private function productFilters(): array
    {
        return [
            'categoryOptions' => ProductCategory::treeOptions(4),
            'itemOptions' => Product::query()->orderBy('title')->get(['id', 'title', 'category'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => $p->title, 'chain' => (string) $p->category])->all(),
            'storeOptions' => Store::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryFilters(): array
    {
        return [
            'categoryNewOptions' => PatientController::twoLevelOptions(PatientCategoryNew::class),
            'subCategoryOptions' => PatientController::twoLevelOptions(PatientCategory::class),
        ];
    }

    /**
     * Full addresses and category paths for rows with patient / category ids.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function patientLookups(array $rows): array
    {
        return [
            'addresses' => Patient::query()->with('thana0', 'district0')->whereKey(array_unique(array_column($rows, 'id')))->get()
                ->mapWithKeys(fn ($p) => [$p->id => $p->fullAddress()]),
            'pathsNew' => self::paths(PatientCategoryNew::class, array_column($rows, 'category_new')),
            'paths' => self::paths(PatientCategory::class, array_column($rows, 'category')),
        ];
    }

    /**
     * @param  class-string<PatientCategory|PatientCategoryNew|Store>  $model
     * @param  array<int, mixed>  $ids
     * @return Collection<int, string>
     */
    private static function paths(string $model, array $ids): Collection
    {
        return $model::query()->whereKey(array_unique(array_filter($ids)))->get()->mapWithKeys(fn ($row) => [$row->id => $row->fullPath()]);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return Collection<int, string>
     */
    private static function storePaths(array $ids): Collection
    {
        return self::paths(Store::class, $ids);
    }

    /**
     * @param  array<int, mixed>  $itemIds
     * @return Collection<int, string>
     */
    private static function units(array $itemIds): Collection
    {
        return Product::query()->with('unit0')->whereKey(array_unique($itemIds))->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->unit0?->formal_name ?: 'N/A']);
    }

    /**
     * Top-level services with their children, indented (Service::getServiceCategorySearch()).
     *
     * @return array<int, string>
     */
    private static function serviceTree(): array
    {
        $services = Service::query()->orderBy('ordering')->orderBy('path')->get(['id', 'parent', 'title']);
        $children = $services->where('parent', '>', 0)->groupBy('parent');
        $options = [];

        foreach ($services->filter(fn ($s) => (int) $s->parent === 0) as $root) {
            $options[$root->id] = $root->title;
            foreach ($children[$root->id] ?? [] as $child) {
                $options[$child->id] = "\u{00A0}\u{00A0}".$child->title;
            }
        }

        return $options;
    }

    /**
     * Services grouped under their parents (Service::getServiceCategoryReport()).
     *
     * @return array<string, array<int, string>>
     */
    private static function serviceGroups(): array
    {
        $services = Service::query()->orderBy('ordering')->orderBy('path')->get(['id', 'parent', 'title']);
        $children = $services->where('parent', '>', 0)->groupBy('parent');

        return $services->filter(fn ($s) => (int) $s->parent === 0)
            ->mapWithKeys(fn ($root) => [$root->title => ($children[$root->id] ?? collect())->pluck('title', 'id')->all()])
            ->all();
    }

    /**
     * Products grouped under their top-level categories (Product::getProductCategoryReport()).
     *
     * @return array<string, array<int, string>>
     */
    private static function productGroups(): array
    {
        $products = Product::query()->orderBy('title')->get(['id', 'title', 'category'])->groupBy('category');

        return ProductCategory::query()->where(fn ($q) => $q->whereNull('parent')->orWhere('parent', 0))->orderBy('path')->get(['id', 'title'])
            ->mapWithKeys(fn ($category) => [$category->title => ($products[$category->id] ?? collect())->pluck('title', 'id')->all()])
            ->all();
    }

    /**
     * Patient dropdown options, "Name [PAT#...]".
     *
     * @return Collection<int, non-falsy-string>
     */
    private static function patientOptions(): Collection
    {
        return Patient::query()->orderBy('name')->get(['id', 'name', 'pat_id'])->mapWithKeys(fn (Patient $p) => [$p->id => $p->name.' ['.$p->pat_id.']']);
    }

    /**
     * On-hand quantity used by the stock receive report's "Available" column.
     */
    public static function available(mixed $store, mixed $item, mixed $batch): float
    {
        return StockSummary::availableQty($store, $item, $batch);
    }
}
