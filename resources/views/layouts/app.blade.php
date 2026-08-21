<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sterke Digital') }}</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @rappasoftTableStyles
    @rappasoftTableThirdPartyStyles
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-slate-50">
    <div class="flex flex-col sm:flex-row w-full">
    @include('layouts.sidebar2')
        <main class="flex-1 flex flex-col overflow-hidden bg-indigo-50 md:ml-55">
            @include('layouts.navigation')
            <div class="overflow-y-scroll max-h-[calc(100vh-70px)]">
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
@rappasoftTableScripts
@rappasoftTableThirdPartyScripts
@livewireScripts
@stack('scripts')
</body>
</html>
