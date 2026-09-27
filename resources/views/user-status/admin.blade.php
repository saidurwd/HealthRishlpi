@extends('crud.admin')

@section('grid')
    <x-grid id="user-status-grid" route="userStatus" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
