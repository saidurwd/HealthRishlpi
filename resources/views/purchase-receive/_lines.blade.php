{{--
    Receive lines. $mode: 'draft' (new/pending: store, quantity and rate
    editable, delete), 'edit' (special edit of a received MRR: quantity and
    rate change stock at once), 'view'.
--}}
@php
    $adjustUrl = $mode === 'edit' ? route('purchaseReceive.adjustmentEdit') : route('purchaseReceive.adjustment');
    $input = fn ($row, $field) => '<input type=\'text\' class=\'form-control form-control-sm\' style=\'width:100px\' value=\''.e($row->{$field}).'\' data-adjust-url=\''.e($adjustUrl).'\' data-adjust-id=\''.$row->id.'\' data-adjust-type=\''.$field.'\'>';
    $storeSelect = function ($row) use ($adjustUrl, $stores) {
        $html = '<select class=\'form-select form-select-sm\' style=\'width:150px\' data-adjust-url=\''.e($adjustUrl).'\' data-adjust-id=\''.$row->id.'\' data-adjust-type=\'store\'><option value=\'\'>Select a Store</option>';
        foreach ($stores as $storeId => $title) {
            $html .= '<option value=\''.$storeId.'\''.((int) $row->store === (int) $storeId ? ' selected' : '').'>'.e($title).'</option>';
        }

        return $html.'</select>';
    };
    $files = fn ($row) => $mode === 'view'
        ? ($row->documents_count > 0
            ? '<a href=\''.e(route('purchaseReceive.downloadall', $row->id)).'\' class=\'btn btn-sm btn-outline-secondary\'><i class=\'fa fa-download\'></i> '.$row->documents_count.' file(s)</a>'
            : '<span class=\'btn btn-sm btn-outline-secondary disabled\'><i class=\'fa fa-download\'></i> 0 file(s)</span>')
        : '<button type=\'button\' class=\'btn btn-sm btn-outline-secondary\' data-files-url=\''.e(route('purchaseReceive.upload', $row->id)).'\'><i class=\'fa fa-upload\'></i> '.$row->documents_count.' file(s)</button>';

    $columns = match ($mode) {
        'view' => [
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Reference', 'value' => fn ($row) => $row->referenceOrderNumber()],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.$row->uom(), 'class' => 'text-end'],
            ['header' => 'Sale Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['header' => 'Sale Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
            ['header' => 'Buy Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->buy_rate), 'class' => 'text-end'],
            ['header' => 'Buy Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->buy_amount), 'class' => 'text-end'],
            ['header' => 'Lot No.', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Expiry', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->batch0?->expiry)],
            ['header' => 'Files', 'value' => $files, 'raw' => true, 'class' => 'text-center'],
        ],
        'edit' => [
            ['header' => 'Reference', 'value' => fn ($row) => $row->referenceOrderNumber()],
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Store', 'value' => fn ($row) => $row->store0?->fullPath(), 'raw' => true],
            ['header' => 'Quantity', 'value' => fn ($row) => $input($row, 'quantity'), 'raw' => true],
            ['header' => 'UOM', 'value' => fn ($row) => $row->uom(), 'class' => 'text-center'],
            ['header' => 'Sale Rate', 'value' => fn ($row) => $input($row, 'rate'), 'raw' => true],
            ['header' => 'Sale Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
            ['header' => 'Lot No.', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Expiry', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->batch0?->expiry)],
            ['header' => 'Files', 'value' => $files, 'raw' => true, 'class' => 'text-center'],
        ],
        default => [
            ['header' => 'Reference', 'value' => fn ($row) => $row->referenceOrderNumber()],
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Store', 'value' => $storeSelect, 'raw' => true],
            ['header' => 'Quantity', 'value' => fn ($row) => $input($row, 'quantity'), 'raw' => true],
            ['header' => 'UOM', 'value' => fn ($row) => $row->uom(), 'class' => 'text-center'],
            ['header' => 'Sale Rate', 'value' => fn ($row) => $input($row, 'rate'), 'raw' => true],
            ['header' => 'Sale Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
            ['header' => 'Buy Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->buy_rate), 'class' => 'text-end'],
            ['header' => 'Buy Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->buy_amount), 'class' => 'text-end'],
            ['header' => 'Expiry', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->batch0?->expiry)],
            ['header' => 'Files', 'value' => $files, 'raw' => true, 'class' => 'text-center'],
            ['header' => 'Actions', 'buttons' => [fn ($row) => '<a href=\''.e(route('purchaseReceive.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>']],
        ],
    };
@endphp
<x-grid id="purchase-receive-grid" :grid="$lines" :columns="$columns" />

@if ($mode !== 'view')
    @include('purchase-receive._files-modal')
@endif
