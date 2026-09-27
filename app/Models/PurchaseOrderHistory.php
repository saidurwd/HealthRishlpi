<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Which purchase order line fed which receive line, and how much
 * (`os_purchase_order_history`).
 *
 * @property int $id
 * @property int $po_number
 * @property int $pr_number
 * @property int $item
 * @property string|float $quantity
 * @property int $converted
 * @property int $created_by
 * @property string $created_on
 */
#[Table('purchase_order_history', timestamps: false)]
#[Fillable(['po_number', 'pr_number', 'item', 'quantity', 'converted', 'created_by', 'created_on'])]
class PurchaseOrderHistory extends LegacyModel {}
