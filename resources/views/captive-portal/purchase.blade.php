<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase {{ $package->name }} - Confirmation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white/10 backdrop-blur-md border-b border-white/20">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-white mb-2">Package Purchase</h1>
                    <p class="text-white/80">Complete your internet package purchase</p>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 py-8">
            <div class="max-w-2xl mx-auto px-4">
                <!-- Purchase Confirmation Card -->
                <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 p-8 text-white text-center">
                        <div class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold mb-2">Purchase Initiated</h2>
                        <p class="text-green-100">Your package selection has been processed</p>
                    </div>

                    <!-- Package Summary -->
                    <div class="p-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-6">Package Summary</h3>
                        
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800">{{ $package->name }}</h4>
                                    <p class="text-gray-600">{{ $package->router->name }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-800">
                                        {{ config('app.currency') }}{{ number_format($package->price, 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-4 space-y-2">
                                @if($package->bandwidth_upload && $package->bandwidth_download)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Speed:</span>
                                        <span class="text-gray-800">{{ $package->bandwidth_upload }}/{{ $package->bandwidth_download }} Mbps</span>
                                    </div>
                                @endif
                                
                                @if($package->session_timeout)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Session Duration:</span>
                                        <span class="text-gray-800">{{ $package->session_timeout }} hours</span>
                                    </div>
                                @endif
                                
                                @if($package->validity_days)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Validity:</span>
                                        <span class="text-gray-800">{{ $package->validity_days }} days</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Options -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-800">Choose Payment Method</h4>
                            
                            <!-- Payment Methods -->
                            <div class="space-y-3">
                                <!-- Mobile Payment -->
                                <button class="w-full p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors duration-200 text-left">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 p-2 rounded-full mr-4">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-gray-800">Mobile Payment</h5>
                                            <p class="text-gray-600 text-sm">Pay using bKash, Nagad, or Rocket</p>
                                        </div>
                                    </div>
                                </button>

                                <!-- Voucher Code -->
                                <button class="w-full p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors duration-200 text-left">
                                    <div class="flex items-center">
                                        <div class="bg-green-100 p-2 rounded-full mr-4">
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-gray-800">Voucher Code</h5>
                                            <p class="text-gray-600 text-sm">Enter a prepaid voucher code</p>
                                        </div>
                                    </div>
                                </button>

                                <!-- Cash Payment -->
                                <button class="w-full p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors duration-200 text-left">
                                    <div class="flex items-center">
                                        <div class="bg-purple-100 p-2 rounded-full mr-4">
                                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-gray-800">Cash Payment</h5>
                                            <p class="text-gray-600 text-sm">Pay at the counter or to an agent</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Voucher Input Section (Initially Hidden) -->
                        <div id="voucher-section" class="hidden mt-6 p-4 bg-green-50 rounded-lg">
                            <h5 class="font-semibold text-gray-800 mb-3">Enter Voucher Code</h5>
                            <div class="flex space-x-3">
                                <input type="text" 
                                       id="voucher-code" 
                                       placeholder="Enter your voucher code" 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <button onclick="activateVoucher()" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold transition-colors duration-200">
                                    Activate
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-8 space-y-3">
                            <a href="{{ route('portal.package', $package) }}" 
                               class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 text-center block">
                                Back to Package Details
                            </a>
                            
                            <a href="{{ route('portal.index') }}" 
                               class="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors duration-200 text-center block">
                                Choose Different Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white/10 backdrop-blur-md border-t border-white/20 py-6">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <p class="text-white/80 text-sm">
                    Need help with payment? Contact our support team for assistance.
                </p>
            </div>
        </footer>
    </div>

    <!-- JavaScript for payment interactions -->
    <script>
        // Show voucher input when voucher payment is selected
        document.addEventListener('DOMContentLoaded', function() {
            const paymentButtons = document.querySelectorAll('button[class*="border-2"]');
            const voucherSection = document.getElementById('voucher-section');
            
            paymentButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    // Remove active state from all buttons
                    paymentButtons.forEach(btn => {
                        btn.classList.remove('border-blue-500', 'bg-blue-50', 'border-green-500', 'bg-green-50', 'border-purple-500', 'bg-purple-50');
                        btn.classList.add('border-gray-200');
                    });
                    
                    // Add active state to clicked button
                    if (index === 0) { // Mobile Payment
                        this.classList.add('border-blue-500', 'bg-blue-50');
                        voucherSection.classList.add('hidden');
                        // Redirect to mobile payment
                        setTimeout(() => {
                            alert('Redirecting to mobile payment gateway...');
                        }, 500);
                    } else if (index === 1) { // Voucher Code
                        this.classList.add('border-green-500', 'bg-green-50');
                        voucherSection.classList.remove('hidden');
                    } else if (index === 2) { // Cash Payment
                        this.classList.add('border-purple-500', 'bg-purple-50');
                        voucherSection.classList.add('hidden');
                        setTimeout(() => {
                            alert('Please visit our counter or contact an agent for cash payment.');
                        }, 500);
                    }
                });
            });
        });

        function activateVoucher() {
            const voucherCode = document.getElementById('voucher-code').value.trim();
            
            if (!voucherCode) {
                alert('Please enter a voucher code.');
                return;
            }
            
            // Here you would send the voucher code to your backend for validation
            // For now, we'll simulate the process
            if (voucherCode.length >= 8) {
                alert('Voucher activated successfully! You are now connected to the internet.');
                // Redirect to success page or close the captive portal
                window.location.href = 'https://google.com'; // Redirect to indicate successful connection
            } else {
                alert('Invalid voucher code. Please check and try again.');
            }
        }
    </script>
</body>
</html>
