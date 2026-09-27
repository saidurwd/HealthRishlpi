<?php

namespace App\Http\Controllers;

use App\Support\DashboardStats;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard: today at a glance, then the chosen period (today, last 7
 * days, this month, this year) against the one before. Each section is
 * shown only to users who may open the pages behind it: money to those who
 * manage invoices, patient figures to those who manage patients, stock
 * alerts to those who see the expiry report.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $period = array_key_exists((string) $request->query('period'), DashboardStats::PERIODS) ? (string) $request->query('period') : 'month';
        [$start, $end] = DashboardStats::range($period);

        $can = [
            'money' => $user->can('invoice.admin'),
            'patients' => $user->can('patient.admin'),
            'stock' => $user->can('report.expiration'),
        ];

        return view('dashboard.index', [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'can' => $can,
            'today' => DashboardStats::today(),
            'summary' => DashboardStats::summary($start, $end),
            'trend' => DashboardStats::trend($start, $end),
            'salesMix' => $can['money'] ? DashboardStats::salesMix($start, $end) : [],
            'topProducts' => $can['money'] ? DashboardStats::topProducts($start, $end) : [],
            'staff' => $can['money'] ? DashboardStats::staff($start, $end) : [],
            'recentInvoices' => $can['money'] ? DashboardStats::recentInvoices() : [],
            'diagnoses' => $can['patients'] ? DashboardStats::topDiagnoses($start, $end) : [],
            'categories' => $can['patients'] ? DashboardStats::patientsByCategory($start, $end) : [],
            'expiring' => $can['stock'] ? DashboardStats::expiringStock() : ['count' => 0, 'rows' => []],
        ]);
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

    private static function recentInvoices(int $limit): Builder
    {
        return DB::table('invoice_parent as ip')
            ->join('patient as p', 'ip.patient', '=', 'p.id')
            ->leftJoin('patient_category_new as pcn', 'p.category_new', '=', 'pcn.id')
            ->orderByDesc('ip.id')
            ->limit($limit)
            ->select('ip.id', 'p.pat_id', 'p.name', 'pcn.title as dept', DB::raw('DATE('.DB::getQueryGrammar()->wrap('ip.invoice_date').') as dt'), 'ip.invoice_date', 'ip.total_amount', 'ip.payment_status');
    }

    private static function revenue(string $start, string $end): mixed
    {
        return DB::table('invoice_parent')->where('invoice_date', '>=', $start)->where('invoice_date', '<', $end)->rawValue('COALESCE(SUM(total_amount),0)');
    }

    private static function prescriptions(string $start, string $end): int
    {
        return DB::table('patient_prescription')->where('created_on', '>=', $start)->where('created_on', '<', $end)->count();
    }
}
