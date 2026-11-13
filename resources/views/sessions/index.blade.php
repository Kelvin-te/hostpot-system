<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-900">
                {{ __('Active Hotspot Sessions') }}
            </h2>
            <div class="flex gap-2">
                <button onclick="syncSessions()" id="syncButton" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Sync with Router
                </button>
                <a href="{{ route('sessions.history') }}" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">
                    History
                </a>
                <a href="{{ route('sessions.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    Export
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Active Sessions</div>
                    <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Expired Sessions</div>
                    <div class="text-2xl font-bold text-red-600">{{ $stats['expired'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Sessions Today</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total_today'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Data Used Today</div>
                    <div class="text-2xl font-bold text-purple-600">{{ number_format($stats['data_used_today'] / (1024 * 1024 * 1024), 2) }} GB</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('sessions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="status" class="block font-medium text-slate-700 mb-1">Status</label>
                            <x-select name="status" id="status" class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
                            </x-select>
                        </div>
                        <div>
                            <label for="router_id" class="block font-medium text-slate-700 mb-1">Router</label>
                            <x-select name="router_id" id="router_id" class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">All Routers</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>
                                        {{ $router->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                        <div>
                            <label for="search" class="block font-medium text-slate-700 mb-1">Search</label>
                            <x-text-input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Username, MAC, IP..." />
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                                Filter
                            </button>
                            <a href="{{ route('sessions.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded-md hover:bg-slate-400 transition">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Auto-refresh toggle -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <label for="auto-refresh" class="flex items-center cursor-pointer">
                        <x-text-input type="checkbox" id="auto-refresh" />
                        <span class="ml-2 font-medium text-slate-700">Auto-refresh (30s)</span>
                    </label>
                </div>
                <div class="text-slate-600">
                    Last updated: <span id="last-updated">{{ now()->format('H:i:s') }}</span>
                </div>
            </div>

            <!-- Sessions Table -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6">
                    @if($sessions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Package</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Device</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Data Used</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200" id="sessions-table-body">
                                    @foreach($sessions as $session)
                                        <tr class="{{ $loop->even ? 'bg-white' : 'bg-slate-50' }} hover:bg-slate-100">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-slate-900">{{ $session->username ?? 'Guest' }}</div>
                                                <div class="text-xs text-slate-500">{{ $session->session_id }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-slate-900">{{ $session->package->name }}</div>
                                                <div class="text-xs text-slate-500">KES {{ number_format($session->package->price, 2) }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-slate-900">{{ $session->ip_address }}</div>
                                                <div class="text-xs text-slate-500">{{ $session->mac_address }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $totalMB = $session->bytes_total / (1024 * 1024);
                                                    $limitMB = 0;
                                                    if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $session->package->data_cap, $matches)) {
                                                        $limitMB = $matches[2] === 'GB' ? $matches[1] * 1024 : $matches[1];
                                                    }
                                                    $percentage = $limitMB > 0 ? min(100, ($totalMB / $limitMB) * 100) : 0;
                                                @endphp
                                                <div class="text-slate-900">{{ number_format($totalMB, 2) }} MB</div>
                                                @if($limitMB > 0)
                                                    <div class="w-full bg-slate-200 rounded-full h-2 mt-1">
                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-slate-900">{{ $session->started_at->format('M d, H:i') }}</div>
                                                <div class="text-xs text-slate-500">
                                                    @if($session->isActive())
                                                        Expires {{ $session->expires_at->diffForHumans() }}
                                                    @else
                                                        Expired {{ $session->expires_at->diffForHumans() }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($session->status === 'active' && $session->expires_at > now())
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Active
                                                    </span>
                                                @elseif($session->status === 'expired')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Expired
                                                    </span>
                                                @elseif($session->status === 'blocked')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Blocked
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">
                                                        {{ ucfirst($session->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('sessions.show', $session->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    View
                                                </a>
                                                @if($session->isActive())
                                                    <form action="{{ route('sessions.disconnect', $session->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this session?');">
                                                        @csrf
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            Disconnect
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $sessions->links() }}
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-500">
                            <p>No sessions found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let autoRefreshInterval;
        const autoRefreshCheckbox = document.getElementById('auto-refresh');
        
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });

        function startAutoRefresh() {
            autoRefreshInterval = setInterval(refreshSessionData, 30000); // 30 seconds
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }

        function refreshSessionData() {
            fetch('{{ route('sessions.live-data') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                        // You can update specific parts of the table here if needed
                        // For now, we'll just update the timestamp
                    }
                })
                .catch(error => console.error('Error refreshing session data:', error));
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', stopAutoRefresh);

        // Sync sessions with router
        function syncSessions() {
            const button = document.getElementById('syncButton');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = 'Syncing...';
            
            fetch('{{ route('sessions.sync') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = 'Sync Complete!\n\n';
                    message += `Synced: ${data.synced} sessions\n`;
                    if (data.created > 0) {
                        message += `Created: ${data.created} sessions on router\n`;
                    }
                    if (data.expired > 0) {
                        message += `Expired: ${data.expired} stale sessions\n`;
                    }
                    if (data.routers) {
                        message += `Routers: ${data.routers}`;
                    }
                    alert(message);
                    // Reload page to show updated sessions
                    window.location.reload();
                } else {
                    alert('Sync failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error syncing sessions:', error);
                alert('Sync failed: ' + error.message);
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
    </script>
    @endpush
</x-app-layout>
