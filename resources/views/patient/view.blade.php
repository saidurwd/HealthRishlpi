@extends('layouts.app')

@section('title', 'Patient Details')

@section('header')
    <x-page-header icon="fa fa-user-injured" title="Patients" subtitle="Patient Details" :breadcrumbs="['Patients' => route('patient.admin'), $record->name]" />
@endsection

@section('content')
    <div class="mb-3 text-end d-flex flex-wrap gap-1 justify-content-end">
        <a href="{{ route('patient.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('patient.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        <a href="{{ route('patient.update', $record->id) }}" class="btn btn-primary"><i class="fa fa-pencil"></i> EDIT</a>
        <a href="{{ route('patient.newprescription', $record->id) }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW PRESCRIPTION</a>
        <a href="{{ route('patient.card', $record->id) }}" target="_blank" class="btn btn-info"><i class="fa fa-print"></i> HEALTH CARD</a>
        <a href="{{ route('patient.rehabilitation', $record->id) }}" target="_blank" class="btn btn-info"><i class="fa fa-print"></i> REHABILITATION</a>
        <a href="{{ route('patient.registration', $record->id) }}" target="_blank" class="btn btn-info"><i class="fa fa-print"></i> REGISTRATION</a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title me-auto">Patient Details</h3>
            <ul class="nav nav-tabs card-header-tabs ms-auto" role="tablist">
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#TAB1" type="button">HOME</button></li>
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#TAB2" type="button">PRESCRIPTION</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#TAB3" type="button">INVOICES</button></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade" id="TAB1">
                    <x-detail-view :rows="[
                        $record::label('category_new') => $record->category_new0?->alias,
                        $record::label('category') => $record->category0?->alias,
                        $record::label('pat_id') => $record->pat_id,
                        $record::label('ref_no') => $record->ref_no,
                        $record::label('name') => $record->name,
                        $record::label('age') => $record->ageText(),
                        $record::label('sex') => $record->sex,
                        $record::label('birth_date') => \App\Support\YiiFormat::date($record->birth_date),
                        $record::label('blood_groop') => $record->blood_groop,
                        $record::label('marital_status') => $record->marital_status,
                        $record::label('email') => $record->email,
                        $record::label('national_id') => $record->national_id,
                        $record::label('spouse') => $record->spouse,
                        $record::label('occupation') => $record->occupation,
                        $record::label('religion') => $record->religion,
                        $record::label('address') => $record->address,
                        $record::label('thana') => $record->thana0?->title,
                        $record::label('district') => $record->district0?->title,
                        $record::label('country') => $record->country0?->title,
                        $record::label('mobile') => $record->mobile,
                        $record::label('emergency_name') => $record->emergency_name,
                        $record::label('emergency_relation') => $record->emergency_relation,
                        $record::label('emergency_contact') => $record->emergency_contact,
                        $record::label('created_on') => \App\Support\YiiFormat::dateTime($record->created_on),
                        $record::label('created_by') => $record->createdBy?->full_name,
                    ]" />
                </div>
                <div class="tab-pane fade show active" id="TAB2">
                    <x-grid id="prescription-grid" :grid="$prescriptions" :columns="[
                        ['name' => 'pre_number'],
                        ['name' => 'diagnosis', 'value' => fn ($row) => $row->diagnosis0?->title, 'filter' => $diseases],
                        ['name' => 'cc'],
                        ['name' => 'oe'],
                        ['name' => 'bp'],
                        ['name' => 'pulse'],
                        ['name' => 'temp'],
                        ['name' => 'created_on', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->created_on)],
                        ['header' => 'Actions', 'buttons' => [
                            fn ($row) => '<a href=\''.e(route('patient.editprescription', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>',
                            fn ($row) => '<a href=\''.e(route('patient.remove', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>',
                            fn ($row) => '<a href=\''.e(route('patient.prescription', ['id' => $row->patient, 'preid' => $row->id])).'\' class=\'btn btn-sm btn-primary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
                            fn ($row) => '<a href=\''.e(route('patient.preblank', ['id' => $row->patient, 'preid' => $row->id])).'\' class=\'btn btn-sm btn-warning\' title=\'Blank Prescription\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
                        ]],
                    ]" />
                </div>
                <div class="tab-pane fade" id="TAB3">
                    @include('invoice._grid', ['grid' => $invoices, 'statuses' => $invoiceStatuses])
                </div>
            </div>
        </div>
    </div>
@endsection
