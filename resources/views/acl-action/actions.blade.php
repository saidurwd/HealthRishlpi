@extends('layouts.app')

@section('title', 'ACL Actions')

@section('header')
    <x-page-header icon="fa fa-home" title="ACL Actions" subtitle="Manage" :breadcrumbs="['Configuration', 'ACL Actions' => route('aclAction.actions', ['cid' => $controller->id]), $controller->controller]" />
@endsection

@section('content')
    <x-card icon="fa fa-home" :title="'Controller Actions ('.$controller->controller.')'" flush>
        <x-slot:tools>
            <a href="{{ route('aclAction.create', ['cid' => $controller->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> New Action</a>
            <a href="{{ route('aclController.admin') }}" class="btn btn-sm btn-primary"><i class="fa fa-home"></i> Manage Controllers</a>
        </x-slot:tools>
        <x-grid id="acl-action-grid" :grid="$grid" :columns="[
            ['name' => 'title'],
            ['name' => 'controller_id', 'value' => fn ($row) => $controller->controller],
            ['name' => 'action'],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<a href=\''.e(route('aclAction.view', ['id' => $row->id, 'cid' => $controller->id])).'\' class=\'btn btn-sm btn-info\' title=\'View\'><i class=\'fa fa-eye\'></i></a>',
                fn ($row) => '<a href=\''.e(route('aclAction.update', ['id' => $row->id, 'cid' => $controller->id])).'\' class=\'btn btn-sm btn-primary\' title=\'Update\'><i class=\'fa fa-pencil\'></i></a>',
                fn ($row) => '<a href=\''.e(route('aclAction.delete', ['id' => $row->id, 'cid' => $controller->id])).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-times\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
