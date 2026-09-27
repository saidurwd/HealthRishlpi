<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Which purchase order line fed which receive line, and how much
 * (`os_purchase_order_history`).
 */
#[Table('purchase_order_history', timestamps: false)]
#[Fillable(['po_number', 'pr_number', 'item', 'quantity', 'converted', 'created_by', 'created_on'])]
class PurchaseOrderHistory extends LegacyModel {}
