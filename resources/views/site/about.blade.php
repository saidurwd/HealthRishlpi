@extends('layouts.app')

@section('title', 'About - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-info-circle" title="About" :breadcrumbs="['About']" />
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body text-center py-4">
                    <img src="{{ asset('images/rishilpi_logo.png') }}" alt="" class="mb-3" style="max-height:4rem;max-width:100%">
                    <h2 class="h4 mb-1">{{ config('app.name') }}</h2>
                    <div class="text-body-secondary mb-2">{{ config('legacy.adminName') }}</div>
                    <span class="badge text-bg-primary fs-6">Version {{ $version }}</span>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach (array_filter([
                        'Build' => $release['commit'] ? substr($release['commit'], 0, 7) : null,
                        'Built' => $release['built_at'],
                        'Environment' => app()->environment(),
                        'Framework' => 'Laravel '.app()->version(),
                        'PHP' => PHP_VERSION,
                        'Database' => $database,
                        'Interface' => 'AdminLTE 4 · Bootstrap 5',
                    ]) as $label => $value)
                        <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">{{ $label }}</span><span>{{ $value }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="card">
                <div class="card-body small">
                    Developed by <a href="http://www.optimosolution.com" target="_blank" rel="noopener">Optimo Solution</a>
                    for {{ config('legacy.adminName') }}. Patient and billing data are private: use them only for care
                    and administration, and sign out when you leave the computer.
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fa fa-star text-warning"></i> What's new</h3></div>
                <div class="card-body changelog">{!! $changes !!}</div>
            </div>
        </div>
    </div>
@endsection
