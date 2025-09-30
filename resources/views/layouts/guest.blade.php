<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sterke Digital') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex justify-center items-center">
            <div class="w-1/2 bg-gray-100 min-h-screen">
                
            </div>
            <div class="w-1/2 flex justify-center align-center">

                <div class="w-full sm:max-w-sm mt-6 px-6 py-4 bg-white">
                    <div class="flex justify-center mb-6">
                        <a href="{{ route('dashboard') }}">
                            <x-application-logo />
                        </a>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
