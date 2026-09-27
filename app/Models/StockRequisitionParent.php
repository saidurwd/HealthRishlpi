<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock requisition header (`os_stock_requisition_parent`). status
 * (TransectionStatus::STOCK_REQUISITION): 0 pending, 1 approved, 2 deleted.
 * An approved requisition can be turned into a stock issue.
 *
 * @property int $id
 * @property string $requisition_date
 * @property string $requisition_number "SR#ADMIN-2026-1"
 * @property int $requisition_by
 * @property int $status
 * @property string|null $comments
 * @property string|null $created_on
 * @property int|null $created_by
 */
#[Table('stock_requisition_parent', timestamps: false)]
#[Fillable(['comments', 'status'])]
class StockRequisitionParent extends LegacyModel
{
    protected $attributes = ['status' => 0];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'requisition_date' => 'Date',
            'requisition_number' => 'Req#',
            'requisition_by' => 'Req By',
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

    /** @return HasMany<StockRequisition, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StockRequisition::class, 'parent');
    }

    /** @return BelongsTo<User, $this> */
    public function requisitionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requisition_by');
    }

    public function isEditable(): bool
    {
        return ! in_array((int) $this->status, [1, 2], true);
    }

    public static function nextNumber(string $loginName): string
    {
        return 'SR#'.strtoupper($loginName).'-'.date('Y').'-'.PatientPrescription::nextSequence(
            static::query()->orderByDesc('created_on')->orderByDesc('id')->value('requisition_number')
        );
    }
}
