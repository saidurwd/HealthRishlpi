{{--
    Dashboard (DashboardController): today at a glance, then the chosen
    period against the one before. Sections follow the user's permissions.
    Charts: resources/js/dashboard.js.
--}}
@extends('layouts.app')

@php
    use App\Support\DashboardStats;
    use App\Support\YiiFormat;

    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $change = function (?float $percent): string {
        if ($percent === null) {
            return '<span class="change none">no earlier data</span>';
        }
        $class = $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'none');
        $arrow = $percent > 0 ? 'fa-arrow-up' : ($percent < 0 ? 'fa-arrow-down' : 'fa-minus');

        return '<span class="change '.$class.'"><i class="fa '.$arrow.'"></i> '.abs($percent).'%</span> <span class="small text-body-secondary">vs previous</span>';
    };
    $rank = function (array $rows, string $tone, callable $format) {
        $max = max(array_column($rows, 'value') ?: [1]) ?: 1;

        return collect($rows)->map(fn ($row) => '<li><div class="d-flex justify-content-between small"><span class="text-truncate me-2">'.e($row['label']).'</span><span class="fw-semibold">'.e($format($row)).'</span></div>'
            .'<div class="progress mt-1"><div class="progress-bar bg-'.$tone.'" style="width:'.round($row['value'] / $max * 100).'%"></div></div></li>')->implode('');
    };
    $periodLabel = DashboardStats::PERIODS[$period];
@endphp

@section('title', 'Dashboard - '.config('app.name'))

