@extends('crud.admin')

@section('grid')
    <x-grid id="acl-controller-grid" route="aclController" :grid="$grid" :columns="[
        ['name' => 'title', 'value' => fn ($row) => '<a href=\''.e(route('aclAction.actions', ['cid' => $row->id])).'\'>'.e($row->title).'</a>', 'raw' => true],
        ['name' => 'controller', 'value' => fn ($row) => '<a href=\''.e(route('aclAction.actions', ['cid' => $row->id])).'\'>'.e($row->controller).'</a>', 'raw' => true],
        ['name' => 'status', 'value' => fn ($row) => $row->status ? 'Active' : 'Inactive', 'filter' => ['0' => 'Inactive', '1' => 'Active']],
        ['header' => 'Actions', 'value' => fn ($row) => '<a href=\''.e(route('aclAction.actions', ['cid' => $row->id])).'\' class=\'btn btn-sm btn-danger\' title=\'Manage Actions!\'>Actions ('.$row->acl_actions_count.')</a>', 'raw' => true],
        ['header' => 'Actions', 'buttons' => ['view', 'update', 'delete']],
    ]" />
@endsection
