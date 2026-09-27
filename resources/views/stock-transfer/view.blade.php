@extends('layouts.app')

@section('title', 'Stock Transfer Details')

@section('header')
    <x-page-header icon="fa fa-exchange" title="Stock Transfer" subtitle="Transfer Details" :breadcrumbs="['Stock Transfer' => route('stockTransfer.admin'), $parent->transfer_number]" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('stockTransfer.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('stockTransfer.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('stock-transfer._heading')</x-slot:title>
        <x-grid id="stock-transfer-grid" :grid="$lines" :columns="[
            ['header' => 'Item', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'UOM', 'value' => fn ($row) => $row->uom(), 'class' => 'text-center'],
            ['header' => 'From Store', 'value' => fn ($row) => $row->storeFrom0?->title ?: 'N/A'],
            ['header' => 'To Store', 'value' => fn ($row) => $row->storeTo0?->title ?: 'N/A'],
            ['header' => 'Batch', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity), 'class' => 'text-end'],
            ['header' => 'Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount), 'class' => 'text-end'],
        ]" />
        <h3 class="mt-3">Comments</h3>
        <p>{{ $parent->comments }}</p>
    </x-card>
@endsection
