@extends('crud.admin')

@section('grid')
    <x-grid id="disease-grid" route="disease" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
