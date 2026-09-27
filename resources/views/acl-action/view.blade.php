@extends('layouts.app')

@section('title', 'ACL action details')

@section('header')
    <x-page-header icon="fa fa-user-md" title="ACL Actions" subtitle="Details ACL Action" :breadcrumbs="['Configuration', 'ACL Actions' => route('aclAction.actions', ['cid' => $controller->id]), $controller->controller.' - Details']" />
@endsection

@section('content')
    <x-card icon="fa fa-user-md" :title="'Action Details ('.$record->action.')'">
        <x-slot:tools>
            <a href="{{ route('aclAction.actions', ['cid' => $controller->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-home"></i> Manage Actions</a>
            <a href="{{ route('aclAction.create', ['cid' => $controller->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> New Action</a>
            <a href="{{ route('aclAction.update', ['id' => $record->id, 'cid' => $controller->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i> Edit Action</a>
            <a href="{{ route('aclController.admin') }}" class="btn btn-sm btn-primary"><i class="fa fa-home"></i> Manage Controllers</a>
        </x-slot:tools>
        <x-detail-view :rows="[
            $record::label('id') => $record->id,
            $record::label('controller_id') => $record->controller0?->controller,
            $record::label('title') => $record->title,
            $record::label('action') => $record->action,
        ]" />
    </x-card>
@endsection
