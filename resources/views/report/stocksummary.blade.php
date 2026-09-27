@extends('report.layout', ['title' => 'Stock Summary'])

@section('heading', 'Stock Summary Report')

@section('filters')
    @include('report._product_filters')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>Category</th><th>Product</th><th>In Qty.</th><th class="text-end">In Amount</th><th>Out Qty.</th><th class="text-end">Out Amount</th><th>Available Qty.</th><th class="text-end">Avail. Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['in_quantity'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['in_amount']) }}</td>
                    <td>{{ $row['out_quantity'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['out_amount']) }}</td>
                    <td>{{ $row['avl_quantity'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['avl_amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
