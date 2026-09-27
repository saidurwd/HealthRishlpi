@extends('report.layout', ['title' => 'Patient Register Medicine'])

@section('heading', 'Patient Register Medicine: '.\App\Support\YiiFormat::date($f['start_date']).' to '.\App\Support\YiiFormat::date($f['end_date']))

@section('filters')
    @include('report._category_filters')
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>SL#</th><th>Date</th><th>Patient ID</th><th>Ref. No.</th><th>Category</th><th>Sub Category</th><th>Patient Name</th><th>Age</th><th>Sex</th><th>Address</th><th>Reg. Fee</th><th>Invoice#</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['invoice_date']) }}</td>
                    <td>{{ $row['pid'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{!! $pathsNew[$row['category_new']] ?? '' !!}</td>
                    <td>{!! $paths[$row['category']] ?? '' !!}</td>
                    <td>{{ $row['pname'] }}</td>
                    <td>{{ $row['age'] }}</td>
                    <td>{{ $row['sex'] }}</td>
                    <td>{{ $addresses[$row['id']] ?? '' }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount']) }}</td>
                    <td class="text-start">{{ $row['invoice_number'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
