@extends('layouts.report')

@section('title', 'Purchase Order')

@section('content')
    <div class="d-flex justify-content-between">
        <div>
            <div class="fs-4">{{ config('legacy.adminName') }}</div>
            {{ config('legacy.adminAddress') }}
        </div>
        <h1 class="fw-normal">Purchase Order</h1>
    </div>
    <div class="row mt-3">
        <div class="col-9">{{ $parent->supplier0?->addressDetails() }}</div>
        <div class="col-3">
            <div class="d-flex justify-content-between"><strong>STATUS :</strong> {{ \App\Models\TransectionStatus::badge($parent->status, \App\Models\TransectionStatus::PURCHASE_ORDER) }}</div>
            <div class="d-flex justify-content-between"><strong>ORDER NO :</strong> {{ $parent->order_number }}</div>
            <div class="d-flex justify-content-between"><strong>ORDER DATE :</strong> {{ \App\Support\YiiFormat::date($parent->order_date) }}</div>
        </div>
    </div>
    <table class="table table-hover mt-3">
        <thead><tr><th>Product</th><th>Catalogue</th><th class="text-end" style="width:100px;">Quantity</th></tr></thead>
        <tbody>
            @foreach ($lines->rows as $row)
                <tr>
                    <td>{{ $row->item0?->title }}</td>
                    <td>{{ $row->item0?->product_code }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::number($row->quantity) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h5>Comments</h5>
    <p>{{ $parent->comments }}</p>
    <p class="text-body-secondary">Generated on {{ date('l jS \of F Y h:i:s A') }}</p>
@endsection
