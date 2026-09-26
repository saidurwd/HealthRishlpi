@extends('crud.admin')

@section('grid')
    <x-grid id="product-grid" route="product" :grid="$grid" :columns="[
        ['name' => 'category', 'value' => fn ($row) => $row->category0?->title],
        ['name' => 'title'],
        ['name' => 'product_code'],
        ['name' => 'description'],
        ['name' => 'unit', 'value' => fn ($row) => $row->unit0?->formal_name],
        ['name' => 'threshold_value'],
        ['name' => 'minimum_storage_limit'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
