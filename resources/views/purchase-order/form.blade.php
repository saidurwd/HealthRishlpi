@extends('layouts.app')

@php $editing = $parent->exists; @endphp

@section('title', $editing ? 'Edit Purchase Order' : 'New Purchase Order')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase Orders" :subtitle="$editing ? 'Edit Order' : 'New Order'" :breadcrumbs="$editing
        ? ['Purchase Orders' => route('purchaseOrder.admin'), $parent->order_number => route('purchaseOrder.view', $parent->id), 'Update']
        : ['Purchase Orders' => route('purchaseOrder.admin'), 'Create']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('purchaseOrder.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        @if ($editing)
            <a href="{{ route('purchaseOrder.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
            <a href="{{ route('purchaseOrder.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
        @endif
    </div>
    <x-card icon="fa fa-plus">
        <x-slot:title>@if ($editing) @include('purchase-order._heading') @else New Order @endif</x-slot:title>

        <x-grid id="purchase-order-grid" :grid="$lines" :columns="[
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Catalogue', 'value' => fn ($row) => $row->item0?->product_code],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.($row->item0?->unit0?->formal_name ?: 'N/A')],
            ['header' => 'Actions', 'buttons' => [fn ($row) => '<a href=\''.e(route('purchaseOrder.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>']],
        ]" />

        <form method="post" action="{{ route('purchaseOrder.add') }}" id="purchase-order-form" data-line-form="purchase-order-grid" class="row g-2 align-items-start mt-3">
            @csrf
            <input type="hidden" name="parent" value="{{ $editing ? $parent->id : 0 }}">
            <div class="col-md-4">
                <x-form.select name="item" :label="''" :options="$items" empty="Select a Product" searchable />
            </div>
            <div class="col-md-2">
                <x-form.input name="quantity" :label="''" maxlength="20" placeholder="Quantity" />
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add</button>
            </div>
        </form>

        <form method="post" id="purchase-order-parent-form" class="mt-3 border-top pt-3">
            @csrf
            <x-form.errors />
            <div class="row g-2">
                @isset($statuses)
                    <div class="col-md-2">
                        <x-form.select name="status" :label="$parent::label('status')" :options="$statuses" :value="$parent->status" />
                    </div>
                @endisset
                <div class="col-md-3">
                    <x-form.select name="supplier" :label="$parent::label('supplier')" :options="$vendors" :value="$parent->supplier" empty="Select a Supplier" required searchable />
                </div>
                <div class="col-md-5">
                    <x-form.input name="comments" :label="$parent::label('comments')" :value="$parent->comments" maxlength="1000" placeholder="Comments" />
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <button type="button" class="btn btn-default" onclick="window.history.back();">Back</button>
        </form>
    </x-card>
@endsection
