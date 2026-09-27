@extends('crud.admin')

@php
    $user = auth()->user();
    $actions = [
        fn ($row) => '<a href=\''.e(route('patient.view', $row->id)).'\' class=\'btn btn-sm btn-outline-secondary\' title=\'Open\'><i class=\'fa fa-folder-open\'></i></a>',
        fn ($row) => $user->can('patient.newprescription') ? '<a href=\''.e(route('patient.newprescription', $row->id)).'\' class=\'btn btn-sm btn-outline-primary\' title=\'New prescription\'><i class=\'fa fa-file-text-o\'></i></a>' : '',
        fn ($row) => $user->can('invoice.create') ? '<a href=\''.e(route('invoice.create', ['patient' => $row->id])).'\' class=\'btn btn-sm btn-outline-success\' title=\'New invoice\'><i class=\'fa fa-shopping-cart\'></i></a>' : '',
        fn ($row) => $user->can('patient.update') ? '<a href=\''.e(route('patient.update', $row->id)).'\' class=\'btn btn-sm btn-outline-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
        fn ($row) => $user->can('patient.delete') ? '<a href=\''.e(route('patient.delete', $row->id)).'\' class=\'btn btn-sm btn-outline-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
    ];
@endphp

@section('grid')
    <x-grid id="patient-grid" route="patient" :grid="$grid" :columns="[
        ['name' => 'name', 'header' => 'Patient (name, PAT#, mobile, NID)', 'raw' => true, 'value' => fn ($row) =>
            '<a href=\''.e(route('patient.view', $row->id)).'\' class=\'fw-semibold\'>'.e($row->name).'</a>'
            .'<div class=\'small text-body-secondary\'>'.e($row->pat_id).'</div>'],
        ['name' => 'mobile'],
        ['name' => 'sex', 'filter' => ['Male' => 'Male', 'Female' => 'Female']],
        ['name' => 'age', 'value' => fn ($row) => $row->ageShort(), 'class' => 'text-nowrap'],
        ['name' => 'category_new', 'header' => 'Category', 'raw' => true, 'value' => fn ($row) =>
            e($row->category_new0?->title).($row->category0 ? '<div class=\'small text-body-secondary\'>'.e($row->category0->title).'</div>' : '')],
        ['name' => 'created_on', 'header' => 'Registered', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->created_on), 'class' => 'text-nowrap'],
        ['header' => 'Actions', 'buttons' => $actions],
    ]" />
@endsection
