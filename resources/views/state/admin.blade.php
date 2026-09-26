@extends('crud.admin')

@section('grid')
    <x-grid id="state-grid" route="state" :grid="$grid" :columns="[
        ['name' => 'country', 'value' => fn ($row) => $row->country0?->title, 'filter' => $countries],
        ['name' => 'title'],
        ['name' => 'state_2_code'],
        ['name' => 'state_3_code'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
