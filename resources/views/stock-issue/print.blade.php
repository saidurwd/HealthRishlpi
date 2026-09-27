@extends('layouts.report')

@section('title', 'Stock Issue')

@push('head')
    <style>body { font-size: 10px; } .table { font-size: 10px; }</style>
@endpush

@section('content')
    <x-report-header style="font-size:14px;" />
    <div class="d-flex mb-2">
        <div style="width:300px;"><h5>STOCK ISSUE</h5></div>
        <div>ISSUE No.: {{ $parent->issue_number }}<br>DATE: {{ \App\Support\YiiFormat::date($parent->issue_date) }}</div>
    </div>
    <table class="table table-hover">
        <thead><tr><th>Product</th><th>Batch</th><th class="text-end">Quantity</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
            @foreach ($lines->rows as $row)
                <tr>
                    <td>{{ $row->item0?->title }}</td>
                    <td>{{ $row->batch0?->title }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::number($row->quantity) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->rate) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold"><td>TOTAL</td><td></td><td></td><td></td><td class="text-end">{{ \App\Support\YiiFormat::currency($lines->rows->sum(fn ($r) => (float) $r->amount)) }}</td></tr>
        </tfoot>
    </table>
    <div style="margin-top:10px;border:1px solid #999;padding:5px;text-align:right;font-size:14px;text-transform:uppercase;">
        Grand Total: {{ \App\Support\YiiFormat::currency($total) }}
    </div>
    @if (! empty($parent->comments))
        <h5 class="mt-3">Comments</h5>
        <p>{{ $parent->comments }}</p>
    @endif
@endsection
