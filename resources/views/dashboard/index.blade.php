@extends('layouts.app')

@section('title', 'Dashboard - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-home" title="Dashboard" :breadcrumbs="['Dashboard']" />
@endsection

@section('content')
    {{-- TODO(port): themes/classic/views/dashboard/index.php --}}
@endsection
