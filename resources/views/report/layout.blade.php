{{--
    Shared frame of the reports. On screen: page header, the filter form
    (section "filters", posted back to the same URL) with Search and Print,
    then the table and "Displaying N results." Printed: organisation header,
    the heading (section "heading") and the table.
--}}
@extends($print ? 'layouts.report' : 'layouts.app')

@section('title', $title)
@section('print-delay', 5000)

@push('head')
    @if ($print)
        <style>body { font-size: 10px; } .table { font-size: 10px; }</style>
    @endif
@endpush

@section('header')
    <x-page-header icon="fa fa-bar-chart" title="Reports" :subtitle="$title" :breadcrumbs="['Reports' => route('report.'.$report), $title]" />
@endsection

@section('content')
    @if ($print)
        <x-report-header />
        <hr>
        <h3 class="text-start">@yield('heading')</h3>
        @yield('table')
    @else
        <x-card icon="fa fa-bar-chart" :title="$title">
            <form method="post" action="{{ route('report.'.$report) }}" class="row g-2 align-items-start mb-3">
                @csrf
                @yield('filters')
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-search"></i> Search</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('report.'.$report.'print', $printQuery) }}" target="_blank" class="btn btn-info btn-sm w-100"><i class="fa fa-print"></i> Print</a>
                </div>
            </form>
            <div class="table-responsive">
                @yield('table')
            </div>
            @hasSection('count')
                <div class="mt-2">Displaying @yield('count') results.</div>
            @endif
        </x-card>
    @endif
@endsection
