@extends('captive-portal.layout')
@section('title', 'Forgot Password')

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; background: #ffffff88; border-radius: 5px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 16px; }
    .help { font-size: .85em; color: #6c757d; margin-top: 6px; }
    @media (max-width: 480px) { .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
        <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.login') }}'">← Back to Login</button>
        <div style="text-align: center; margin: 10px 0 20px;">
            <div class="text-lg font-medium text-gray-900" style="margin-bottom: 8px;">Reset Your Password</div>
            <div class="text-sm text-gray-600">Enter your phone number to receive an OTP, then set a new password.</div>
        </div>

        @if ($errors->any())
            <div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;padding:12px;margin-bottom:16px;color:#721c24;font-size:.9em;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin:8px 0 0 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('error'))
            <div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;padding:12px;margin-bottom:16px;color:#721c24;font-size:.9em;">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;padding:12px;margin-bottom:16px;color:#155724;font-size:.9em;">{{ session('success') }}</div>
        @endif

        <form action="{{ route('portal.forgot-password.reset') }}" method="POST" id="resetForm">
            @csrf
            <div class="form-group">
                <x-input-label for="resetPhone" :value="__('Phone Number')" />
                <x-text-input id="resetPhone" name="phone" type="tel" class="mt-1 block w-full" required placeholder="e.g 0712345678" />
                <div class="help">We'll send an OTP to this number.</div>
            </div>

            <div class="form-group" style="display:flex; gap:8px; align-items:flex-end;">
                <div style="flex:1;">
                    <x-input-label for="resetOtp" :value="__('OTP Code')" />
                    <x-text-input id="resetOtp" name="otp" type="text" class="mt-1 block w-full" placeholder="6-digit code" maxlength="6" />
                </div>
                <button type="button" class="btn btn-light" onclick="sendResetOtp()" id="sendOtpBtn" style="min-width:160px;">Send OTP</button>
            </div>

            <div class="form-group">
                <x-input-label for="newPassword" :value="__('New Password')" />
                <x-text-input id="newPassword" name="password" type="password" class="mt-1 block w-full" required placeholder="Create a new password" minlength="6" />
            </div>
            <div class="form-group">
                <x-input-label for="confirmPassword" :value="__('Confirm Password')" />
                <x-text-input id="confirmPassword" name="password_confirmation" type="password" class="mt-1 block w-full" required placeholder="Confirm password" minlength="6" />
            </div>

            <button type="submit" class="btn btn-green" id="submitBtn">Reset Password</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
function sendResetOtp() {
    const btn = document.getElementById('sendOtpBtn');
    const phone = document.getElementById('resetPhone').value;
    if (!phone) { alert('Enter your phone number first'); return; }
    btn.disabled = true; btn.innerText = 'Sending...';
    fetch('{{ route('portal.forgot-password.send-otp') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ phone })
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || (data.success ? 'OTP sent' : 'Failed to send OTP'));
    })
    .catch(() => alert('Failed to send OTP'))
    .finally(() => { btn.disabled = false; btn.innerText = 'Send OTP'; });
}
</script>
@endpush
