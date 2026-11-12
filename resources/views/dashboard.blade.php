<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white shadow-sm">
                <h3 class="text-xl font-semibold">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</h3>
                <p class="mt-1 text-sm text-indigo-100">{{ auth()->user()->isAdmin() ? __('Here is what is happening across your network today.') : __('Manage your account, sessions and purchases.') }}</p>
            </div>

            @if(auth()->user()->isAdmin())
                @php
                    $todayRevenue = \App\Models\PaymentTransaction::whereDate('created_at', today())->where('status', 'completed')->sum('amount');
                    $totalRevenue = \App\Models\PaymentTransaction::where('status', 'completed')->sum('amount');
                    $activeSessions = \App\Models\HotspotSession::active()->count();
                    $totalUsers = \App\Models\User::count();
                    $routers = \App\Models\Router::all();
                @endphp

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-slate-500">{{ __("Today's Revenue") }}</div>
                                <div class="mt-1 text-2xl font-bold text-slate-900">KES {{ number_format($todayRevenue, 0) }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ __('Last 24 hours') }}</div>
                            </div>
                            <div class="rounded-lg bg-green-50 p-3"><x-icon name="card" class="h-6 w-6 text-green-600" /></div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-slate-500">{{ __('Active Sessions') }}</div>
                                <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($activeSessions) }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ __('Currently online') }}</div>
                            </div>
                            <div class="rounded-lg bg-blue-50 p-3"><x-icon name="globe" class="h-6 w-6 text-blue-600" /></div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-slate-500">{{ __('Total Users') }}</div>
                                <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalUsers) }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ __('Registered subscribers') }}</div>
                            </div>
                            <div class="rounded-lg bg-violet-50 p-3"><x-icon name="users" class="h-6 w-6 text-violet-600" /></div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-slate-500">{{ __('Total Revenue') }}</div>
                                <div class="mt-1 text-2xl font-bold text-slate-900">KES {{ number_format($totalRevenue, 0) }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ __('All time') }}</div>
                            </div>
                            <div class="rounded-lg bg-indigo-50 p-3"><x-icon name="chart" class="h-6 w-6 text-indigo-600" /></div>
                        </div>
                    </div>
                </div>

                {{-- Router status --}}
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="font-semibold text-slate-900">{{ __('Router Status') }}</h3>
                        <a href="{{ route('router.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Manage') }} →</a>
                    </div>
                    <div class="p-6">
                        @if($routers->count() > 0)
                            <div id="router-status-container" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($routers as $router)
                                    <div id="router-{{ $router->id }}" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <div class="mb-2 flex items-start justify-between">
                                            <div>
                                                <h4 class="font-semibold text-slate-900">{{ $router->name }}</h4>
                                                <p class="text-xs text-slate-500">{{ $router->ip_address }}</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ __('Checking…') }}</span>
                                        </div>
                                        <div class="mt-2 text-xs text-slate-400">{{ __('Loading status…') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-8 text-center">
                                <x-icon name="router" class="mx-auto mb-3 h-10 w-10 text-slate-300" />
                                <p class="mb-4 text-slate-500">{{ __('No routers configured') }}</p>
                                <a href="{{ route('router.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Add Router') }}</a>
                            </div>
                        @endif
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        fetch('{{ route('router.status.all') }}')
                            .then(r => r.json())
                            .then(data => {
                                if (data.success && data.routers) {
                                    Object.values(data.routers).forEach(router => {
                                        const el = document.getElementById('router-' + router.id);
                                        if (!el) return;
                                        const online = router.online;
                                        el.className = 'rounded-lg border p-4 ' + (online ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50');
                                        let html = `<div class="flex justify-between items-start mb-2">
                                            <div><h4 class="font-semibold text-slate-900">${router.name}</h4><p class="text-xs text-slate-500">${router.ip_address}</p></div>
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${online ? 'Online' : 'Offline'}</span>
                                        </div>`;
                                        if (online && router.data && router.data[0]) {
                                            const s = router.data[0];
                                            html += `<div class="mt-2 space-y-1 text-xs">
                                                <div class="flex justify-between"><span class="text-slate-500">Version:</span><span class="font-medium">${s['version'] || 'N/A'}</span></div>
                                                <div class="flex justify-between"><span class="text-slate-500">Uptime:</span><span class="font-medium">${s['uptime'] || 'N/A'}</span></div>
                                                <div class="flex justify-between"><span class="text-slate-500">CPU:</span><span class="font-medium">${s['cpu-load'] || '0'}%</span></div>
                                            </div>`;
                                        } else if (!online) {
                                            html += `<div class="mt-2 text-xs text-red-700">${router.message || ''}</div>`;
                                        }
                                        html += `<div class="mt-2 text-xs text-slate-500">Sync: <span class="${router.sync_status === 'synced' ? 'text-green-600' : 'text-yellow-600'}">${router.sync_status || 'unknown'}</span> · ${router.synced_count || 0}/${router.total_packages || 0}</div>`;
                                        el.innerHTML = html;
                                    });
                                }
                            })
                            .catch(() => {
                                const c = document.getElementById('router-status-container');
                                if (c) c.innerHTML = '<div class="col-span-full py-4 text-center text-sm text-red-600">Failed to load router status.</div>';
                            });
                    });
                </script>

                {{-- Quick actions --}}
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4"><h3 class="font-semibold text-slate-900">{{ __('Quick Actions') }}</h3></div>
                    <div class="grid grid-cols-2 gap-3 p-6 sm:grid-cols-4">
                        @foreach([
                            ['packages.index', 'package', 'Manage Packages', 'bg-blue-50 text-blue-600'],
                            ['sessions.index', 'globe', 'View Sessions', 'bg-green-50 text-green-600'],
                            ['reports.index', 'chart', 'Reports', 'bg-violet-50 text-violet-600'],
                            ['users.index', 'users', 'Manage Users', 'bg-indigo-50 text-indigo-600'],
                        ] as [$route, $icon, $label, $colors])
                            <a href="{{ route($route) }}" class="flex flex-col items-center gap-2 rounded-lg border border-slate-100 p-4 transition hover:border-indigo-200 hover:bg-slate-50">
                                <span class="rounded-lg p-2 {{ $colors }}"><x-icon :name="$icon" class="h-6 w-6" /></span>
                                <span class="text-sm font-medium text-slate-700">{{ __($label) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Recent activity --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <h3 class="font-semibold text-slate-900">{{ __('Recent Transactions') }}</h3>
                            <a href="{{ route('payment.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }} →</a>
                        </div>
                        <div class="p-6">
                            @php $recentTransactions = \App\Models\PaymentTransaction::latest()->take(5)->get(); @endphp
                            @forelse($recentTransactions as $t)
                                <div class="flex items-center justify-between border-b border-slate-50 py-2.5 last:border-0">
                                    <div>
                                        <div class="text-sm font-medium text-slate-900">{{ $t->phone_number }}</div>
                                        <div class="text-xs text-slate-400">{{ $t->created_at->format('M d, H:i') }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-slate-900">KES {{ number_format($t->amount, 0) }}</div>
                                        <span class="text-xs {{ $t->status === 'completed' ? 'text-green-600' : 'text-amber-600' }}">{{ $t->status === 'completed' ? 'Paid' : 'Pending' }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="py-4 text-center text-sm text-slate-400">{{ __('No recent transactions') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <h3 class="font-semibold text-slate-900">{{ __('Active Sessions') }}</h3>
                            <a href="{{ route('sessions.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }} →</a>
                        </div>
                        <div class="p-6">
                            @php $recentSessions = \App\Models\HotspotSession::active()->latest()->take(5)->get(); @endphp
                            @forelse($recentSessions as $s)
                                <div class="flex items-center justify-between border-b border-slate-50 py-2.5 last:border-0">
                                    <div>
                                        <div class="text-sm font-medium text-slate-900">{{ $s->package->name ?? 'Package' }}</div>
                                        <div class="text-xs text-slate-400">{{ $s->username }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-slate-900">{{ number_format($s->bytes_total / (1024 * 1024), 0) }} MB</div>
                                        <span class="text-xs text-green-600">{{ __('Active') }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="py-4 text-center text-sm text-slate-400">{{ __('No active sessions') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                @include('user.dashboard')
            @endif
        </div>
    </div>
</x-app-layout>
