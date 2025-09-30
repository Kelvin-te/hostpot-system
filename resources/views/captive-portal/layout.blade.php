<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @include('captive-portal.components.base-styles')
        /* Default text color */
        body { color: #6b7280; } /* Tailwind gray-500 */
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
