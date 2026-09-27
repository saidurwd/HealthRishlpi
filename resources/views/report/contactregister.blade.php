@extends('report.layout', ['title' => 'Physiotherapy Patient Contact Register'])

@section('heading')
    Physiotherapy Patient Contact Register: {{ \App\Support\YiiFormat::date($f['start_date']) }} to {{ \App\Support\YiiFormat::date($f['end_date']) }}
    @if ($f['category_new']) ; Category: {{ \App\Models\PatientCategoryNew::query()->whereKey($f['category_new'])->value('alias') }} @endif
    @if ($f['category']) ; Sub Category: {{ \App\Models\PatientCategory::query()->whereKey($f['category'])->value('alias') }} @endif
@endsection

@section('filters')
    @include('report._category_filters')
    <div class="col-md-2"><x-form.select name="admission" :label="''" :options="['Yes' => 'Yes', 'No' => 'No']" :value="$f['admission']" empty="All Admission" class="form-select-sm" /></div>
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>SL#</th><th>Date</th><th>Patient ID</th><th>Category</th><th>Sub Category</th><th>Patient Name</th><th>Ref. No</th><th>Age</th><th>Sex</th><th>Problem</th><th>Address</th><th>Contact No.</th><th>Referred</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ \App\Support\YiiFormat::date($row['created_on']) }}</td>
                    <td>{{ $row['pid'] }}</td>
                    <td>{!! $pathsNew[$row['category_new']] ?? '' !!}</td>
                    <td>{!! $paths[$row['category']] ?? '' !!}</td>
                    <td>{{ $row['pname'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $row['age'] }}</td>
                    <td>{{ $row['sex'] }}</td>
                    <td>{{ $row['problem'] }}</td>
                    <td>{{ $addresses[$row['id']] ?? '' }}</td>
                    <td>{{ $row['mobile'] }}</td>
                    <td>{{ $row['referred'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
