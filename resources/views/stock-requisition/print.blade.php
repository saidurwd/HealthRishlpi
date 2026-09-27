@extends('layouts.report')

@section('title', 'Stock Requisition')

@section('content')
    @include('stock._print', [
        'title' => 'Stock Requisition',
        'facts' => [
            'STATUS' => \App\Models\TransectionStatus::badge($parent->status, \App\Models\TransectionStatus::STOCK_REQUISITION),
            'REQUISITION NO' => $parent->requisition_number,
            'REQUISITION DATE' => \App\Support\YiiFormat::date($parent->requisition_date),
        ],
        'total' => \App\Support\YiiFormat::currency($lines->rows->sum(fn ($r) => (float) $r->amount)),
        'columns' => [
            'Product' => fn ($row) => $row->item0?->title,
            'Store' => fn ($row) => $row->store0?->title ?: 'N/A',
            'Batch' => fn ($row) => $row->batch0?->title,
            'Quantity' => fn ($row) => \App\Support\YiiFormat::number($row->quantity),
            'Rate' => fn ($row) => \App\Support\YiiFormat::currency($row->rate),
            'Amount' => fn ($row) => \App\Support\YiiFormat::currency($row->amount),
        ],
    ])
@endsection
