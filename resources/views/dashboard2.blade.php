<x-app-layout>
    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Dashboard') }}
                        </h2>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <div class="border-l-2 bg-blue-50 border-blue-500 p-4">
                            <h2 class="text-l font-bold">{{ __('Current Package') }}</h2>
                            <p class="text-xl mt-2 font-bold">{{ $user->detail->package_name }}</p>
                        </div>
                        <div class="border-l-2 bg-blue-50 border-blue-500 p-4">
                            <h2 class="text-l font-bold">{{ __('Package Start') }}</h2>
                            <p class="text-xl mt-2 font-bold">{{ $user->detail->package_start }}</p>
                        </div>
                        <div class="border-l-2 bg-blue-50 border-blue-500 p-4">
                            <h2 class="text-l font-bold">{{ __('Current Due') }}</h2>
                            <p class="text-xl mt-2 font-bold">{{ $user->due_amount($user->id) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
