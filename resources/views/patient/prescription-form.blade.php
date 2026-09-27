{{--
    Write or edit a prescription (resources/js/prescription.js): medicines on
    the left (searched on demand, added and removed without reloading),
    clinical notes on the right. Saving posts the notes form; medicines added
    before saving are attached to the new prescription, as before.
--}}
@extends('layouts.app')

@php
    $title = $prescription->exists ? 'Edit Prescription' : 'New Prescription';
    $data = [
        'parent' => $prescription->exists ? $prescription->id : 0,
        'lines' => $lines,
        'instructions' => $instructions,
        'urls' => [
            'lines' => route('patient.medicines'),
            'products' => route('patient.products'),
            'add' => route('patient.addmedicine'),
            'delete' => route('patient.removemedicine', '__ID__'),
        ],
    ];
@endphp

@section('title', $title.' - '.$patient->name)

@section('header')
    <x-page-header icon="fa fa-file-text-o" title="Prescriptions" :subtitle="$title" :breadcrumbs="array_merge(
        ['Patients' => route('patient.admin'), $patient->name => route('patient.view', $patient->id)],
        $prescription->exists ? [$prescription->pre_number] : ['New prescription'],
    )" />
@endsection

@section('content')
    <div id="prescription-workspace" class="invoice-workspace">
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3 py-2">
                <div class="patient-avatar patient-avatar-sm" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($patient->name), 0, 1)) }}</div>
                <div class="me-auto">
                    <a href="{{ route('patient.view', $patient->id) }}" class="fw-semibold">{{ $patient->name }}</a>
                    <div class="small text-body-secondary">
                        {{ $patient->pat_id }}@if ($patient->sex) · {{ $patient->sex }}@endif @if ($patient->ageShort() !== '') · {{ $patient->ageShort() }}@endif @if ($patient->mobile) · {{ $patient->mobile }}@endif
                    </div>
                </div>
                @if ($prescription->exists)
                    <span class="badge text-bg-dark">{{ $prescription->pre_number }}</span>
                    <a href="{{ route('patient.prescription', ['id' => $patient->id, 'preid' => $prescription->id]) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print"></i> Print</a>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-medkit text-primary"></i> Add medicine</h3></div>
                    <div class="card-body">
                        <form id="medicine-form" action="{{ route('patient.addmedicine') }}" autocomplete="off" novalidate>
                            <input type="hidden" name="parent" value="{{ $prescription->exists ? $prescription->id : 0 }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small text-body-secondary" for="medicine-product">Medicine <kbd class="ms-1">/</kbd></label>
                                    <select id="medicine-product" name="product" placeholder="Search a medicine…"></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-body-secondary" for="medicine-instruction">Instruction</label>
                                    <select id="medicine-instruction" name="instruction" placeholder="e.g. 1+0+1 after meal"></select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-body-secondary" for="medicine-days">Days</label>
                                    <input type="number" id="medicine-days" name="no_of_days" class="form-control" min="0" step="1" placeholder="Days">
                                </div>
                                <div class="col-md-1 d-grid">
                                    <button type="submit" class="btn btn-primary" title="Add (Enter)"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small" id="medicine-error" role="alert" hidden></div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-list text-primary"></i> Medicines <span class="badge text-bg-secondary ms-1" id="medicine-count">0</span></h3></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 invoice-lines" id="medicine-lines">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:3rem">#</th>
                                    <th>Medicine</th>
                                    <th>Instruction</th>
                                    <th class="text-end">Days</th>
                                    <th style="width:3rem"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <form method="post" id="prescription-form" class="invoice-sidebar" autocomplete="off">
                    @csrf
                    <div class="card">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-stethoscope text-primary"></i> Clinical notes</h3></div>
                        <div class="card-body">
                            <x-form.errors />
                            <x-form.select name="diagnosis" :label="$prescription::label('diagnosis')" :options="$diseases" :value="$prescription->diagnosis" empty="Select a Disease" required searchable />
                            <div class="row g-2">
                                <div class="col-4">
                                    <x-form.input name="bp" :label="$prescription::label('bp')" :value="$prescription->bp" maxlength="250" placeholder="120/80" />
                                </div>
                                <div class="col-4">
                                    <x-form.input name="pulse" :label="$prescription::label('pulse')" :value="$prescription->pulse" maxlength="250" placeholder="/min" />
                                </div>
                                <div class="col-4">
                                    <x-form.input name="temp" :label="$prescription::label('temp')" :value="$prescription->temp" maxlength="250" placeholder="°F" />
                                </div>
                            </div>
                            <x-form.input name="cc" :label="$prescription::label('cc')" :value="$prescription->cc" maxlength="250" placeholder="Chief complaint" />
                            <x-form.input name="oe" :label="$prescription::label('oe')" :value="$prescription->oe" maxlength="250" placeholder="On examination" />
                            <x-form.input name="advice" :label="$prescription::label('advice')" :value="$prescription->advice" maxlength="250" placeholder="Advice" />
                            <x-form.textarea name="rx" :label="$prescription::label('rx')" :value="$prescription->rx" rows="3" />
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1" id="prescription-save"><i class="fa fa-check"></i> {{ $prescription->exists ? 'Save changes' : 'Save prescription' }}</button>
                            <a href="{{ route('patient.view', $patient->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="application/json" id="prescription-data">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
