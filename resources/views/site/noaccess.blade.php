@extends('layouts.app')

@section('title', 'Error 403')

@section('header')
    <x-page-header icon="fa fa-ban" title="Error 403" :breadcrumbs="['Error 403']" />
@endsection

@section('content')
    <div class="text-center py-5">
        <h1 class="display-4"><i class="fa fa-times-circle text-danger"></i> Error 403</h1>
        <h2 class="fs-3"><strong>Oooops, Something went wrong!</strong></h2>
        <p class="lead mt-4">
            <strong>You are not authorized to perform this action.</strong><br><br>
            <small>Please contact with the application administrator for permission.</small>
        </p>
    </div>
@endsection
