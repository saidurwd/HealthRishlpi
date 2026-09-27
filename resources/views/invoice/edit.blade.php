@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Invoices" subtitle="Edit Invoice" :breadcrumbs="['Invoices' => route('invoice.admin'), $parent->invoice_number => route('invoice.view', $parent->id), 'Update']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('invoice.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('invoice.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        <a href="{{ route('invoice.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('invoice._page', ['byLabel' => 'Order By'])</x-slot:title>
        @include('invoice._lines', ['mode' => 'edit'])
        @include('invoice._header_form', ['submit' => 'UPDATE INVOICE', 'fields' => ['categories']])
    </x-card>
@endsection
