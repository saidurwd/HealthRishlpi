@extends('layouts.app')

@section('title', 'Purchase Receive Details')

@section('header')
    <x-page-header icon="fa fa-shopping-cart" title="Purchase" subtitle="Receive Details" :breadcrumbs="['Purchase Receives' => route('purchaseReceive.admin'), $parent->receive_number]" />
@endsection

@section('content')
    <div class="mb-3 text-end">
        <a href="{{ route('purchaseReceive.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> MANAGE</a>
        <a href="{{ route('purchaseReceive.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> NEW</a>
        @if ((int) $parent->status === 0)
            <a href="{{ route('purchaseReceive.update', $parent->id) }}" class="btn btn-primary"><i class="fa fa-pencil"></i> EDIT</a>
        @endif
    </div>
    <x-card icon="fa fa-home">
        <x-slot:title>@include('purchase-receive._heading')</x-slot:title>
        @include('purchase-receive._lines', ['mode' => 'view', 'stores' => []])
        @if ($parent->documents->isNotEmpty())
            <h3 class="mt-3">Documents</h3>
            <ul>
                @foreach ($parent->documents as $document)
                    <li><a href="{{ route('purchaseReceive.download', $document->id) }}">{{ $document->doc_title }}</a></li>
                @endforeach
            </ul>
        @endif
        <h3 class="mt-3">Comments</h3>
        <p>{{ $parent->comments }}</p>
    </x-card>
@endsection
