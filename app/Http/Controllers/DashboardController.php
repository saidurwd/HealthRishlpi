<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard: KPIs, trends and breakdowns (cached for five minutes),
 * a filter that reloads them as JSON, and a CSV export.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $data = Cache::remember('DashboardIndexData', 300, function () {
            $todayStart = date('Y-m-d 00:00:00');
            $tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));
            $monthStart = date('Y-m-01 00:00:00');
            $nextMonthStart = date('Y-m-01 00:00:00', strtotime('+1 month'));

            $totalPatients = DB::table('patient')->count();
            $monthlyRevenue = self::revenue($monthStart, $nextMonthStart);
            $prescriptionsToday = self::prescriptions($todayStart, $tomorrowStart);
            $admissions = DB::table('patient')->where('admission', 'Yes')->count();

            $lastMonthPatients = DB::table('patient')->where('created_on', '>=', date('Y-m-d H:i:s', strtotime('-2 months')))->where('created_on', '<', date('Y-m-d H:i:s', strtotime('-1 month')))->count();
            $lastMonthRevenue = self::revenue(date('Y-m-01 00:00:00', strtotime('-1 month')), $monthStart);
            $yesterdayPrescriptions = self::prescriptions(date('Y-m-d 00:00:00', strtotime('-1 day')), $todayStart);
            $lastWeekAdmissions = DB::table('patient')->where('admission', 'Yes')->where('created_on', '>=', date('Y-m-d H:i:s', strtotime('-14 days')))->where('created_on', '<', date('Y-m-d H:i:s', strtotime('-7 days')))->count();

            $patientsByDay = self::perDay(DB::table('patient'), 'created_on', 'COUNT(*)', date('Y-m-d 00:00:00', strtotime('-6 days')), $tomorrowStart);
            $revenueByDay = self::perDay(DB::table('invoice_parent'), 'invoice_date', 'COALESCE(SUM(total_amount),0)', date('Y-m-d 00:00:00', strtotime('-6 days')), $tomorrowStart);

            $trendMonth = ['labels' => [], 'patients' => [], 'revenue' => []];
            foreach (self::weekPeriods() as $period) {
                $trendMonth['labels'][] = $period['label'];
                $trendMonth['patients'][] = DB::table('patient')->where('created_on', '>=', $period['start'])->where('created_on', '<', $period['end'])->count();
                $trendMonth['revenue'][] = (float) self::revenue($period['start'], $period['end']);
            }

            return array_merge([
                'totalPatients' => $totalPatients,
                'monthlyRevenue' => $monthlyRevenue,
                'prescriptionsToday' => $prescriptionsToday,
                'admissions' => $admissions,
                'patientTrend' => self::trend($totalPatients, $lastMonthPatients),
                'revenueTrend' => self::trend($monthlyRevenue, $lastMonthRevenue),
                'prescriptionTrend' => self::trend($prescriptionsToday, $yesterdayPrescriptions),
                'admissionTrend' => self::trend($admissions, $lastWeekAdmissions),
                'trendWeek' => self::lastSevenDays($patientsByDay, $revenueByDay),
                'trendMonth' => $trendMonth,
                'trendYear' => self::lastTwelveMonths(fn ($q) => $q),
                'demographics' => self::demographics(fn ($q) => $q),
                'departments' => self::chart(DB::table('patient as p')->join('patient_category_new as pcn', 'p.category_new', '=', 'pcn.id')
                    ->where(fn ($q) => $q->where('pcn.parent', 0)->orWhereNull('pcn.parent'))
                    ->groupBy('p.category_new', 'pcn.title')->orderByDesc('cnt')->select('pcn.title as label', DB::raw('COUNT(*) as cnt'))->get(), 'int'),
                'diseases' => self::chart(DB::table('patient_prescription as pp')->join('disease as d', 'pp.diagnosis', '=', 'd.id')
                    ->groupBy('pp.diagnosis', 'd.title')->orderByDesc('cnt')->limit(7)->select('d.title as label', DB::raw('COUNT(*) as cnt'))->get(), 'int'),
            ], self::common());
        });

        return view('dashboard.index', $data + [
            'categories' => DB::table('patient_category_new')->where('status', 'Active')->orderBy('title')->pluck('title', 'id'),
            'departmentOptions' => DB::table('department')->orderBy('title')->pluck('title', 'id'),
        ]);
    }

    /**
     * The filter bar (AJAX): dates and category narrow the patient figures,
     * "department" takes a patient category with its sub categories, as in
     * the Yii app.
     */
    public function ajaxFilter(Request $request): JsonResponse
    {
        $startDate = (string) $request->input('start_date', date('Y-m-01'));
        $endDate = (string) $request->input('end_date', date('Y-m-t'));
        $category = (string) $request->input('category', 'all');
        $department = (string) $request->input('department', 'all');

        $startDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) === 1 ? $startDate : date('Y-m-01');
        $endDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) === 1 ? $endDate : date('Y-m-t');
        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $data = Cache::remember('DashboardFilter_'.md5($startDate.$endDate.$category.$department), 300, function () use ($startDate, $endDate, $category, $department) {
            $start = $startDate.' 00:00:00';
            $end = date('Y-m-d 00:00:00', strtotime($endDate.' +1 day'));
            $todayStart = date('Y-m-d 00:00:00');
            $tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));

            $patients = function ($query) use ($start, $end, $category, $department) {
                $query->where('created_on', '>=', $start)->where('created_on', '<', $end);
                if ($category !== 'all') {
                    $query->where('category_new', (int) $category);
                }
                if ($department !== 'all') {
                    $query->whereIn('category_new', DB::table('patient_category_new')->where('id', (int) $department)->orWhere('parent', (int) $department)->select('id'));
                }

                return $query;
            };

            $trendStart = date('Y-m-d 00:00:00', strtotime('-20 days'));
            $patientsByDay = self::perDay($patients(DB::table('patient')), 'created_on', 'COUNT(*)', $trendStart, $tomorrowStart);
            $revenueByDay = self::perDay(DB::table('invoice_parent'), 'invoice_date', 'COALESCE(SUM(total_amount),0)', $trendStart, $tomorrowStart);

            $trendMonth = ['labels' => [], 'patients' => [], 'revenue' => []];
            foreach (self::weekPeriods() as $period) {
                $trendMonth['labels'][] = $period['label'];
                // Compared as strings, like Yii: "2026-09-20" < "2026-09-20 00:00:00"
                $trendMonth['patients'][] = $patientsByDay->filter(fn ($cnt, $day) => $day >= $period['start'] && $day < $period['end'])->sum();
                $trendMonth['revenue'][] = (float) $revenueByDay->filter(fn ($cnt, $day) => $day >= $period['start'] && $day < $period['end'])->sum();
            }

            $departmentQuery = DB::table('patient as p')->join('patient_category_new as pcn', 'p.category_new', '=', 'pcn.id');
            if ($department !== 'all') {
                $departmentQuery->where(fn ($q) => $q->where('pcn.id', (int) $department)->orWhere('pcn.parent', (int) $department));
            }

            $diseaseQuery = DB::table('patient_prescription as pp')->join('patient as p', 'pp.patient', '=', 'p.id')->join('disease as d', 'pp.diagnosis', '=', 'd.id');
            if ($category !== 'all') {
                $diseaseQuery->where('p.category_new', (int) $category);
            }

            return array_merge([
                'totalPatients' => $patients(DB::table('patient'))->count(),
                'monthlyRevenue' => self::revenue($start, $end),
                'prescriptionsToday' => self::prescriptions($todayStart, $tomorrowStart),
                'admissions' => $patients(DB::table('patient'))->where('admission', 'Yes')->count(),
                'trendWeek' => self::lastSevenDays($patientsByDay, $revenueByDay),
                'trendMonth' => $trendMonth,
                'trendYear' => self::lastTwelveMonths($patients),
                'demographics' => self::demographics($patients),
                'departments' => self::chart($departmentQuery->groupBy('p.category_new', 'pcn.title')->orderByDesc('cnt')->select('pcn.title as label', DB::raw('COUNT(*) as cnt'))->get(), 'int'),
                'diseases' => self::chart($diseaseQuery->groupBy('pp.diagnosis', 'd.title')->orderByDesc('cnt')->limit(7)->select('d.title as label', DB::raw('COUNT(*) as cnt'))->get(), 'int'),
            ], self::common());
        });

        return response()->json($data);
    }

    /**
     * KPIs and the last 100 invoices as CSV.
     */
    public function export(): Response
    {
        $recent = self::recentInvoices(100)->get();

        $csv = "Dashboard Export\n";
        $csv .= 'Generated On,'.date('Y-m-d H:i:s')."\n\n";
        $csv .= "KPIs\n";
        $csv .= 'Total Patients,'.DB::table('patient')->count()."\n";
        $csv .= 'Monthly Revenue,'.self::revenue(date('Y-m-01 00:00:00'), date('Y-m-01 00:00:00', strtotime('+1 month')))."\n";
        $csv .= 'Prescriptions Today,'.self::prescriptions(date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+1 day')))."\n";
        $csv .= 'Admissions,'.DB::table('patient')->where('admission', 'Yes')->count()."\n\n";
        $csv .= "Recent Patient Activity\n";
        $csv .= "Patient ID,Patient Name,Department,Date,Amount,Status\n";
        foreach ($recent as $row) {
            $csv .= $row->pat_id.',';
            $csv .= '"'.str_replace('"', '""', (string) $row->name).'",';
            $csv .= '"'.str_replace('"', '""', $row->dept ?: 'General').'",';
            $csv .= ($row->dt ?: date('Y-m-d', strtotime((string) $row->invoice_date))).',';
            $csv .= $row->total_amount.',';
            $csv .= ucfirst((string) $row->payment_status)."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=dashboard_export_'.date('Y-m-d_H-i-s').'.csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Figures that ignore the filters: service revenue, referrals,
     * districts, staff, stock alerts, the invoice heatmap and recent activity.
     *
     * @return array<string, mixed>
     */
    private static function common(): array
    {
        $heatmap = array_fill(0, 84, 0);
        foreach (DB::table('invoice_parent')->selectRaw('DAYOFWEEK(created_on) AS dow, HOUR(created_on) AS hod, COUNT(*) AS cnt')->groupByRaw('DAYOFWEEK(created_on), HOUR(created_on)')->get() as $row) {
            $day = (int) $row->dow - 1;
            $hour = (int) $row->hod - 8;
            if ($day >= 0 && $day < 7 && $hour >= 0 && $hour < 12) {
                $heatmap[$day * 12 + $hour] = (int) $row->cnt;
            }
        }

        return [
            'serviceRevenue' => self::chart(DB::table('invoice as i')->join('service as s', 'i.service', '=', 's.id')->where('i.service', '>', 0)
                ->groupBy('i.service', 's.title')->orderByDesc('cnt')->limit(6)->select('s.title as label', DB::raw('SUM('.self::column('i.amount').') as cnt'))->get(), 'float'),
            'referralSources' => self::chart(DB::table('patient')->whereNotNull('referred')->where('referred', '!=', '')
                ->groupBy('referred')->orderByDesc('cnt')->limit(8)->select('referred as label', DB::raw('COUNT(*) as cnt'))->get(), 'int'),
            'geographicData' => self::chart(DB::table('patient as p')->join('district as d', 'p.district', '=', 'd.id')
                ->groupBy('p.district', 'd.title')->orderByDesc('cnt')->limit(8)->select('d.title as label', DB::raw('COUNT(*) as cnt'))->get(), 'int'),
            'staffPerformance' => self::chart(DB::table('invoice_parent as ip')->join('user as u', 'ip.invoice_by', '=', 'u.id')
                ->groupBy('ip.invoice_by', 'u.full_name')->orderByDesc('cnt')->limit(8)->select('u.full_name as label', DB::raw('SUM('.self::column('ip.total_amount').') as cnt'))->get(), 'float'),
            'stockAlerts' => DB::table('stock_summary as ss')->join('product as p', 'ss.item', '=', 'p.id')->join('store as st', 'ss.store', '=', 'st.id')
                ->where('ss.quantity', '>', 0)->orderBy('ss.quantity')->limit(10)->get(['st.title as store', 'p.title as product', 'ss.quantity', 'ss.rate'])
                ->map(fn ($row) => ['store' => $row->store, 'product' => $row->product, 'quantity' => (float) $row->quantity, 'rate' => (float) $row->rate])->all(),
            'heatmap' => $heatmap,
            'recentActivity' => self::recentInvoices(8)->get()->map(function ($row) {
                $status = strtolower((string) $row->payment_status) ?: 'pending';

                return [
                    'id' => $row->pat_id,
                    'name' => $row->name,
                    'dept' => $row->dept ?: 'General',
                    'date' => $row->dt ?: date('Y-m-d', strtotime((string) $row->invoice_date)),
                    'service' => $row->dept ?: 'Consultation',
                    'amount' => (float) $row->total_amount,
                    'status' => $status,
                    'progress' => $status === 'paid' ? 100 : ($status === 'pending' ? 60 : 20),
                ];
            })->all(),
        ];
    }

    private static function recentInvoices(int $limit): Builder
    {
        return DB::table('invoice_parent as ip')
            ->join('patient as p', 'ip.patient', '=', 'p.id')
            ->leftJoin('patient_category_new as pcn', 'p.category_new', '=', 'pcn.id')
            ->orderByDesc('ip.id')
            ->limit($limit)
            ->select('ip.id', 'p.pat_id', 'p.name', 'pcn.title as dept', DB::raw('DATE('.self::column('ip.invoice_date').') as dt'), 'ip.invoice_date', 'ip.total_amount', 'ip.payment_status');
    }

    /**
     * A column for raw SQL: table aliases get the table prefix too ("invoice
     * as i" is `os_i`), so aliased columns must go through the grammar.
     */
    private static function column(string $column): string
    {
        return DB::getQueryGrammar()->wrap($column);
    }

    private static function revenue(string $start, string $end): mixed
    {
        return DB::table('invoice_parent')->where('invoice_date', '>=', $start)->where('invoice_date', '<', $end)->value(DB::raw('COALESCE(SUM(total_amount),0)'));
    }

    private static function prescriptions(string $start, string $end): int
    {
        return DB::table('patient_prescription')->where('created_on', '>=', $start)->where('created_on', '<', $end)->count();
    }

    private static function trend(mixed $current, mixed $previous): float
    {
        return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;
    }

    /**
     * @return Collection<string, mixed> day => value
     */
    private static function perDay(Builder $query, string $column, string $aggregate, string $start, string $end): Collection
    {
        return $query->where($column, '>=', $start)->where($column, '<', $end)
            ->groupByRaw("DATE($column)")
            ->selectRaw("DATE($column) AS day, $aggregate AS cnt")
            ->pluck('cnt', 'day');
    }

    /**
     * @return array<int, array{label: string, start: string, end: string}>
     */
    private static function weekPeriods(): array
    {
        $periods = [];
        for ($i = 3; $i >= 1; $i--) {
            $start = date('Y-m-d 00:00:00', strtotime('-'.(($i - 1) * 7).' days'));
            $periods[] = ['label' => 'Week '.(5 - $i), 'start' => $start, 'end' => date('Y-m-d 00:00:00', strtotime($start.' +7 days'))];
        }

        return $periods;
    }

    /**
     * @param  Collection<string, mixed>  $patients
     * @param  Collection<string, mixed>  $revenue
     * @return array{labels: array<int, string>, patients: array<int, int>, revenue: array<int, float>}
     */
    private static function lastSevenDays($patients, $revenue): array
    {
        $trend = ['labels' => [], 'patients' => [], 'revenue' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $trend['labels'][] = date('D', strtotime($date));
            $trend['patients'][] = (int) ($patients[$date] ?? 0);
            $trend['revenue'][] = (float) ($revenue[$date] ?? 0);
        }

        return $trend;
    }

    /**
     * @param  callable(Builder): Builder  $patients  narrows the patient query
     * @return array{labels: array<int, string>, patients: array<int, int>, revenue: array<int, float>}
     */
    private static function lastTwelveMonths(callable $patients): array
    {
        $start = date('Y-m-01 00:00:00', strtotime('-11 months'));
        $end = date('Y-m-01 00:00:00', strtotime('+1 month'));
        $byMonth = fn (Builder $query, string $column, string $aggregate) => $query->where($column, '>=', $start)->where($column, '<', $end)
            ->groupByRaw("DATE_FORMAT($column, '%Y-%m')")->selectRaw("DATE_FORMAT($column, '%Y-%m') AS month, $aggregate AS cnt")->pluck('cnt', 'month');

        $patientCounts = $byMonth($patients(DB::table('patient')), 'created_on', 'COUNT(*)');
        $revenue = $byMonth(DB::table('invoice_parent'), 'invoice_date', 'COALESCE(SUM(total_amount),0)');

        $trend = ['labels' => [], 'patients' => [], 'revenue' => []];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $trend['labels'][] = date('M', strtotime($month));
            $trend['patients'][] = (int) ($patientCounts[$month] ?? 0);
            $trend['revenue'][] = (float) ($revenue[$month] ?? 0);
        }

        return $trend;
    }

    /**
     * Patients per sex and age group, "Male 0-5", "Female 0-5", ...
     *
     * @param  callable(Builder): Builder  $patients
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private static function demographics(callable $patients): array
    {
        $rows = $patients(DB::table('patient'))
            ->selectRaw('sex,
                SUM(CASE WHEN age >= 0 AND age <= 5 THEN 1 ELSE 0 END) AS age_0_5,
                SUM(CASE WHEN age BETWEEN 6 AND 14 THEN 1 ELSE 0 END) AS age_6_14,
                SUM(CASE WHEN age BETWEEN 15 AND 24 THEN 1 ELSE 0 END) AS age_15_24,
                SUM(CASE WHEN age >= 25 THEN 1 ELSE 0 END) AS age_25_plus')
            ->groupBy('sex')->get()->keyBy('sex');

        $demographics = ['labels' => [], 'values' => []];
        foreach (['0-5', '6-14', '15-24', '25+'] as $group) {
            foreach (['Male', 'Female'] as $sex) {
                $demographics['labels'][] = $sex.' '.$group;
                $column = 'age_'.str_replace(['-', '+'], ['_', '_plus'], $group);
                $demographics['values'][] = (int) ($rows[$sex]->{$column} ?? 0);
            }
        }

        return $demographics;
    }

    /**
     * @param  Collection<int, object>  $rows  label + cnt
     * @return array{labels: array<int, string>, values: array<int, int|float>}
     */
    private static function chart($rows, string $type): array
    {
        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('cnt')->map(fn ($v) => $type === 'int' ? (int) $v : (float) $v)->all(),
        ];
    }
}
