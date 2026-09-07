<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="alert alert-success text-green-600">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert text-amber-700 bg-amber-50 border border-amber-200 rounded p-3">
                            {{ session('warning') }}
                        </div>
                    @endif
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Mikrotik Routers') }}
                        </h2>
                        <x-create-button url="{{ route('router.create') }}"></x-create-button>
                    </div>
                    <div>
                        <livewire:router-table/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        function formatRate(mbps) {
            if (mbps === null || mbps === undefined) return '—';
            if (mbps >= 1000) return (mbps / 1000).toFixed(1) + ' Gbps';
            return mbps.toFixed(1) + ' Mbps';
        }

        function formatUptime(seconds) {
            if (!seconds || seconds <= 0) return '—';
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            if (days > 0) return days + 'd ' + hours + 'h';
            if (hours > 0) return hours + 'h ' + mins + 'm';
            return mins + 'm';
        }

        function cpuColor(cpu) {
            if (cpu < 70) return 'text-green-600';
            if (cpu < 90) return 'text-amber-600';
            return 'text-red-600';
        }

        function updateRouterData() {
            fetch('{{ route("router.traffic.all") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.routers) return;
                Object.values(data.routers).forEach(function(router) {
                    const trafficEl = document.querySelector('.router-traffic[data-router-id="' + router.id + '"]');
                    const cpuEl = document.querySelector('.router-cpu[data-router-id="' + router.id + '"]');
                    const uptimeEl = document.querySelector('.router-uptime[data-router-id="' + router.id + '"]');

                    if (trafficEl) {
                        if (router.online) {
                            trafficEl.textContent = formatRate(router.rx_rate_mbps) + ' ↓ / ' + formatRate(router.tx_rate_mbps) + ' ↑';
                            trafficEl.className = 'router-traffic text-xs text-gray-700';
                        } else {
                            trafficEl.textContent = 'Offline';
                            trafficEl.className = 'router-traffic text-xs text-red-500';
                        }
                    }

                    if (cpuEl) {
                        if (router.cpu_load !== null) {
                            cpuEl.textContent = router.cpu_load + '%';
                            cpuEl.className = 'router-cpu text-xs font-semibold ' + cpuColor(router.cpu_load);
                        } else {
                            cpuEl.textContent = '—';
                        }
                    }

                    if (uptimeEl) {
                        uptimeEl.textContent = formatUptime(router.uptime_seconds);
                        if (router.online) {
                            uptimeEl.className = 'router-uptime text-xs text-gray-700';
                        }
                    }
                });
            })
            .catch(err => console.warn('Router traffic fetch failed:', err));
        }

        document.addEventListener('DOMContentLoaded', updateRouterData);
        setInterval(updateRouterData, 30000);
    })();
    </script>
    @endpush
</x-app-layout>
