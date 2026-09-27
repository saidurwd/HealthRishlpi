@extends('report.layout', ['title' => 'Patient by Disease'])

@section('heading', 'Patient by Disease: '.\App\Support\YiiFormat::date($f['start_date']).' to '.\App\Support\YiiFormat::date($f['end_date']))

@section('filters')
    @include('report._dates')
@endsection

@section('count', count($rows))

@section('table')
    @php $totalPatient = 0; @endphp
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th class="text-center">SL NO.</th><th>Name of Disease</th><th class="text-center">No of Patient</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $totalPatient += $row['total']; @endphp
                <tr>
                    <td class="text-center" style="width:100px;">{{ $loop->iteration }}</td>
                    <td>{{ $diseases[$row['diagnosis']] ?? '' }}</td>
                    <td class="text-center">{{ $row['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><th></th><th class="text-center">TOTAL: </th><th class="text-center">{{ $totalPatient }}</th></tr>
        </tfoot>
    </table>
@endsection
