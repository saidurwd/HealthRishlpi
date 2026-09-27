@extends('report.layout', ['title' => 'Patient Register Physiotherapy'])

@section('heading')
    Patient Register Physiotherapy: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
    @if ($f['category_new']) ; Category: {{ \App\Models\PatientCategoryNew::query()->whereKey($f['category_new'])->value('alias') }} @endif
    @if ($f['category']) ; Sub Category: {{ \App\Models\PatientCategory::query()->whereKey($f['category'])->value('alias') }} @endif
@endsection

@section('filters')
    @include('report._category_filters')
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>SL#</th><th>Date</th><th>Patient ID</th><th>Category</th><th>Sub Category</th><th>Patient Name</th><th>Ref. No</th><th>Grade</th><th>Age</th><th>Sex</th><th>Guardian Name</th><th>Problem</th><th>Address</th><th>Contact No.</th><th>Reg. Fee</th><th>Invoice#</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['invoice_date']) }}</td>
                    <td>{{ $row['pid'] }}</td>
                    <td>{!! $pathsNew[$row['category_new']] ?? '' !!}</td>
                    <td>{!! $paths[$row['category']] ?? '' !!}</td>
                    <td>{{ $row['pname'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $grades[$row['patient_grade']] ?? '' }}</td>
                    <td>{{ $row['age'] }}</td>
                    <td>{{ $row['sex'] }}</td>
                    {{-- "name<br />contact", as the query builds it --}}
                    <td>{!! implode('<br />', array_map('e', explode('<br />', (string) $row['emergency_person']))) !!}</td>
                    <td>{{ $row['problem'] }}</td>
                    <td>{{ $addresses[$row['id']] ?? '' }}</td>
                    <td>{{ $row['mobile'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount']) }}</td>
                    <td class="text-start">{{ $row['invoice_number'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
