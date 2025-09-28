<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @include('captive-portal.components.base-styles')
        @stack('styles')
    </style>
</head>
<body class="font-sans">
    <div class="container">
        <div class="content">
            @hasSection('logo')
                @yield('logo')
            @else
                @include('captive-portal.components.logo')
            @endif

            @yield('content')
        </div>
    </div>

    @hasSection('after')
        @yield('after')
    @endif

    @stack('scripts')
</body>
</html>
