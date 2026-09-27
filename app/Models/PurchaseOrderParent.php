<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchase order header (`os_purchase_order_parent`). status
 * (TransectionStatus::PURCHASE_ORDER): 0 pending, 1 approved, 2 deleted.
 *
 * @property int $id
 * @property string $order_date
 * @property string $order_number "PO/26/00001"
 * @property int $order_by
 * @property int $supplier
 * @property int $status
 * @property string|null $comments
 * @property string|null $created_on
 * @property int|null $created_by
 */
#[Table('purchase_order_parent', timestamps: false)]
#[Fillable(['supplier', 'comments', 'status'])]
class PurchaseOrderParent extends LegacyModel
{
    protected $attributes = ['status' => 0];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'order_date' => 'Date',
            'order_number' => 'Order #',
            'order_by' => 'Order By',
            'supplier' => 'Supplier',
            'comments' => 'Comments',
            'status' => 'Status',
            'created_on' => 'Created On',
            'created_by' => 'Created By',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'supplier' => ['required', 'integer'],
            'status' => ['integer'],
            'comments' => ['nullable'],
        ];
    }

    /** @return HasMany<PurchaseOrder, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'parent');
    }

    /** @return BelongsTo<Vendor, $this> */
    public function supplier0(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier');
    }

    /** @return BelongsTo<User, $this> */
    public function orderBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'order_by');
    }

    /** Update / delete buttons: not for approved or deleted orders */
    public function isEditable(): bool
    {
        return ! in_array((int) $this->status, [1, 2], true);
    }

    /**
     * PO/<yy>/<5-digit n>, n continuing from the newest order of the same
     * year (PurchaseOrderParent::generatePurchaseOrderNumber()).
     */
    public static function nextNumber(): string
    {
        return self::slashNumber('PO', static::query()->orderByDesc('created_on')->orderByDesc('id')->value('order_number'));
    }

    /**
     * "<prefix>/<yy>/<00001>" numbering shared by purchase orders, receives,
     * requisitions, issues and transfers.
     */
    public static function slashNumber(string $prefix, ?string $latest): string
    {
        $next = 1;

        if ($latest !== null && $latest !== '') {
            $parts = explode('/', $latest);
            $next = ($parts[1] ?? null) == date('y') ? (int) ($parts[2] ?? 0) + 1 : 1;
        }

        return $prefix.'/'.date('y').'/'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
