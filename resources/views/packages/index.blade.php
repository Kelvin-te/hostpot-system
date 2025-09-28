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
                        <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                {{ __('Packages') }}
                            </h2>
                            @if (auth()->user()->isAdmin())
                                <div class="flex space-x-2">
                                    <a href="{{ route('packages.clone.form') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Clone Packages
                                    </a>
                                    <x-create-button url="{{ route('packages.create') }}"></x-create-button>
                                </div>
                            @endif
                        </div>
                        <div>
                            @if (auth()->user()->isAdmin())
                                <livewire:package-table/>
                            @endif
                            @if (auth()->user()->isUser())
                                <livewire:user-package-table/>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
