{{--
    Invoice header grid: the Invoice manage page ($admin = true) and the
    Invoices tab of a patient's page. Needs $grid, $users, $statuses. The
    patient filter takes a name or PAT#.
--}}
@php $admin ??= false; @endphp
<x-grid id="invoice-parent-grid" :grid="$grid" :columns="array_values(array_filter([
    ['name' => 'patient', 'value' => fn ($row) => $admin
        ? '<a href=\''.e(route('patient.view', (int) $row->patient)).'\' target=\'_blank\'>'.e($row->patient0?->name).'</a>'
        : e($row->patient0?->name), 'raw' => true],
    ['name' => 'invoice_number', 'value' => fn ($row) => '<a href=\''.e(route('invoice.view', $row->id)).'\''.($admin ? '' : ' target=\'_blank\'').'>'.e($row->invoice_number).'</a>', 'raw' => true],
    ['name' => 'invoice_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->invoice_date)],
    ['header' => '# of Items', 'value' => fn ($row) => $row->lines_count, 'class' => 'text-center'],
    ['name' => 'total_amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
    ['name' => 'invoice_by', 'value' => fn ($row) => $row->invoiceBy?->full_name, 'filter' => $users],
    $admin ? ['name' => 'payment_status', 'header' => 'Payment', 'filter' => \App\Models\InvoiceParent::PAYMENT_STATUSES, 'class' => 'text-center'] : null,
    ['name' => 'status', 'value' => fn ($row) => \App\Models\TransectionStatus::badge($row->status, \App\Models\TransectionStatus::INVOICE), 'filter' => $statuses],
    ['header' => 'Actions', 'buttons' => [
        fn ($row) => '<a href=\''.e(route('invoice.view', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
        fn ($row) => $row->isEditable() ? '<a href=\''.e(route('invoice.update', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
        fn ($row) => $row->isEditable() ? '<a href=\''.e(route('invoice.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
        fn ($row) => $row->canSpecialEdit() ? '<a href=\''.e(route('invoice.edit', $row->id)).'\' class=\'btn btn-sm btn-warning\' title=\'Special Edit\'><i class=\'fa fa-edit\'></i></a>' : '',
        fn ($row) => '<a href=\''.e(route('invoice.print', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
        fn ($row) => $admin && $row->canRollback() ? '<a href=\''.e(route('invoice.rollback', $row->id)).'\' class=\'btn btn-sm btn-warning\' title=\'Rollback\'><i class=\'fa fa-rotate-left\'></i></a>' : '',
    ]],
]))" />
