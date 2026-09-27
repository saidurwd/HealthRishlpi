@extends('report.layout', ['title' => 'Invoice By Prescription'])

@section('heading')
    {{ $patientName ?? 'Patient ' }} Invoice By Prescription: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
@endsection

@section('filters')
    <div class="col-md-2"><x-form.select name="type" :label="''" :options="['0' => 'All Prescription', '1' => 'Without Invoice']" :value="$f['type']" class="form-select-sm" /></div>
    <div class="col-md-3"><x-form.select name="patient" :label="''" :options="$patientOptions" :value="$f['patient']" empty="All Patient" class="form-select-sm" searchable /></div>
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>SL#</th><th>Date</th><th>Patient ID</th><th>Prescription#</th><th>Invoice#</th><th>Payment Status</th><th>Total Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['created_on']) }}</td>
                    <td class="text-start">{{ $row['pid'] }}</td>
                    <td class="text-start">{{ $row['pre_number'] }}</td>
                    <td class="text-start">{{ $row['invoice_number'] }}</td>
                    <td>{{ $row['payment_status'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['total_amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="6" class="text-end">TOTAL: </th><th class="text-end">{{ \App\Support\YiiFormat::currency(array_sum(array_column($rows, 'total_amount')), 0) }}</th></tr>
        </tfoot>
    </table>
@endsection
