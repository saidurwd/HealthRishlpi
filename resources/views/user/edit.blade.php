@extends('layouts.app')

@section('title', 'Change Password')

@section('header')
    <x-page-header icon="fa fa-key" title="Users" subtitle="Change Password" :breadcrumbs="['Users' => route('user.admin'), $record->full_name, 'Change Password']" />
@endsection

@section('content')
    <x-card icon="fa fa-key" :title="'CHANGE PASSWORD - '.$record->full_name">
        <x-slot:tools>
            <a href="{{ route('user.admin') }}" class="btn btn-sm btn-primary" title="Manage"><i class="fa fa-home"></i> MANAGE</a>
        </x-slot:tools>
        <form method="post" id="user-form">
            @csrf
            <x-form.errors />
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="password" type="password" :label="$record::label('password')" maxlength="150" placeholder="Password" required />
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </x-card>
@endsection
