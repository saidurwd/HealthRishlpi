@extends('layouts.app')

@section('title', 'Audit entry #'.$entry->id.' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-history" title="Audit Log" subtitle="Entry #{{ $entry->id }}" :breadcrumbs="['Audit Log' => route('auditLog.admin'), '#'.$entry->id]" />
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">{{ ucfirst((string) $entry->event) }} {{ \App\Models\Activity::typeLabel($entry->subject_type) }} #{{ $entry->subject_id }}</h3></div>
                <div class="card-body">
                    <dl class="patient-details mb-3">
                        <div><dt>Time</dt><dd>{{ \App\Support\YiiFormat::dateTime($entry->created_at?->toDateTimeString()) }}</dd></div>
                        <div><dt>User</dt><dd>{{ $entry->causer?->full_name ?? 'System' }}</dd></div>
                        <div><dt>Description</dt><dd>{{ $entry->description }}</dd></div>
                    </dl>
                    @php $changes = $entry->attribute_changes?->all() ?? []; @endphp
                    @if (($changes['attributes'] ?? []) !== [] || ($changes['old'] ?? []) !== [])
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
                            <tbody>
                                @foreach (array_keys(($changes['attributes'] ?? []) + ($changes['old'] ?? [])) as $field)
                                    <tr>
                                        <td class="fw-semibold">{{ $field }}</td>
                                        <td class="text-danger-emphasis">{{ is_scalar($changes['old'][$field] ?? null) ? $changes['old'][$field] : json_encode($changes['old'][$field] ?? null) }}</td>
                                        <td class="text-success-emphasis">{{ is_scalar($changes['attributes'][$field] ?? null) ? $changes['attributes'][$field] : json_encode($changes['attributes'][$field] ?? null) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if ($entry->properties?->isNotEmpty())
                        <div class="mt-3 small">{!! $entry->changesHtml() !!}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-history text-primary"></i> History of this record</h3></div>
                <ul class="list-group list-group-flush audit-timeline">
                    @forelse ($history as $item)
                        <li @class(['list-group-item', 'active-entry' => $item->id === $entry->id])>
                            <div class="d-flex justify-content-between gap-2">
                                <span><strong>{{ ucfirst((string) $item->event) }}</strong> by {{ $item->causer?->full_name ?? 'System' }}</span>
                                <a href="{{ route('auditLog.view', $item->id) }}" class="small text-body-secondary text-nowrap">{{ \App\Support\YiiFormat::dateTime($item->created_at?->toDateTimeString()) }}</a>
                            </div>
                            <div class="small audit-changes">{!! $item->changesHtml() !!}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">No other entries</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
