<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800">Revenue Dashboard (Last 30 Days)</h2>
                        <a href="{{ route('billing.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Back to Billing</a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Total Revenue</div>
                            <div class="text-2xl font-bold text-green-600">{{ config('app.currency', 'KSh') }} {{ number_format($summary['total_revenue'], 2) }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Transactions</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $summary['total_transactions'] }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Avg Transaction</div>
                            <div class="text-2xl font-bold text-purple-600">{{ config('app.currency', 'KSh') }} {{ number_format($summary['average_transaction'], 2) }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Unique Customers</div>
                            <div class="text-2xl font-bold text-indigo-600">{{ $summary['unique_customers'] }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Revenue by Gateway</h3>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="py-2 px-3">Gateway</th>
                                        <th class="py-2 px-3 text-right">Count</th>
                                        <th class="py-2 px-3 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summary['by_gateway'] as $gateway => $data)
                                        <tr class="border-b">
                                            <td class="py-2 px-3 capitalize">{{ $gateway }}</td>
                                            <td class="py-2 px-3 text-right">{{ $data['count'] }}</td>
                                            <td class="py-2 px-3 text-right font-semibold">{{ config('app.currency', 'KSh') }} {{ number_format($data['revenue'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @if(empty($summary['by_gateway']))
                                        <tr><td colspan="3" class="py-4 text-center text-gray-400">No data</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Revenue by Router</h3>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="py-2 px-3">Router</th>
                                        <th class="py-2 px-3 text-right">Count</th>
                                        <th class="py-2 px-3 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summary['by_router'] as $data)
                                        <tr class="border-b">
                                            <td class="py-2 px-3">{{ $data['router_name'] }}</td>
                                            <td class="py-2 px-3 text-right">{{ $data['count'] }}</td>
                                            <td class="py-2 px-3 text-right font-semibold">{{ config('app.currency', 'KSh') }} {{ number_format($data['revenue'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @if(empty($summary['by_router']))
                                        <tr><td colspan="3" class="py-4 text-center text-gray-400">No data</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-700 mb-3">Revenue by Type</h3>
                        <div class="flex gap-4 flex-wrap">
                            @foreach($summary['by_type'] as $type => $data)
                                <div class="bg-slate-50 rounded-lg p-4 min-w-[200px]">
                                    <div class="text-xs text-gray-500 uppercase capitalize">{{ $type }}</div>
                                    <div class="text-xl font-bold">{{ config('app.currency', 'KSh') }} {{ number_format($data['revenue'], 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ $data['count'] }} transactions</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-700 mb-3">Daily Revenue Trend</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="py-2 px-3">Date</th>
                                        <th class="py-2 px-3 text-right">Transactions</th>
                                        <th class="py-2 px-3 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summary['daily_revenue'] as $day)
                                        <tr class="border-b">
                                            <td class="py-2 px-3">{{ $day['date'] }}</td>
                                            <td class="py-2 px-3 text-right">{{ $day['count'] }}</td>
                                            <td class="py-2 px-3 text-right font-semibold">{{ config('app.currency', 'KSh') }} {{ number_format($day['revenue'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @if(empty($summary['daily_revenue']))
                                        <tr><td colspan="3" class="py-4 text-center text-gray-400">No data</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
