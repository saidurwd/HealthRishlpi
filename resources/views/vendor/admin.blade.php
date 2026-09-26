@extends('crud.admin')

@section('grid')
    <x-grid id="vendor-grid" route="vendor" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['name' => 'email'],
        ['name' => 'phone'],
        ['name' => 'mobile'],
        ['name' => 'address'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
