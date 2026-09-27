@extends('layouts.app')

@section('title', 'Buy & Sale Price Comparison')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase" subtitle="Buy & Sale Price Comparison" :breadcrumbs="['Purchase Receives' => route('purchaseReceive.admin'), 'Price Comparison']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Buy & Sale Price Comparison" flush>
        <x-grid id="purchase-receive-price-grid" :grid="$grid" :columns="[
            ['name' => 'item', 'value' => fn ($row) => $row->item0?->title, 'filter' => $products],
            ['name' => 'buy_rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->buy_rate), 'class' => 'text-end'],
            ['name' => 'rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['name' => 'batch', 'header' => 'Lot No.', 'value' => fn ($row) => $row->batch0?->title],
            ['name' => 'batch', 'header' => 'Expiry', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->batch0?->expiry)],
        ]" />
    </x-card>
@endsection
