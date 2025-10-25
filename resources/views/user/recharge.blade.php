<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buy Data') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Info Banner -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Quick Recharge</h3>
                        <p class="mt-1 text-sm text-blue-700">Select a package below and pay via M-Pesa to get instant internet access.</p>
                    </div>
                </div>
            </div>

            <!-- Available Packages -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Available Packages</h3>
                    <p class="text-sm text-gray-600 mt-1">Choose the best package for your needs</p>
                </div>
                
                <div class="p-6">
                    @if($packages->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($packages as $package)
                                <div class="border-2 border-gray-200 rounded-lg p-6 hover:border-indigo-500 transition group relative overflow-hidden">
                                    <!-- Popular Badge -->
                                    @if($loop->index === 1)
                                        <div class="absolute top-0 right-0 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">
                                            POPULAR
                                        </div>
                                    @endif

                                    <div class="text-center">
                                        <!-- Package Name -->
                                        <h4 class="text-xl font-bold text-gray-900 mb-2">{{ $package->name }}</h4>
                                        
                                        <!-- Price -->
                                        <div class="mb-4">
                                            <span class="text-4xl font-bold text-indigo-600">KES {{ number_format($package->price, 0) }}</span>
                                        </div>

                                        <!-- Features -->
                                        <div class="space-y-3 mb-6">
                                            <div class="flex items-center justify-center text-sm text-gray-600">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span class="font-semibold">{{ $package->rate_limit }}</span>
                                            </div>
                                            <div class="flex items-center justify-center text-sm text-gray-600">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>Speed: {{ $package->bandwidth_download }}/{{ $package->bandwidth_upload }} Mbps</span>
                                            </div>
                                            <div class="flex items-center justify-center text-sm text-gray-600">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span>Valid for {{ $package->validity_days }} day{{ $package->validity_days > 1 ? 's' : '' }}</span>
                                            </div>
                                        </div>

                                        <!-- Buy Button -->
                                        <a href="{{ route('portal.purchase', $package->id) }}" 
                                           class="block w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition group-hover:scale-105 transform">
                                            Buy Now
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📦</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No packages available</h3>
                            <p class="text-gray-600">Please check back later.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- How to Use -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">How to Purchase</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 mb-3">
                                <span class="text-xl font-bold">1</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">Choose Package</h4>
                            <p class="text-sm text-gray-600">Select the data package that suits your needs</p>
                        </div>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 mb-3">
                                <span class="text-xl font-bold">2</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">Enter Phone Number</h4>
                            <p class="text-sm text-gray-600">Provide your M-Pesa phone number for payment</p>
                        </div>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 mb-3">
                                <span class="text-xl font-bold">3</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">Complete Payment</h4>
                            <p class="text-sm text-gray-600">Enter M-Pesa PIN to complete the transaction</p>
                        </div>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 mb-3">
                                <span class="text-xl font-bold">4</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-2">Get Connected</h4>
                            <p class="text-sm text-gray-600">Instant internet access upon successful payment</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Frequently Asked Questions</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">How long does activation take?</h4>
                            <p class="text-sm text-gray-600">Your internet access is activated instantly once payment is confirmed (usually within 30 seconds).</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">Can I use the data on multiple devices?</h4>
                            <p class="text-sm text-gray-600">Each package is tied to your registered phone number and can be used on one device at a time.</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">What happens if payment fails?</h4>
                            <p class="text-sm text-gray-600">If payment fails, no charges will be applied. You can try again or contact support for assistance.</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">Do unused data roll over?</h4>
                            <p class="text-sm text-gray-600">Data expires based on the package validity period. Unused data does not roll over to the next purchase.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
