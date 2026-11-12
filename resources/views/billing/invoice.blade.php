<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800">{{ config('app.name', 'MatuNet') }}</h2>
                            <p class="text-slate-500 text-sm mt-1">Internet Service Provider</p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-semibold text-slate-800">{{ $invoiceNumber }}</div>
                            <div class="text-sm text-slate-500">{{ $transaction->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <div class="border-t border-b border-slate-200 py-4 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs text-slate-500 uppercase mb-1">Billed To</div>
                                <div class="font-semibold">{{ $transaction->user?->name ?? $transaction->phone_number }}</div>
                                <div class="text-sm text-slate-600">{{ $transaction->phone_number }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-slate-500 uppercase mb-1">Payment Method</div>
                                <div class="font-semibold capitalize">{{ $transaction->gateway }}</div>
                                @if($transaction->mpesa_receipt_number)
                                    <div class="text-sm text-slate-600">Receipt: {{ $transaction->mpesa_receipt_number }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <table class="w-full text-sm mb-6">
                        <thead>
                            <tr class="border-b text-left text-slate-500">
                                <th class="py-2">Description</th>
                                <th class="py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="py-3">
                                    <div class="font-medium">{{ $transaction->package?->name ?? 'Package Purchase' }}</div>
                                    @if($transaction->router)
                                        <div class="text-xs text-slate-500">Router: {{ $transaction->router->name }}</div>
                                    @endif
                                    <div class="text-xs text-slate-500 capitalize">Type: {{ $transaction->type }}</div>
                                </td>
                                <td class="py-3 text-right font-semibold">
                                    {{ config('app.currency', 'KSh') }} {{ number_format((float) $transaction->amount, 2) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="py-3 text-right font-bold">Total</td>
                                <td class="py-3 text-right font-bold text-lg">
                                    {{ config('app.currency', 'KSh') }} {{ number_format((float) $transaction->amount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="text-center text-xs text-slate-400 mt-8">
                        <p>This is a system-generated invoice. Payment status: <span class="font-semibold capitalize">{{ $transaction->status }}</span></p>
                        <p>Thank you for choosing {{ config('app.name', 'MatuNet') }}!</p>
                    </div>

                    <div class="text-center mt-6">
                        <button onclick="window.print()" class="px-6 py-2 bg-slate-800 text-white rounded hover:bg-slate-700">Print Invoice</button>
                        <a href="{{ route('billing.index') }}" class="px-6 py-2 bg-slate-200 text-slate-700 rounded hover:bg-slate-300 ml-2">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