@section('header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="me-auto">
            <h3 class="mb-0">{{ $greeting }}, {{ strtok((string) $user->full_name, ' ') }}</h3>
            <div class="text-body-secondary small">{{ now()->format('l, j F Y') }} · {{ config('legacy.adminName') }}</div>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Period">
            @foreach (DashboardStats::PERIODS as $key => $label)
                <a href="{{ route('dashboard.index', ['period' => $key]) }}" @class(['btn', 'btn-primary' => $key === $period, 'btn-outline-primary' => $key !== $period])>{{ $label }}</a>
            @endforeach
        </div>
        @if ($can['money'])
            <a href="{{ route('dashboard.export') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-download"></i> Export</a>
        @endif
    </div>
@endsection

@section('content')
    <div class="dashboard">
        {{-- Today --}}
        <div class="row">
            @if ($can['patients'])
                <div class="col-lg-3 col-6">
                    <div class="small-box text-bg-primary">
                        <div class="inner">
                            <h3>{{ number_format($today['new_patients']) }}</h3>
                            <p>New patients today</p>
                        </div>
                        <i class="small-box-icon fa fa-user-plus"></i>
                        <a href="{{ route('patient.create') }}" class="small-box-footer link-light link-underline-opacity-0">Register a patient <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box text-bg-info">
                        <div class="inner">
                            <h3>{{ number_format($today['prescriptions']) }}</h3>
                            <p>Consultations today</p>
                        </div>
                        <i class="small-box-icon fa fa-stethoscope"></i>
                        <a href="{{ route('patient.admin') }}" class="small-box-footer link-light link-underline-opacity-0">Find a patient <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            @endif
            @if ($can['money'])
                <div class="col-lg-3 col-6">
                    <div class="small-box text-bg-success">
                        <div class="inner">
                            <h3>{{ YiiFormat::currencyRound($today['sales']) }}</h3>
                            <p>Approved sales today · {{ $today['invoices'] }} invoice(s)</p>
                        </div>
                        <i class="small-box-icon fa fa-coins"></i>
                        <a href="{{ route('invoice.create') }}" class="small-box-footer link-light link-underline-opacity-0">New invoice <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div @class(['small-box', 'text-bg-warning' => $today['pending'] > 0, 'text-bg-secondary' => $today['pending'] === 0])>
                        <div class="inner">
                            <h3>{{ number_format($today['pending']) }}</h3>
                            <p>Invoices waiting for approval · {{ YiiFormat::currencyRound($today['pending_amount']) }}</p>
                        </div>
                        <i class="small-box-icon fa fa-hourglass-half"></i>
                        <a href="{{ route('invoice.admin', ['InvoiceParent[status]' => 0]) }}" class="small-box-footer link-dark link-underline-opacity-0">Review them <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            @endif
        </div>

        {{-- The chosen period --}}
        <div class="row">
            @if ($can['money'])
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-success shadow-sm"><i class="fa fa-sack-dollar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Revenue · {{ $periodLabel }}</span>
                            <span class="info-box-number">{{ YiiFormat::currencyRound($summary['revenue']['value']) }}</span>
                            <span>{!! $change($summary['revenue']['change']) !!}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-primary shadow-sm"><i class="fa fa-receipt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Average invoice · {{ number_format($summary['invoices']['value']) }} approved</span>
                            <span class="info-box-number">{{ YiiFormat::currencyRound($summary['average']['value']) }}</span>
                            <span>{!! $change($summary['average']['change']) !!}</span>
                        </div>
                    </div>
                </div>
            @endif
            @if ($can['patients'])
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-info shadow-sm"><i class="fa fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">New patients · {{ $periodLabel }}</span>
                            <span class="info-box-number">{{ number_format($summary['patients']['value']) }}</span>
                            <span>{!! $change($summary['patients']['change']) !!}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-warning shadow-sm"><i class="fa fa-notes-medical"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Consultations · {{ $periodLabel }}</span>
                            <span class="info-box-number">{{ number_format($summary['visits']['value']) }}</span>
                            <span>{!! $change($summary['visits']['change']) !!}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if ($can['money'] || $can['patients'])
            <div class="row">
                <div @class(['col-lg-8' => $can['money'], 'col-12' => ! $can['money']])>
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title">{{ $can['money'] ? 'Revenue and consultations' : 'Consultations' }}</h3>
                            <div class="card-tools small text-body-secondary">{{ $periodLabel }}{{ count($trend['labels']) > 31 || $period === 'year' ? ' · per month' : ' · per day' }}</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-box"><canvas id="dashboard-trend" aria-label="Revenue and consultations"></canvas></div>
                        </div>
                    </div>
                </div>
                @if ($can['money'])
                    <div class="col-lg-4">
                        <div class="card card-success card-outline mb-4">
                            <div class="card-header"><h3 class="card-title">Sales mix</h3><div class="card-tools small text-body-secondary">{{ $periodLabel }}</div></div>
                            <div class="card-body">
                                @if (array_sum($salesMix) > 0)
                                    <div class="chart-box" style="height:12rem"><canvas id="dashboard-mix" aria-label="Sales mix"></canvas></div>
                                    <ul class="list-unstyled mt-3 mb-0 small">
                                        @foreach ($salesMix as $type => $amount)
                                            <li class="d-flex justify-content-between"><span>{{ $type ?: 'Other' }}</span><span class="fw-semibold">{{ YiiFormat::currencyRound($amount) }} ({{ round($amount / max(array_sum($salesMix), 1) * 100) }}%)</span></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-body-secondary mb-0">No approved sales in this period.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="row">
            @if ($can['money'])
                <div @class(['col-lg-7' => $can['stock'], 'col-12' => ! $can['stock']])>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Latest invoices</h3>
                            <div class="card-tools"><a href="{{ route('invoice.admin') }}" class="btn btn-sm btn-tool">All invoices <i class="fa fa-arrow-right"></i></a></div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th>Invoice</th><th>Patient</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @forelse ($recentInvoices as $invoice)
                                            <tr>
                                                <td><a href="{{ route('invoice.view', $invoice->id) }}" class="fw-semibold">{{ $invoice->invoice_number }}</a><div class="small text-body-secondary">{{ YiiFormat::dateTime($invoice->invoice_date) }}</div></td>
                                                <td>@if ($invoice->patient_id)<a href="{{ route('patient.view', $invoice->patient_id) }}">{{ $invoice->name }}</a>@endif<div class="small text-body-secondary">{{ $invoice->pat_id }}</div></td>
                                                <td class="text-end">{{ YiiFormat::currency($invoice->total_amount) }}</td>
                                                <td>
                                                    <span @class(['badge', 'text-bg-success' => (int) $invoice->status === 1, 'text-bg-primary' => (int) $invoice->status !== 1])>{{ (int) $invoice->status === 1 ? 'Approved' : 'Pending' }}</span>
                                                    <span @class(['badge', 'text-bg-light border' => $invoice->payment_status !== 'Paid', 'text-bg-success' => $invoice->payment_status === 'Paid'])>{{ $invoice->payment_status ?: 'Unpaid' }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-body-secondary py-3">No invoices yet</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($can['stock'])
                <div @class(['col-lg-5' => $can['money'], 'col-12' => ! $can['money']])>
                    <div class="card card-danger card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Stock expiring within 90 days <span @class(['badge ms-1', 'text-bg-danger' => $expiring['count'] > 0, 'text-bg-success' => $expiring['count'] === 0])>{{ $expiring['count'] }}</span></h3>
                            <div class="card-tools"><a href="{{ route('report.expiration') }}" class="btn btn-sm btn-tool">Report <i class="fa fa-arrow-right"></i></a></div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light"><tr><th>Product · batch</th><th>Expiry</th><th class="text-end">On hand</th></tr></thead>
                                    <tbody>
                                        @forelse ($expiring['rows'] as $row)
                                            @php $expired = strtotime($row->expiry) < strtotime('today'); @endphp
                                            <tr>
                                                <td class="text-wrap">{{ $row->product }} <span class="small text-body-secondary">· {{ $row->batch }}</span></td>
                                                <td><span @class(['badge', 'text-bg-danger' => $expired, 'text-bg-warning' => ! $expired])>{{ $expired ? 'Expired ' : '' }}{{ YiiFormat::date($row->expiry) }}</span></td>
                                                <td class="text-end">{{ YiiFormat::number($row->quantity, 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-body-secondary py-3"><i class="fa fa-check-circle text-success"></i> Nothing expires in the next 90 days</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row">
            @if ($can['patients'])
                <div class="col-lg-3 col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header"><h3 class="card-title">Top diagnoses</h3></div>
                        <div class="card-body">
                            @if ($diagnoses === [])<p class="text-body-secondary mb-0">No consultations in this period.</p>@endif
                            <ul class="list-unstyled rank-list mb-0">{!! $rank($diagnoses, 'warning', fn ($row) => number_format($row['value'])) !!}</ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header"><h3 class="card-title">New patients by category</h3></div>
                        <div class="card-body">
                            @if ($categories === [])<p class="text-body-secondary mb-0">No new patients in this period.</p>@endif
                            <ul class="list-unstyled rank-list mb-0">{!! $rank($categories, 'info', fn ($row) => number_format($row['value'])) !!}</ul>
                        </div>
                    </div>
                </div>
            @endif
            @if ($can['money'])
                <div class="col-lg-3 col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header"><h3 class="card-title">Top medicines</h3></div>
                        <div class="card-body">
                            @if ($topProducts === [])<p class="text-body-secondary mb-0">No medicine sales in this period.</p>@endif
                            <ul class="list-unstyled rank-list mb-0">{!! $rank($topProducts, 'success', fn ($row) => YiiFormat::currencyRound($row['value'])) !!}</ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header"><h3 class="card-title">Invoices by staff</h3></div>
                        <div class="card-body">
                            @if ($staff === [])<p class="text-body-secondary mb-0">No approved invoices in this period.</p>@endif
                            <ul class="list-unstyled rank-list mb-0">{!! $rank($staff, 'primary', fn ($row) => YiiFormat::currencyRound($row['value']).' · '.$row['count']) !!}</ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if (! $can['money'] && ! $can['patients'] && ! $can['stock'])
            <div class="card"><div class="card-body text-center py-5">
                <i class="fa fa-compass fa-2x text-primary mb-2"></i>
                <p class="mb-0">Welcome. Use the menu on the left to get started.</p>
            </div></div>
        @endif
    </div>

    <script type="application/json" id="dashboard-data">{!! json_encode([
        'currency' => (string) session('currency'),
        'money' => $can['money'],
        'trend' => $trend,
        'mix' => $salesMix,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
