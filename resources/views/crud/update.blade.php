@extends('layouts.app')

@section('title', 'Edit '.$page['singular'])

@section('header')
    <x-page-header icon="fa fa-home" :title="$page['plural']" :subtitle="'Edit '.$page['singular']" :breadcrumbs="[$page['plural'] => route($page['route'].'.admin'), $record->getKey() => route($page['route'].'.update', $record->getKey()), 'Update']" />
@endsection

@section('content')
    <x-card icon="fa fa-plus" :title="'Edit '.$page['singular']">
        <x-slot:tools>
            <a href="{{ route($page['route'].'.admin') }}" class="btn btn-sm btn-primary" title="Manage"><i class="fa fa-home"></i> MANAGE</a>
        </x-slot:tools>
        <form method="post" id="{{ Str::kebab($page['route']) }}-form" @if ($multipart ?? false) enctype="multipart/form-data" @endif>
            @csrf
            <p class="text-body-secondary">Fields with <span class="required text-danger">*</span> are required.</p>
            <x-form.errors />
            @include($form)
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </x-card>
@endsection
