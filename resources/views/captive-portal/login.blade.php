@extends('captive-portal.layout')
@section('title', 'Login - Hotspot Portal')

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; background: #ffffff88; border-radius: 5px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 24px; }
    .form-label { display: block; margin-bottom: .5rem; font-weight: 500; color: #374151; font-size: .875rem; }
    .back-btn { margin-bottom: 20px; min-width: fit-content; width: auto; }
    .info-box { background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #2d5a2d; font-size: .85em; line-height: 1.4; }
    .info-box strong { color: #1a4a1a; margin-bottom: 8px; display: block; }
    .login-options { text-align: center; margin-bottom: 20px; }
    .login-options a { color: #0e770e; text-decoration: none; font-size: .9em; transition: color .3s ease; }
    .login-options a:hover { color: #ff8800; text-decoration: underline; }
    @media (max-width: 480px) { .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
        <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.index') }}'">← Back to Packages</button>
        <div style="text-align: center; margin-bottom: 20px;">
            <div class="text-lg font-medium text-gray-900" style="margin-bottom: 8px;">Login to Internet</div>
            <div class="text-sm text-gray-600">Use your voucher code or registered phone number</div>
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

        <form action="{{ route('portal.authenticate') }}" method="POST" id="loginForm">
            @csrf
            <div class="form-group">
                <x-input-label for="username" :value="__('Voucher Code or Phone Number')" />
                <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" required placeholder="Enter voucher code or phone number" />
                <div class="text-sm text-gray-600" style="margin-top: 5px;">Enter your voucher code or the phone number registered in your profile</div>
            </div>
            <div class="form-group" id="passwordGroup" style="display: none;">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="Enter your password" />
                <div class="text-sm text-gray-600" style="margin-top: 5px;">Password is required when using phone number</div>
            </div>
            <div class="info-box">
                <strong>🎫 Login Instructions:</strong>
                • For voucher codes: Enter the code directly<br>
                • For phone numbers: Enter your registered number and password<br>
                • Make sure you have an active package or valid voucher
            </div>
            <button type="submit" class="btn btn-green" id="loginSubmitBtn">🌐 Connect to Internet</button>
        </form>

        <div class="login-options">
            <p>Don't have a voucher? <a href="{{ route('portal.index') }}">Purchase a package</a></p>
        </div>
    </div>
@endsection

@section('after')
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help logging in? Contact support',
        'fallbackMessage' => 'for assistance with your login'
    ])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const passwordGroup = document.getElementById('passwordGroup');
    const passwordInput = document.getElementById('password');
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmitBtn');

    usernameInput.addEventListener('input', function() {
        const value = this.value.trim();
        const phonePattern = /^[\d\+\-\s\(\)]+$/;
        const isPhoneNumber = phonePattern.test(value) && value.length >= 10;
        if (isPhoneNumber) {
            passwordGroup.style.display = 'block';
            passwordInput.required = true;
        } else {
            passwordGroup.style.display = 'none';
            passwordInput.required = false;
            passwordInput.value = '';
        }
    });

    form.addEventListener('submit', function(e) {
        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();
        submitBtn.innerHTML = 'Connecting...';
        submitBtn.disabled = true;
        if (!username) {
            e.preventDefault();
            alert('Please enter your voucher code or phone number');
            submitBtn.innerHTML = '🌐 Connect to Internet';
            submitBtn.disabled = false;
            return;
        }
        const phonePattern = /^[\d\+\-\s\(\)]+$/;
        const isPhoneNumber = phonePattern.test(username) && username.length >= 10;
        if (isPhoneNumber && !password) {
            e.preventDefault();
            alert('Password is required when using phone number');
            submitBtn.innerHTML = '🌐 Connect to Internet';
            submitBtn.disabled = false;
            return;
        }
    });
});
</script>
@endpush
