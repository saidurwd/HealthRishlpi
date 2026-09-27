{{-- Issue lines. $mode: 'draft' (quantity editable, delete), 'edit' (special edit: quantity moves stock at once), 'view' --}}
@php
    $adjustUrl = $mode === 'edit' ? route('stockIssue.adjustmentEdit') : route('stockIssue.adjustment');
    $quantityInput = fn ($row) => '<input type=\'text\' class=\'form-control form-control-sm\' style=\'width:110px\' value=\''.e($row->quantity).'\' data-adjust-url=\''.e($adjustUrl).'\' data-adjust-id=\''.$row->id.'\'>';
    $columns = $mode === 'view'
        ? [
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Reference', 'value' => fn ($row) => $row->referenceNumber()],
            ['header' => 'Store', 'value' => fn ($row) => $row->store0?->title ?: 'N/A'],
            ['header' => 'Batch', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.$row->uom(), 'class' => 'text-end'],
            ['header' => 'Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->amount), 'class' => 'text-end'],
        ]
        : [
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Store', 'value' => fn ($row) => $row->store0?->title ?: 'N/A'],
            ['header' => $mode === 'draft' && ! $parent->exists ? 'Expiry' : 'Batch', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Quantity', 'value' => $quantityInput, 'raw' => true],
            ['header' => $mode === 'edit' ? 'UOM' : 'Unit', 'value' => fn ($row) => $row->uom(), 'class' => 'text-center'],
            ['header' => 'Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->amount), 'class' => 'text-end'],
        ];
    if ($mode === 'draft') {
        $columns[] = ['header' => 'Actions', 'buttons' => [fn ($row) => '<a href=\''.e(route('stockIssue.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>']];
    }
@endphp
<x-grid id="stock-issue-grid" :grid="$lines" :columns="$columns" />
