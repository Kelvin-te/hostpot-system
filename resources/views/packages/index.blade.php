<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Packages') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    ✓ {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    ✗ {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Internet Packages</h3>
                            <p class="text-sm text-gray-600 mt-1">Manage your hotspot data packages</p>
                        </div>
                        @if (auth()->user()->isAdmin())
                            <div class="flex space-x-2">
                                <a href="{{ route('packages.clone.form') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                    📋 Clone Package
                                </a>
                                <a href="{{ route('packages.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                                    ➕ Create New
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="p-6">
                    @if (auth()->user()->isAdmin())
                        <livewire:package-table/>
                    @else
                        <livewire:user-package-table/>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
