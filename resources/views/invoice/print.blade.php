@extends('layouts.report')

@section('title', 'Invoice')

@push('head')
    <style>body { font-size: 10px; } .table { font-size: 10px; }</style>
@endpush

@section('content')
    <x-report-header style="font-size:14px;" />
    <div class="d-flex">
        <div style="width:300px;">
            PATIENT: {{ $parent->patient0?->name }}<br>
            PAYMENT: {{ $parent->payment_status }}<br>
            CATEGORY: {{ \App\Models\PatientCategoryNew::query()->whereKey($parent->patient_category_new)->value('alias') }}
        </div>
        <div>
            Sub CATEGORY: {{ \App\Models\PatientCategory::query()->whereKey($parent->patient_category)->value('alias') }}<br>
            INVOICE#: {{ $parent->invoice_number }}<br>
            DATE: {{ \App\Support\YiiFormat::date($parent->invoice_date) }}
        </div>
    </div>
    @php $rows = $lines->rows->getCollection(); @endphp
    <table class="table table-hover mt-2">
        <thead>
            <tr>
                <th>Product/Service</th>
                <th>Note</th>
                <th class="text-center" style="width:100px;">Quantity</th>
                <th class="text-end" style="width:100px;">Rate</th>
                <th class="text-end" style="width:100px;">Discount</th>
                <th class="text-end" style="width:150px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->item0?->title }}{!! $row->service0?->fullPath() !!}</td>
                    <td>{{ $row->note }}</td>
                    <td class="text-center">{{ \App\Support\YiiFormat::number($row->quantity) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->rate) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->discount) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td>TOTAL</td><td></td><td></td><td></td>
                <td class="text-end">{{ \App\Support\YiiFormat::currency($rows->sum(fn ($r) => (float) $r->discount)) }}</td>
                <td class="text-end">{{ \App\Support\YiiFormat::currency($rows->sum(fn ($r) => (float) $r->amount)) }}</td>
            </tr>
        </tfoot>
    </table>
    <div style="margin-top:10px;border:1px solid #999;padding:5px;text-align:right;font-size:14px;text-transform:uppercase;">
        Grand Total: {{ \App\Support\YiiFormat::currencyRound($total) }}
    </div>
    <div style="margin-top:20px;">
        @if (! empty($parent->comments))
            <h5>Comments</h5>
            <p>{{ $parent->comments }}</p>
        @endif
    </div>
@endsection
