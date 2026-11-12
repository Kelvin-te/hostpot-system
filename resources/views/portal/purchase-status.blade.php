<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 text-center">

                    @if($state === 'pending')
                        <div class="text-5xl mb-4">📱</div>
                        <h2 class="text-xl font-semibold mb-2">{{ __('Check your phone') }}</h2>
                        <p class="text-gray-600 mb-4">{{ __('Enter your M-Pesa PIN to complete payment of KES') }} {{ number_format($transaction->amount, 2) }}</p>
                        <div class="text-sm text-gray-500">{{ $transaction->checkout_request_id }}</div>
                        <div id="poll-status" class="mt-4 text-sm text-gray-500">{{ __('Waiting for payment...') }}</div>
                        <meta http-equiv="refresh" content="5">
                        <script>
                            setTimeout(() => location.reload(), 5000);
                        </script>
                    @elseif($state === 'complete')
                        <div class="text-5xl mb-4">✅</div>
                        <h2 class="text-xl font-semibold mb-2 text-green-600">{{ __('Payment successful') }}</h2>
                        @if(($connectionType ?? '') === 'pppoe')
                            <p class="text-gray-600 mb-3">{{ __('Your PPPoE service has been activated/extended.') }}</p>
                            @if(!empty($credentials['username']))
                                <div class="bg-gray-50 rounded p-3 text-left font-mono text-sm">
                                    {{ __('Username') }}: {{ $credentials['username'] }}
                                </div>
                            @endif
                            <p class="text-xs text-gray-500 mt-3">{{ __('View your password in My Settings.') }}</p>
                        @else
                            <p class="text-gray-600">{{ $message ?? __('Your package is active.') }}</p>
                        @endif
                        <a href="{{ route('user.dashboard') }}" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg">{{ __('Back to dashboard') }}</a>
                    @elseif($state === 'provision_failed')
                        <div class="text-5xl mb-4">⚠️</div>
                        <h2 class="text-xl font-semibold mb-2 text-yellow-600">{{ __('Payment received, activation failed') }}</h2>
                        <p class="text-gray-600">{{ $message ?? '' }}</p>
                        <p class="text-sm text-gray-500 mt-2">{{ __('Contact support — your payment was received.') }}</p>
                        <a href="{{ route('user.dashboard') }}" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg">{{ __('Back to dashboard') }}</a>
                    @else
                        <div class="text-5xl mb-4">❌</div>
                        <h2 class="text-xl font-semibold mb-2 text-red-600">{{ __('Payment failed or expired') }}</h2>
                        <p class="text-gray-600">{{ $transaction->result_description ?? __('The payment was not completed.') }}</p>
                        <a href="{{ route('user.recharge') }}" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg">{{ __('Try again') }}</a>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
