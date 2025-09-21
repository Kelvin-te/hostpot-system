<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $package->name }} - Package Details</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white/10 backdrop-blur-md border-b border-white/20">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center justify-between">
                    <a href="{{ route('portal.index') }}" class="text-white hover:text-white/80 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Packages
                    </a>
                    <h1 class="text-2xl font-bold text-white">Package Details</h1>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 py-8">
            <div class="max-w-2xl mx-auto px-4">
                <!-- Package Card -->
                <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
                    <!-- Package Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-8 text-white text-center">
                        <h2 class="text-3xl font-bold mb-2">{{ $package->name }}</h2>
                        <div class="text-5xl font-bold mb-2">
                            {{ config('app.currency') }}{{ number_format($package->price, 2) }}
                        </div>
                        <p class="text-blue-100">Premium Internet Package</p>
                    </div>

                    <!-- Package Features -->
                    <div class="p-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-6">Package Features</h3>
                        
                        <div class="space-y-4">
                            @if($package->bandwidth_upload && $package->bandwidth_download)
                                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                                    <div class="bg-green-100 p-2 rounded-full mr-4">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Internet Speed</h4>
                                        <p class="text-gray-600">{{ $package->bandwidth_upload }} Mbps Upload / {{ $package->bandwidth_download }} Mbps Download</p>
                                    </div>
                                </div>
                            @endif

                            @if($package->session_timeout)
                                <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                                    <div class="bg-blue-100 p-2 rounded-full mr-4">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Session Duration</h4>
                                        <p class="text-gray-600">{{ $package->session_timeout }} hours of continuous usage</p>
                                    </div>
                                </div>
                            @endif

                            @if($package->validity_days)
                                <div class="flex items-center p-4 bg-purple-50 rounded-lg">
                                    <div class="bg-purple-100 p-2 rounded-full mr-4">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Package Validity</h4>
                                        <p class="text-gray-600">Valid for {{ $package->validity_days }} days from activation</p>
                                    </div>
                                </div>
                            @endif

                            @if($package->shared_users && $package->shared_users > 1)
                                <div class="flex items-center p-4 bg-orange-50 rounded-lg">
                                    <div class="bg-orange-100 p-2 rounded-full mr-4">
                                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Shared Access</h4>
                                        <p class="text-gray-600">Can be shared with up to {{ $package->shared_users }} users simultaneously</p>
                                    </div>
                                </div>
                            @endif

                            @if($package->idle_timeout)
                                <div class="flex items-center p-4 bg-red-50 rounded-lg">
                                    <div class="bg-red-100 p-2 rounded-full mr-4">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Idle Timeout</h4>
                                        <p class="text-gray-600">Disconnects after {{ $package->idle_timeout }} minutes of inactivity</p>
                                    </div>
                                </div>
                            @endif

                            @if($package->rate_limit)
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="bg-gray-100 p-2 rounded-full mr-4">
                                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Rate Limit</h4>
                                        <p class="text-gray-600">{{ $package->rate_limit }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Router Information -->
                        @if($router)
                            <div class="mt-8 p-4 bg-gray-100 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Router Information</h4>
                                <p class="text-gray-600">{{ $router->name }} ({{ $router->ip }})</p>
                                @if($router->location)
                                    <p class="text-gray-500 text-sm">Location: {{ $router->location }}</p>
                                @endif
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="mt-8 space-y-4">
                            <form action="{{ route('portal.purchase', $package) }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition-all duration-200 transform hover:scale-105">
                                    Purchase This Package
                                </button>
                            </form>
                            
                            <a href="{{ route('portal.index') }}" 
                               class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 text-center block">
                                Choose Different Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white/10 backdrop-blur-md border-t border-white/20 py-6">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <p class="text-white/80 text-sm">
                    Need help? Contact support for assistance with your internet package.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
