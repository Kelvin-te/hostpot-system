<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}! 👋</h3>
                        <p class="text-blue-100 mt-1">{{ auth()->user()->isAdmin() ? 'Admin Dashboard Overview' : 'Your Account Overview' }}</p>
                    </div>
                    <div class="hidden md:block text-6xl opacity-50">📊</div>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
                <!-- Admin Stats -->
                @php
                    $todayRevenue = \App\Models\PaymentTransaction::whereDate('created_at', today())
                        ->where('status', 'completed')
                        ->sum('amount');
                    $totalRevenue = \App\Models\PaymentTransaction::where('status', 'completed')->sum('amount');
                    $activeSessions = \App\Models\HotspotSession::active()->count();
                    $totalUsers = \App\Models\User::count();
                    $totalPackages = \App\Models\Package::count();
                    $routers = \App\Models\Router::all();
                @endphp

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm text-gray-600">Today's Revenue</div>
                                <div class="text-3xl font-bold text-green-600">KES {{ number_format($todayRevenue, 0) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Last 24 hours</div>
                            </div>
                            <div class="text-4xl text-green-500 opacity-50">💰</div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm text-gray-600">Active Sessions</div>
                                <div class="text-3xl font-bold text-blue-600">{{ number_format($activeSessions) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Currently online</div>
                            </div>
                            <div class="text-4xl text-blue-500 opacity-50">🌐</div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm text-gray-600">Total Users</div>
                                <div class="text-3xl font-bold text-purple-600">{{ number_format($totalUsers) }}</div>
                                <div class="text-xs text-gray-500 mt-1">Registered users</div>
                            </div>
                            <div class="text-4xl text-purple-500 opacity-50">👥</div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-sm text-gray-600">Total Revenue</div>
                                <div class="text-3xl font-bold text-indigo-600">KES {{ number_format($totalRevenue, 0) }}</div>
                                <div class="text-xs text-gray-500 mt-1">All time</div>
                            </div>
                            <div class="text-4xl text-indigo-500 opacity-50">📈</div>
                        </div>
                    </div>
                </div>

                <!-- Router Status -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">🖧 Router Status</h3>
                            <a href="{{ route('router.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Manage Routers →</a>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($routers->count() > 0)
                            <div id="router-status-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($routers as $router)
                                    <div id="router-{{ $router->id }}" class="border rounded-lg p-4 border-gray-200 bg-gray-50">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">{{ $router->name }}</h4>
                                                <p class="text-xs text-gray-600">{{ $router->ip_address }}</p>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                ⏳ Checking...
                                            </span>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">
                                            Loading status...
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="text-4xl mb-3">🖧</div>
                                <p class="text-gray-500 mb-4">No routers configured</p>
                                <a href="{{ route('router.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                    Add Router
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Load router status asynchronously
                        fetch('{{ route('router.status.all') }}')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.routers) {
                                    Object.values(data.routers).forEach(router => {
                                        const container = document.getElementById('router-' + router.id);
                                        if (container) {
                                            const isOnline = router.online;
                                            container.className = 'border rounded-lg p-4 ' + (isOnline ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50');

                                            let detailsHtml = '';
                                            if (isOnline) {
                                                detailsHtml = '<div class="mt-3 space-y-1 text-xs">';
                                                if (router.data && router.data[0]) {
                                                    const sysInfo = router.data[0];
                                                    detailsHtml += `
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Version:</span>
                                                            <span class="font-medium text-gray-900">${sysInfo['version'] || 'N/A'}</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Uptime:</span>
                                                            <span class="font-medium text-gray-900">${sysInfo['uptime'] || 'N/A'}</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">CPU Load:</span>
                                                            <span class="font-medium text-gray-900">${sysInfo['cpu-load'] || '0'}%</span>
                                                        </div>
                                                    `;
                                                } else {
                                                    detailsHtml += '<div class="text-green-700 font-medium">✓ Connected</div>';
                                                }
                                                if (router.diagnostics) {
                                                    detailsHtml += `
                                                        <div class="flex justify-between pt-1 border-t border-green-200">
                                                            <span class="text-gray-600">Ping:</span>
                                                            <span class="font-medium text-gray-900">${router.diagnostics.ping || 'N/A'}</span>
                                                        </div>
                                                    `;
                                                }
                                                detailsHtml += '</div>';
                                            } else {
                                                detailsHtml = `
                                                    <div class="mt-2 text-xs text-red-700">
                                                        ${router.message}
                                                    </div>
                                                `;
                                                if (router.diagnostics) {
                                                    detailsHtml += `
                                                        <div class="mt-2 pt-2 border-t border-red-200 text-xs space-y-1">
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Ping:</span>
                                                                <span class="font-medium">${router.diagnostics.ping || 'N/A'}</span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-gray-600">Port 8728:</span>
                                                                <span class="font-medium">${router.diagnostics.port || 'N/A'}</span>
                                                            </div>
                                                        </div>
                                                    `;
                                                }
                                            }

                                            container.innerHTML = `
                                                <div class="flex justify-between items-start mb-2">
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900">${router.name}</h4>
                                                        <p class="text-xs text-gray-600">${router.ip_address}</p>
                                                    </div>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                        ${isOnline ? '🟢 Online' : '🔴 Offline'}
                                                    </span>
                                                </div>
                                                ${detailsHtml}
                                            `;
                                        }
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Failed to load router status:', error);
                                document.getElementById('router-status-container').innerHTML = `
                                    <div class="col-span-full text-center py-4 text-red-600">
                                        Failed to load router status. <a href="{{ route('router.index') }}" class="text-indigo-600 hover:underline">Check routers manually →</a>
                                    </div>
                                `;
                            });
                    });
                </script>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <a href="{{ route('packages.index') }}" class="flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="text-sm font-semibold text-gray-900">Manage Packages</div>
                            </a>
                            <a href="{{ route('sessions.index') }}" class="flex flex-col items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition">
                                <div class="text-3xl mb-2">🌐</div>
                                <div class="text-sm font-semibold text-gray-900">View Sessions</div>
                            </a>
                            <a href="{{ route('reports.index') }}" class="flex flex-col items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                                <div class="text-3xl mb-2">📊</div>
                                <div class="text-sm font-semibold text-gray-900">Reports</div>
                            </a>
                            <a href="{{ route('users.index') }}" class="flex flex-col items-center p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition">
                                <div class="text-3xl mb-2">👥</div>
                                <div class="text-sm font-semibold text-gray-900">Manage Users</div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Transactions -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
                                <a href="{{ route('payment.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                            </div>
                        </div>
                        <div class="p-6">
                            @php
                                $recentTransactions = \App\Models\PaymentTransaction::latest()->take(5)->get();
                            @endphp
                            @if($recentTransactions->count() > 0)
                                <div class="space-y-3">
                                    @foreach($recentTransactions as $transaction)
                                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $transaction->phone_number }}</div>
                                                <div class="text-xs text-gray-500">{{ $transaction->created_at->format('M d, H:i') }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-gray-900">KES {{ number_format($transaction->amount, 0) }}</div>
                                                @if($transaction->status === 'completed')
                                                    <span class="text-xs text-green-600">✓ Paid</span>
                                                @else
                                                    <span class="text-xs text-yellow-600">Pending</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4">No recent transactions</p>
                            @endif
                        </div>
                    </div>

                    <!-- Active Sessions -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900">Active Sessions</h3>
                                <a href="{{ route('sessions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                            </div>
                        </div>
                        <div class="p-6">
                            @php
                                $recentSessions = \App\Models\HotspotSession::active()->latest()->take(5)->get();
                            @endphp
                            @if($recentSessions->count() > 0)
                                <div class="space-y-3">
                                    @foreach($recentSessions as $session)
                                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $session->package->name ?? 'Package' }}</div>
                                                <div class="text-xs text-gray-500">{{ $session->username }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm text-gray-900">{{ number_format($session->bytes_total / (1024 * 1024), 0) }} MB</div>
                                                <span class="text-xs text-green-600">Active</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4">No active sessions</p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <!-- Regular User - Show Personal Stats -->
                @include('user.dashboard')
            @endif

        </div>
    </div>
</x-app-layout>
