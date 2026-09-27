{{-- Shared "Manage" page; each module's admin.blade.php extends this and fills the grid section --}}
@extends('layouts.app')

@section('title', $page['plural'].' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-home" :title="$page['plural']" subtitle="Manage" :breadcrumbs="[$page['plural'] => route($page['route'].'.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-list" :title="$page['plural']" flush class="card-primary card-outline">
        <x-slot:tools>
            @yield('tools')
            @if (Route::has($page['route'].'.create'))
                <a href="{{ route($page['route'].'.create') }}" class="btn btn-sm btn-primary" title="New {{ $page['singular'] }}"><i class="fa fa-plus"></i> New {{ strtolower($page['singular']) }}</a>
            @endif
        </x-slot:tools>
        @yield('grid')
    </x-card>
@endsection
