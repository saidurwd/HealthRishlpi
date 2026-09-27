<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Which requisition line fed which issue line, and how much
 * (`os_stock_requisition_history`).
 *
 * @property int $id
 * @property int $requisition_number
 * @property int $issue_number
 * @property int $item
 * @property string|float $quantity
 * @property int $converted
 * @property int $created_by
 * @property string $created_on
 */
#[Table('stock_requisition_history', timestamps: false)]
#[Fillable(['requisition_number', 'issue_number', 'item', 'quantity', 'converted', 'created_by', 'created_on'])]
class StockRequisitionHistory extends LegacyModel {}
