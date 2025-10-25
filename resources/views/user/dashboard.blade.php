<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold">Welcome back, {{ auth()->user()->name }}! 👋</h3>
                        <p class="text-indigo-100 mt-1">Manage your hotspot sessions and purchases</p>
                    </div>
                    <div class="hidden md:block text-6xl opacity-50">🌐</div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <div class="text-sm text-gray-600">Total Spent</div>
                            <div class="text-3xl font-bold text-green-600">KES {{ number_format($totalSpent, 0) }}</div>
                            <div class="text-xs text-gray-500 mt-1">All time</div>
                        </div>
                        <div class="text-4xl text-green-500 opacity-50">💰</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <div class="text-sm text-gray-600">Total Sessions</div>
                            <div class="text-3xl font-bold text-blue-600">{{ number_format($totalSessions) }}</div>
                            <div class="text-xs text-gray-500 mt-1">All time</div>
                        </div>
                        <div class="text-4xl text-blue-500 opacity-50">🌐</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <div class="text-sm text-gray-600">Data Used</div>
                            <div class="text-3xl font-bold text-purple-600">{{ number_format($totalDataUsed / (1024 * 1024 * 1024), 2) }} GB</div>
                            <div class="text-xs text-gray-500 mt-1">All time</div>
                        </div>
                        <div class="text-4xl text-purple-500 opacity-50">📊</div>
                    </div>
                </div>
            </div>

            <!-- Active Sessions -->
            @if($activeSessions->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">🟢 Active Sessions</h3>
                        <a href="{{ route('user.sessions') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4" id="active-sessions-container">
                        @foreach($activeSessions as $session)
                            <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">{{ $session->package->name }}</div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            Started: {{ $session->started_at->format('M d, Y H:i') }}
                                        </div>
                                        @php
                                            $dataUsedMB = $session->bytes_total / (1024 * 1024);
                                            $limitMB = 0;
                                            if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $session->package->rate_limit, $matches)) {
                                                $limitMB = $matches[2] === 'GB' ? $matches[1] * 1024 : $matches[1];
                                            }
                                            $percentage = $limitMB > 0 ? min(100, ($dataUsedMB / $limitMB) * 100) : 0;
                                        @endphp
                                        <div class="mt-3">
                                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>Data Usage</span>
                                                <span>
                                                    {{ number_format($dataUsedMB, 2) }} MB
                                                    @if($limitMB > 0)
                                                        / {{ number_format($limitMB, 0) }} MB
                                                    @endif
                                                </span>
                                            </div>
                                            @if($limitMB > 0)
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-green-600 h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                        <div class="text-xs text-gray-600 mt-2">
                                            Expires {{ $session->expires_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                <div class="text-4xl mb-3">🌐</div>
                <h4 class="font-semibold text-gray-900 mb-2">No Active Sessions</h4>
                <p class="text-gray-600 mb-4">You don't have any active internet sessions at the moment.</p>
                <a href="{{ route('user.recharge') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Buy Data Now
                </a>
            </div>
            @endif

            <!-- Recent Activity Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Sessions -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Sessions</h3>
                            <a href="{{ route('user.sessions') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($recentSessions->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentSessions as $session)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $session->package->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $session->started_at->format('M d, Y') }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-900">{{ number_format($session->bytes_total / (1024 * 1024), 0) }} MB</div>
                                            @if($session->isActive())
                                                <span class="text-xs text-green-600">Active</span>
                                            @else
                                                <span class="text-xs text-gray-500">Ended</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No sessions yet</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Purchases</h3>
                            <a href="{{ route('user.purchases') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($recentTransactions->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentTransactions as $transaction)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $transaction->package->name ?? 'Package' }}</div>
                                            <div class="text-xs text-gray-500">{{ $transaction->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-900">KES {{ number_format($transaction->amount, 0) }}</div>
                                            @if($transaction->status === 'completed')
                                                <span class="text-xs text-green-600">✓ Paid</span>
                                            @elseif($transaction->status === 'pending')
                                                <span class="text-xs text-yellow-600">⏳ Pending</span>
                                            @else
                                                <span class="text-xs text-red-600">✗ Failed</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No purchases yet</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="{{ route('user.recharge') }}" class="bg-white hover:bg-indigo-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4 group-hover:scale-110 transition">💳</div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-indigo-600">Buy Data</div>
                            <div class="text-xs text-gray-500">Recharge now</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('user.sessions') }}" class="bg-white hover:bg-blue-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4 group-hover:scale-110 transition">🌐</div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-blue-600">My Sessions</div>
                            <div class="text-xs text-gray-500">View history</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('user.purchases') }}" class="bg-white hover:bg-green-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4 group-hover:scale-110 transition">📜</div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-green-600">Purchases</div>
                            <div class="text-xs text-gray-500">Payment history</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('user.settings') }}" class="bg-white hover:bg-purple-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4 group-hover:scale-110 transition">⚙️</div>
                        <div>
                            <div class="font-semibold text-gray-900 group-hover:text-purple-600">Settings</div>
                            <div class="text-xs text-gray-500">Account settings</div>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh active sessions every 30 seconds
        @if($activeSessions->count() > 0)
        setInterval(function() {
            fetch('{{ route('user.sessions.live-data') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count === 0) {
                        // Reload page if sessions have ended
                        location.reload();
                    }
                })
                .catch(error => console.error('Error refreshing sessions:', error));
        }, 30000);
        @endif
    </script>
    @endpush
</x-app-layout>
