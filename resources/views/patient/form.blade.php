{{-- Register / edit a patient: every section visible, save bar always in reach --}}
@extends('layouts.app')

@php $editing = $record->exists; @endphp

@section('title', ($editing ? 'Edit '.$record->name : 'New Patient').' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-user-plus" title="Patients" :subtitle="$editing ? 'Edit Patient' : 'New Patient'" :breadcrumbs="$editing
        ? ['Patients' => route('patient.admin'), $record->name => route('patient.view', $record->id), 'Edit']
        : ['Patients' => route('patient.admin'), 'New']" />
@endsection

@section('content')
    <form method="post" id="patient-form" class="patient-form" autocomplete="off">
        @csrf
        <x-form.errors />
        @include($form)
        <div class="patient-form-actions card">
            <div class="card-body d-flex flex-wrap align-items-center gap-2 py-2">
                <span class="small text-body-secondary me-auto">Fields with <span class="text-danger">*</span> are required.@if ($editing) Patient ID {{ $record->pat_id }}.@else The patient ID is given on saving.@endif</span>
                <a href="{{ $editing ? route('patient.view', $record->id) : route('patient.admin') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> {{ $editing ? 'Save changes' : 'Register patient' }}</button>
            </div>
        </div>
    </form>
@endsection
