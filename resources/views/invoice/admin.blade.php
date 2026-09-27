@extends('layouts.app')

@section('title', 'Invoices - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Invoices" subtitle="Manage" :breadcrumbs="['Invoices' => route('invoice.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" title="Invoices" flush>
        <x-slot:tools>
            <a href="{{ route('invoice.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        @include('invoice._grid', ['admin' => true])
    </x-card>
@endsection
