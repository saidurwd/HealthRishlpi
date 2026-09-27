@extends('crud.admin')

@section('tools')
    <span class="small text-body-secondary me-2">Today: {{ $today['requests'] }} pages and actions by {{ $today['users'] }} users ({{ $today['actions'] }} actions)</span>
@endsection

@section('grid')
    <x-grid id="activity-log-grid" :grid="$grid" :columns="[
        ['name' => 'created_at', 'header' => 'Time', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->created_at?->toDateTimeString()), 'class' => 'text-nowrap'],
        ['name' => 'causer_id', 'header' => 'User', 'value' => fn ($row) => $row->causer?->full_name, 'filter' => $users],
        ['name' => 'event', 'header' => 'Kind', 'raw' => true, 'filter' => ['get' => 'Page opened', 'post' => 'Action'], 'value' => fn ($row) =>
            $row->event === 'get' ? '<span class=\'badge text-bg-light border\'>Page</span>' : '<span class=\'badge text-bg-primary\'>Action</span>'],
        ['name' => 'description', 'header' => 'What'],
        ['name' => 'path', 'header' => 'Address', 'raw' => true, 'value' => fn ($row) => '<code class=\'small\'>'.e($row->properties['path'] ?? '').'</code>'],
        ['header' => 'Result', 'raw' => true, 'value' => fn ($row) => ($status = (int) ($row->properties['status'] ?? 0)) >= 400
            ? '<span class=\'badge text-bg-danger\'>'.$status.'</span>'
            : '<span class=\'text-body-secondary small\'>'.$status.' · '.(int) ($row->properties['ms'] ?? 0).' ms</span>'],
        ['name' => 'ip', 'header' => 'IP / browser', 'raw' => true, 'value' => fn ($row) =>
            e($row->properties['ip'] ?? '').'<div class=\'small text-body-secondary text-truncate\' style=\'max-width:14rem\' title=\''.e($row->properties['agent'] ?? '').'\'>'.e(\App\Support\UserAgent::summary($row->properties['agent'] ?? '')).'</div>'],
    ]" />
@endsection
