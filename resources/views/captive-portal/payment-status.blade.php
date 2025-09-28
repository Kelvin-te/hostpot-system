@extends('captive-portal.layout')
@section('title', 'Payment Status - ' . $package->name)

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; background: #ffffff88; border-radius: 5px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .status-header { text-align: center; margin-bottom: 20px; }
    .status-title { font-size: 1.2em; color: #2c3e50; margin-bottom: 8px; }
    .package-info { color: #0e770e; font-weight: bold; font-size: 1.1em; }
    .status-indicator { display: flex; align-items: center; justify-content: center; margin: 20px 0; padding: 15px; border-radius: 8px; font-weight: 500; }
    .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .status-completed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-failed { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .status-expired { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #0e770e; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 10px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .instructions { background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px; padding: 15px; margin: 20px 0; color: #2d5a2d; font-size: .9em; line-height: 1.4; }
    .instructions strong { color: #1a4a1a; display: block; margin-bottom: 8px; }
    .btn-orange { background: #ff8800; color: white; }
    .btn-orange:hover { background: #e67700; box-shadow: 0 8px 25px rgba(255,136,0,.4); }
    .back-btn { margin-bottom: 20px; min-width: fit-content; width: auto; }
    .countdown { text-align: center; margin: 10px 0; font-size: .9em; color: #6c757d; }
    @media (max-width: 480px) { .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
        <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.purchase', $package) }}'">← Back to Payment</button>
        <div class="status-header">
            <div class="status-title">Payment Status</div>
            <div class="package-info">{{ $package->name }} - <span style="font-size: .8em;">KES</span> {{ number_format($package->price, 0) }}</div>
        </div>

        @if (session('success'))
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #155724; font-size: .9em;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: .9em;">{{ session('error') }}</div>
        @endif

        <div id="statusIndicator" class="status-indicator status-pending">
            <div class="spinner"></div>
            <span id="statusMessage">Waiting for payment confirmation...</span>
        </div>

        <div id="countdown" class="countdown">Time remaining: <span id="timeRemaining">5:00</span></div>

        <div class="instructions">
            <strong>📱 Complete Your Payment:</strong>
            1. Check your phone for the M-Pesa payment prompt<br>
            2. Enter your M-Pesa PIN to confirm payment<br>
            3. Wait for the confirmation message<br>
            4. Your internet will be activated automatically
        </div>

        <div id="actionButtons">
            <button type="button" class="btn btn-orange" onclick="checkPaymentStatus()">🔄 Check Payment Status</button>
            <a href="{{ route('portal.index') }}" class="btn btn-light">← Back to Packages</a>
        </div>

        <div id="successActions" style="display:none;">
            <a href="{{ route('portal.status') }}" class="btn btn-green">🌐 Go to Internet Status</a>
            <a href="{{ route('portal.index') }}" class="btn btn-light">← Back to Packages</a>
        </div>
    </div>
@endsection

@section('after')
    @include('captive-portal.components.contact-section', [
        'message' => 'Having payment issues? Contact support',
        'fallbackMessage' => 'for assistance with your payment'
    ])
@endsection

@push('scripts')
<script>
let checkoutRequestId = '{{ $checkoutRequestId }}';
let statusCheckInterval, countdownInterval;
let timeRemaining = 300; // 5 minutes
let isPaymentCompleted = false;

document.addEventListener('DOMContentLoaded', function() {
    startStatusChecking();
    startCountdown();
});

function startStatusChecking() {
    checkPaymentStatus();
    statusCheckInterval = setInterval(checkPaymentStatus, 5000);
}

function startCountdown() {
    updateCountdownDisplay();
    countdownInterval = setInterval(function() {
        timeRemaining--;
        updateCountdownDisplay();
        if (timeRemaining <= 0) {
            clearInterval(countdownInterval);
            clearInterval(statusCheckInterval);
            handleTimeout();
        }
    }, 1000);
}

function updateCountdownDisplay() {
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    document.getElementById('timeRemaining').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

function checkPaymentStatus() {
    if (isPaymentCompleted) return;
    fetch('{{ route('portal.check-payment-status') }}', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ checkout_request_id: checkoutRequestId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateStatusDisplay(data.status, data.message);
            if (data.status === 'completed') {
                handlePaymentSuccess(data);
            } else if (data.status === 'failed') {
                handlePaymentFailure(data);
            } else if (data.is_expired) {
                handleTimeout();
            }
        } else {
            console.error('Status check failed:', data.message);
        }
    })
    .catch(err => console.error('Error checking payment status:', err));
}

function updateStatusDisplay(status, message) {
    const indicator = document.getElementById('statusIndicator');
    indicator.className = 'status-indicator';
    switch(status) {
        case 'completed':
            indicator.classList.add('status-completed');
            indicator.innerHTML = '<span style="margin-right:10px;">✅</span><span>' + message + '</span>';
            break;
        case 'failed':
            indicator.classList.add('status-failed');
            indicator.innerHTML = '<span style="margin-right:10px;">❌</span><span>' + message + '</span>';
            break;
        case 'expired':
            indicator.classList.add('status-expired');
            indicator.innerHTML = '<span style="margin-right:10px;">⏰</span><span>' + message + '</span>';
            break;
        default:
            indicator.classList.add('status-pending');
            indicator.innerHTML = '<div class="spinner"></div><span>' + message + '</span>';
    }
}

function handlePaymentSuccess(data) {
    isPaymentCompleted = true;
    clearInterval(statusCheckInterval);
    clearInterval(countdownInterval);
    document.getElementById('countdown').style.display = 'none';
    document.getElementById('actionButtons').style.display = 'none';
    document.getElementById('successActions').style.display = 'block';
    setTimeout(function(){ if (data.redirect_url) window.location.href = data.redirect_url; }, 3000);
}

function handlePaymentFailure(data) {
    clearInterval(statusCheckInterval);
    clearInterval(countdownInterval);
    document.getElementById('countdown').style.display = 'none';
    const actionButtons = document.getElementById('actionButtons');
    actionButtons.innerHTML = `
        <a href="{{ route('portal.purchase', $package) }}" class="btn btn-orange">🔄 Try Payment Again</a>
        <a href="{{ route('portal.index') }}" class="btn btn-light">← Back to Packages</a>
    `;
}

function handleTimeout() {
    clearInterval(statusCheckInterval);
    clearInterval(countdownInterval);
    updateStatusDisplay('expired', 'Payment request expired. Please try again.');
    document.getElementById('countdown').style.display = 'none';
    const actionButtons = document.getElementById('actionButtons');
    actionButtons.innerHTML = `
        <a href="{{ route('portal.purchase', $package) }}" class="btn btn-orange">🔄 Try Payment Again</a>
        <a href="{{ route('portal.index') }}" class="btn btn-light">← Back to Packages</a>
    `;
}

window.addEventListener('beforeunload', function() {
    if (statusCheckInterval) clearInterval(statusCheckInterval);
    if (countdownInterval) clearInterval(countdownInterval);
});
</script>
@endpush
