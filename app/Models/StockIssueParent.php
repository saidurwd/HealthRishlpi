<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Stock issue header (`os_stock_issue_parent`). status
 * (TransectionStatus::STOCK_ISSUE): 0 pending, 1 issued (stock taken out),
 * 2 deleted.
 *
 * @property int $id
 * @property string $issue_date
 * @property string $issue_number "SI#ADMIN-2026-1"
 * @property int $issue_by
 * @property string|null $total_amount
 * @property int $status
 */
#[Table('stock_issue_parent', timestamps: false)]
#[Fillable(['comments', 'status'])]
class StockIssueParent extends LegacyModel
{
    protected $attributes = ['status' => 0];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'issue_date' => 'Date',
            'issue_number' => 'Issue#',
            'issue_by' => 'Issue By',
            'total_amount' => 'Amount',
            'comments' => 'Comments',
            'status' => 'Status',
            'created_on' => 'Created On',
            'created_by' => 'Created By',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return ['status' => ['integer'], 'comments' => ['nullable']];
    }

    /** @return HasMany<StockIssue, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StockIssue::class, 'parent');
    }

    /** @return BelongsTo<User, $this> */
    public function issueBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issue_by');
    }

    public function isEditable(): bool
    {
        return ! in_array((int) $this->status, [1, 2], true);
    }

    public function canSpecialEdit(): bool
    {
        return (int) $this->status === 1;
    }

    /**
     * Numbers of the requisitions its lines came from (StockIssue::getReferences()).
     */
    public function references(): string
    {
        return DB::table('stock_requisition as sr')
            ->join('stock_requisition_parent as srp', 'srp.id', '=', 'sr.parent')
            ->whereIn('sr.id', DB::table('stock_issue')->where('parent', $this->id)->select('reference'))
            ->groupBy('sr.parent', 'srp.requisition_number')
            ->pluck('srp.requisition_number')
            ->implode(', ');
    }

    /**
     * Sum of the line amounts (StockIssue::getTotalAmount()).
     */
    public static function totalAmount(int $id): mixed
    {
        return DB::table('stock_issue')->where('parent', $id)->value(DB::raw('ROUND((SUM(amount)),6)'));
    }

    public static function nextNumber(string $loginName): string
    {
        return 'SI#'.strtoupper($loginName).'-'.date('Y').'-'.PatientPrescription::nextSequence(
            static::query()->orderByDesc('created_on')->value('issue_number')
        );
    }
}
