@extends('report.layout', ['title' => 'Patient Category'])

@section('heading', 'Patient Category: '.\App\Support\YiiFormat::date($f['start_date']).' to '.\App\Support\YiiFormat::date($f['end_date']))

@section('filters')
    @include('report._dates')
@endsection

@section('table')
    @php
        $totalAll = $male['total'] + $female['total'];
        $groups = [1 => '0 - 5 Years', 2 => '6 - 14 Years', 3 => '15 - 24 Years', 4 => 'Above 25 Years'];
        $ratio = fn ($n) => $totalAll > 0 ? round(($n * 100) / $totalAll, 2) : 0;
        $totalPatient = array_sum(array_column($sexes, 'total'));
    @endphp
    <div class="row">
        <div class="col-lg-6">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr><th colspan="6">Patient Attendance (Age Analysis)</th></tr>
                    <tr><th class="text-center">SL NO.</th><th>Particulars</th><th class="text-center">Male Patients</th><th class="text-center">Female Patients</th><th class="text-center">Total</th><th class="text-center">Patients Ratio</th></tr>
                </thead>
                <tbody>
                    @foreach ($groups as $n => $label)
                        <tr>
                            <td class="text-center">{{ $n }}</td>
                            <td>{{ $label }}</td>
                            <td class="text-center">{{ $male['AGE_GROUP_'.$n] }}</td>
                            <td class="text-center">{{ $female['AGE_GROUP_'.$n] }}</td>
                            <td class="text-center">{{ $male['AGE_GROUP_'.$n] + $female['AGE_GROUP_'.$n] }}</td>
                            <td class="text-center">{{ $ratio($male['AGE_GROUP_'.$n] + $female['AGE_GROUP_'.$n]) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th></th><th class="text-end">TOTAL: </th><th class="text-center">{{ $male['total'] }}</th><th class="text-center">{{ $female['total'] }}</th><th class="text-center">{{ $totalAll }}</th><th class="text-center">100.00%</th></tr>
                </tfoot>
            </table>
        </div>
        <div class="col-lg-6">
            <x-pie-chart title="Age Wise Patients Category" :data="collect($groups)->flatMap(fn ($label, $n) => ['Male '.$label => (int) $male['AGE_GROUP_'.$n], 'Female '.$label => (int) $female['AGE_GROUP_'.$n]])->all()" />
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-lg-6">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr><th colspan="3">Patient Attendance (Sex Analysis)</th></tr>
                    <tr><th class="text-center">SL NO.</th><th>Particulars</th><th class="text-center">No of Patients</th></tr>
                </thead>
                <tbody>
                    @foreach ($sexes as $row)
                        <tr>
                            <td class="text-center" style="width:100px;">{{ $loop->iteration }}</td>
                            <td>{{ $row['sex'] }}</td>
                            <td class="text-center">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th></th><th class="text-end">TOTAL: </th><th class="text-center">{{ $totalPatient }}</th></tr>
                </tfoot>
            </table>
        </div>
        <div class="col-lg-6">
            <x-pie-chart title="Sex Wise Patients Category" :data="collect($sexes)->mapWithKeys(fn ($row) => [$row['sex'] => (int) $row['total']])->all()" />
        </div>
    </div>
@endsection
