<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Analytics Dashboard') }}
            </h2>
            <div class="flex gap-2">
                <select id="period-selector" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                </select>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm opacity-90 mb-1">Total Revenue</div>
                            <div class="text-3xl font-bold">KES {{ number_format($totalRevenue, 0) }}</div>
                            <div class="text-xs opacity-75 mt-2">All time</div>
                        </div>
                        <div class="text-4xl opacity-50">💰</div>
                    </div>
                </div>

                <!-- Period Revenue -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm opacity-90 mb-1">Period Revenue</div>
                            <div class="text-3xl font-bold">KES {{ number_format($periodRevenue, 0) }}</div>
                            <div class="text-xs opacity-75 mt-2">Selected period</div>
                        </div>
                        <div class="text-4xl opacity-50">📊</div>
                    </div>
                </div>

                <!-- Today's Revenue -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm opacity-90 mb-1">Today's Revenue</div>
                            <div class="text-3xl font-bold">KES {{ number_format($todayRevenue, 0) }}</div>
                            <div class="text-xs opacity-75 mt-2">{{ now()->format('M d, Y') }}</div>
                        </div>
                        <div class="text-4xl opacity-50">📈</div>
                    </div>
                </div>

                <!-- Active Sessions -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm opacity-90 mb-1">Active Sessions</div>
                            <div class="text-3xl font-bold">{{ $activeSessions }}</div>
                            <div class="text-xs opacity-75 mt-2">Currently online</div>
                        </div>
                        <div class="text-4xl opacity-50">🌐</div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-600 mb-1">Total Sessions</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($totalSessions) }}</div>
                    <div class="text-xs text-green-600 mt-1">{{ number_format($periodSessions) }} this period</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-600 mb-1">Total Data Used</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($totalDataUsed / (1024 * 1024 * 1024), 2) }} GB</div>
                    <div class="text-xs text-green-600 mt-1">{{ number_format($periodDataUsed / (1024 * 1024 * 1024), 2) }} GB this period</div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-600 mb-1">Average Revenue/Day</div>
                    <div class="text-2xl font-bold text-gray-900">KES {{ number_format($periodRevenue / 30, 0) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Based on 30-day average</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue Chart -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Revenue Trend (Last 30 Days)</h3>
                    </div>
                    <div class="p-6">
                        <canvas id="revenueChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Sessions Chart -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Sessions Trend (Last 30 Days)</h3>
                    </div>
                    <div class="p-6">
                        <canvas id="sessionsChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Packages and Revenue by Package -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Packages -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Packages by Sessions</h3>
                    </div>
                    <div class="p-6">
                        @if($topPackages->count() > 0)
                            <div class="space-y-4">
                                @foreach($topPackages as $package)
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-medium text-gray-700">{{ $package->name }}</span>
                                            <span class="text-sm font-bold text-gray-900">{{ $package->sessions_count }} sessions</span>
                                        </div>
                                        @php
                                            $maxSessions = $topPackages->first()->sessions_count;
                                            $percentage = $maxSessions > 0 ? ($package->sessions_count / $maxSessions) * 100 : 0;
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-3">
                                            <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                                            <span>KES {{ number_format($package->price, 2) }}</span>
                                            <span>{{ $package->rate_limit }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No data available</p>
                        @endif
                    </div>
                </div>

                <!-- Revenue by Package -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Revenue by Package</h3>
                    </div>
                    <div class="p-6">
                        @if($revenueByPackage->count() > 0)
                            <div class="space-y-3">
                                @foreach($revenueByPackage->take(5) as $item)
                                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $item->package->name ?? 'Unknown' }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->transaction_count }} transactions</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-green-600">KES {{ number_format($item->total_revenue, 0) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No revenue data available</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="{{ route('reports.revenue') }}" class="bg-white hover:bg-gray-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">💵</div>
                        <div>
                            <div class="text-sm text-gray-600">Detailed</div>
                            <div class="font-semibold text-gray-900 group-hover:text-indigo-600">Revenue Report</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('reports.usage') }}" class="bg-white hover:bg-gray-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">📊</div>
                        <div>
                            <div class="text-sm text-gray-600">Data</div>
                            <div class="font-semibold text-gray-900 group-hover:text-indigo-600">Usage Statistics</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('reports.packages') }}" class="bg-white hover:bg-gray-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">📦</div>
                        <div>
                            <div class="text-sm text-gray-600">Performance</div>
                            <div class="font-semibold text-gray-900 group-hover:text-indigo-600">Package Analysis</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('sessions.history') }}" class="bg-white hover:bg-gray-50 overflow-hidden shadow-sm rounded-lg p-6 transition group">
                    <div class="flex items-center">
                        <div class="text-3xl mr-4">📜</div>
                        <div>
                            <div class="text-sm text-gray-600">Complete</div>
                            <div class="font-semibold text-gray-900 group-hover:text-indigo-600">Session History</div>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Period selector
        document.getElementById('period-selector').addEventListener('change', function() {
            window.location.href = '{{ route('reports.index') }}?period=' + this.value;
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($last30Days->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))) !!},
                datasets: [{
                    label: 'Revenue (KES)',
                    data: {!! json_encode($revenueChartData) !!},
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Sessions Chart
        const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
        const sessionsChart = new Chart(sessionsCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($last30Days->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))) !!},
                datasets: [{
                    label: 'Sessions',
                    data: {!! json_encode($sessionsChartData) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
