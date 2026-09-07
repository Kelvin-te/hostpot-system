<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="text-green-600 mb-4">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="text-red-600 mb-4">{{ session('error') }}</div>
                    @endif
                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Customers') }}
                        </h2>
                    </div>
                    <div>
                        <livewire:customer-table/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
