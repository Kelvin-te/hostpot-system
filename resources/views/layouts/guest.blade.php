<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MatuNet') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen">
            <!-- Left side - decorative (hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-gray-100 items-center justify-center">
                <div class="text-center p-8">
                    <h1 class="text-4xl font-bold text-gray-800 mb-4">Welcome Back</h1>
                    <p class="text-gray-600 text-lg">Sign in to access your dashboard</p>
                </div>
            </div>

            <!-- Right side - login form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8">
                <div class="w-full max-w-md">
                    <div class="flex justify-center mb-8">
                        <a href="{{ route('dashboard') }}">
                            <x-application-logo class="w-full max-w-[190px] h-auto" />
                        </a>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
