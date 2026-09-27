@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase Orders" subtitle="Manage" :breadcrumbs="['Purchase Orders' => route('purchaseOrder.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Purchase Orders" flush>
        <x-slot:tools>
            <a href="{{ route('purchaseOrder.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        <x-grid id="purchase-order-parent-grid" :grid="$grid" :columns="[
            ['name' => 'order_number'],
            ['name' => 'order_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->order_date)],
            ['header' => '# of Items', 'value' => fn ($row) => $row->lines_count, 'class' => 'text-center'],
            ['name' => 'order_by', 'value' => fn ($row) => $row->orderBy?->full_name, 'filter' => $users],
            ['name' => 'supplier', 'value' => fn ($row) => $row->supplier0?->title, 'filter' => $vendors],
            ['name' => 'status', 'value' => fn ($row) => \App\Models\TransectionStatus::badge($row->status, \App\Models\TransectionStatus::PURCHASE_ORDER), 'filter' => $statuses],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('purchaseOrder.view', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('purchaseOrder.update', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('purchaseOrder.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
                fn ($row) => '<a href=\''.e(route('purchaseOrder.print', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
