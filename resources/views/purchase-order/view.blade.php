@extends('layouts.app')

@section('title', 'Purchase Order Details')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase Orders" subtitle="Order Details" :breadcrumbs="['Purchase Orders' => route('purchaseOrder.admin'), $parent->order_number]" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('purchaseOrder.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('purchaseOrder.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        <a href="{{ route('purchaseOrder.print', $parent->id) }}" target="_blank" class="btn btn-info"><i class="fa fa-print"></i> PRINT</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('purchase-order._heading')</x-slot:title>
        <x-grid id="purchase-order-grid" :grid="$lines" :columns="[
            ['header' => 'Category', 'value' => fn ($row) => $row->item0?->category0?->title],
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Catalogue', 'value' => fn ($row) => $row->item0?->product_code],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.($row->item0?->unit0?->formal_name ?: 'N/A')],
        ]" />
        <h3 class="mt-3">Comments</h3>
        <p>{{ $parent->comments }}</p>
    </x-card>
@endsection
