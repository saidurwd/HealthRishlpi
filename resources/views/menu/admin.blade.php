@extends('crud.admin')

@section('grid')
    <x-grid id="menu-grid" route="menu" :grid="$grid" :columns="[
        ['name' => 'parent', 'value' => fn ($row) => $row->parentRow?->title, 'filter' => false],
        ['name' => 'title'],
        ['name' => 'controller'],
        ['name' => 'url'],
        ['name' => 'icon'],
        ['name' => 'ordering', 'class' => 'text-center'],
        ['name' => 'status', 'value' => fn ($row) => $row->status ? 'Active' : 'Inactive', 'filter' => ['0' => 'Inactive', '1' => 'Active'], 'class' => 'text-center'],
        ['name' => 'group', 'class' => 'text-center'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
