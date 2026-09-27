@extends('layouts.app')

@php $editing = $parent->exists; @endphp

@section('title', $editing ? 'Edit Stock Transfer' : 'New Stock Transfer')

@section('header')
    <x-page-header icon="fa fa-exchange" title="Stock Transfer" :subtitle="$editing ? 'Edit Transfer' : 'New Transfer'" :breadcrumbs="$editing
        ? ['Stock Transfer' => route('stockTransfer.admin'), $parent->transfer_number => route('stockTransfer.view', $parent->id), 'Update']
        : ['Stock Transfer' => route('stockTransfer.admin'), 'Create']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('stockTransfer.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        @if ($editing)
            <a href="{{ route('stockTransfer.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
            <a href="{{ route('stockTransfer.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
        @endif
    </div>
    <x-card icon="fa fa-plus">
        <x-slot:title>@if ($editing) @include('stock-transfer._heading') @else New Transfer @endif</x-slot:title>
        <x-grid id="stock-transfer-grid" :grid="$lines" :columns="[
            ['header' => 'Item', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'From Store', 'value' => fn ($row) => $row->storeFrom0?->title ?: 'N/A'],
            ['header' => 'To Store', 'value' => fn ($row) => $row->storeTo0?->title ?: 'N/A'],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.$row->uom(), 'class' => 'text-end'],
            ['header' => 'Rate', 'value' => fn ($row) => $editing ? \App\Support\YiiFormat::currency($row->rate) : \App\Support\YiiFormat::number($row->rate), 'class' => 'text-end'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
            ['header' => 'Lot No.', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Expiry', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->batch0?->expiry)],
            ['header' => 'Actions', 'buttons' => [fn ($row) => '<a href=\''.e(route('stockTransfer.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>']],
        ]" />
        @include('stock._line_form', ['action' => route('stockTransfer.add'), 'formId' => 'stock-transfer-form', 'gridId' => 'stock-transfer-grid', 'storeField' => 'store_from', 'storeTo' => $storeTree])
        @include('stock._parent_form', ['formId' => 'stock-transfer-parent-form'])
    </x-card>
@endsection
