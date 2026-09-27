@extends('crud.admin')

@section('grid')
    <x-grid id="user-group-grid" route="userGroup" :grid="$grid" :columns="[
        ['name' => 'name'],
        ['name' => 'details'],
        ['header' => 'Actions', 'buttons' => [
            'update',
            fn ($row) => '<a href=\''.e(route('userGroup.access', $row->id)).'\' class=\'btn btn-sm btn-primary\' title=\'Set user access\'><i class=\'fa fa-lock\'></i></a>',
            'delete',
        ]],
    ]" />
@endsection
