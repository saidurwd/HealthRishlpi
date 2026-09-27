@extends('layouts.report')

@section('title', 'Stock Transfer')

@section('content')
    @include('stock._print', [
        'title' => 'Stock Transfer',
        'facts' => [
            'STATUS' => \App\Models\TransectionStatus::badge($parent->status, \App\Models\TransectionStatus::STOCK_TRANSFER),
            'TRANSFER NO' => $parent->transfer_number,
            'TRANSFER DATE' => \App\Support\YiiFormat::date($parent->transfer_date),
        ],
        'total' => \App\Support\YiiFormat::currency($lines->rows->sum(fn ($r) => (float) $r->total_amount)),
        'columns' => [
            'Item' => fn ($row) => $row->item0?->title,
            'From Store' => fn ($row) => $row->storeFrom0?->title ?: 'N/A',
            'To Store' => fn ($row) => $row->storeTo0?->title ?: 'N/A',
            'Batch' => fn ($row) => $row->batch0?->title,
            'Quantity' => fn ($row) => \App\Support\YiiFormat::number($row->quantity),
            'Rate' => fn ($row) => \App\Support\YiiFormat::currency($row->rate),
            'Amount' => fn ($row) => \App\Support\YiiFormat::currency($row->total_amount),
        ],
    ])
@endsection
