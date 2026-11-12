<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:bg-slate-800 dark:border-slate-700">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    @if(session('success'))
                        <div class="alert alert-success text-green-600">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">{{ session('error') }}</div>
                    @endif

                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                            {{ __('Static IPs') }}
                        </h2>
                        <a href="{{ route('static-ips.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            {{ __('Add IP') }}
                        </a>
                    </div>

                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-slate-50 dark:bg-slate-700 p-4 rounded"><div class="text-sm text-slate-500">{{ __('Total') }}</div><div class="text-2xl font-bold">{{ $stats['total'] }}</div></div>
                        <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded"><div class="text-sm text-slate-500">{{ __('Free') }}</div><div class="text-2xl font-bold text-green-600">{{ $stats['free'] }}</div></div>
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded"><div class="text-sm text-slate-500">{{ __('Assigned') }}</div><div class="text-2xl font-bold text-blue-600">{{ $stats['assigned'] }}</div></div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded"><div class="text-sm text-slate-500">{{ __('Reserved') }}</div><div class="text-2xl font-bold text-yellow-600">{{ $stats['reserved'] }}</div></div>
                    </div>

                    <form method="get" class="flex gap-4 mb-4">
                        <select name="router_id" class="rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">{{ __('All routers') }}</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="rounded-md border-slate-300 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach(['free', 'assigned', 'reserved'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded">{{ __('Filter') }}</button>
                    </form>

                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('IP') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('MAC') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Router') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Subscriber') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Package') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left">{{ __('Router sync') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($staticIps as $ip)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $ip->ip_address }}</td>
                                    <td class="px-4 py-2 font-mono">{{ $ip->mac_address ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $ip->router?->name }}</td>
                                    <td class="px-4 py-2">{{ $ip->user?->name ?? $ip->user?->phone ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $ip->package?->name ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded text-xs {{ $ip->status === 'assigned' ? 'bg-blue-100 text-blue-800' : ($ip->status === 'reserved' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                            {{ ucfirst($ip->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $ip->synced_to_router ? __('Yes') : __('No') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if(!$ip->synced_to_router && $ip->status !== 'free' && $ip->mac_address)
                                            <form method="post" action="{{ route('static-ips.sync', $ip) }}" class="inline">@csrf<button class="text-blue-600 hover:underline text-sm">{{ __('Sync') }}</button></form>
                                        @endif
                                        <form method="post" action="{{ route('static-ips.destroy', $ip) }}" class="inline" onsubmit="return confirm('{{ __('Remove this static IP?') }}')">@csrf @method('DELETE')<button class="text-red-600 hover:underline text-sm ml-2">{{ __('Delete') }}</button></form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">{{ __('No static IPs yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">{{ $staticIps->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
