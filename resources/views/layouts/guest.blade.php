<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MatuNet') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-10 sm:px-6">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-50 via-slate-50 to-slate-100"></div>

            <div class="relative w-full max-w-md">
                <div class="mb-6 flex flex-col items-center">
                    <a href="{{ route('dashboard') }}" class="mb-4">
                        <x-application-logo class="h-10 w-auto" />
                    </a>
                    <h1 class="text-xl font-semibold text-slate-900">{{ config('app.name', 'MatuNet') }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Sign in to your account') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MatuNet') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </div>
    </body>
</html>
