@extends('report.layout', ['title' => 'Stock Receive'])

@section('heading', 'Stock Receive: '.\App\Support\YiiFormat::date($f['start_date']).' to '.\App\Support\YiiFormat::date($f['end_date']))

@section('filters')
    @include('report._product_filters', ['withStore' => false])
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>Receive#</th><th>Date</th><th>Supplier</th><th>Store</th><th>Product</th><th>Lot No.</th><th>Expiry</th><th>Quantity</th><th>Rate</th><th>Amount</th><th>Available</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $unit = $units[$row['itemid']] ?? 'N/A'; @endphp
                <tr>
                    <td>{{ $row['receive_number'] }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['receive_date']) }}</td>
                    <td>{{ $row['supplier'] }}</td>
                    <td>{!! $stores[$row['storeid']] ?? '' !!}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['batch'] }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['expiry']) }}</td>
                    <td>{{ $print ? round((float) $row['quantity'], 2) : $row['quantity'] }} {{ $unit }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['rate']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['total']) }}</td>
                    <td>{{ \App\Http\Controllers\ReportController::available($row['storeid'], $row['itemid'], $row['batchid']) }} {{ $unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
