@extends('layouts.app')

@section('title', 'System Health - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-heartbeat" title="System Health" subtitle="Monitor" :breadcrumbs="['System Health']" />
@endsection

@php
    $look = [
        'ok' => ['success', 'fa-check-circle', 'OK'],
        'info' => ['secondary', 'fa-info-circle', 'Info'],
        'warning' => ['warning', 'fa-exclamation-triangle', 'Attention'],
        'error' => ['danger', 'fa-times-circle', 'Problem'],
    ];
    $summary = ['ok' => 'Everything is working.', 'info' => 'Everything is working.', 'warning' => 'Working, but something needs attention.', 'error' => 'Something is wrong and needs fixing.'];
@endphp

@section('content')
    <div class="alert alert-{{ $look[$overall][0] }} d-flex align-items-center gap-3">
        <i class="fa {{ $look[$overall][1] }} fa-2x"></i>
        <div class="me-auto">
            <div class="fw-semibold">{{ $summary[$overall] }}</div>
            <div class="small">Checked {{ now()->format('M j, Y g:i:s A') }}</div>
        </div>
        <a href="{{ route('systemHealth.admin') }}" class="btn btn-sm btn-outline-dark"><i class="fa fa-refresh"></i> Check again</a>
    </div>

    <div class="row g-3">
        @foreach ($checks as $group => $items)
            @php $worst = \App\Support\SystemHealth::overall([$items]); @endphp
            <div class="col-lg-6">
                <div class="card h-100 health-card border-{{ $look[$worst][0] }}">
                    <div class="card-header d-flex align-items-center">
                        <h3 class="card-title mb-0">{{ $group }}</h3>
                        <span class="badge text-bg-{{ $look[$worst][0] }} ms-auto">{{ $look[$worst][2] }}</span>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($items as $item)
                            <li class="list-group-item d-flex gap-2 align-items-start">
                                <i class="fa {{ $look[$item['status']][1] }} text-{{ $look[$item['status']][0] }} mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span>{{ $item['label'] }}</span>
                                        <span class="text-body-secondary text-end text-break">{{ $item['value'] }}</span>
                                    </div>
                                    @if (($item['hint'] ?? null) && in_array($item['status'], ['warning', 'error'], true))
                                        <div class="small text-{{ $look[$item['status']][0] }}-emphasis">{{ $item['hint'] }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
@endsection
