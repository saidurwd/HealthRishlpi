{{--
    Invoice lines. $mode: 'draft' (create/update/rollback: editable
    quantity, delete), 'edit' (special edit: quantity moves stock at once),
    'view'. Totals are of the rows shown, as in the Yii grid footer.
--}}
@php
    $rows = $lines->rows->getCollection();
    $adjustUrl = $mode === 'edit' ? route('invoice.adjustmentEdit') : route('invoice.adjustment');
    $columns = [
        ['header' => 'Product/Service', 'value' => fn ($row) => $row->title(), 'footer' => 'TOTAL'],
    ];
    if ($mode === 'view') {
        $columns[] = ['header' => 'Note', 'value' => fn ($row) => $row->note];
    }
    $columns[] = ['header' => 'Store', 'value' => fn ($row) => $row->store0?->title ?: 'N/A'];
    $columns[] = ['header' => $mode === 'draft' ? 'Expiry' : 'Batch', 'value' => fn ($row) => $row->batch0?->title ?: 'N/A'];
    if ($mode === 'view') {
        $columns[] = ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.$row->uom(), 'class' => 'text-end'];
    } else {
        $columns[] = ['header' => 'Quantity', 'raw' => true, 'class' => 'text-end', 'value' => fn ($row) => '<input type=\'text\' class=\'form-control form-control-sm\' style=\'width:110px\' value=\''.e($row->quantity).'\' data-adjust-url=\''.e($adjustUrl).'\' data-adjust-id=\''.$row->id.'\'>'];
        $columns[] = ['header' => $mode === 'edit' ? 'UOM' : 'Unit', 'value' => fn ($row) => $row->uom(), 'class' => 'text-center'];
    }
    $columns[] = ['header' => 'Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'];
    $columns[] = ['header' => 'Discount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->discount), 'class' => 'text-end', 'footer' => \App\Support\YiiFormat::currency($rows->sum(fn ($r) => (float) $r->discount))];
    $columns[] = ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->amount), 'class' => 'text-end', 'footer' => \App\Support\YiiFormat::currency($rows->sum(fn ($r) => (float) $r->amount))];
    if ($mode === 'draft') {
        $columns[] = ['header' => 'Actions', 'buttons' => [fn ($row) => '<a href=\''.e(route('invoice.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>']];
    }
@endphp
<x-grid id="invoice-grid" :grid="$lines" :columns="$columns" />
