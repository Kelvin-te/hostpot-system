<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Wallet') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Balance Card -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 overflow-hidden shadow-lg rounded-lg p-8 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-sm text-indigo-100">Current Balance</div>
                        <div class="text-4xl font-bold mt-2">KES {{ number_format($walletBalance, 2) }}</div>
                    </div>
                    <div class="text-6xl opacity-50">👛</div>
                </div>
                <div class="mt-6">
                    <a href="{{ route('user.recharge') }}" class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition">
                        Top Up Wallet
                    </a>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Transaction History</h3>
                </div>
                <div class="p-6">
                    @if($transactions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="py-2 px-3">Date</th>
                                        <th class="py-2 px-3">Description</th>
                                        <th class="py-2 px-3">Type</th>
                                        <th class="py-2 px-3 text-right">Amount</th>
                                        <th class="py-2 px-3 text-right">Balance After</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $txn)
                                        <tr class="border-b">
                                            <td class="py-2 px-3 text-gray-600">{{ $txn->created_at->format('M d, Y H:i') }}</td>
                                            <td class="py-2 px-3">{{ $txn->description }}</td>
                                            <td class="py-2 px-3">
                                                @if($txn->type === 'credit')
                                                    <span class="text-green-600 font-semibold">Credit</span>
                                                @else
                                                    <span class="text-red-600 font-semibold">Debit</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-right
                                                @if($txn->type === 'credit') text-green-600
                                                @else text-red-600 @endif font-semibold">
                                                @if($txn->type === 'credit') + @else - @endif
                                                KES {{ number_format($txn->amount, 2) }}
                                            </td>
                                            <td class="py-2 px-3 text-right text-gray-700">
                                                KES {{ number_format($txn->balance_after, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $transactions->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No wallet transactions yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
