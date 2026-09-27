@extends('layouts.app')

@section('title', 'New Invoice')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Invoices" subtitle="New Invoice" :breadcrumbs="['Invoices' => route('invoice.admin'), 'Create']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('invoice.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
    </div>
    <x-card icon="fa fa-plus" title="New Invoice">
        @include('invoice._lines', ['mode' => 'draft'])
        @include('invoice._line_form')
        @include('invoice._header_form', ['submit' => 'SAVE INVOICE'])
    </x-card>
@endsection
