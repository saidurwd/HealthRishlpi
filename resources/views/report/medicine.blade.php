@extends('report.layout', ['title' => 'Medicine Bill Report'])

@section('heading')
    @include('report._bill_heading', ['name' => 'Medicine Bill Report'])
@endsection

@section('filters')
    @include('report._bill_filters')
@endsection

@section('count', count($rows))

@section('table')
    @php $totalService = 0; $totalMedicine = 0; @endphp
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>@if ($print)<th>SL#</th>@endif<th>Date</th><th>{{ $print ? 'Patient ID' : 'Patient#' }}</th><th>Reference#</th><th>Category</th><th>Sub Category</th><th>Name</th><th>Physician Visit Charge</th><th>Medicine</th><th>{{ $print ? 'Total' : 'Total Amount' }}</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $totalService += $row['amount_service']; $totalMedicine += $row['amount_medicine']; @endphp
                <tr>
                    @if ($print)<td>{{ $loop->iteration }}</td>@endif
                    <td>{{ \App\Support\YiiFormat::date($row['created_on']) }}</td>
                    <td>{{ $row['pat_id'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $aliasesNew[$row['category_new']] ?? '' }}</td>
                    <td>{{ $aliases[$row['category']] ?? '' }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount_service']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount_medicine']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount_service'] + $row['amount_medicine']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="{{ $print ? 7 : 6 }}" class="text-end">{{ $print ? 'GRAND TOTAL:' : 'TOTAL:' }} </th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalService, 0) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalMedicine, 0) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalService + $totalMedicine, 0) }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
