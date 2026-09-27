@extends('layouts.app')

@section('title', 'Stock Transfer')

@section('header')
    <x-page-header icon="fa fa-exchange" title="Stock Transfer" subtitle="Manage" :breadcrumbs="['Stock Transfer' => route('stockTransfer.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Stock Transfer" flush>
        <x-slot:tools>
            <a href="{{ route('stockTransfer.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        <x-grid id="stock-transfer-parent-grid" :grid="$grid" :columns="[
            ['name' => 'transfer_number'],
            ['name' => 'transfer_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->transfer_date)],
            ['header' => '# of Items', 'value' => fn ($row) => $row->lines_count, 'class' => 'text-center'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->lines_sum_total_amount), 'class' => 'text-end'],
            ['name' => 'transfer_by', 'value' => fn ($row) => $row->transferBy?->full_name, 'filter' => $users],
            ['name' => 'status', 'value' => fn ($row) => \App\Models\TransectionStatus::badge($row->status, \App\Models\TransectionStatus::STOCK_TRANSFER), 'filter' => $statuses],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('stockTransfer.view', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('stockTransfer.update', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('stockTransfer.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
                fn ($row) => '<a href=\''.e(route('stockTransfer.print', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
