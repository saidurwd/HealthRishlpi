@extends('layouts.app')

@section('title', 'Stock Requisition Details')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Requisitions" subtitle="Requisition Details" :breadcrumbs="['Stock Requisition' => route('stockRequisition.admin'), $parent->requisition_number]" />
@endsection

@section('content')
    <div class="mb-3 text-end d-flex justify-content-end gap-1">
        <a href="{{ route('stockRequisition.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('stockRequisition.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        @if ((int) $parent->status === 0)
            <a href="{{ route('stockRequisition.update', $parent->id) }}" class="btn btn-primary"><i class="fa fa-pencil"></i> EDIT</a>
        @endif
        @if ($canConvert)
            <form method="post" action="{{ route('stockRequisition.convertissue', $parent->id) }}">
                @csrf
                <button type="submit" class="btn btn-success"><i class="fa fa-arrows-alt"></i> MAKE ME ISSUE</button>
            </form>
        @endif
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('stock-requisition._heading')</x-slot:title>
        <x-grid id="stock-requisition-grid" :grid="$lines" :columns="[
            ['header' => 'Product', 'value' => fn ($row) => $row->item0?->title],
            ['header' => 'Store', 'value' => fn ($row) => $row->store0?->title ?: 'N/A'],
            ['header' => 'Batch', 'value' => fn ($row) => $row->batch0?->title],
            ['header' => 'Quantity', 'value' => fn ($row) => \App\Support\YiiFormat::number($row->quantity).' '.$row->uom(), 'class' => 'text-end'],
            ['header' => 'Rate', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->rate), 'class' => 'text-end'],
            ['header' => 'Amount', 'value' => fn ($row) => \App\Support\YiiFormat::currency($row->amount), 'class' => 'text-end'],
        ]" />
        <h3 class="mt-3">Comments</h3>
        <p>{{ $parent->comments }}</p>
    </x-card>
@endsection
