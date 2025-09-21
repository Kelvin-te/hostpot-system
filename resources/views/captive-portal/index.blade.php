<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $routerName }} - Internet Packages</title>
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
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-white mb-2">Welcome to {{ $routerName }}</h1>
                    <p class="text-white/80">Choose your internet package to get connected</p>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 py-8">
            <div class="max-w-4xl mx-auto px-4">
                @if($packages->count() > 0)
                    <!-- Packages Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($packages as $package)
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                <!-- Package Header -->
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                                    <h3 class="text-xl font-bold mb-2">{{ $package->name }}</h3>
                                    <div class="text-3xl font-bold">
                                        {{ config('app.currency') }}{{ number_format($package->price, 2) }}
                                    </div>
                                </div>

                                <!-- Package Details -->
                                <div class="p-6">
                                    <div class="space-y-3">
                                        @if($package->bandwidth_upload && $package->bandwidth_download)
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                <span>Speed: {{ $package->bandwidth_upload }}/{{ $package->bandwidth_download }} Mbps</span>
                                            </div>
                                        @endif

                                        @if($package->session_timeout)
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Session: {{ $package->session_timeout }} hours</span>
                                            </div>
                                        @endif

                                        @if($package->validity_days)
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span>Valid for: {{ $package->validity_days }} days</span>
                                            </div>
                                        @endif

                                        @if($package->shared_users && $package->shared_users > 1)
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                <span>Shared: {{ $package->shared_users }} users</span>
                                            </div>
                                        @endif

                                        @if($package->idle_timeout)
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Idle timeout: {{ $package->idle_timeout }} min</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="mt-6 space-y-3">
                                        <a href="{{ route('portal.package', $package) }}" 
                                           class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 text-center block">
                                            View Details
                                        </a>
                                        <button onclick="selectPackage({{ $package->id }})" 
                                                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                                            Select Package
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- No Packages Available -->
                    <div class="text-center py-12">
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-8">
                            <svg class="w-16 h-16 mx-auto text-white/60 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-white mb-2">No Packages Available</h3>
                            <p class="text-white/80">Please contact the administrator for available internet packages.</p>
                        </div>
                    </div>
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white/10 backdrop-blur-md border-t border-white/20 py-6">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <p class="text-white/80 text-sm">
                    Need help? Contact support or visit our website for more information.
                </p>
                @if($router)
                    <p class="text-white/60 text-xs mt-2">
                        Connected through: {{ $router->name }} ({{ $router->ip }})
                    </p>
                @endif
            </div>
        </footer>
    </div>

    <!-- JavaScript for package selection -->
    <script>
        function selectPackage(packageId) {
            // This would typically redirect to payment or voucher activation
            // For now, we'll show an alert
            if (confirm('Are you sure you want to select this package?')) {
                window.location.href = `/portal/package/${packageId}`;
            }
        }

        // Auto-refresh packages every 30 seconds (optional)
        setInterval(function() {
            // You could implement AJAX refresh here if needed
        }, 30000);
    </script>
</body>
</html>
