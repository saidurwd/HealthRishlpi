@extends('crud.admin', ['page' => ['route' => 'auditLog', 'plural' => 'Audit Log', 'singular' => 'Entry']])

@section('tools')
    <span class="small text-body-secondary me-2">Who created, changed or deleted which record, with old and new values</span>
@endsection

@section('grid')
    <x-grid id="audit-log-grid" :grid="$grid" :columns="[
        ['name' => 'created_at', 'header' => 'Time', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->created_at?->toDateTimeString()), 'class' => 'text-nowrap'],
        ['name' => 'causer_id', 'header' => 'User', 'value' => fn ($row) => $row->causer?->full_name ?? 'System', 'filter' => $users],
        ['name' => 'event', 'header' => 'Action', 'raw' => true, 'filter' => ['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted'], 'value' => fn ($row) =>
            '<span class=\'badge text-bg-'.match ($row->event) { 'created' => 'success', 'deleted' => 'danger', default => 'primary' }.'\'>'.e(ucfirst((string) $row->event)).'</span>'],
        ['name' => 'subject_type', 'header' => 'Record', 'raw' => true, 'filter' => $types, 'value' => fn ($row) =>
            e(\App\Models\Activity::typeLabel($row->subject_type)).' <span class=\'text-body-secondary\'>#'.e($row->subject_id).'</span>'],
        ['name' => 'log_name', 'header' => 'Kind', 'filter' => \App\Http\Controllers\AuditLogController::LOGS, 'value' => fn ($row) => \App\Http\Controllers\AuditLogController::LOGS[$row->log_name] ?? $row->log_name],
        ['header' => 'Changes', 'value' => fn ($row) => '<div class=\'audit-changes\'>'.$row->changesHtml().'</div>', 'raw' => true],
        ['header' => '', 'raw' => true, 'value' => fn ($row) => '<a href=\''.e(route('auditLog.view', $row->id)).'\' class=\'btn btn-sm btn-outline-secondary\' title=\'Details and history\'><i class=\'fa fa-history\'></i></a>'],
    ]" />
@endsection
