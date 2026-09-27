@extends('layouts.app')

@php $title = $record->exists ? 'Edit Action' : 'New Action'; @endphp

@section('title', $title)

@section('header')
    <x-page-header icon="fa fa-home" title="ACL Actions" :subtitle="$title" :breadcrumbs="['Configuration', 'ACL Actions' => route('aclAction.actions', ['cid' => $controller->id]), $record->exists ? 'Update' : 'Create']" />
@endsection

@section('content')
    <x-card icon="fa fa-plus" :title="$title.' ('.$controller->controller.')'">
        <x-slot:tools>
            <a href="{{ route('aclAction.actions', ['cid' => $controller->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-home"></i> Manage Actions</a>
        </x-slot:tools>
        <form method="post" id="acl-action-form">
            @csrf
            <p class="text-body-secondary">Fields with <span class="required text-danger">*</span> are required.</p>
            <x-form.errors />
            <input type="hidden" name="controller_id" value="{{ $controller->id }}">
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="title" :label="$record::label('title')" :value="$record->title" maxlength="150" placeholder="Action Title" required />
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="action" :label="$record::label('action')" :value="$record->action" maxlength="150" placeholder="Action" required />
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ $record->exists ? 'Save' : 'Create' }}</button>
        </form>
    </x-card>
@endsection
