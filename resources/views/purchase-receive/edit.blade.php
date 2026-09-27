@extends('layouts.app')

@section('title', 'Edit Purchase Receive')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase" subtitle="Edit Receive" :breadcrumbs="['Purchase Receives' => route('purchaseReceive.admin'), $parent->receive_number => route('purchaseReceive.view', $parent->id), 'Update']" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('purchaseReceive.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('purchaseReceive.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        <a href="{{ route('purchaseReceive.view', $parent->id) }}" class="btn btn-primary"><i class="fa fa-external-link-square"></i> DETAILS</a>
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('purchase-receive._heading')</x-slot:title>
        @include('purchase-receive._lines', ['mode' => 'edit', 'stores' => []])
    </x-card>
@endsection
