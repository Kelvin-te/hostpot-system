<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">
            {{ __('My Purchases') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="opacity-90">Total Spent</div>
                    <div class="text-2xl font-bold">KES {{ number_format($stats['total_spent'], 0) }}</div>
                    <div class="text-xs opacity-75 mt-1">All successful payments</div>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="opacity-90">Successful Payments</div>
                    <div class="text-2xl font-bold">{{ $stats['total_transactions'] }}</div>
                    <div class="text-xs opacity-75 mt-1">Completed transactions</div>
                </div>
                <div class="bg-gradient-to-br from-red-500 to-red-600 overflow-hidden shadow-lg rounded-lg p-6 text-white">
                    <div class="opacity-90">Failed Payments</div>
                    <div class="text-2xl font-bold">{{ $stats['failed_transactions'] }}</div>
                    <div class="text-xs opacity-75 mt-1">Unsuccessful attempts</div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Payment History</h3>
                </div>
                
                <div class="p-6">
                    @if($transactions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Package</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Receipt</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200">
                                    @foreach($transactions as $transaction)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-slate-900">{{ $transaction->created_at->format('M d, Y') }}</div>
                                                <div class="text-xs text-slate-500">{{ $transaction->created_at->format('H:i:s') }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-slate-900">{{ $transaction->package->name ?? 'Package' }}</div>
                                                @if($transaction->package)
                                                    <div class="text-xs text-slate-500">{{ $transaction->package->data_cap }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-900">KES {{ number_format($transaction->amount, 2) }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($transaction->mpesa_receipt_number)
                                                    <div class="font-mono text-slate-900">{{ $transaction->mpesa_receipt_number }}</div>
                                                @else
                                                    <span class="text-xs text-slate-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($transaction->status === 'completed')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Completed
                                                    </span>
                                                @elseif($transaction->status === 'pending')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        ⏳ Pending
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Failed
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $transactions->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            
                            <h3 class="text-lg font-semibold text-slate-900 mb-2">No purchases yet</h3>
                            <p class="text-slate-600 mb-4">You haven't made any purchases yet.</p>
                            <a href="{{ route('user.recharge') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Buy Data Now
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
