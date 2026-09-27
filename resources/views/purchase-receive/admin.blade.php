@extends('layouts.app')

@section('title', 'Purchase Receives')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase Receives" subtitle="Manage" :breadcrumbs="['Purchase Receives' => route('purchaseReceive.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Purchase Receive" flush>
        <x-slot:tools>
            <a href="{{ route('purchaseReceive.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        <x-grid id="purchase-receive-parent-grid" :grid="$grid" :columns="[
            ['header' => 'References', 'value' => fn ($row) => $row->references()],
            ['name' => 'receive_number'],
            ['name' => 'receive_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->receive_date)],
            ['header' => '# of Items', 'value' => fn ($row) => $row->lines_count, 'class' => 'text-center'],
            ['header' => 'Buy Price', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->lines_sum_buy_amount), 'class' => 'text-end'],
            ['header' => 'Sale Price', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->lines_sum_total_amount), 'class' => 'text-end'],
            ['name' => 'receive_by', 'value' => fn ($row) => $row->receiveBy?->full_name, 'filter' => $users],
            ['name' => 'supplier', 'value' => fn ($row) => $row->supplier0?->title, 'filter' => $vendors],
            ['name' => 'status', 'value' => fn ($row) => \App\Models\TransectionStatus::badge($row->status, \App\Models\TransectionStatus::PURCHASE_RECEIVE), 'filter' => $statuses],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('purchaseReceive.view', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('purchaseReceive.update', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
                fn ($row) => $row->isEditable() ? '<a href=\''.e(route('purchaseReceive.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
                fn ($row) => $row->canSpecialEdit() ? '<a href=\''.e(route('purchaseReceive.edit', $row->id)).'\' class=\'btn btn-sm btn-warning\' title=\'Special Edit\'><i class=\'fa fa-edit\'></i></a>' : '',
                fn ($row) => '<a href=\''.e(route('purchaseReceive.print', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
