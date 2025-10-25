<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Session Details') }}
            </h2>
            <a href="{{ route('sessions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                ← Back to Sessions
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Session Status Banner -->
            <div class="mb-6">
                @if($session->isActive())
                    <div class="bg-green-50 border-l-4 border-green-400 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700 font-medium">
                                        Session is active and will expire {{ $session->expires_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('sessions.disconnect', $session->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect this session?');">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                                    Disconnect Session
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-red-50 border-l-4 border-red-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 font-medium">
                                    Session has ended - Status: {{ ucfirst($session->status) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Session Information -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Session Information</h3>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Session ID</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $session->session_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Username</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->username ?? 'Guest' }}</dd>
                            </div>
                            @if($session->user)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Associated User</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="{{ route('users.show', $session->user->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $session->user->name }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if($session->status === 'active' && $session->expires_at > now())
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @elseif($session->status === 'expired')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Expired
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Started At</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->started_at->format('M d, Y H:i:s') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Expires At</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->expires_at->format('M d, Y H:i:s') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Duration</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->started_at->diffForHumans($session->expires_at, true) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Package Information -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Package Details</h3>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Package Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->package->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Price</dt>
                                <dd class="mt-1 text-sm text-gray-900">KES {{ number_format($session->package->price, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Data Limit</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->package->rate_limit }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Bandwidth</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    ↓ {{ $session->package->bandwidth_download }} Mbps / ↑ {{ $session->package->bandwidth_upload }} Mbps
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Validity</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $session->package->validity_days }} day(s)</dd>
                            </div>
                            @if($session->package->router)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Router</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="{{ route('router.show', $session->package->router->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $session->package->router->name }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Device Information -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Device Information</h3>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $session->ip_address ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">MAC Address</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $session->mac_address ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Device Fingerprint</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono text-xs break-all">{{ $session->device_fingerprint ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">User Agent</dt>
                                <dd class="mt-1 text-sm text-gray-900 break-words">{{ $session->user_agent ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Data Usage -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Data Usage</h3>
                    </div>
                    <div class="p-6">
                        @php
                            $uploadedMB = $session->bytes_uploaded / (1024 * 1024);
                            $downloadedMB = $session->bytes_downloaded / (1024 * 1024);
                            $totalMB = $session->bytes_total / (1024 * 1024);
                            $limitMB = 0;
                            if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $session->package->rate_limit, $matches)) {
                                $limitMB = $matches[2] === 'GB' ? $matches[1] * 1024 : $matches[1];
                            }
                            $percentage = $limitMB > 0 ? min(100, ($totalMB / $limitMB) * 100) : 0;
                        @endphp

                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-500">Total Usage</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($totalMB, 2) }} MB</span>
                                </div>
                                @if($limitMB > 0)
                                    <div class="w-full bg-gray-200 rounded-full h-4">
                                        <div class="bg-blue-600 h-4 rounded-full flex items-center justify-center text-xs text-white" style="width: {{ $percentage }}%">
                                            {{ number_format($percentage, 1) }}%
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ number_format($limitMB - $totalMB, 2) }} MB remaining
                                    </div>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-xs text-green-600 font-medium">Downloaded</div>
                                    <div class="text-xl font-bold text-green-700">{{ number_format($downloadedMB, 2) }} MB</div>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-xs text-blue-600 font-medium">Uploaded</div>
                                    <div class="text-xl font-bold text-blue-700">{{ number_format($uploadedMB, 2) }} MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MikroTik Data -->
            @if($session->mikrotik_username)
            <div class="bg-white overflow-hidden shadow-sm rounded-lg mt-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">MikroTik Details</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Username</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $session->mikrotik_username }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Profile</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $session->mikrotik_profile ?? 'N/A' }}</dd>
                        </div>
                        @if($liveData)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Live Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Online
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>

                    @if($liveData)
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Live Data from Router</h4>
                        <pre class="text-xs text-gray-600 overflow-x-auto">{{ json_encode($liveData, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
