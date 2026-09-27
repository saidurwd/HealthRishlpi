@extends('crud.admin')

@section('grid')
    <x-grid id="audit-trail-grid" route="auditTrail" :grid="$grid" :columns="[
        ['name' => 'user_id', 'value' => fn ($row) => '<a href=\''.e(route('user.view', $row->user_id)).'\'>'.e($row->user?->full_name).'</a>', 'raw' => true, 'filter' => $users],
        ['name' => 'login_time', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->login_time)],
        ['name' => 'logout_time', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->logout_time)],
        ['header' => 'Duration', 'value' => fn ($row) => \App\Models\AuditTrail::interval($row->login_time, $row->logout_time)],
        ['header' => 'Actions', 'buttons' => ['delete']],
    ]" />
@endsection
