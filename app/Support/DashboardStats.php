<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the dashboard. Money counts approved invoices (status 1);
 * deleted invoices (status 2) never count. A period is [start, end) and is
 * compared with the same stretch of the period before (previous()).
 */
class DashboardStats
{
    public const PERIODS = ['today' => 'Today', '7d' => 'Last 7 days', 'month' => 'This month', 'year' => 'This year'];

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function range(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->startOfDay(), $now->startOfDay()->addDay()],
            '7d' => [$now->startOfDay()->subDays(6), $now->startOfDay()->addDay()],
            'year' => [$now->startOfYear(), $now->startOfYear()->addYear()],
            default => [$now->startOfMonth(), $now->startOfMonth()->addMonth()],
        };
    }

    /**
     * The same stretch of time one period earlier, cut at the same point: this
     * month so far is compared with the same days of last month, this year so
     * far with the same part of last year.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function previous(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $cut = $end->min(CarbonImmutable::now());

        return match (true) {
            $start->isStartOfYear() && $end->equalTo($start->addYear()) => [$start->subYear(), $cut->subYear()],
            $start->day === 1 && $start->isStartOfDay() && $end->equalTo($start->addMonth()) => [$start->subMonthNoOverflow(), $cut->subMonthNoOverflow()],
            default => [$start->sub($start->diff($end)), $cut->sub($start->diff($end))],
        };
    }

    /**
     * Today's operational figures.
     *
     * @return array<string, float|int>
     */
    public static function today(): array
    {
        [$start, $end] = self::range('today');

        return [
            'new_patients' => DB::table('patient')->where('created_on', '>=', $start)->where('created_on', '<', $end)->count(),
            'prescriptions' => DB::table('patient_prescription')->where('created_on', '>=', $start)->where('created_on', '<', $end)->count(),
            'invoices' => DB::table('invoice_parent')->whereIn('status', [0, 1])->where('invoice_date', '>=', $start)->where('invoice_date', '<', $end)->count(),
            'sales' => (float) DB::table('invoice_parent')->where('status', 1)->where('invoice_date', '>=', $start)->where('invoice_date', '<', $end)->sum('total_amount'),
            'pending' => DB::table('invoice_parent')->where('status', 0)->count(),
            'pending_amount' => (float) DB::table('invoice_parent')->where('status', 0)->sum('total_amount'),
        ];
    }

    /**
     * Revenue, new patients, visits and invoices of a period with the change
     * against the previous one (percent, null when there is nothing to
     * compare with).
     *
     * @return array<string, array{value: float|int, change: ?float}>
     */
    public static function summary(CarbonImmutable $start, CarbonImmutable $end): array
    {
        [$prevStart, $prevEnd] = self::previous($start, $end);
        $figures = fn (CarbonImmutable $from, CarbonImmutable $to) => [
            'revenue' => (float) DB::table('invoice_parent')->where('status', 1)->where('invoice_date', '>=', $from)->where('invoice_date', '<', $to)->sum('total_amount'),
            'invoices' => DB::table('invoice_parent')->where('status', 1)->where('invoice_date', '>=', $from)->where('invoice_date', '<', $to)->count(),
            'patients' => DB::table('patient')->where('created_on', '>=', $from)->where('created_on', '<', $to)->count(),
            'visits' => DB::table('patient_prescription')->where('created_on', '>=', $from)->where('created_on', '<', $to)->count(),
        ];
        $now = $figures($start, $end->min(CarbonImmutable::now()));
        $before = $figures($prevStart, $prevEnd);
        $now['average'] = $now['invoices'] > 0 ? $now['revenue'] / $now['invoices'] : 0;
        $before['average'] = $before['invoices'] > 0 ? $before['revenue'] / $before['invoices'] : 0;

        return collect($now)->map(fn ($value, $key) => [
            'value' => $value,
            'change' => $before[$key] > 0 ? round(($value - $before[$key]) / $before[$key] * 100, 1) : null,
        ])->all();
    }

    /**
     * Revenue and visits per day (per month for a year).
     *
     * @return array{labels: list<string>, revenue: list<float>, visits: list<int>}
     */
    public static function trend(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $monthly = $start->diffInDays($end) > 62;
        $format = $monthly ? '%Y-%m' : '%Y-%m-%d';
        $bucket = fn (string $column) => DB::raw('DATE_FORMAT('.DB::getQueryGrammar()->wrap($column).", '$format') AS bucket");

        $revenue = DB::table('invoice_parent')->where('status', 1)->where('invoice_date', '>=', $start)->where('invoice_date', '<', $end)
            ->groupBy('bucket')->select($bucket('invoice_date'), DB::raw('SUM(total_amount) AS amount'))->pluck('amount', 'bucket');
        $visits = DB::table('patient_prescription')->where('created_on', '>=', $start)->where('created_on', '<', $end)
            ->groupBy('bucket')->select($bucket('created_on'), DB::raw('COUNT(*) AS visits'))->pluck('visits', 'bucket');

        $trend = ['labels' => [], 'revenue' => [], 'visits' => []];
        for ($day = $start; $day < $end && $day <= now(); $day = $monthly ? $day->addMonth() : $day->addDay()) {
            $key = $day->format($monthly ? 'Y-m' : 'Y-m-d');
            $trend['labels'][] = $day->format($monthly ? 'M' : 'j M');
            $trend['revenue'][] = round((float) ($revenue[$key] ?? 0), 2);
            $trend['visits'][] = (int) ($visits[$key] ?? 0);
        }

        return $trend;
    }

    /**
     * Medicine and service sales of approved invoices.
     *
     * @return array<string, float>
     */
    public static function salesMix(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return DB::table('invoice as i')
            ->join('invoice_parent as p', 'p.id', '=', 'i.parent')
            ->where('p.status', 1)->where('p.invoice_date', '>=', $start)->where('p.invoice_date', '<', $end)
            ->groupBy('i.servicetype')
            ->select('i.servicetype', DB::raw('SUM('.DB::getQueryGrammar()->wrap('i.amount').') AS amount'))
            ->pluck('amount', 'servicetype')
            ->map(fn ($amount) => round((float) $amount, 2))
            ->all();
    }

    /**
     * @return list<array{label: string, value: float, count: int}>
     */
    public static function topProducts(CarbonImmutable $start, CarbonImmutable $end, int $limit = 6): array
    {
        return DB::table('invoice as i')
            ->join('invoice_parent as p', 'p.id', '=', 'i.parent')
            ->join('product as pr', 'pr.id', '=', 'i.item')
            ->where('p.status', 1)->where('p.invoice_date', '>=', $start)->where('p.invoice_date', '<', $end)
            ->groupBy('i.item', 'pr.title')
            ->select('pr.title', DB::raw('SUM('.DB::getQueryGrammar()->wrap('i.amount').') AS amount'), DB::raw('SUM('.DB::getQueryGrammar()->wrap('i.quantity').') AS qty'))
            ->orderByDesc('amount')->limit($limit)->get()
            ->map(fn ($row) => ['label' => (string) $row->title, 'value' => round((float) $row->amount, 2), 'count' => (int) $row->qty])->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public static function topDiagnoses(CarbonImmutable $start, CarbonImmutable $end, int $limit = 6): array
    {
        return DB::table('patient_prescription as pp')
            ->join('disease as d', 'd.id', '=', 'pp.diagnosis')
            ->where('pp.created_on', '>=', $start)->where('pp.created_on', '<', $end)
            ->groupBy('pp.diagnosis', 'd.title')
            ->select('d.title', DB::raw('COUNT(*) AS visits'))
            ->orderByDesc('visits')->limit($limit)->get()
            ->map(fn ($row) => ['label' => (string) $row->title, 'value' => (int) $row->visits])->all();
    }

    /**
     * New patients per category.
     *
     * @return list<array{label: string, value: int}>
     */
    public static function patientsByCategory(CarbonImmutable $start, CarbonImmutable $end, int $limit = 6): array
    {
        return DB::table('patient as p')
            ->join('patient_category_new as c', 'c.id', '=', 'p.category_new')
            ->where('p.created_on', '>=', $start)->where('p.created_on', '<', $end)
            ->groupBy('p.category_new', 'c.title')
            ->select('c.title', DB::raw('COUNT(*) AS patients'))
            ->orderByDesc('patients')->limit($limit)->get()
            ->map(fn ($row) => ['label' => (string) $row->title, 'value' => (int) $row->patients])->all();
    }

    /**
     * Approved invoices per staff member.
     *
     * @return list<array{label: string, value: float, count: int}>
     */
    public static function staff(CarbonImmutable $start, CarbonImmutable $end, int $limit = 6): array
    {
        return DB::table('invoice_parent as p')
            ->join('user as u', 'u.id', '=', 'p.invoice_by')
            ->where('p.status', 1)->where('p.invoice_date', '>=', $start)->where('p.invoice_date', '<', $end)
            ->groupBy('p.invoice_by', 'u.full_name')
            ->select('u.full_name', DB::raw('SUM(total_amount) AS amount'), DB::raw('COUNT(*) AS invoices'))
            ->orderByDesc('amount')->limit($limit)->get()
            ->map(fn ($row) => ['label' => (string) $row->full_name, 'value' => round((float) $row->amount, 2), 'count' => (int) $row->invoices])->all();
    }

    /**
     * Latest invoices (not deleted).
     *
     * @return list<object>
     */
    public static function recentInvoices(int $limit = 8): array
    {
        return DB::table('invoice_parent as i')
            ->leftJoin('patient as p', 'p.id', '=', 'i.patient')
            ->whereIn('i.status', [0, 1])
            ->orderByDesc('i.id')->limit($limit)
            ->get(['i.id', 'i.invoice_number', 'i.invoice_date', 'i.total_amount', 'i.status', 'i.payment_status', 'p.id as patient_id', 'p.name', 'p.pat_id'])
            ->all();
    }

    /**
     * Batches in stock that expired or expire within $days days, soonest first.
     *
     * @return array{count: int, rows: list<object>}
     */
    public static function expiringStock(int $days = 90, int $limit = 8): array
    {
        $query = DB::table('stock_summary as s')
            ->join('batch as b', 'b.id', '=', 's.batch')
            ->join('product as p', 'p.id', '=', 's.item')
            ->where('s.quantity', '>', 0)
            ->whereNotNull('b.expiry')
            ->where('b.expiry', '<', now()->addDays($days)->toDateString());

        return [
            'count' => (clone $query)->count(),
            'rows' => $query->orderBy('b.expiry')->limit($limit)->get(['p.title as product', 'b.title as batch', 'b.expiry', 's.quantity'])->all(),
        ];
    }
}
