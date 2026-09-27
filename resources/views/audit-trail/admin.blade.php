@extends('crud.admin')

@section('tools')
    <span class="small text-body-secondary me-2">{{ $openSessions }} session(s) opened in the last 12 hours without signing out</span>
@endsection

@section('grid')
    <ul class="nav nav-tabs px-3 pt-2" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login-sessions" type="button"><i class="fa fa-sign-in"></i> Sessions</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#login-failures" type="button"><i class="fa fa-times-circle"></i> Failed attempts <span class="badge text-bg-secondary">{{ $failed->count() }}</span></button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="login-sessions">
            <x-grid id="audit-trail-grid" route="auditTrail" :grid="$grid" :columns="[
                ['name' => 'user_id', 'header' => 'User', 'value' => fn ($row) => '<a href=\''.e(route('user.view', $row->user_id)).'\'>'.e($row->user?->full_name).'</a>', 'raw' => true, 'filter' => $users],
                ['name' => 'login_time', 'header' => 'Signed in', 'value' => fn ($row) => \App\Support\YiiFormat::dateTime($row->login_time)],
                ['name' => 'logout_time', 'header' => 'Signed out', 'raw' => true, 'value' => fn ($row) => $row->logout_time
                    ? e(\App\Support\YiiFormat::dateTime($row->logout_time))
                    : '<span class=\'badge text-bg-warning\'>No sign-out</span>'],
                ['header' => 'Duration', 'value' => fn ($row) => \App\Models\AuditTrail::interval($row->login_time, $row->logout_time)],
                ['header' => 'Actions', 'buttons' => ['delete']],
            ]" />
        </div>
        <div class="tab-pane fade" id="login-failures">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Time</th><th>Username tried</th><th>Reason</th><th>IP / browser</th></tr></thead>
                    <tbody>
                        @forelse ($failed as $attempt)
                            <tr>
                                <td class="text-nowrap">{{ \App\Support\YiiFormat::dateTime($attempt->created_at?->toDateTimeString()) }}</td>
                                <td>{{ $attempt->properties['username'] ?? '' }}</td>
                                <td>
                                    @if ($attempt->event === 'login.lockout')
                                        <span class="badge text-bg-danger">Locked out</span>
                                    @else
                                        {{ $attempt->properties['reason'] ?? '' }}
                                    @endif
                                </td>
                                <td>{{ $attempt->properties['ip'] ?? '' }} <span class="small text-body-secondary">{{ \App\Support\UserAgent::summary($attempt->properties['agent'] ?? '') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-body-secondary py-3">No failed sign-ins recorded</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
