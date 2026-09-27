@extends('layouts.app')

@section('title', 'Edit Stock Issue')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Issues" subtitle="Edit Issue" :breadcrumbs="['Stock Issues' => route('stockIssue.admin'), $parent->issue_number => route('stockIssue.view', $parent->id), 'Update']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('stockIssue.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('stockIssue.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        <a href="{{ route('stockIssue.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('stock-issue._heading')</x-slot:title>
        @include('stock-issue._lines', ['mode' => 'edit'])
    </x-card>
@endsection
