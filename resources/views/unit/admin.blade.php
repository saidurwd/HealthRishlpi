@extends('crud.admin')

@section('grid')
    <x-grid id="unit-grid" route="unit" :grid="$grid" :columns="[
        ['name' => 'full_name'],
        ['name' => 'formal_name'],
        ['name' => 'decimal_place'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
