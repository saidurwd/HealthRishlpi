@extends('layouts.app')

@section('title', 'New '.$page['singular'])

@section('header')
    <x-page-header icon="fa fa-home" :title="$page['plural']" :subtitle="'New '.$page['singular']" :breadcrumbs="[$page['plural'] => route($page['route'].'.admin'), 'Create']" />
@endsection

@section('content')
    <x-card icon="fa fa-plus" :title="'New '.$page['singular']">
        <x-slot:tools>
            <a href="{{ route($page['route'].'.admin') }}" class="btn btn-sm btn-primary" title="Manage"><i class="fa fa-home"></i> MANAGE</a>
        </x-slot:tools>
        <form method="post" id="{{ Str::kebab($page['route']) }}-form">
            @csrf
            <p class="text-body-secondary">Fields with <span class="required text-danger">*</span> are required.</p>
            <x-form.errors />
            @include($form)
            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </x-card>
@endsection
