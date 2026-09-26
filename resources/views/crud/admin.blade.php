{{-- Shared "Manage" page; each module's admin.blade.php extends this and fills the grid section --}}
@extends('layouts.app')

@section('title', $page['plural'].' - '.config('app.name'))

@section('header')
    <x-page-header icon="fa fa-home" :title="$page['plural']" subtitle="Manage" :breadcrumbs="[$page['plural'] => route($page['route'].'.admin'), 'Manage']" />
@endsection

@section('content')
    <x-card icon="fa fa-home" :title="$page['plural']" flush>
        <x-slot:tools>
            <a href="{{ route($page['route'].'.create') }}" class="btn btn-sm btn-primary" title="New"><i class="fa fa-plus"></i> NEW</a>
        </x-slot:tools>
        @yield('grid')
    </x-card>
@endsection
