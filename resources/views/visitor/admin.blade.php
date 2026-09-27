@extends('crud.admin')

@section('tools')
    <form method="post" action="{{ route('visitor.truncate') }}" class="d-inline" onsubmit="return confirm('Delete all visitor statistics?')">
        @csrf
        <button type="submit" class="btn btn-sm btn-danger" title="Truncate Data"><i class="fa fa-times"></i></button>
    </form>
@endsection

@section('grid')
    <x-grid id="visitor-grid" route="visitor" :grid="$grid" :columns="[
        ['name' => 'user_id', 'value' => fn ($row) => '<a href=\''.e(route('user.view', (int) $row->user_id)).'\'>'.e($row->user?->full_name).'</a>', 'raw' => true, 'filter' => $users],
        ['name' => 'user_name'],
        ['name' => 'page_title'],
        ['name' => 'page_link'],
        ['name' => 'server_time', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->server_time)],
        ['name' => 'browser'],
        ['name' => 'visitor_ip'],
        ['header' => 'Actions', 'buttons' => ['delete']],
    ]" />
@endsection
