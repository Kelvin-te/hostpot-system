<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl">{{ __('Payment Reconciliation') }}</h2>
                        <form method="get" class="flex gap-2 items-center">
                            <input type="date" name="from_date" value="{{ $fromDate }}" class="rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-100">
                            <input type="date" name="to_date" value="{{ $toDate }}" class="rounded-md border-gray-300 dark:bg-gray-900 dark:text-gray-100">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded">{{ __('Apply') }}</button>
                        </form>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded">
                            <div class="text-sm text-gray-500">{{ __('Completed') }}</div>
                            <div class="text-2xl font-bold text-green-600">{{ $summary['completed'] }}</div>
                            <div class="text-sm">KES {{ number_format($summary['completed_amount'], 2) }}</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded">
                            <div class="text-sm text-gray-500">{{ __('Pending') }}</div>
                            <div class="text-2xl font-bold text-yellow-600">{{ $summary['pending'] }}</div>
                            <div class="text-sm">KES {{ number_format($summary['pending_amount'], 2) }}</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/30 p-4 rounded">
                            <div class="text-sm text-gray-500">{{ __('Failed') }}</div>
                            <div class="text-2xl font-bold text-red-600">{{ $summary['failed'] }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="border border-red-200 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                            <div class="font-semibold text-red-700">{{ __('Needs attention') }}</div>
                            <div class="mt-1">{{ $summary['stale_pending'] }} {{ __('stale pending (>2h)') }}</div>
                            <div>{{ $summary['missing_receipt'] }} {{ __('completed w/o M-Pesa receipt') }}</div>
                        </div>
                        <div class="border p-4 rounded col-span-2">
                            <div class="font-semibold mb-2">{{ __('By gateway / status') }}</div>
                            <table class="w-full text-sm">
                                <thead><tr class="text-left text-gray-500"><th>{{ __('Gateway') }}</th><th>{{ __('Status') }}</th><th>{{ __('Count') }}</th><th>{{ __('Total') }}</th></tr></thead>
                                <tbody>
                                    @forelse($byGateway as $row)
                                        <tr class="border-t"><td>{{ $row->gateway }}</td><td>{{ $row->status }}</td><td>{{ $row->cnt }}</td><td>KES {{ number_format($row->total, 2) }}</td></tr>
                                    @empty
                                        <tr><td colspan="4" class="text-gray-500 py-2">{{ __('No transactions in range') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($issues->isNotEmpty())
                        <h3 class="font-semibold mb-2 text-red-700">{{ __('Reconciliation issues') }}</h3>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mb-6">
                            <thead><tr class="text-left text-sm text-gray-500"><th class="px-2 py-1">{{ __('Date') }}</th><th class="px-2 py-1">{{ __('Phone') }}</th><th class="px-2 py-1">{{ __('Amount') }}</th><th class="px-2 py-1">{{ __('Gateway') }}</th><th class="px-2 py-1">{{ __('Status') }}</th><th class="px-2 py-1">{{ __('Receipt') }}</th></tr></thead>
                            <tbody>
                                @foreach($issues as $t)
                                    <tr class="border-t">
                                        <td class="px-2 py-1">{{ $t->created_at->format('M d H:i') }}</td>
                                        <td class="px-2 py-1">{{ $t->phone_number }}</td>
                                        <td class="px-2 py-1">KES {{ number_format($t->amount, 2) }}</td>
                                        <td class="px-2 py-1">{{ $t->gateway }}</td>
                                        <td class="px-2 py-1">{{ $t->status }}</td>
                                        <td class="px-2 py-1">{{ $t->mpesa_receipt_number ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <h3 class="font-semibold mb-2">{{ __('Recent transactions') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead><tr class="text-left text-sm text-gray-500"><th class="px-2 py-1">{{ __('Date') }}</th><th class="px-2 py-1">{{ __('Phone') }}</th><th class="px-2 py-1">{{ __('Amount') }}</th><th class="px-2 py-1">{{ __('Gateway') }}</th><th class="px-2 py-1">{{ __('Status') }}</th><th class="px-2 py-1">{{ __('Receipt') }}</th></tr></thead>
                        <tbody>
                            @foreach($recent as $t)
                                <tr class="border-t">
                                    <td class="px-2 py-1">{{ $t->created_at->format('M d H:i') }}</td>
                                    <td class="px-2 py-1">{{ $t->phone_number }}</td>
                                    <td class="px-2 py-1">KES {{ number_format($t->amount, 2) }}</td>
                                    <td class="px-2 py-1">{{ $t->gateway }}</td>
                                    <td class="px-2 py-1">{{ $t->status }}</td>
                                    <td class="px-2 py-1">{{ $t->mpesa_receipt_number ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
