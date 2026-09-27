<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Goods receive (MRR) header (`os_purchase_receive_parent`). status
 * (TransectionStatus::PURCHASE_RECEIVE): 0 pending, 1 received (stock
 * added), 2 deleted.
 *
 * @property int $id
 * @property string $receive_date
 * @property string $receive_number "MRR/26/00001"
 * @property int $receive_by
 * @property int $supplier
 * @property int $status
 * @property string|null $comments
 * @property string|null $created_on
 * @property int|null $created_by
 */
#[Table('purchase_receive_parent', timestamps: false)]
#[Fillable(['supplier', 'comments', 'status'])]
class PurchaseReceiveParent extends LegacyModel
{
    protected $attributes = ['status' => 0];

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'receive_date' => 'Date',
            'receive_number' => 'Receive #',
            'receive_by' => 'Receive By',
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

    /** @return HasMany<PurchaseReceive, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReceive::class, 'parent');
    }

    /** @return BelongsTo<Vendor, $this> */
    public function supplier0(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier');
    }

    /** @return BelongsTo<User, $this> */
    public function receiveBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receive_by');
    }

    /** @return HasMany<StoreDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(StoreDocument::class, 'transection_id')->where('transection_type', StoreDocument::PURCHASE_RECEIVE);
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
     * Order numbers of the purchase orders its lines came from
     * (PurchaseReceive::getReferences()).
     */
    public function references(): string
    {
        return DB::table('purchase_order as po')
            ->join('purchase_order_parent as pop', 'pop.id', '=', 'po.parent')
            ->whereIn('po.id', DB::table('purchase_receive')->where('parent', $this->id)->select('reference'))
            ->groupBy('po.parent', 'pop.order_number')
            ->pluck('pop.order_number')
            ->implode(', ');
    }

    public static function nextNumber(): string
    {
        return PurchaseOrderParent::slashNumber('MRR', static::query()->orderByDesc('created_on')->orderByDesc('id')->value('receive_number'));
    }
}
