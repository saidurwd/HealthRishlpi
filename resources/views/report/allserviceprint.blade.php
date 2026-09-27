@extends('layouts.report')

@section('title', 'Patient Register Physiotherapy')
@section('print-delay', 5000)

@push('head')
    <style>body { font-size: 12px; } .table { font-size: 12px; }</style>
@endpush

@section('content')
    <x-report-header style="font-size:14px;" />
    <hr>
    <h3 class="text-start">All Services</h3>
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr><th>Parent</th><th>Service</th><th>Rate</th><th>Discount</th><th>Service Type</th><th>Grade</th></tr>
        </thead>
        <tbody>
            @forelse ($services as $service)
                <tr>
                    <td>{{ $service->parentRow?->title }}</td>
                    <td>{!! $service->fullPath() !!}</td>
                    <td>{{ $service->rate }}</td>
                    <td class="text-center">{{ $service->discount }}</td>
                    <td>{{ $service->service_type }}</td>
                    <td>{{ $grades[$service->service_grade] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No result found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
