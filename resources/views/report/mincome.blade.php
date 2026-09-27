@extends('report.layout', ['title' => 'Income by Medicine'])

@section('heading')
    Income by Medicine: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
    @if ($f['product']) ; Product: {{ $products[$f['product']] ?? '' }} @endif
@endsection

@section('filters')
    @include('report._dates')
    <div class="col-md-3"><x-form.select name="product" :label="''" :options="$productOptions" :value="$f['product']" empty="Select a Product" class="form-select-sm" searchable /></div>
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>Product</th><th>Sale Quantity</th><th>Buy Amount</th><th>Sale Amount</th><th>Income</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $products[$row['item']] ?? '' }}</td>
                    <td>{{ round((float) $row['quantity'], 2) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['buy_amount']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['sale_amount']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['income']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th class="text-end">TOTAL: </th>
                <th>{{ array_sum(array_column($rows, 'quantity')) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency(array_sum(array_column($rows, 'buy_amount')), 0) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency(array_sum(array_column($rows, 'sale_amount')), 0) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency(array_sum(array_column($rows, 'income')), 0) }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
