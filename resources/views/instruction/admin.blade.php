@extends('crud.admin')

@section('grid')
    <x-grid id="instruction-grid" route="instruction" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
