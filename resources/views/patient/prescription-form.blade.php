@extends('layouts.app')

@php $title = $prescription->exists ? 'Edit Prescription' : 'New Prescription'; @endphp

@section('title', $title)

@section('header')
    <x-page-header icon="fa fa-home" title="Prescriptions" :subtitle="$title" :breadcrumbs="$prescription->exists
        ? ['Prescriptions' => route('patient.view', $patient->id), $prescription->pre_number => route('patient.view', $patient->id), 'Update']
        : ['Prescriptions' => route('patient.admin'), 'Create']" />
@endsection

@section('content')
    <x-card icon="fa fa-plus" :title="$title.' - '.$patient->name.' ['.$patient->pat_id.']'">
        <x-slot:tools>
            <a href="{{ route('patient.view', $patient->id) }}" class="btn btn-sm btn-primary" title="Back"><i class="fa fa-home"></i> BACK</a>
            @if ($prescription->exists)
                <a href="{{ route('patient.prescription', ['id' => $patient->id, 'preid' => $prescription->id]) }}" target="_blank" class="btn btn-sm btn-info" title="Print"><i class="fa fa-print"></i> PRINT</a>
            @endif
        </x-slot:tools>

        <x-grid id="prescription-medicine-grid" :grid="$lines" :columns="[
            ['name' => 'servicetype', 'sortable' => false, 'filter' => false],
            ['name' => 'product', 'sortable' => false, 'filter' => false],
            ['name' => 'instruction', 'sortable' => false, 'filter' => false],
            ['name' => 'no_of_days', 'sortable' => false, 'filter' => false, 'class' => 'text-center'],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('patient.removemedicine', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>',
            ]],
        ]" />

        <form method="post" action="{{ route('patient.addmedicine') }}" id="prescription-medicine-form" data-line-form="prescription-medicine-grid" class="row g-2 align-items-start mt-3">
            @csrf
            <input type="hidden" name="parent" value="{{ $prescription->exists ? $prescription->id : 0 }}">
            <div class="col-md-3">
                <x-form.select name="product" :label="''" :options="$products" empty="Select a Product" searchable />
            </div>
            <div class="col-md-3">
                <x-form.select name="instruction" :label="''" :options="$instructions" empty="Select an Instruction" searchable />
            </div>
            <div class="col-md-2">
                <x-form.input name="no_of_days" :label="''" maxlength="20" placeholder="No of Days" />
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add</button>
            </div>
        </form>

        <form method="post" id="prescription-form" class="mt-3 border-top pt-3">
            @csrf
            <x-form.errors />
            <div class="row">
                <div class="col-md-4">
                    <x-form.select name="diagnosis" :label="$prescription::label('diagnosis')" :options="$diseases" :value="$prescription->diagnosis" empty="Select a Disease" required searchable />
                </div>
                <div class="col-md-4">
                    <x-form.input name="cc" :label="$prescription::label('cc')" :value="$prescription->cc" maxlength="250" placeholder="C/C" />
                </div>
                <div class="col-md-4">
                    <x-form.input name="temp" :label="$prescription::label('temp')" :value="$prescription->temp" maxlength="250" placeholder="Temp" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.input name="oe" :label="$prescription::label('oe')" :value="$prescription->oe" maxlength="250" placeholder="O/E" />
                </div>
                <div class="col-md-4">
                    <x-form.input name="bp" :label="$prescription::label('bp')" :value="$prescription->bp" maxlength="250" placeholder="B/P" />
                </div>
                <div class="col-md-4">
                    <x-form.input name="pulse" :label="$prescription::label('pulse')" :value="$prescription->pulse" maxlength="250" placeholder="Pulse" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="advice" :label="$prescription::label('advice')" :value="$prescription->advice" maxlength="250" placeholder="Advice" />
                </div>
                <div class="col-md-6">
                    <x-form.textarea name="rx" :label="$prescription::label('rx')" :value="$prescription->rx" rows="2" />
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ $prescription->exists ? 'Save' : 'Create' }}</button>
        </form>
    </x-card>
@endsection
