<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">
            {{ __('My Sessions') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Active Sessions</div>
                    <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Total Sessions</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-6">
                    <div class="text-slate-600">Total Data Used</div>
                    <div class="text-2xl font-bold text-purple-600">{{ number_format($stats['data_used'] / (1024 * 1024 * 1024), 2) }} GB</div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <div class="flex gap-2">
                        <a href="{{ route('user.sessions', ['status' => 'all']) }}" 
                           class="px-4 py-2 rounded-md font-medium {{ $status === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            All Sessions
                        </a>
                        <a href="{{ route('user.sessions', ['status' => 'active']) }}" 
                           class="px-4 py-2 rounded-md font-medium {{ $status === 'active' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Active Only
                        </a>
                        <a href="{{ route('user.sessions', ['status' => 'expired']) }}" 
                           class="px-4 py-2 rounded-md font-medium {{ $status === 'expired' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Expired
                        </a>
                    </div>
                </div>
                
                <div class="p-6">
                    @if($sessions->count() > 0)
                        <div class="space-y-4">
                            @foreach($sessions as $session)
                                <div class="border {{ $session->isActive() ? 'border-green-200 bg-green-50' : 'border-slate-200 bg-white' }} rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-semibold text-slate-900">{{ $session->package->name }}</h4>
                                                @if($session->isActive())
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800">Ended</span>
                                                @endif
                                            </div>
                                            
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3 ">
                                                <div>
                                                    <div class="text-slate-500 text-xs">Started</div>
                                                    <div class="text-slate-900">{{ $session->started_at->format('M d, Y') }}</div>
                                                    <div class="text-slate-600 text-xs">{{ $session->started_at->format('H:i') }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-slate-500 text-xs">Expires</div>
                                                    <div class="text-slate-900">{{ $session->expires_at->format('M d, Y') }}</div>
                                                    <div class="text-slate-600 text-xs">{{ $session->expires_at->format('H:i') }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-slate-500 text-xs">Data Used</div>
                                                    <div class="text-slate-900 font-semibold">{{ number_format($session->bytes_total / (1024 * 1024), 2) }} MB</div>
                                                </div>
                                                <div>
                                                    <div class="text-slate-500 text-xs">Duration</div>
                                                    <div class="text-slate-900">{{ $session->started_at->diffForHumans($session->expires_at, true) }}</div>
                                                </div>
                                            </div>

                                            @php
                                                $dataUsedMB = $session->bytes_total / (1024 * 1024);
                                                $limitMB = 0;
                                                if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $session->package->data_cap, $matches)) {
                                                    $limitMB = $matches[2] === 'GB' ? $matches[1] * 1024 : $matches[1];
                                                }
                                                $percentage = $limitMB > 0 ? min(100, ($dataUsedMB / $limitMB) * 100) : 0;
                                            @endphp

                                            @if($limitMB > 0)
                                                <div class="mt-3">
                                                    <div class="flex justify-between text-xs text-slate-600 mb-1">
                                                        <span>Data Progress</span>
                                                        <span>{{ number_format($percentage, 1) }}%</span>
                                                    </div>
                                                    <div class="w-full bg-slate-200 rounded-full h-2">
                                                        <div class="h-2 rounded-full {{ $session->isActive() ? 'bg-green-600' : 'bg-slate-400' }}" style="width: {{ $percentage }}%"></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $sessions->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            
                            <h3 class="text-lg font-semibold text-slate-900 mb-2">No sessions found</h3>
                            <p class="text-slate-600 mb-4">You haven't used any internet sessions yet.</p>
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
