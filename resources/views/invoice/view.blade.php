@extends('layouts.app')

@section('title', 'Invoice Details')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Invoices" subtitle="Invoice Details" :breadcrumbs="['Invoices' => route('invoice.admin'), $parent->invoice_number]" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('invoice.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('invoice.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        <a href="{{ route('invoice.print', $parent->id) }}" target="_blank" class="btn btn-info"><i class="fa fa-print"></i> PRINT</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('invoice._page', ['byLabel' => 'Order By'])</x-slot:title>
        @include('invoice._lines', ['mode' => 'view'])
        <h3 class="mt-3">Comments</h3>
        <p>{{ $parent->comments }}</p>
    </x-card>
@endsection
