@extends('layouts.app')

@php $editing = $parent->exists; @endphp

@section('title', $editing ? 'Edit Stock Requisition' : 'New Stock Requisition')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Requisitions" :subtitle="$editing ? 'Edit Requisition' : 'New Requisition'" :breadcrumbs="$editing
        ? ['Stock Requisitions' => route('stockRequisition.admin'), $parent->requisition_number => route('stockRequisition.view', $parent->id), 'Update']
        : ['Stock Requisitions' => route('stockRequisition.admin'), 'Create']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('stockRequisition.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        @if ($editing)
            <a href="{{ route('stockRequisition.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
            <a href="{{ route('stockRequisition.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
        @endif
    </div>
    <x-card icon="fa fa-plus">
        <x-slot:title>@if ($editing) @include('stock-requisition._heading') @else New Requisition @endif</x-slot:title>
        <x-grid id="stock-requisition-grid" :grid="$lines" :columns="[
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Store', 'value' => fn ($row) => $row->store0?->title ?: 'N/A'],
            ['header' => $editing ? 'Batch' : 'Lot No.', 'value' => fn ($row) => $row->batch0?->title],
            $editing
                ? ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity), 'class' => 'text-end']
                : ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.$row->uom(), 'class' => 'text-end'],
            ...($editing ? [['header' => 'UOM', 'value' => fn ($row) => $row->uom(), 'class' => 'text-center']] : []),
            ['header' => 'Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->amount), 'class' => 'text-end'],
            ['header' => 'Actions', 'buttons' => [fn ($row) => '<a href=\''.e(route('stockRequisition.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>']],
        ]" />
        @include('stock._line_form', ['action' => route('stockRequisition.add'), 'formId' => 'stock-requisition-form', 'gridId' => 'stock-requisition-grid'])
        @include('stock._parent_form', ['formId' => 'stock-requisition-parent-form'])
    </x-card>
@endsection
