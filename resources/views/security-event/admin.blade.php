@extends('layouts.app')

@section('title', 'Security Events - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-shield" title="Security Events" subtitle="Monitor" :breadcrumbs="['Security Events']" />
@endsection

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['Sign-ins', $summary['signins'], 'fa-sign-in', 'primary'],
            ['Failed sign-ins', $summary['failed'], 'fa-times-circle', $summary['failed'] > 20 ? 'danger' : 'warning'],
            ['Lockouts', $summary['lockouts'], 'fa-lock', $summary['lockouts'] > 0 ? 'danger' : 'secondary'],
            ['Access denied', $summary['denied'], 'fa-ban', $summary['denied'] > 0 ? 'warning' : 'secondary'],
        ] as [$label, $count, $icon, $tone])
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="monitor-icon text-bg-{{ $tone }}"><i class="fa {{ $icon }}"></i></span>
                        <div>
                            <div class="fs-4 fw-semibold lh-1">{{ $count }}</div>
                            <div class="small text-body-secondary">{{ $label }} · last 24 h</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($failedSources->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-crosshairs text-danger"></i> Failed sign-ins this week, by source</h3></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light"><tr><th>IP address</th><th>Username tried</th><th class="text-end">Attempts</th><th>Last attempt</th></tr></thead>
                    <tbody>
                        @foreach ($failedSources as $source)
                            <tr>
                                <td><code>{{ $source->ip }}</code></td>
                                <td>{{ $source->username }}</td>
                                <td class="text-end fw-semibold">{{ $source->attempts }}</td>
                                <td>{{ \App\Support\YiiFormat::dateTime($source->last_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <x-card icon="fa fa-shield" title="All security events" flush>
        <x-grid id="security-event-grid" :grid="$grid" :columns="[
            ['name' => 'created_at', 'header' => 'Time', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->created_at?->toDateTimeString()), 'class' => 'text-nowrap'],
            ['name' => 'event', 'header' => 'Event', 'raw' => true, 'filter' => $events, 'value' => fn ($row) =>
                '<span class=\'badge text-bg-'.\App\Support\SecurityLog::severity($row->event).'\'>'.e(\App\Support\SecurityLog::title($row->event)).'</span>'],
            ['name' => 'causer_id', 'header' => 'User', 'value' => fn ($row) => $row->causer?->full_name ?? '-', 'filter' => $users],
            ['name' => 'detail', 'header' => 'Details', 'raw' => true, 'value' => fn ($row) => collect($row->properties?->except(['ip', 'agent'])->all() ?? [])
                ->map(fn ($value, $key) => '<span class=\'text-nowrap\'><span class=\'text-body-secondary\'>'.e(str_replace('_', ' ', $key)).'</span> '.e(is_array($value) ? implode(', ', array_map('strval', $value)) : (is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value)).'</span>')
                ->implode(' · ')],
            ['name' => 'ip', 'header' => 'IP / browser', 'raw' => true, 'value' => fn ($row) =>
                e($row->properties['ip'] ?? '').'<div class=\'small text-body-secondary\'>'.e(\App\Support\UserAgent::summary($row->properties['agent'] ?? '')).'</div>'],
        ]" />
    </x-card>
@endsection
