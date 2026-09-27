@extends('crud.admin')

@section('grid')
    <x-grid id="user-grid" route="user" :grid="$grid" :columns="[
        ['name' => 'full_name'],
        ['name' => 'username'],
        ['name' => 'email'],
        ['name' => 'register_date', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->register_date)],
        ['name' => 'lastvisit', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->lastvisit)],
        ['name' => 'group_id', 'value' => fn ($row) => $row->group0?->title, 'filter' => $groups],
        ['name' => 'status', 'value' => fn ($row) => $row->status ? 'Active' : 'Inactive', 'filter' => ['0' => 'Inactive', '1' => 'Active']],
        ['header' => 'Actions', 'buttons' => [
            'update',
            fn ($row) => '<a href=\''.e(route('user.edit', $row->id)).'\' class=\'btn btn-sm btn-warning\' title=\'Change Password\'><i class=\'fa fa-key\'></i></a>',
            'delete',
        ]],
    ]" />
@endsection
