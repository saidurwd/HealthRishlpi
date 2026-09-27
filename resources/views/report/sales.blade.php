@extends('report.layout', ['title' => 'Sales Report'])

@section('heading')
    Sales Report: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
    @if ($f['type']) ; Type: {{ $f['type'] }} @endif
    @if ($f['type'] === 'Service' && $f['service']) ; Service: {{ \App\Models\Service::query()->whereKey($f['service'])->value('title') }} @endif
    @if ($f['category']) ; Category: {{ \App\Models\PatientCategory::query()->whereKey($f['category'])->value('alias') }} @endif
    @if ($f['status']) ; Status: {{ $f['status'] }} @endif
@endsection

@section('filters')
    @include('report._dates')
    <div class="col-md-2"><x-form.select name="type" :label="''" :options="['Medicine' => 'Medicine', 'Service' => 'Service']" :value="$f['type']" empty="Select a Type" class="form-select-sm" /></div>
    <div class="col-md-2"><x-form.select name="service" :label="''" :options="$serviceOptions" :value="$f['service']" empty="Select a Service" class="form-select-sm" searchable /></div>
    <div class="col-md-2"><x-form.select name="product" :label="''" :options="$productOptions" :value="$f['product']" empty="Select a Product" class="form-select-sm" searchable /></div>
    <div class="col-md-2"><x-form.select name="category" :label="''" :options="$subCategoryOptions" :value="$f['category']" empty="All Sub Categories" class="form-select-sm" searchable /></div>
    <div class="col-md-2"><x-form.select name="status" :label="''" :options="\App\Models\InvoiceParent::PAYMENT_STATUSES" :value="$f['status']" empty="All Status" class="form-select-sm" /></div>
@endsection

@section('count', count($rows))

@section('table')
    @php $totalQty = 0; $totalAmount = 0; @endphp
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>@if ($print)<th class="text-center">SL#</th>@endif<th>Date</th><th>Invoice#</th><th>PID</th><th>Product/Service</th><th>Sold</th><th>Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $totalQty += $row['sold']; $totalAmount += $row['amount']; @endphp
                <tr>
                    @if ($print)<td class="text-center">{{ $loop->iteration }}</td>@endif
                    <td>{{ \App\Support\YiiFormat::date($row['created_on']) }}</td>
                    <td>{{ $row['invoice_number'] }}</td>
                    <td>{{ $patients[$row['patient']] ?? '' }}</td>
                    <td>{{ $row['servicetype'] === 'Medicine' ? ($products[$row['item']] ?? '') : ($services[$row['service']] ?? '') }}</td>
                    <td>{{ round((float) $row['sold'], 2) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="{{ $print ? 5 : 4 }}" class="text-end">TOTAL: </th>
                <th>{{ $totalQty }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalAmount, 0) }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
