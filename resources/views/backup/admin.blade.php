@extends('layouts.app')

@section('title', 'Database Backup')

@section('header')
    <x-page-header icon="fa fa-database" title="Database Backup" subtitle="Manage" :breadcrumbs="['Database Backup' => route('backup.admin'), 'Manage']" />
@endsection

@section('content')
    <div class="row text-center mb-3">
        <div class="col-6 col-md-3"><div class="border rounded p-2 bg-body"><h3 class="mb-0">{{ $stats['total_backups'] }}</h3><small>Total Backups</small></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2 bg-body"><h3 class="mb-0 text-success">{{ $stats['success_count'] }}</h3><small>Successful</small></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2 bg-body"><h3 class="mb-0 text-danger">{{ $stats['failed_count'] }}</h3><small>Failed</small></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2 bg-body"><h3 class="mb-0 text-warning">{{ \App\Models\Backup::formatBytes($stats['total_size']) }}</h3><small>Storage Used</small></div></div>
    </div>
    <x-card icon="fa fa-database" title="Database Backups" flush>
        <x-slot:tools>
            @foreach (['gzip' => ['fa fa-download', 'EXPORT (GZIP)', 'Export database with GZIP compression (recommended)'], 'zip' => ['fa fa-file-archive', 'EXPORT (ZIP)', 'Export database as ZIP archive'], 'sql' => ['fa fa-file-code', 'EXPORT (SQL)', 'Export database as plain SQL (uncompressed)']] as $type => [$icon, $label, $hint])
                <form method="post" action="{{ route('backup.exportdatabase') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <button type="submit" class="btn btn-sm btn-primary" title="{{ $hint }}"><i class="{{ $icon }}"></i> {{ $label }}</button>
                </form>
            @endforeach
            <form method="post" action="{{ route('backup.cleanup') }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete backups older than {{ \App\Http\Controllers\BackupController::RETENTION_DAYS }} days?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning" title="Delete backups older than {{ \App\Http\Controllers\BackupController::RETENTION_DAYS }} days"><i class="fa fa-trash"></i> CLEANUP OLD</button>
            </form>
        </x-slot:tools>
        <x-grid id="backup-grid" :grid="$grid" :columns="[
            ['name' => 'created_on', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->created_on), 'filter' => false],
            ['name' => 'attachment', 'value' => fn ($row) => e($row->attachment).'<br><small class=\'text-body-secondary\'>'.e(\App\Models\Backup::formatBytes($row->file_size)).'</small>', 'raw' => true, 'filter' => false],
            ['header' => 'Type', 'value' => fn ($row) => strtoupper((string) $row->type)],
            ['header' => 'Status', 'value' => fn ($row) => $row->status === \App\Models\Backup::STATUS_SUCCESS ? '<span class=\'badge text-bg-success\'>Success</span>' : '<span class=\'badge text-bg-danger\'>Failed</span>', 'raw' => true],
            ['header' => 'Duration', 'value' => fn ($row) => $row->duration],
            ['header' => 'Tables', 'value' => fn ($row) => $row->tables_count],
            ['header' => 'Checksum', 'value' => fn ($row) => $row->checksum],
            ['name' => 'created_by', 'value' => fn ($row) => $row->createdBy?->full_name, 'filter' => false],
            ['header' => 'Actions', 'buttons' => [
                fn ($row) => '<form method=\'post\' action=\''.e(route('backup.restore', $row->id)).'\' class=\'d-inline\' onsubmit=\'return confirm(&quot;WARNING: Restoring will overwrite the current database. Are you absolutely sure?&quot;)\'><input type=\'hidden\' name=\'_token\' value=\''.csrf_token().'\'><button type=\'submit\' class=\'btn btn-sm btn-success\' title=\'Restore this backup\'><i class=\'fa fa-undo\'></i></button></form>',
                fn ($row) => '<a href=\''.e(route('backup.download', $row->id)).'\' class=\'btn btn-sm btn-info\' title=\'Download\'><i class=\'fa fa-download\'></i></a>',
                fn ($row) => '<a href=\''.e(route('backup.delete', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-times\'></i></a>',
            ]],
        ]" />
    </x-card>
@endsection
