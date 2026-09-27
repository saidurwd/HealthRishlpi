@extends('report.layout', ['title' => 'Patient Invoices'])

@section('heading')
    {{ $patientName ?? 'Patient ' }} Invoices: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
    @if ($f['status']) ; Status: {{ $f['status'] }} @endif
@endsection

@section('filters')
    <div class="col-md-3"><x-form.select name="patient" :label="''" :options="$patientOptions" :value="$f['patient']" empty="All Patient" class="form-select-sm" searchable /></div>
    <div class="col-md-2"><x-form.select name="status" :label="''" :options="\App\Models\InvoiceParent::PAYMENT_STATUSES" :value="$f['status']" empty="All Status" class="form-select-sm" /></div>
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>SL#</th><th>Invoice#</th><th>Date</th><th>Payment Status</th><th>Total Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-start">{{ $row['invoice_number'] }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['invoice_date']) }}</td>
                    <td>{{ $row['payment_status'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['total_amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="4" class="text-end">TOTAL: </th><th class="text-end">{{ \App\Support\YiiFormat::currency(array_sum(array_column($rows, 'total_amount')), 0) }}</th></tr>
        </tfoot>
    </table>
@endsection
