<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="font-semibold text-xl text-gray-800">{{ $customer->name ?: $customer->phone }}</h2>
                            <p class="text-gray-500 text-sm mt-1">
                                {{ $customer->phone ?? 'No phone' }} &middot;
                                Member since {{ $customer->created_at->format('M Y') }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if($customer->status === 'active')
                                <form action="{{ route('customers.suspend', $customer) }}" method="POST" onsubmit="return confirm('Suspend this customer?')">
                                    @csrf @method('PATCH')
                                    <button class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600">Suspend</button>
                                </form>
                            @else
                                <form action="{{ route('customers.activate', $customer) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Activate</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Status</div>
                            <div class="text-lg font-semibold capitalize
                                @if($customer->status === 'active') text-green-600
                                @elseif($customer->status === 'suspended') text-amber-600
                                @else text-red-600 @endif">
                                {{ $customer->status }}
                            </div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Total Spent</div>
                            <div class="text-lg font-semibold">{{ config('app.currency', 'KSh') }} {{ number_format($totalSpent, 2) }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Total Sessions</div>
                            <div class="text-lg font-semibold">{{ $totalSessions }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Data Used</div>
                            <div class="text-lg font-semibold">{{ number_format($totalDataUsed / 1073741824, 2) }} GB</div>
                        </div>
                    </div>

                    @if($customer->router)
                        <div class="mb-4 text-sm text-gray-600">
                            <span class="font-semibold">Router:</span> {{ $customer->router->name }} ({{ $customer->router->ip }})
                        </div>
                    @endif
                </div>
            </div>

            @if($activeSession)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg text-gray-800 mb-4">Active Session</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Package</div>
                                <div class="font-semibold">{{ $activeSession->package->name ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Started</div>
                                <div class="font-semibold">{{ $activeSession->created_at->format('M d, H:i') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Expires</div>
                                <div class="font-semibold">{{ \Carbon\Carbon::parse($activeSession->expires_at)->format('M d, H:i') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Data Used</div>
                                <div class="font-semibold">{{ number_format($activeSession->bytes_total / 1048576, 1) }} MB</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-800 mb-4">Payment History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2 px-3">Date</th>
                                    <th class="py-2 px-3">Package</th>
                                    <th class="py-2 px-3">Amount</th>
                                    <th class="py-2 px-3">Gateway</th>
                                    <th class="py-2 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->transactions as $txn)
                                    <tr class="border-b">
                                        <td class="py-2 px-3">{{ $txn->created_at->format('M d, Y') }}</td>
                                        <td class="py-2 px-3">{{ $txn->package->name ?? '—' }}</td>
                                        <td class="py-2 px-3">{{ config('app.currency', 'KSh') }} {{ number_format($txn->amount, 2) }}</td>
                                        <td class="py-2 px-3 capitalize">{{ $txn->gateway }}</td>
                                        <td class="py-2 px-3">
                                            <span class="capitalize
                                                @if($txn->status === 'completed') text-green-600
                                                @elseif($txn->status === 'pending') text-amber-600
                                                @else text-red-600 @endif">{{ $txn->status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                @if($customer->transactions->isEmpty())
                                    <tr><td colspan="5" class="py-4 text-center text-gray-400">No transactions</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-800 mb-4">Session History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2 px-3">Started</th>
                                    <th class="py-2 px-3">Package</th>
                                    <th class="py-2 px-3">Data Used</th>
                                    <th class="py-2 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->sessions as $session)
                                    <tr class="border-b">
                                        <td class="py-2 px-3">{{ $session->created_at->format('M d, H:i') }}</td>
                                        <td class="py-2 px-3">{{ $session->package->name ?? '—' }}</td>
                                        <td class="py-2 px-3">{{ number_format($session->bytes_total / 1048576, 1) }} MB</td>
                                        <td class="py-2 px-3 capitalize">{{ $session->status }}</td>
                                    </tr>
                                @endforeach
                                @if($customer->sessions->isEmpty())
                                    <tr><td colspan="4" class="py-4 text-center text-gray-400">No sessions</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-800 mb-4">Send SMS</h3>
                    <form action="{{ route('customers.sms', $customer) }}" method="POST">
                        @csrf
                        <div class="flex gap-2">
                            <input type="text" name="message" class="flex-1 rounded border-gray-300" placeholder="Type a message..." required maxlength="500">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
