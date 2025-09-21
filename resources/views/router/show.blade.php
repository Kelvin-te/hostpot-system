<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="alert alert-success text-green-600 mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger text-red-600 mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <div>
                            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                                {{ $router->name }}
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">MikroTik Router Details</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('router.edit', $router) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </a>
                            <a href="{{ route('router.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Quick Actions Row -->
                    <div class=" mb-6">
                        
                        <div class="pb-6">
                            <div class="grid-cols-4 md:grid-cols-4 gap-4 text-right">
                                <a href="{{ route('log', ['param' => $router]) }}" class="inline-flex items-center justify-center px-4 py-2 text-green-600 font-medium rounded text-sm transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    View Logs
                                </a>
                                <button class="inline-flex items-center justify-center px-4 py-2 text-yellow font-medium rounded text-sm transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Reboot
                                </button>
                                <button class="inline-flex items-center justify-center px-4 py-2 text-red-600 font-medium rounded text-sm transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Backup
                                </button>
                                <button class="inline-flex items-center justify-center px-4 py-2 text-blue-600 font-medium rounded text-sm transition duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Config
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Router Information Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                        <!-- Basic Information Card -->
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="flex items-center mb-3">
                                <div class="pe-2 text-blue-600 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 ml-2">Basic Information</h3>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Router Name:</span>
                                    <span class="text-gray-900 font-medium">{{ $router->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Location:</span>
                                    <span class="text-gray-900">{{ $router->location ?? 'Not specified' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">IP Address:</span>
                                    <span class="text-gray-900 font-mono bg-gray-100 px-1 py-0.5 rounded text-xs">{{ $router->ip }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Username:</span>
                                    <span class="text-gray-900">{{ $router->username }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Connection Status Card -->
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="flex items-center mb-3">
                                <div class="pe-2 text-green-600 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 ml-2">Connection Status</h3>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Status:</span>
                                    @if($connectionStatus)
                                        @if($connectionStatus['success'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3"></circle>
                                                </svg>
                                                Online
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="3"></circle>
                                                </svg>
                                                Offline
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"></circle>
                                            </svg>
                                            Unknown
                                        </span>
                                    @endif
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Ping:</span>
                                    <span class="text-gray-900">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Response Time:</span>
                                    <span class="text-gray-900">-</span>
                                </div>
                                <div class="mt-3">
                                    <button id="check-status-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-1.5 px-3 py-2 rounded text-sm transition duration-200">
                                        Check Connection Status
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Timestamps Card -->
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <div class="flex items-center mb-3">
                                <div class="pe-2 text-gray-600 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 ml-2">Timestamps</h3>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Added:</span>
                                    <span class="text-gray-900">{{ $router->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Updated:</span>
                                    <span class="text-gray-900">{{ $router->updated_at->format('M d, Y H:i') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Seen:</span>
                                    <span class="text-gray-900">
                                        @if($connectionStatus && $connectionStatus['success'])
                                            {{ now()->format('M d, Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Uptime:</span>
                                    <span class="text-gray-900">{{ $systemInfo['uptime'] ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MikroTik Specific Information -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                        <!-- System Information -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center">
                                    <div class="pe-2 text-red-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-800 ml-2">System Info</h3>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Model:</span>
                                        <span class="text-gray-900 font-medium">{{ $systemInfo['board-name'] ?? $systemInfo['platform'] ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">RouterOS:</span>
                                        <span class="text-gray-900 font-medium">{{ $systemInfo['version'] ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Architecture:</span>
                                        <span class="text-gray-900 font-medium">{{ $systemInfo['architecture-name'] ?? $systemInfo['cpu'] ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">CPU Load:</span>
                                        <span class="text-gray-900 font-medium">{{ isset($systemInfo['cpu-load']) ? $systemInfo['cpu-load'] . '%' : '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Memory:</span>
                                        <span class="text-gray-900 font-medium">
                                            @if(isset($systemInfo['total-memory']))
                                                {{ number_format($systemInfo['total-memory'] / 1024 / 1024, 1) }} MB
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <button id="refresh-system-btn" class="w-full mt-3 bg-red-600 hover:bg-red-700 text-white font-medium py-1.5 px-3 py-2 rounded text-sm transition duration-200">
                                    Refresh
                                </button>
                            </div>
                        </div>

                        <!-- Interface Status -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center">
                                    <div class="pe-2 text-purple-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-800 ml-2">Interfaces</h3>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="space-y-2 text-sm">
                                    @php
                                        $ethernetCount = 0;
                                        $wirelessCount = 0;
                                        $bridgeCount = 0;
                                        $vlanCount = 0;
                                        $pppoeCount = 0;
                                        
                                        if ($interfaces) {
                                            foreach ($interfaces as $interface) {
                                                $type = $interface['type'] ?? '';
                                                if (str_contains($type, 'ether')) $ethernetCount++;
                                                elseif (str_contains($type, 'wlan') || str_contains($type, 'wireless')) $wirelessCount++;
                                                elseif (str_contains($type, 'bridge')) $bridgeCount++;
                                                elseif (str_contains($type, 'vlan')) $vlanCount++;
                                                elseif (str_contains($type, 'pppoe')) $pppoeCount++;
                                            }
                                        }
                                    @endphp
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Ethernet:</span>
                                        <span class="text-gray-900 font-medium">{{ $ethernetCount ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Wireless:</span>
                                        <span class="text-gray-900 font-medium">{{ $wirelessCount ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Bridge:</span>
                                        <span class="text-gray-900 font-medium">{{ $bridgeCount ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">VLAN:</span>
                                        <span class="text-gray-900 font-medium">{{ $vlanCount ?: '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">PPPoE:</span>
                                        <span class="text-gray-900 font-medium">{{ $pppoeCount ?: '-' }}</span>
                                    </div>
                                </div>
                                <button id="load-interfaces-btn" class="w-full mt-3 bg-purple-600 hover:bg-purple-700 text-white font-medium py-1.5 px-3 py-2 rounded text-sm transition duration-200">
                                    Load
                                </button>
                            </div>
                        </div>

                        <!-- Resources -->
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center">
                                    <div class="pe-2 text-indigo-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-800 ml-2">Resources</h3>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">CPU Usage:</span>
                                        <span class="text-gray-900 font-medium">{{ isset($systemInfo['cpu-load']) ? $systemInfo['cpu-load'] . '%' : '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">RAM Usage:</span>
                                        <span class="text-gray-900 font-medium">
                                            @if(isset($systemInfo['total-memory']) && isset($systemInfo['free-memory']))
                                                @php
                                                    $usedMemory = $systemInfo['total-memory'] - $systemInfo['free-memory'];
                                                    $memoryPercent = round(($usedMemory / $systemInfo['total-memory']) * 100, 1);
                                                @endphp
                                                {{ $memoryPercent }}%
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Disk Usage:</span>
                                        <span class="text-gray-900 font-medium">
                                            @if(isset($systemInfo['total-hdd-space']) && isset($systemInfo['free-hdd-space']))
                                                @php
                                                    $usedDisk = $systemInfo['total-hdd-space'] - $systemInfo['free-hdd-space'];
                                                    $diskPercent = round(($usedDisk / $systemInfo['total-hdd-space']) * 100, 1);
                                                @endphp
                                                {{ $diskPercent }}%
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Uptime:</span>
                                        <span class="text-gray-900 font-medium">{{ $systemInfo['uptime'] ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Board Name:</span>
                                        <span class="text-gray-900 font-medium">{{ $systemInfo['board-name'] ?? '-' }}</span>
                                    </div>
                                </div>
                                <button class="w-full mt-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-1.5 px-3 py-2 rounded text-sm transition duration-200">
                                    Monitor
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="pe-2 text-indigo-600 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800 ml-3">Recent Activity</h3>
                                </div>
                                <a href="{{ route('log', ['param' => $router]) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    View All Logs →
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                <p class="text-lg font-medium">No recent activity</p>
                                <p class="text-sm">Router activity will appear here once monitoring is active</p>
                            </div>
                        </div>
                    </div>

                    <!-- Packages Information -->
                    @if($router->packages && $router->packages->count() > 0)
                    <div class="mt-8 bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center">
                                <div class="pe-2 text-teal-600 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800 ml-3">Associated Packages</h3>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($router->packages as $package)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                                    <h4 class="font-semibold text-gray-800">{{ $package->name }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ $package->description ?? 'No description' }}</p>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Added: {{ $package->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const routerId = {{ $router->id }};
            
            // Check Connection Status Button
            document.getElementById('check-status-btn').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.textContent;
                
                btn.disabled = true;
                btn.textContent = 'Checking...';
                btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                btn.classList.add('bg-gray-400');
                
                fetch(`{{ url('/') }}/router/${routerId}/test-connection`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btn.classList.remove('bg-gray-400');
                        btn.classList.add('bg-green-600', 'hover:bg-green-700');
                        
                        // Update connection status
                        updateConnectionStatus('online', 'Connected');
                        showNotification('Router is online and accessible!', 'success');
                    } else {
                        btn.classList.remove('bg-gray-400');
                        btn.classList.add('bg-red-600', 'hover:bg-red-700');
                        
                        // Update connection status
                        updateConnectionStatus('offline', data.message || 'Connection failed');
                        showNotification(data.message || 'Router connection failed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    btn.classList.remove('bg-gray-400');
                    btn.classList.add('bg-red-600', 'hover:bg-red-700');
                    
                    updateConnectionStatus('offline', 'Connection error');
                    showNotification('Connection check failed: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
            });
            
            // Refresh System Info Button
            document.getElementById('refresh-system-btn').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.textContent;
                
                btn.disabled = true;
                btn.textContent = 'Loading...';
                
                fetch(`{{ url('/') }}/router/${routerId}/system-info`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        updateSystemInfo(data.data);
                        showNotification('System information updated!', 'success');
                    } else {
                        showNotification(data.message || 'Failed to load system info', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to load system info: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
            });
            
            // Load Interfaces Button
            document.getElementById('load-interfaces-btn').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.textContent;
                
                btn.disabled = true;
                btn.textContent = 'Loading...';
                
                fetch(`{{ url('/') }}/router/${routerId}/interfaces`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        updateInterfaceInfo(data.data);
                        showNotification('Interface information updated!', 'success');
                    } else {
                        showNotification(data.message || 'Failed to load interfaces', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to load interfaces: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
            });
            
            function updateConnectionStatus(status, message) {
                const statusElement = document.querySelector('.inline-flex.items-center.px-2.py-0\\.5');
                if (statusElement) {
                    statusElement.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium';
                    
                    if (status === 'online') {
                        statusElement.classList.add('bg-green-100', 'text-green-800');
                        statusElement.innerHTML = '<svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg>Online';
                    } else {
                        statusElement.classList.add('bg-red-100', 'text-red-800');
                        statusElement.innerHTML = '<svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg>Offline';
                    }
                }
            }
            
            function updateSystemInfo(data) {
                // Update system info fields
                const fields = {
                    'Model': data['board-name'] || data.platform || '-',
                    'RouterOS': data.version || '-',
                    'Architecture': data['architecture-name'] || data.cpu || '-',
                    'CPU Load': data['cpu-load'] ? data['cpu-load'] + '%' : '-',
                    'Memory': data['total-memory'] ? formatBytes(data['total-memory']) : '-'
                };
                
                Object.keys(fields).forEach(key => {
                    const elements = document.querySelectorAll('#system-info .flex.justify-between');
                    elements.forEach(element => {
                        const label = element.querySelector('.text-gray-600');
                        if (label && label.textContent.includes(key)) {
                            const value = element.querySelector('.text-gray-900.font-medium');
                            if (value) {
                                value.textContent = fields[key];
                            }
                        }
                    });
                });
            }
            
            function updateInterfaceInfo(interfaces) {
                let ethernetCount = 0;
                let wirelessCount = 0;
                let bridgeCount = 0;
                let vlanCount = 0;
                let pppoeCount = 0;
                
                interfaces.forEach(interface => {
                    const type = interface.type || '';
                    if (type.includes('ether')) ethernetCount++;
                    else if (type.includes('wlan') || type.includes('wireless')) wirelessCount++;
                    else if (type.includes('bridge')) bridgeCount++;
                    else if (type.includes('vlan')) vlanCount++;
                    else if (type.includes('pppoe')) pppoeCount++;
                });
                
                // Update interface counts
                const interfaceFields = {
                    'Ethernet': ethernetCount || '-',
                    'Wireless': wirelessCount || '-',
                    'Bridge': bridgeCount || '-',
                    'VLAN': vlanCount || '-',
                    'PPPoE': pppoeCount || '-'
                };
                
                Object.keys(interfaceFields).forEach(key => {
                    const elements = document.querySelectorAll('.space-y-2 .flex.justify-between');
                    elements.forEach(element => {
                        const label = element.querySelector('.text-gray-600');
                        if (label && label.textContent.includes(key)) {
                            const value = element.querySelector('.text-gray-900.font-medium');
                            if (value) {
                                value.textContent = interfaceFields[key];
                            }
                        }
                    });
                });
            }
            
            function formatBytes(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            function showNotification(message, type) {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 max-w-md ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`;
                
                // Handle multiline messages
                if (message.includes('\n')) {
                    notification.innerHTML = message.replace(/\n/g, '<br>');
                } else {
                    notification.textContent = message;
                }
                
                document.body.appendChild(notification);
                
                // Remove after 8 seconds for detailed messages
                setTimeout(() => {
                    notification.remove();
                }, 8000);
            }
        });
    </script>
    @endpush
</x-app-layout>
