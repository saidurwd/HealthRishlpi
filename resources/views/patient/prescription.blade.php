@extends('layouts.report')

@section('print-delay', 5000)

@push('head')
    <style>body { font-size: 10px; }</style>
@endpush

@section('content')
    @include('patient._prescription-print', ['full' => true])
@endsection
