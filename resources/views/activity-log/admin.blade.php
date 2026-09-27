@extends('crud.admin')

@section('grid')
    <x-grid id="activity-log-grid" route="activityLog" :grid="$grid" :columns="[
        ['name' => 'created_at', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->created_at?->toDateTimeString())],
        ['name' => 'causer_id', 'value' => fn ($row) => $row->causer?->full_name, 'filter' => $users],
        ['name' => 'log_name', 'filter' => ['data' => 'Data', 'access' => 'Access']],
        ['name' => 'event', 'filter' => ['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted']],
        ['name' => 'subject_type', 'value' => fn ($row) => \App\Models\Activity::typeLabel($row->subject_type), 'filter' => $types],
        ['name' => 'subject_id'],
        ['name' => 'description'],
        ['header' => 'Changes', 'value' => fn ($row) => $row->changesHtml(), 'raw' => true],
    ]" />
@endsection
