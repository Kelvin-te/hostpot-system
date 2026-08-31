@extends('captive-portal.layout')
@section('title', 'Sign Up - Get 500MB FREE')

@section('logo')
    @include('captive-portal.components.logo', ['margin' => '38px'])
@endsection

@push('styles')
    .content { width: 100%; max-width: 400px; padding: 20px; position: relative; z-index: 2; }
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; background: #ffffff88; border-radius: 5px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 24px; }
    .form-label { display: block; margin-bottom: .5rem; font-weight: 500; color: #374151; font-size: .875rem; }
    .back-btn { position: absolute; top: 20px; left: 20px; min-width: auto; padding: 8px 16px; font-size: .9em; }
    .signup-promo { margin-top: -3rem; position: absolute; display: inline-block; }
    .promo-badge { position: absolute; font-size: .7em; font-weight: bold; top: -25px; left: 50%; transform: translateX(-50%); min-width: max-content; padding: 4px 8px; border-radius: 4px 4px 4px 0; z-index: 10; }
    .promo-badge-top { font-size: .7em; top: -20px; color: #175c17; background: #ffd82a; }
    .promo-badge-top::after { border-left: 18px solid transparent; border-top: 8px solid #aa8206; content: ""; position: absolute; left: 0; top: 100%; }
    .promo-badge-bottom { padding: 5px 12px; top: 0; background: #c82333; left: 11px; letter-spacing: .5px; color: #fff; }
    .promo-badge-bottom::after { border-right: 18px solid transparent; border-top: 8px solid #8b1c29; content: ""; position: absolute; left: 0; top: 100%; }
    @media (max-width: 480px) { .content { padding: 15px; } .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
        <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.index') }}' + (window.location.search || '')">← Back to Packages</button>
        <div class="text-center mt-6">
            <div class="signup-promo">
                <div class="promo-badge promo-badge-top">SIGN UP & ENJOY</div>
                <div class="promo-badge promo-badge-bottom">500 MB <span style="color: #ffd82a;">FREE</span></div>
            </div>
            <div class="text-sm text-gray-600 my-6">Sign up and enjoy free internet access</div>
        </div>

        @if ($errors->any())
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: .9em;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #155724; font-size: .9em;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #0c5460; font-size: .9em;">
                {{ session('info') }}
            </div>
        @endif

        @if (session('error'))
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: .9em;">
                {{ session('error') }}
            </div>
        @endif

        @php
            $signupQueryParams = request()->query();
        @endphp
        <form action="{{ route('portal.process-signup', $signupQueryParams) }}" method="POST" id="signupForm">
            @csrf
            @if(isset($router) && $router)
                <input type="hidden" name="router" value="{{ $router->identifier }}">
            @elseif(request()->has('router'))
                <input type="hidden" name="router" value="{{ request()->query('router') }}">
            @endif
            <div class="mt-3">
                <x-input-label for="signupPhone" :value="__('Phone Number')" />
                <div class="flex gap-2">
                    <x-text-input id="signupPhone" name="phone" type="tel" class="mt-1 block w-full text-sm text-green-900" required placeholder="e.g., 0712345678" pattern="[0-9]{10,12}" />
                    <button type="button" id="sendOtpBtn" class="bg-indigo-600 text-white text-sm px-4 py-1 mt-1 rounded whitespace-nowrap" onclick="sendOtp()">
                        OTP
                    </button>
                </div>
                <div class="text-xs text-gray-500 mt-1">We'll send you a verification code</div>
            </div>

            <div class="mt-3" id="otpGroup" style="display: none;">
                <x-input-label for="signupOtp" :value="__('Verification Code (OTP)')" />
                <x-text-input id="signupOtp" name="otp" type="text" class="mt-1 block w-full text-sm text-green-900" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" />
                <div class="text-xs text-green-600 mt-1" id="otpSuccessMsg" style="display: none;">✓ OTP sent successfully! Check your SMS.</div>
            </div>

            <div class="mt-3">
                <x-input-label for="signupName" :value="__('Full Name')" />
                <x-text-input id="signupName" name="name" type="text" class="mt-1 block w-full text-sm text-green-900" required placeholder="Your full name" />
            </div>
            <div class="my-3">
                <x-input-label for="signupPassword" :value="__('Password')" />
                <x-text-input id="signupPassword" name="password" type="password" class="mt-1 text-green-900 block w-full text-sm" required placeholder="Create a password" minlength="6" />
                <div class="text-sm text-gray-400" style="margin-top: 5px;">Minimum 6 characters</div>
            </div>

            <div style="background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #2d5a2d; font-size: 0.85em; line-height: 1.4;">
                <strong style="color: #1a4a1a; margin-bottom: 8px; display: block;">🎉 Free 500MB Package Includes:</strong>
                • 500MB of high-speed internet<br>
                • Valid for 24 hours<br>
                • Instant activation
            </div>
            <div class="flex gap-4 justify-between">
                <button type="button" class="btn p-1 bg-red-500 text-white" onclick="window.location.href='{{ route('portal.index') }}'">← Back</button>
                <button type="submit" class="btn bg-green-700 text-white">Sign Up</button>
            </div>
        </form>

    </div>

    @push('scripts')
    <script>
        let otpSent = false;

        function sendOtp() {
            const phoneInput = document.getElementById('signupPhone');
            const phone = phoneInput.value.trim();
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            
            if (!phone || phone.length < 10) {
                alert('Please enter a valid phone number');
                return;
            }

            // Disable button and show loading
            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'Sending...';

            // Send OTP via AJAX
            fetch('{{ route('portal.signup.send-otp') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ phone: phone })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show OTP field
                    document.getElementById('otpGroup').style.display = 'block';
                    document.getElementById('otpSuccessMsg').style.display = 'block';
                    document.getElementById('signupOtp').required = true;
                    
                    // Update button
                    sendOtpBtn.textContent = 'Resend';
                    sendOtpBtn.disabled = false;
                    otpSent = true;
                    
                    // Focus on OTP input
                    document.getElementById('signupOtp').focus();
                } else {
                    alert(data.message || 'Failed to send OTP. Please try again.');
                    sendOtpBtn.textContent = 'OTP';
                    sendOtpBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please check your connection and try again.');
                sendOtpBtn.textContent = 'OTP';
                sendOtpBtn.disabled = false;
            });
        }

        // Validate form before submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            if (!otpSent) {
                e.preventDefault();
                alert('Please send and verify OTP first');
                return false;
            }
            
            const otp = document.getElementById('signupOtp').value;
            if (!otp || otp.length !== 6) {
                e.preventDefault();
                alert('Please enter the 6-digit verification code');
                return false;
            }
        });
    </script>
    @endpush
@endsection
