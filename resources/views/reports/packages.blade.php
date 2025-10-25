<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Package Performance Report') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    ← Dashboard
                </a>
                <a href="{{ route('reports.export', ['type' => 'packages', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    📥 Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('reports.packages') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="to_date" id="to_date" value="{{ $toDate }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div class="flex items-end col-span-2 gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                                🔍 Apply Filter
                            </button>
                            <button type="button" onclick="setQuickDate('today')" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition text-sm">
                                Today
                            </button>
                            <button type="button" onclick="setQuickDate('week')" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition text-sm">
                                This Week
                            </button>
                            <button type="button" onclick="setQuickDate('month')" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition text-sm">
                                This Month
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Package Performance Table -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">All Packages Performance</h3>
                    <p class="text-sm text-gray-600 mt-1">Comprehensive overview of all package metrics</p>
                </div>
                <div class="p-6">
                    @if($packages->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Used</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($packages as $package)
                                        @php
                                            $dataGB = $package->period_data_used / (1024 * 1024 * 1024);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">{{ $package->name }} -
                                                            @if($package->router)
                                                                <span> {{ $package->router->name }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            {{ $package->rate_limit }} • {{ $package->bandwidth_download }}/{{ $package->bandwidth_upload }} Mbps
                                                        
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-gray-900">KES {{ number_format($package->price, 0) }}</div>
                                                <div class="text-xs text-gray-500">{{ $package->validity_days }}d validity</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-bold text-blue-600">{{ number_format($package->sessions_count) }}</div>
                                                @if($package->sessions_count > 0)
                                                    <div class="text-xs text-gray-500">Active usage</div>
                                                @else
                                                    <div class="text-xs text-red-500">No sessions</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-bold text-green-600">KES {{ number_format($package->period_revenue, 0) }}</div>
                                                @if($package->sessions_count > 0)
                                                    <div class="text-xs text-gray-500">{{ number_format($package->period_revenue / $package->sessions_count, 0) }}/session</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-purple-600">{{ number_format($dataGB, 2) }} GB</div>
                                                @if($package->sessions_count > 0)
                                                    <div class="text-xs text-gray-500">{{ number_format($dataGB / $package->sessions_count, 2) }} GB/session</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($package->avg_duration_minutes > 0)
                                                    @if($package->avg_duration_minutes < 60)
                                                        {{ round($package->avg_duration_minutes) }} min
                                                    @elseif($package->avg_duration_minutes < 1440)
                                                        {{ round($package->avg_duration_minutes / 60, 1) }} hrs
                                                    @else
                                                        {{ round($package->avg_duration_minutes / 1440, 1) }} days
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($package->is_active)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No packages found</p>
                    @endif
                </div>
            </div>

            <!-- Performance Insights -->
            @php
                $activePackages = $packages->filter(fn($p) => $p->sessions_count > 0);
                $totalSessions = $activePackages->sum('sessions_count');
                $totalRevenue = $activePackages->sum('period_revenue');
                $topPerformer = $activePackages->sortByDesc('period_revenue')->first();
                $mostPopular = $activePackages->sortByDesc('sessions_count')->first();
            @endphp

            @if($activePackages->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Performer -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 overflow-hidden shadow-sm rounded-lg p-6 border-2 border-green-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm font-medium text-green-600 mb-2">🏆 Top Revenue Generator</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $topPerformer->name ?? 'N/A' }}</div>
                        </div>
                        <div class="text-5xl">💰</div>
                    </div>
                    <div class="mt-4 flex justify-between align-center w-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Revenue: </span>
                            <span class="font-semibold text-green-600">KES {{ number_format($topPerformer->period_revenue ?? 0, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Sessions: </span>
                            <span class="font-semibold">{{ number_format($topPerformer->sessions_count ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Price: </span>
                            <span class="font-semibold">KES {{ number_format($topPerformer->price ?? 0, 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Most Popular -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 overflow-hidden shadow-sm rounded-lg p-6 border-2 border-blue-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm font-medium text-blue-600 mb-2">⭐ Most Popular Package</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $mostPopular->name ?? 'N/A' }}</div>
                            
                        </div>
                        <div class="text-5xl">🌟</div>
                    </div>
                    <div class="mt-4 flex justify-between align-center w-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Sessions: </span>
                            <span class="font-semibold text-blue-600">{{ number_format($mostPopular->sessions_count ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Market Share: </span>
                            <span class="font-semibold">{{ $totalSessions > 0 ? number_format(($mostPopular->sessions_count ?? 0) / $totalSessions * 100, 1) : 0 }}%</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Price: </span>
                            <span class="font-semibold">KES {{ number_format($mostPopular->price ?? 0, 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue Distribution -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Revenue Distribution</h3>
                    </div>
                    <div class="p-6">
                        <canvas id="revenueDistChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Session Distribution -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Session Distribution</h3>
                    </div>
                    <div class="p-6">
                        <canvas id="sessionDistChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        function setQuickDate(period) {
            const today = new Date();
            const fromDateInput = document.getElementById('from_date');
            const toDateInput = document.getElementById('to_date');
            
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            switch(period) {
                case 'today':
                    fromDateInput.value = formatDate(today);
                    toDateInput.value = formatDate(today);
                    break;
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    fromDateInput.value = formatDate(weekStart);
                    toDateInput.value = formatDate(today);
                    break;
                case 'month':
                    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    fromDateInput.value = formatDate(monthStart);
                    toDateInput.value = formatDate(today);
                    break;
            }
        }

        @php
            $chartPackages = $packages->filter(fn($p) => $p->sessions_count > 0)->take(10);
        @endphp

        @if($chartPackages->count() > 0)
        // Revenue Distribution Chart
        const revenueCtx = document.getElementById('revenueDistChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartPackages->pluck('name')) !!},
                datasets: [{
                    data: {!! json_encode($chartPackages->pluck('period_revenue')) !!},
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(132, 204, 22, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(107, 114, 128, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Session Distribution Chart
        const sessionCtx = document.getElementById('sessionDistChart').getContext('2d');
        new Chart(sessionCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($chartPackages->pluck('name')) !!},
                datasets: [{
                    data: {!! json_encode($chartPackages->pluck('sessions_count')) !!},
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(132, 204, 22, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(107, 114, 128, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        @endif
    </script>
    @endpush
</x-app-layout>
