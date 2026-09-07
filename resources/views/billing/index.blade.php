<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
                    @endif
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
                    @endif
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Billing') }}
                        </h2>
                        <div class="flex items-center">
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('billing.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white border border-transparent rounded-md font-semibold text-xs uppercase mr-2">
                                    {{ __('Dashboard') }}
                                </a>
                                <a href="{{ route('billing.download') }}" class="inline-flex items-center px-4 py-2 bg-orange-400 text-white dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs rounded uppercase">
                                    {{ __('Download') }}
                                </a>

                                <a href="{{ route('billing.create') }}" class="ml-2 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white rounded uppercase">
                                    {{ __('Create') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    @if (auth()->user()->isAdmin() && isset($summary))
                        <!-- Revenue Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <div class="text-xs text-gray-500 uppercase">Total Revenue (30d)</div>
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

                        <!-- Top Customers -->
                        @if(!empty($topCustomers))
                            <div class="mb-6">
                                <h3 class="font-semibold text-gray-700 mb-3">Top Customers (30d)</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="border-b text-left text-gray-500">
                                                <th class="py-2 px-3">Customer</th>
                                                <th class="py-2 px-3">Phone</th>
                                                <th class="py-2 px-3 text-right">Total Spent</th>
                                                <th class="py-2 px-3 text-right">Transactions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topCustomers as $customer)
                                                <tr class="border-b">
                                                    <td class="py-2 px-3">{{ $customer['name'] }}</td>
                                                    <td class="py-2 px-3">{{ $customer['phone'] }}</td>
                                                    <td class="py-2 px-3 text-right font-semibold">{{ config('app.currency', 'KSh') }} {{ number_format($customer['total_spent'], 2) }}</td>
                                                    <td class="py-2 px-3 text-right">{{ $customer['transaction_count'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div>
                        @if (auth()->user()->isAdmin())
                            <livewire:billing-table/>
                        @endif
                        @if (auth()->user()->isUser())
                            <livewire:user-billing-table/>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
