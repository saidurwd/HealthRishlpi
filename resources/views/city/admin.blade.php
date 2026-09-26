@extends('crud.admin')

@section('grid')
    <x-grid id="city-grid" route="city" :grid="$grid" :columns="[
        ['name' => 'country', 'value' => fn ($row) => $row->country0?->title, 'filter' => $countries],
        ['name' => 'state', 'value' => fn ($row) => $row->state0?->title, 'filter' => $states],
        ['name' => 'title'],
        ['name' => 'city_2_code'],
        ['name' => 'city_3_code'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
