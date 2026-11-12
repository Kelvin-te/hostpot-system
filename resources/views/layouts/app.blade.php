<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'NetBill') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @rappasoftTableStyles
    @rappasoftTableThirdPartyStyles
</head>
<body class="font-sans antialiased bg-slate-100" x-data="{ sidebarOpen: false }">
<div class="min-h-screen">
    {{-- Sidebar: fixed on desktop, off-canvas on mobile --}}
    <div class="hidden lg:block">
        @include('layouts.sidebar2')
    </div>
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" x-transition.opacity>
        <div class="absolute inset-0 bg-black/50" @click="sidebarOpen = false"></div>
        <div class="absolute inset-y-0 left-0 w-64" @click="sidebarOpen = false">
            @include('layouts.sidebar2')
        </div>
    </div>

    <div class="lg:pl-64">
        @include('layouts.navigation')
        <main class="min-h-[calc(100vh-4rem)]">
            @isset($header)
                <header class="border-b border-slate-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset
            {{ $slot }}
        </main>
    </div>
</div>
@rappasoftTableScripts
@rappasoftTableThirdPartyScripts
@livewireScripts
@stack('scripts')
</body>
</html>
