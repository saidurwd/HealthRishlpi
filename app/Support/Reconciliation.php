<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Consistency checks run daily while the Yii and Laravel apps share the
 * database (health:reconcile). Each check returns its findings keyed by a
 * stable id with a comparable value, so a run can be compared with a saved
 * baseline: live data already has differences from the Yii years, and what
 * matters is anything new.
 */
class Reconciliation
{
    /** Invoice/issue totals may differ by rounding up to this much */
    private const MONEY_TOLERANCE = 0.005;

    /**
     * @return array<string, string> check => description
     */
    public static function checks(): array
    {
        return [
            'stock' => 'Stock summary differs from its approved movements (key store|item|batch, value summary - movements)',
            'invoice_totals' => 'Invoice total differs from the sum of its lines (key invoice id, value total - lines)',
            'issue_totals' => 'Stock issue total differs from the sum of its lines (key issue id, value total - lines)',
            'duplicate_numbers' => 'Document number used more than once (key table:number, value times used)',
            'orphan_lines' => 'Line whose header no longer exists (key table:id, value header id)',
            'user_roles' => "User's role does not match their group (key user id, value group -> roles)",
        ];
    }

    /**
     * @return array<string, string|float>
     */
    public static function run(string $check): array
    {
        return match ($check) {
            'stock' => self::stock(),
            'invoice_totals' => self::headerTotals('invoice_parent', 'invoice', 'amount'),
            'issue_totals' => self::headerTotals('stock_issue_parent', 'stock_issue', 'amount'),
            'duplicate_numbers' => self::duplicateNumbers(),
            'orphan_lines' => self::orphanLines(),
            'user_roles' => self::userRoles(),
            default => throw new InvalidArgumentException("Unknown check $check"),
        };
    }

