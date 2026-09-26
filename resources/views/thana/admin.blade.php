@extends('crud.admin')

@section('grid')
    <x-grid id="thana-grid" route="thana" :grid="$grid" :columns="[
        ['name' => 'country', 'value' => fn ($row) => $row->country0?->title, 'filter' => $countries],
        ['name' => 'state', 'value' => fn ($row) => $row->state0?->title, 'filter' => $states],
        ['name' => 'city', 'value' => fn ($row) => $row->city0?->title, 'filter' => $cities],
        ['name' => 'district', 'value' => fn ($row) => $row->district0?->title, 'filter' => $districts],
        ['name' => 'title'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
