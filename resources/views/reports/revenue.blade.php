<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Revenue Report') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    ← Dashboard
                </a>
                <a href="{{ route('reports.export', ['type' => 'revenue', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
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
                    <form method="GET" action="{{ route('reports.revenue') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <div class="bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Total Revenue</div>
                    <div class="text-3xl font-bold">KES {{ number_format($totalRevenue, 2) }}</div>
                    <div class="text-xs opacity-75 mt-2">{{ \Carbon\Carbon::parse($fromDate)->format('M d') }} - {{ \Carbon\Carbon::parse($toDate)->format('M d, Y') }}</div>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Transactions</div>
                    <div class="text-3xl font-bold">{{ number_format($transactionCount) }}</div>
                    <div class="text-xs opacity-75 mt-2">Completed payments</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Average Transaction</div>
                    <div class="text-3xl font-bold">KES {{ number_format($averageTransaction, 0) }}</div>
                    <div class="text-xs opacity-75 mt-2">Per sale</div>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-red-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="text-sm opacity-90 mb-1">Failed Transactions</div>
                    <div class="text-3xl font-bold">{{ $failedTransactions }}</div>
                    <div class="text-xs opacity-75 mt-2">Unsuccessful attempts</div>
                </div>
            </div>

            <!-- Revenue by Package -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Revenue by Package</h3>
                </div>
                <div class="p-6">
                    @if($revenueByPackage->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg/Sale</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Share</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($revenueByPackage as $item)
                                        @php
                                            $share = $totalRevenue > 0 ? ($item->total_revenue / $totalRevenue) * 100 : 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $item->package->name ?? 'Unknown' }}</div>
                                                <div class="text-xs text-gray-500">{{ $item->package->rate_limit ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $item->sales_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                                KES {{ number_format($item->total_revenue, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                KES {{ number_format($item->avg_amount, 2) }}
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
                        <p class="text-gray-500 text-center py-8">No revenue data for selected period</p>
                    @endif
                </div>
            </div>

            <!-- Daily Revenue Chart -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Daily Revenue Breakdown</h3>
                </div>
                <div class="p-6">
                    @if($revenueByDay->count() > 0)
                        <div class="mb-6">
                            <canvas id="dailyRevenueChart" height="80"></canvas>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg/Transaction</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($revenueByDay as $day)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($day->date)->format('D, M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $day->transactions }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                                KES {{ number_format($day->revenue, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                KES {{ $day->transactions > 0 ? number_format($day->revenue / $day->transactions, 2) : '0.00' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No daily revenue data available</p>
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

        // Daily Revenue Chart
        @if($revenueByDay->count() > 0)
        const ctx = document.getElementById('dailyRevenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($revenueByDay->map(fn($day) => \Carbon\Carbon::parse($day->date)->format('M d'))) !!},
                datasets: [{
                    label: 'Revenue (KES)',
                    data: {!! json_encode($revenueByDay->pluck('revenue')) !!},
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
        @endif
    </script>
    @endpush
</x-app-layout>
