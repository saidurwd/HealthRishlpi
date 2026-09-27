@extends('layouts.app')

@section('title', 'Stock Issue Details')

@section('header')
    <x-page-header icon="fa fa-cubes" title="Stock Issues" subtitle="Issue Details" :breadcrumbs="['Stock Issues' => route('stockIssue.admin'), $parent->issue_number]" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('stockIssue.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('stockIssue.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('stock-issue._heading')</x-slot:title>
        @include('stock-issue._lines', ['mode' => 'view'])
        <h3 class="mt-3">Comments</h3>
        <p>{{ $parent->comments }}</p>
    </x-card>
@endsection
