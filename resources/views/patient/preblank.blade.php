@extends('layouts.report')

@section('print-delay', 5000)

@section('content')
    @include('patient._prescription-print', ['full' => false])
@endsection
