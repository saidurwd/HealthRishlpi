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
</head>
<body class="bg-body-secondary">
    <nav class="navbar bg-body shadow-sm mb-4">
        <div class="container">
            <span class="navbar-brand">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" height="36">
            </span>
        </div>
    </nav>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>
