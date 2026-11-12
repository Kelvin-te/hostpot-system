<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 shadow sm:rounded-lg">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl">{{ __('Business Metrics') }}</h2>
                        <form method="get" class="flex gap-2 items-center">
                            <select name="days" class="rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100" onchange="this.form.submit()">
                                @foreach([7, 30, 90, 365] as $d)
                                    <option value="{{ $d }}" {{ $days === $d ? 'selected' : '' }}>{{ __('Last') }} {{ $d }} {{ __('days') }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded">
                            <div class="text-sm text-slate-500">{{ __('MRR (est.)') }}</div>
                            <div class="text-2xl font-bold text-blue-600">KES {{ number_format($mrr, 0) }}</div>
                            <div class="text-xs text-slate-500">{{ __('active subscribers × package price') }}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded">
                            <div class="text-sm text-slate-500">{{ __('Period Revenue') }}</div>
                            <div class="text-2xl font-bold text-green-600">KES {{ number_format($periodRevenue, 0) }}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/30 p-4 rounded">
                            <div class="text-sm text-slate-500">{{ __('ARPU') }}</div>
                            <div class="text-2xl font-bold text-purple-600">KES {{ number_format($arpu, 0) }}</div>
                            <div class="text-xs text-slate-500">{{ __('per active subscriber') }}</div>
                        </div>
                        <div class="bg-teal-50 dark:bg-teal-900/30 p-4 rounded">
                            <div class="text-sm text-slate-500">{{ __('Collection Rate') }}</div>
                            <div class="text-2xl font-bold text-teal-600">{{ $collectionRate !== null ? number_format($collectionRate, 1) . '%' : '—' }}</div>
                            <div class="text-xs text-slate-500">{{ __('collected / billed') }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="border p-4 rounded"><div class="text-sm text-slate-500">{{ __('Active Subscribers') }}</div><div class="text-xl font-bold">{{ $activeSubscribers }}</div></div>
                        <div class="border p-4 rounded"><div class="text-sm text-slate-500">{{ __('New (period)') }}</div><div class="text-xl font-bold text-green-600">+{{ $newSubscribers }}</div></div>
                        <div class="border p-4 rounded"><div class="text-sm text-slate-500">{{ __('Churned (period)') }}</div><div class="text-xl font-bold text-red-600">-{{ $churnedSubscribers }}</div></div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-2">{{ __('Revenue by Router') }}</h3>
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-slate-500"><th class="py-1">{{ __('Router') }}</th><th>{{ __('Revenue') }}</th><th>{{ __('Txns') }}</th></tr></thead>
                                <tbody>
                                    @forelse($revenueByRouter as $row)
                                        <tr class="border-t"><td class="py-1">{{ $row->router?->name ?? '#' . $row->router_id }}</td><td>KES {{ number_format($row->revenue, 0) }}</td><td>{{ $row->transactions }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="py-2 text-slate-500">{{ __('No revenue in period') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">{{ __('Top Plans (period sessions)') }}</h3>
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-slate-500"><th class="py-1">{{ __('Package') }}</th><th>{{ __('Price') }}</th><th>{{ __('Sessions') }}</th></tr></thead>
                                <tbody>
                                    @forelse($planMix as $pkg)
                                        <tr class="border-t"><td class="py-1">{{ $pkg->name }}</td><td>KES {{ number_format($pkg->price, 0) }}</td><td>{{ $pkg->sessions_count }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="py-2 text-slate-500">{{ __('No sessions in period') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
