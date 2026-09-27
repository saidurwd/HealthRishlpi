@extends('layouts.app')

@section('title', 'Stock Requisitions')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Requisitions" subtitle="Manage" :breadcrumbs="['Stock Requisitions' => route('stockRequisition.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Stock Requisitions" flush>
        <x-slot:tools>
            <a href="{{ route('stockRequisition.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        <x-grid id="stock-requisition-parent-grid" :grid="$grid" :columns="[
            ['name' => 'requisition_number'],
            ['name' => 'requisition_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->requisition_date)],
            ['header' => '# of Items', 'value' => fn ($row) => $row->lines_count, 'class' => 'text-center'],
            ['name' => 'requisition_by', 'value' => fn ($row) => $row->requisitionBy?->full_name, 'filter' => $users],
            ['name' => 'status', 'value' => fn ($row) => \App\Models\TransectionStatus::badge($row->status, \App\Models\TransectionStatus::STOCK_REQUISITION), 'filter' => $statuses],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('stockRequisition.view', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('stockRequisition.update', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('stockRequisition.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
                fn ($row) => '<a href=\''.e(route('stockRequisition.print', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