    /**
     * On-hand quantity against approved receives, transfers in and out,
     * issues and medicine invoice lines.
     *
     * @return array<string, float>
     */
    private static function stock(): array
    {
        $t = fn (string $table) => DB::getTablePrefix().$table;

        $rows = DB::select("
            SELECT store, item, batch, ROUND(SUM(summary), 6) AS summary, ROUND(SUM(moved), 6) AS moved FROM (
                SELECT store, item, batch, quantity AS summary, 0 AS moved FROM {$t('stock_summary')}
                UNION ALL
                SELECT l.store, l.item, l.batch, 0, l.quantity FROM {$t('purchase_receive')} l
                    JOIN {$t('purchase_receive_parent')} h ON h.id = l.parent WHERE h.status = 1
                UNION ALL
                SELECT l.store_to, l.item, l.batch, 0, l.quantity FROM {$t('stock_transfer')} l
                    JOIN {$t('stock_transfer_parent')} h ON h.id = l.parent WHERE h.status = 1
                UNION ALL
                SELECT l.store_from, l.item, l.batch, 0, -l.quantity FROM {$t('stock_transfer')} l
                    JOIN {$t('stock_transfer_parent')} h ON h.id = l.parent WHERE h.status = 1
                UNION ALL
                SELECT l.store, l.item, l.batch, 0, -l.quantity FROM {$t('stock_issue')} l
                    JOIN {$t('stock_issue_parent')} h ON h.id = l.parent WHERE h.status = 1
                UNION ALL
                SELECT l.store, l.item, l.batch, 0, -l.quantity FROM {$t('invoice')} l
                    JOIN {$t('invoice_parent')} h ON h.id = l.parent WHERE h.status = 1 AND l.servicetype = 'Medicine'
            ) m
            GROUP BY store, item, batch
            HAVING ABS(SUM(summary) - SUM(moved)) > 0.000001
        ");

        $findings = [];
        foreach ($rows as $row) {
            $findings[(int) $row->store.'|'.(int) $row->item.'|'.(int) $row->batch] = round((float) $row->summary - (float) $row->moved, 6);
        }

        return $findings;
    }

    /**
     * Saved (pending or approved) headers whose total_amount is not the sum
     * of their lines.
     *
     * @return array<string, float>
     */
    private static function headerTotals(string $headerTable, string $lineTable, string $amountColumn): array
    {
        $lines = DB::table($lineTable)->selectRaw('parent, SUM('.DB::getQueryGrammar()->wrap($amountColumn).') AS amount')->groupBy('parent');

        return DB::table($headerTable.' as h')
            ->leftJoinSub($lines, 'l', 'l.parent', '=', 'h.id')
            ->whereIn('h.status', [0, 1])
            ->get(['h.id', 'h.total_amount', 'l.amount'])
            ->filter(fn ($row) => abs((float) $row->total_amount - (float) $row->amount) > self::MONEY_TOLERANCE)
            ->mapWithKeys(fn ($row) => [(string) $row->id => round((float) $row->total_amount - (float) $row->amount, 6)])
            ->all();
    }

    /**
     * @return array<string, float>
     */
    private static function duplicateNumbers(): array
    {
        $columns = [
            'invoice_parent' => 'invoice_number',
            'stock_issue_parent' => 'issue_number',
            'stock_requisition_parent' => 'requisition_number',
            'stock_transfer_parent' => 'transfer_number',
            'purchase_order_parent' => 'order_number',
            'purchase_receive_parent' => 'receive_number',
            'patient' => 'pat_id',
            'patient_prescription' => 'pre_number',
        ];

        $findings = [];
        foreach ($columns as $table => $column) {
            $duplicates = DB::table($table)
                ->whereNotNull($column)->where($column, '!=', '')
                ->groupBy($column)->havingRaw('COUNT(*) > 1')
                ->selectRaw(DB::getQueryGrammar()->wrap($column).' AS number, COUNT(*) AS times')
                ->get();

            foreach ($duplicates as $row) {
                $findings[$table.':'.$row->number] = (float) $row->times;
            }
        }

        return $findings;
    }

    /**
     * @return array<string, string>
     */
    private static function orphanLines(): array
    {
        $pairs = [
            'invoice' => 'invoice_parent',
            'stock_issue' => 'stock_issue_parent',
            'stock_requisition' => 'stock_requisition_parent',
            'stock_transfer' => 'stock_transfer_parent',
            'purchase_order' => 'purchase_order_parent',
            'purchase_receive' => 'purchase_receive_parent',
            'prescription_medicine' => 'patient_prescription',
        ];

        $findings = [];
        foreach ($pairs as $lineTable => $headerTable) {
            $orphans = DB::table($lineTable.' as l')
                ->leftJoin($headerTable.' as h', 'h.id', '=', 'l.parent')
                ->where('l.parent', '>', 0)
                ->whereNull('h.id')
                ->get(['l.id', 'l.parent']);

            foreach ($orphans as $row) {
                $findings[$lineTable.':'.$row->id] = (string) $row->parent;
            }
        }

        return $findings;
    }

    /**
     * Users whose roles are not exactly the role of their group (users of a
     * group with no role must have none).
     *
     * @return array<string, string>
     */
    private static function userRoles(): array
    {
        $tables = config('permission.table_names');
        $roleIds = DB::table($tables['roles'])->pluck('id')->map(fn ($id) => (int) $id)->flip();
        $assigned = DB::table($tables['model_has_roles'])->where('model_type', 'App\Models\User')
            ->get(['model_id', 'role_id'])->groupBy('model_id');

        $findings = [];
        foreach (DB::table('user')->orderBy('id')->get(['id', 'group_id']) as $user) {
            $expected = $user->group_id !== null && isset($roleIds[(int) $user->group_id]) ? [(int) $user->group_id] : [];
            $actual = ($assigned[$user->id] ?? collect())->pluck('role_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($actual !== $expected) {
                $findings[(string) $user->id] = ($user->group_id ?? 'none').' -> '.($actual === [] ? 'none' : implode(',', $actual));
            }
        }

        return $findings;
    }
}
