@extends('crud.admin')

@section('grid')
    <x-grid id="patient-grade-grid" route="patientGrade" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['name' => 'remarks'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
