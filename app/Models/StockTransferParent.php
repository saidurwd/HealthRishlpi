<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Store transfer header (`os_stock_transfer_parent`). status
 * (TransectionStatus::STOCK_TRANSFER): 0 pending, 1 transferred (stock
 * moved), 2 deleted.
 *
 * @property int $id
 * @property string $transfer_date
 * @property string $transfer_number "ST/26/00001"
 * @property int $transfer_by
 * @property int $status
 * @property int $supplier
 * @property string|null $comments
 * @property string|null $created_on
 * @property int|null $created_by
 */
#[Table('stock_transfer_parent', timestamps: false)]
#[Fillable(['comments', 'status'])]
class StockTransferParent extends LegacyModel
{
    protected $attributes = ['status' => 0];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'transfer_date' => 'Date',
            'transfer_number' => 'Transfer #',
            'transfer_by' => 'Transfer By',
            'supplier' => 'Supplier',
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

    /** @return HasMany<StockTransfer, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'parent');
    }

    /** @return BelongsTo<User, $this> */
    public function transferBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transfer_by');
    }

    public function isEditable(): bool
    {
        return ! in_array((int) $this->status, [1, 2], true);
    }

    public static function nextNumber(): string
    {
        return PurchaseOrderParent::slashNumber('ST', static::query()->orderByDesc('created_on')->value('transfer_number'));
    }
}
