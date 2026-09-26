@extends('crud.admin')

@section('grid')
    <x-grid id="department-grid" route="department" :grid="$grid" :columns="[
        ['name' => 'parent', 'value' => fn ($row) => $row->parentRow?->title, 'filter' => $parents],
        ['name' => 'code'],
        ['name' => 'title'],
        ['name' => 'alias'],
        ['name' => 'description'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
