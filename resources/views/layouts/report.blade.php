<!DOCTYPE html>
{{-- Printable pages (Yii's layouts/report): no chrome, prints itself on load --}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        body { background: #fff; color: #000; padding: 1rem; }
        .report-header img { max-width: 140px; }
        @media print { body { padding: 0; } }
    </style>
    @stack('head')
</head>
<body>
    <div id="content">
        @yield('content')
    </div>
    <script>
        // The legacy pages printed at once or after a 5 second pause
        setTimeout(() => window.print(), @yield('print-delay', 0));
    </script>
</body>
</html>
