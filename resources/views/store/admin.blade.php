@extends('crud.admin')

@section('grid')
    <x-grid id="store-grid" route="store" :grid="$grid" :columns="[
        ['name' => 'parent', 'value' => fn ($row) => $row->parentRow?->title, 'filter' => $parents],
        ['name' => 'title'],
        ['name' => 'alias'],
        ['name' => 'location'],
        ['name' => 'incharge', 'value' => fn ($row) => $users[$row->incharge] ?? null, 'filter' => $users],
        ['name' => 'description'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
