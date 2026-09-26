<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-lte-color-mode="off">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" title="Collapse Menu"><i class="fa fa-bars"></i></a>
                </li>
                <li class="nav-item dropdown d-none d-md-block">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fa fa-plus"></i> ADD</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url('/user/create') }}">+ USER</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-lte-toggle="fullscreen" title="Full Screen">
                        <i data-lte-icon="maximize" class="fa fa-expand"></i>
                        <i data-lte-icon="minimize" class="fa fa-compress" style="display: none"></i>
                    </a>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="{{ auth()->user()->photoUrl() }}" class="user-image rounded-circle shadow" alt="Picture">
                        <span class="d-none d-md-inline">{{ auth()->user()->full_name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="{{ auth()->user()->photoUrl() }}" class="rounded-circle shadow" alt="Picture">
                            <p>
                                {{ auth()->user()->full_name }}
                                <small>{{ auth()->user()->email }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <a href="{{ url('/user/view/'.auth()->id()) }}" class="btn btn-default btn-flat"><i class="fa fa-user"></i> My Profile</a>
                            <form method="post" action="{{ route('site.logout') }}" class="float-end">
                                @csrf
                                <button type="submit" class="btn btn-default btn-flat"><i class="fa fa-sign-out"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ url('/dashboard/index') }}" class="brand-link">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" title="{{ config('app.name') }}" class="brand-image">
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Main navigation" data-accordion="false">
                    @foreach ($menu as $item)
                        <x-menu-item :item="$item" />
                    @endforeach
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                @yield('header')
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <x-flash />
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">
            <i>Last account activity <i class="fa fa-clock-o"></i> <strong>{{ now()->format('M j Y, g:i:s A') }}</strong></i>
        </div>
        Copyright &copy; {{ config('app.name') }} {{ date('Y') }}. Developed by <a href="http://www.optimosolution.com" target="_blank" rel="noopener">Optimo Solution</a>
    </footer>
</div>
@stack('scripts')
</body>
</html>
