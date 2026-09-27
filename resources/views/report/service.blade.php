@extends('report.layout', ['title' => 'Rehabilitation Service Bill Report'])

@section('heading')
    @include('report._bill_heading', ['name' => 'Rehabilitation Service Bill Report'])
@endsection

@section('filters')
    @include('report._bill_filters')
@endsection

@section('count', count($rows))

@section('table')
    @php $totalConsultation = 0; $totalService = 0; @endphp
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>@if ($print)<th>SL#</th>@endif<th>Date</th><th>{{ $print ? 'Patient ID' : 'Patient#' }}</th><th>Reference#</th><th>Grade</th><th>Category</th><th>Sub Category</th><th>Name</th><th>Note</th><th>Consultation/Admission</th><th>Service</th><th>{{ $print ? 'Total' : 'Total Amount' }}</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $totalConsultation += $row['amount_consultation']; $totalService += $row['amount_service']; @endphp
                <tr>
                    @if ($print)<td>{{ $loop->iteration }}</td>@endif
                    <td>{{ \App\Support\YiiFormat::date($row['created_on']) }}</td>
                    <td>{{ $row['pat_id'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $grades[$row['patient_grade']] ?? '' }}</td>
                    <td>{{ $aliasesNew[$row['category_new']] ?? '' }}</td>
                    <td>{{ $aliases[$row['category']] ?? '' }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['note'] }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount_consultation']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount_service']) }}</td>
                    <td class="text-end">{{ \App\Support\YiiFormat::currency($row['amount_consultation'] + $row['amount_service']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="{{ $print ? 9 : 8 }}" class="text-end">{{ $print ? 'GRAND TOTAL:' : 'TOTAL:' }} </th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalConsultation, 0) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalService, 0) }}</th>
                <th class="text-end">{{ \App\Support\YiiFormat::currency($totalConsultation + $totalService, 0) }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
