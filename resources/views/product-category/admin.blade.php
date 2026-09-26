@extends('crud.admin')

@section('grid')
    <x-grid id="product-category-grid" route="productCategory" :grid="$grid" :columns="[
        ['name' => 'parent', 'value' => fn ($row) => $row->parentRow?->title, 'filter' => $parents],
        ['name' => 'title'],
        ['name' => 'alias'],
        ['name' => 'description'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
