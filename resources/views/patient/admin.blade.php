@extends('crud.admin')

@section('grid')
    <x-grid id="patient-grid" route="patient" :grid="$grid" :columns="[
        ['name' => 'created_on', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->created_on)],
        ['name' => 'category_new', 'value' => fn ($row) => $row->category_new0?->alias],
        ['name' => 'category', 'value' => fn ($row) => $row->category0?->alias],
        ['name' => 'pat_id', 'value' => fn ($row) => '<a href=\''.e(route('patient.view', $row->id)).'\'>'.e($row->pat_id).'</a>', 'raw' => true],
        ['name' => 'name', 'value' => fn ($row) => '<a href=\''.e(route('patient.view', $row->id)).'\'>'.e($row->name).'</a>', 'raw' => true],
        ['name' => 'mobile'],
        ['name' => 'age', 'value' => fn ($row) => $row->ageText()],
        ['name' => 'sex'],
        ['name' => 'blood_groop'],
        ['name' => 'national_id'],
        ['name' => 'address', 'value' => fn ($row) => $row->fullAddress()],
        ['header' => 'Actions', 'buttons' => ['view', 'update', 'delete']],
    ]" />
@endsection
