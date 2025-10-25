<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Data Usage Statistics') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    ← Dashboard
                </a>
                <a href="{{ route('reports.export', ['type' => 'usage', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
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
                    <form method="GET" action="{{ route('reports.usage') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Total Data Used</div>
                    <div class="text-3xl font-bold">{{ number_format($totalDataUsed / (1024 * 1024 * 1024), 2) }} GB</div>
                    <div class="text-xs opacity-75 mt-2">All sessions</div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Downloaded</div>
                    <div class="text-3xl font-bold">{{ number_format($totalDownloaded / (1024 * 1024 * 1024), 2) }} GB</div>
                    <div class="text-xs opacity-75 mt-2">↓ Incoming traffic</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Uploaded</div>
                    <div class="text-3xl font-bold">{{ number_format($totalUploaded / (1024 * 1024 * 1024), 2) }} GB</div>
                    <div class="text-xs opacity-75 mt-2">↑ Outgoing traffic</div>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-orange-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Avg per Session</div>
                    <div class="text-3xl font-bold">{{ number_format($averagePerSession / (1024 * 1024), 0) }} MB</div>
                    <div class="text-xs opacity-75 mt-2">{{ number_format($sessionCount) }} sessions</div>
                </div>
            </div>

            <!-- Usage by Package -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Data Usage by Package</h3>
                </div>
                <div class="p-6">
                    @if($usageByPackage->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg/Session</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($usageByPackage as $item)
                                        @php
                                            $dataGB = $item->total_data / (1024 * 1024 * 1024);
                                            $avgMB = $item->avg_data / (1024 * 1024);
                                            $share = $totalDataUsed > 0 ? ($item->total_data / $totalDataUsed) * 100 : 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $item->package->name ?? 'Unknown' }}</div>
                                                <div class="text-xs text-gray-500">{{ $item->package->rate_limit ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ number_format($item->session_count) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600">
                                                {{ number_format($dataGB, 2) }} GB
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ number_format($avgMB, 2) }} MB
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-full max-w-[100px] bg-gray-200 rounded-full h-2 mr-2">
                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $share }}%"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-700">{{ number_format($share, 1) }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No usage data for selected period</p>
                    @endif
                </div>
            </div>

            <!-- Daily Usage & Peak Hours -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Daily Usage Chart -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Daily Data Usage</h3>
                    </div>
                    <div class="p-6">
                        @if($dailyUsage->count() > 0)
                            <canvas id="dailyUsageChart" height="250"></canvas>
                        @else
                            <p class="text-gray-500 text-center py-8">No daily usage data</p>
                        @endif
                    </div>
                </div>

                <!-- Peak Hours -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Peak Usage Hours</h3>
                    </div>
                    <div class="p-6">
                        @if($peakHours->count() > 0)
                            <div class="space-y-3">
                                @foreach($peakHours->take(12) as $hour)
                                    @php
                                        $dataMB = $hour->data_used / (1024 * 1024);
                                        $maxSessions = $peakHours->first()->session_count;
                                        $percentage = $maxSessions > 0 ? ($hour->session_count / $maxSessions) * 100 : 0;
                                    @endphp
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-medium text-gray-700">{{ str_pad($hour->hour, 2, '0', STR_PAD_LEFT) }}:00 - {{ str_pad($hour->hour + 1, 2, '0', STR_PAD_LEFT) }}:00</span>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-gray-900">{{ $hour->session_count }} sessions</div>
                                                <div class="text-xs text-gray-500">{{ number_format($dataMB, 0) }} MB</div>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No peak hour data</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Users -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Top Users by Data Usage</h3>
                </div>
                <div class="p-6">
                    @if($topUsers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg/Session</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($topUsers as $index => $user)
                                        @php
                                            $dataGB = $user->total_data / (1024 * 1024 * 1024);
                                            $avgMB = $user->total_data / $user->session_count / (1024 * 1024);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->username }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $user->session_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600">
                                                {{ number_format($dataGB, 2) }} GB
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ number_format($avgMB, 2) }} MB
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No user data available</p>
                    @endif
                </div>
            </div>

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

        // Daily Usage Chart
        @if($dailyUsage->count() > 0)
        const ctx = document.getElementById('dailyUsageChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($dailyUsage->map(fn($day) => \Carbon\Carbon::parse($day->date)->format('M d'))) !!},
                datasets: [{
                    label: 'Data Usage (MB)',
                    data: {!! json_encode($dailyUsage->map(fn($day) => round($day->data_used / (1024 * 1024), 2))) !!},
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
                            callback: function(value) {
                                return value + ' MB';
                            }
                        }
                    }
                }
            }
        });
        @endif
    </script>
    @endpush
</x-app-layout>
