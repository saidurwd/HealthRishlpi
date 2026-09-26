<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-lte-color-mode="off">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error @yield('code') - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-body-secondary">
    <main class="container py-5 text-center">
        <h1 class="display-4"><i class="fa fa-times-circle text-danger"></i> Error @yield('code')</h1>
        <p class="lead mt-4">@yield('message')</p>
        <a href="{{ url('/dashboard/index') }}" class="btn btn-primary mt-3"><i class="fa fa-home"></i> Home</a>
    </main>
</body>
</html>
