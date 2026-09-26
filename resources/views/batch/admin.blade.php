@extends('crud.admin')

@section('grid')
    <x-grid id="batch-grid" route="batch" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['name' => 'manufacturing'],
        ['name' => 'expiry'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
