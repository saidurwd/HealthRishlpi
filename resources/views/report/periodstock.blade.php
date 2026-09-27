@extends('report.layout', ['title' => 'Period Wise Stock'])

@section('heading')
    Period Wise Stock Report: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
    @if ((int) $f['cid'] > 0) ; Category: {{ \App\Models\ProductCategory::query()->whereKey($f['cid'])->value('title') }} @endif
    @if ((int) $f['itemid'] > 0) ; Product: {{ \App\Models\Product::query()->whereKey($f['itemid'])->value('title') }} @endif
    @if ((int) $f['store'] > 0) ; Store: {{ \App\Models\Store::query()->whereKey($f['store'])->value('title') }} @endif
@endsection

@section('filters')
    @include('report._product_filters')
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th colspan="2">&nbsp;</th><th>OPENING BALANCE</th><th colspan="2">IN</th><th colspan="2">OUT</th><th>CLOSING BALANCE</th></tr>
            <tr><th>Category</th><th>Product</th><th>Opening Qty.</th><th>In Qty.</th><th class="text-end">In Amount</th><th>Out Qty.</th><th class="text-end">Out Amount</th><th>Closing Qty.</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ number_format((float) $row['opening_quantity'], 2, '.', ',') }}</td>
                    <td>{{ number_format((float) $row['in_quantity'], 2, '.', ',') }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['in_amount']) }}</td>
                    <td>{{ number_format((float) $row['out_quantity'], 2, '.', ',') }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['out_amount']) }}</td>
                    <td>{{ number_format((float) $row['closing_quantity'], 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
