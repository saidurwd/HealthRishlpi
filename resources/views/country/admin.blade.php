@extends('crud.admin')

@section('grid')
    <x-grid id="country-grid" route="country" :grid="$grid" :columns="[
        ['name' => 'title'],
        ['name' => 'country_2_code'],
        ['name' => 'country_3_code'],
        ['name' => 'status', 'filter' => \App\Models\LegacyModel::STATUSES],
        ['header' => 'Actions', 'buttons' => ['update', 'delete']],
    ]" />
@endsection
