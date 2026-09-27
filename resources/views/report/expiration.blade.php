@extends('report.layout', ['title' => 'Expiration Report'])

@section('heading', 'Expiration Report')

@section('filters')
    <div class="col-md-2"><x-form.select name="item" :label="''" :options="$products" :value="$f['item']" empty="All Products" class="form-select-sm" searchable /></div>
    <div class="col-md-2"><x-form.select name="store" :label="''" :options="$storeOptions" :value="$f['store']" empty="All Stores" class="form-select-sm" /></div>
    <div class="col-md-2"><x-form.select name="expiry" :label="''" :options="['0' => 'Expired', '7' => 'Within one week', '14' => 'Within two weeks', '30' => 'Within one month', '60' => 'Within two months', '90' => 'Within three months', '182' => 'Within six months']" :value="$f['expiry']" class="form-select-sm" /></div>
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>Product</th><th>Batch</th><th>Quantity</th><th>Store</th><th class="text-end">Rate</th><th class="text-end">Total Price</th><th>Expiry</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['product'] }}</td>
                    <td>{{ $batches[$row['batch']] ?? '' }}</td>
                    <td>{{ $row['quantity'] }} {{ $row['unit'] }}</td>
                    <td>{!! $stores[$row['storeid']] ?? '' !!}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['rate']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount']) }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['expiry']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
