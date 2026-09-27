@extends('layouts.app')

@section('title', $record->name.' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-user-injured" title="Patients" subtitle="Patient Details" :breadcrumbs="['Patients' => route('patient.admin'), $record->name]" />
@endsection

@php
    $user = auth()->user();
    $detail = fn (array $rows) => collect($rows)->filter(fn ($value) => $value !== null && $value !== '');
@endphp

@section('content')
    {{-- Profile --}}
    <div class="card mb-3 patient-profile">
        <div class="card-body d-flex flex-wrap gap-3 align-items-start">
            <div class="patient-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($record->name), 0, 1)) }}</div>
            <div class="flex-grow-1">
                <h2 class="h4 mb-1">{{ $record->name }}</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span class="badge text-bg-dark">{{ $record->pat_id }}</span>
                    @if ($record->sex)<span class="badge text-bg-light border">{{ $record->sex }}</span>@endif
                    @if ($record->ageShort() !== '')<span class="badge text-bg-light border" title="{{ $record->ageText() }}">{{ $record->ageShort() }}</span>@endif
                    @if ($record->blood_groop)<span class="badge text-bg-danger">{{ $record->blood_groop }}</span>@endif
                    @if ($record->admission === 'Yes')<span class="badge text-bg-warning">Admitted</span>@endif
                </div>
                <div class="small text-body-secondary d-flex flex-wrap gap-3">
                    @if ($record->mobile)<span><i class="fa fa-phone"></i> {{ $record->mobile }}</span>@endif
                    @if ($record->category_new0)<span><i class="fa fa-tag"></i> {{ $record->category_new0->title }}@if ($record->category0) / {{ $record->category0->title }}@endif</span>@endif
                    @if (trim($record->fullAddress(), ', ') !== '')<span><i class="fa fa-map-marker"></i> {{ trim($record->fullAddress(), ', ') }}</span>@endif
                    <span><i class="fa fa-calendar"></i> Registered {{ \App\Support\YiiFormat::date($record->created_on) }}@if ($record->createdBy) by {{ $record->createdBy->full_name }}@endif</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 ms-auto">
                @can('invoice.create')
                    <a href="{{ route('invoice.create', ['patient' => $record->id]) }}" class="btn btn-success"><i class="fa fa-shopping-cart"></i> New invoice</a>
                @endcan
                @can('patient.newprescription')
                    <a href="{{ route('patient.newprescription', $record->id) }}" class="btn btn-primary"><i class="fa fa-file-text-o"></i> New prescription</a>
                @endcan
                @can('patient.update')
                    <a href="{{ route('patient.update', $record->id) }}" class="btn btn-outline-primary"><i class="fa fa-pencil"></i> Edit</a>
                @endcan
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-print"></i> Print</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('patient.card', $record->id) }}" target="_blank">Health card</a></li>
                        <li><a class="dropdown-item" href="{{ route('patient.registration', $record->id) }}" target="_blank">Registration form</a></li>
                        <li><a class="dropdown-item" href="{{ route('patient.rehabilitation', $record->id) }}" target="_blank">Rehabilitation form</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-footer bg-body-tertiary">
            <div class="row text-center g-2 patient-stats">
                <div class="col-6 col-md-3"><div class="stat-value">{{ $stats['prescriptions'] }}</div><div class="stat-label">Prescriptions</div></div>
                <div class="col-6 col-md-3"><div class="stat-value">{{ $stats['invoices'] }}</div><div class="stat-label">Invoices</div></div>
                <div class="col-6 col-md-3"><div class="stat-value">{{ \App\Support\YiiFormat::currency($stats['billed']) }}</div><div class="stat-label">Billed (approved)</div></div>
                <div class="col-6 col-md-3"><div class="stat-value">{{ $stats['last_visit'] ? \App\Support\YiiFormat::date($stats['last_visit']) : '-' }}</div><div class="stat-label">Last visit</div></div>
            </div>
        </div>
    </div>

    {{-- History and details --}}
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-prescriptions" type="button"><i class="fa fa-file-text-o"></i> Prescriptions <span class="badge text-bg-secondary">{{ $stats['prescriptions'] }}</span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-invoices" type="button"><i class="fa fa-shopping-cart"></i> Invoices <span class="badge text-bg-secondary">{{ $stats['invoices'] }}</span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-details" type="button"><i class="fa fa-id-card"></i> Details</button></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-prescriptions">
                    <x-grid id="prescription-grid" :grid="$prescriptions" :columns="[
                        ['name' => 'pre_number', 'raw' => true, 'value' => fn ($row) =>
                            '<span class=\'fw-semibold\'>'.e($row->pre_number).'</span><div class=\'small text-body-secondary\'>'.e(\App\Support\YiiFormat::dateTime($row->created_on)).'</div>'],
                        ['name' => 'diagnosis', 'value' => fn ($row) => $row->diagnosis0?->title, 'filter' => $diseases],
                        ['name' => 'cc', 'header' => 'Complaint'],
                        ['header' => 'Vitals', 'raw' => true, 'value' => fn ($row) => collect(['BP' => $row->bp, 'Pulse' => $row->pulse, 'Temp' => $row->temp])
                            ->filter(fn ($v) => trim((string) $v) !== '')
                            ->map(fn ($v, $k) => '<span class=\'text-nowrap\'><span class=\'text-body-secondary\'>'.$k.'</span> '.e($v).'</span>')->implode('<br>')],
                        ['header' => 'Actions', 'buttons' => [
                            fn ($row) => $user->can('patient.editprescription') ? '<a href=\''.e(route('patient.editprescription', $row->id)).'\' class=\'btn btn-sm btn-outline-primary\' title=\'Edit\'><i class=\'fa fa-pencil\'></i></a>' : '',
                            fn ($row) => '<a href=\''.e(route('patient.prescription', ['id' => $row->patient, 'preid' => $row->id])).'\' class=\'btn btn-sm btn-outline-secondary\' title=\'Print\' target=\'_blank\'><i class=\'fa fa-print\'></i></a>',
                            fn ($row) => '<a href=\''.e(route('patient.preblank', ['id' => $row->patient, 'preid' => $row->id])).'\' class=\'btn btn-sm btn-outline-secondary\' title=\'Print blank (handwriting)\' target=\'_blank\'><i class=\'fa fa-file-o\'></i></a>',
                            fn ($row) => $user->can('invoice.create') ? '<a href=\''.e(route('invoice.create', ['patient' => $row->patient])).'\' class=\'btn btn-sm btn-outline-success\' title=\'New invoice\'><i class=\'fa fa-shopping-cart\'></i></a>' : '',
                            fn ($row) => $user->can('patient.remove') ? '<a href=\''.e(route('patient.remove', $row->id)).'\' class=\'btn btn-sm btn-outline-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>' : '',
                        ]],
                    ]" />
                </div>
                <div class="tab-pane fade" id="tab-invoices">
                    @include('invoice._grid', ['grid' => $invoices, 'statuses' => $invoiceStatuses])
                </div>
                <div class="tab-pane fade" id="tab-details">
                    <div class="row g-4">
                        @foreach ([
                            'Patient' => [
                                $record::label('pat_id') => $record->pat_id,
                                $record::label('ref_no') => $record->ref_no,
                                $record::label('category_new') => $record->category_new0?->alias,
                                $record::label('category') => $record->category0?->alias,
                                $record::label('age') => $record->ageText(),
                                $record::label('birth_date') => \App\Support\YiiFormat::date($record->birth_date),
                                $record::label('blood_groop') => $record->blood_groop,
                                $record::label('admission') => $record->admission,
                                $record::label('problem') => $record->problem,
                                $record::label('referred') => $record->referred,
                            ],
                            'Personal & contact' => [
                                $record::label('mobile') => $record->mobile,
                                $record::label('email') => $record->email,
                                $record::label('national_id') => $record->national_id,
                                $record::label('marital_status') => $record->marital_status,
                                $record::label('spouse') => $record->spouse,
                                $record::label('occupation') => $record->occupation,
                                $record::label('religion') => $record->religion,
                                $record::label('address') => $record->address,
                                $record::label('village') => $record->village,
                                $record::label('post') => $record->post,
                                $record::label('thana') => $record->thana0?->title,
                                $record::label('district') => $record->district0?->title,
                                $record::label('country') => $record->country0?->title,
                            ],
                            'Guardian' => [
                                $record::label('emergency_name') => $record->emergency_name,
                                $record::label('emergency_relation') => $record->emergency_relation,
                                $record::label('emergency_contact') => $record->emergency_contact,
                                $record::label('guardian_occupation') => $record->guardian_occupation,
                                $record::label('no_of_family_member') => $record->no_of_family_member,
                                $record::label('earning_member') => $record->earning_member,
                                $record::label('earning_source') => $record->earning_source,
                            ],
                        ] as $section => $rows)
                            <div class="col-lg-4">
                                <h3 class="h6 text-uppercase text-body-secondary">{{ $section }}</h3>
                                <dl class="patient-details mb-0">
                                    @forelse ($detail($rows) as $label => $value)
                                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                                    @empty
                                        <div class="text-body-secondary small">Nothing recorded</div>
                                    @endforelse
                                </dl>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
