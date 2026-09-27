@extends('layouts.app')

@section('title', 'Controller details')

@section('header')
    <x-page-header icon="fa fa-user-md" title="Controllers" subtitle="Details Controller" :breadcrumbs="['Configuration', 'Controllers' => route('aclController.admin'), $record->controller]" />
@endsection

@section('content')
    <x-card icon="fa fa-user-md" title="Details Controller">
        <x-slot:tools>
            <a href="{{ route('aclController.admin') }}" class="btn btn-sm btn-primary"><i class="fa fa-home"></i> Manage Controller</a>
            <a href="{{ route('aclController.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> New Controller</a>
            <a href="{{ route('aclController.update', $record->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i> Edit Controller</a>
        </x-slot:tools>
        <x-detail-view :rows="[
            $record::label('id') => $record->id,
            $record::label('controller') => $record->controller,
            $record::label('title') => $record->title,
            $record::label('status') => $record->status ? 'Yes' : 'No',
        ]" />
    </x-card>
@endsection
