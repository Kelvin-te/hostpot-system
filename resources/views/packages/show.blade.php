<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Package Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Package Overview Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                            <p class="text-sm text-gray-600 mt-1">Complete package information and settings</p>
                        </div>
                        @if(auth()->user()->isAdmin())
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('packages.edit', $package->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                    ✏️ Edit Package
                                </a>
                                <a href="{{ route('packages.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                                    ← Back
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <span class="text-2xl mr-2">📦</span> Basic Information
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Package Name:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Price:</span>
                                    <span class="font-semibold text-green-600">KES {{ number_format($package->price, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Router:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->router->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $package->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bandwidth Settings -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-6 rounded-lg">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <span class="text-2xl mr-2">📡</span> Bandwidth Settings
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Upload Speed:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->bandwidth_upload ?? 'Unlimited' }} Mbps</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Download Speed:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->bandwidth_download ?? 'Unlimited' }} Mbps</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Rate Limit:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->rate_limit ?? 'Not set' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Time Settings -->
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <span class="text-2xl mr-2">⏰</span> Time Limits
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Session Timeout:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->session_timeout ?? 'N/A' }} hours</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Idle Timeout:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->idle_timeout ?? 'N/A' }} minutes</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Validity:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->getValidityDisplay() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <span class="text-2xl mr-2">⚙️</span> Advanced Settings
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shared Users:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->shared_users ?? 1 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Updated:</span>
                                    <span class="font-semibold text-gray-900">{{ $package->updated_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Usage Statistics</h3>
                </div>
                <div class="p-6">
                    @php
                        $totalSessions = $package->sessions()->count();
                        $activeSessions = $package->sessions()->active()->count();
                        $totalRevenue = \App\Models\PaymentTransaction::where('package_id', $package->id)
                            ->where('status', 'completed')
                            ->sum('amount');
                    @endphp
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Total Sessions</div>
                            <div class="text-3xl font-bold text-blue-600">{{ number_format($totalSessions) }}</div>
                            <div class="text-xs text-gray-500 mt-1">All time</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Active Sessions</div>
                            <div class="text-3xl font-bold text-green-600">{{ number_format($activeSessions) }}</div>
                            <div class="text-xs text-gray-500 mt-1">Currently online</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Total Revenue</div>
                            <div class="text-3xl font-bold text-purple-600">KES {{ number_format($totalRevenue, 0) }}</div>
                            <div class="text-xs text-gray-500 mt-1">All time</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
