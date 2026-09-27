@extends('layouts.report')

@section('title', 'Purchase Receive')

@push('head')
    <style>body { font-size: 10px; } .table { font-size: 10px; }</style>
@endpush

@section('content')
    <x-report-header style="font-size:14px;" />
    <h5>Purchase Receive</h5>
    <div class="row">
        <div class="col-9">{{ $parent->supplier0?->addressDetails() }}</div>
        <div class="col-3">
            <div class="d-flex justify-content-between"><strong>STATUS :</strong> {{ \App\Models\TransectionStatus::badge($parent->status, \App\Models\TransectionStatus::PURCHASE_RECEIVE) }}</div>
            <div class="d-flex justify-content-between"><strong>RECEIVE NO :</strong> {{ $parent->receive_number }}</div>
            <div class="d-flex justify-content-between"><strong>RECEIVE DATE :</strong> {{ \App\Support\YiiFormat::date($parent->receive_date) }}</div>
        </div>
    </div>
    <table class="table table-bordered table-striped table-hover mt-2">
        <thead>
            <tr><th>Product</th><th>Reference</th><th class="text-end">Quantity</th><th>Batch</th><th class="text-end">Buy Rate</th><th class="text-end">Buy Amount</th><th class="text-end">Sale Rate</th><th class="text-end">Sale Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($lines->rows as $row)
                <tr>
                    <td>{{ $row->item0?->title }}</td>
                    <td>{{ $row->referenceOrderNumber() }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::number($row->quantity) }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row->batch0?->expiry) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->buy_rate) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->buy_amount) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->rate) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->total_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-7">
            @if ($parent->documents->isNotEmpty())
                <h3>Documents</h3>
                <ul>
                    @foreach ($parent->documents as $document)
                        <li>{{ $document->doc_title }}</li>
                    @endforeach
                </ul>
            @endif
            <h5>Comments</h5>
            <p>{{ $parent->comments }}</p>
        </div>
        <div class="col-5 text-end">
            <h3><strong>Total Sale Amount <span class="text-success">{{ \App\Support\YiiFormat::currency($lines->rows->sum(fn ($r) => (float) $r->total_amount)) }}</span></strong>, <strong> Purchase Amount<span class="text-success">{{ \App\Support\YiiFormat::currency($lines->rows->sum(fn ($r) => (float) $r->buy_amount)) }}</span></strong></h3>
        </div>
    </div>
@endsection
