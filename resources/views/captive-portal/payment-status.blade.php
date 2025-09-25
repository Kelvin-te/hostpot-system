<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - {{ $package->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-weight: 500;
        }

        body {
            color: #ffffff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-size: 1em;
            font-weight: 500;
            align-items: center;
            position: relative;
            overflow-x: hidden;
            background: linear-gradient(135deg, #eeffef 30%, #ffe0b1 100%);
        }

        .container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .form-widget {
            margin-bottom: 30px;
            min-width: 350px;
            max-width: 350px;
            background: #ffffff88;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .status-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .status-title {
            font-size: 1.2em;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .package-info {
            color: #0e770e;
            font-weight: bold;
            font-size: 1.1em;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-expired {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0e770e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .instructions {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #2d5a2d;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .instructions strong {
            color: #1a4a1a;
            display: block;
            margin-bottom: 8px;
        }

        .btn {
            padding: 10px 20px;
            min-width: 120px;
            border: none;
            font-size: 1em;
            border-radius: 5px;
            font-weight: 500 !important;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            width: 100%;
            margin-bottom: 10px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(0);
        }

        .btn-green {
            background: #0e770e;
            color: white;
        }

        .btn-green:hover {
            background: #084108;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }

        .btn-light {
            background: #ffffff;
            color: #0e770e;
            border: 1px solid #e1e8ed;
        }

        .btn-light:hover {
            background: #f8f9fa;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
        }

        .btn-orange {
            background: #ff8800;
            color: white;
        }

        .btn-orange:hover {
            background: #e67700;
            box-shadow: 0 8px 25px rgba(255, 136, 0, 0.4);
        }

        .back-btn {
            margin-bottom: 20px;
            min-width: fit-content;
            width: auto;
        }

        .countdown {
            text-align: center;
            margin: 10px 0;
            font-size: 0.9em;
            color: #6c757d;
        }

        /* Mobile styles */
        @media (max-width: 480px) {
            .container {
                padding: 15px;
                max-width: 380px;
            }
            
            .form-widget {
                min-width: 300px;
                max-width: 300px;
            }
        }
    </style>
</head>
<body class="font-sans">
    <div class="container">
        <div class="content">
            <!-- Logo Section -->
            <div style="width:100%; display:flex; justify-content:center; margin-bottom: 28px;">
                <img src="/wifi/logo.png" alt="Sterke Hotspot Logo" style="max-width:150px;">
            </div>

            <!-- Payment Status Widget -->
            <div class="form-widget">
                <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.purchase', $package) }}'">← Back to Payment</button>
                
                <!-- Status Header -->
                <div class="status-header">
                    <div class="status-title">Payment Status</div>
                    <div class="package-info">{{ $package->name }} - <span style="font-size: 0.8em;">KES</span> {{ number_format($package->price, 0) }}</div>
                </div>

                <!-- Success Messages -->
                @if (session('success'))
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #155724; font-size: 0.9em;">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Error Messages -->
                @if (session('error'))
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: 0.9em;">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Status Indicator -->
                <div id="statusIndicator" class="status-indicator status-pending">
                    <div class="spinner"></div>
                    <span id="statusMessage">Waiting for payment confirmation...</span>
                </div>

                <!-- Countdown Timer -->
                <div id="countdown" class="countdown">
                    Time remaining: <span id="timeRemaining">5:00</span>
                </div>

                <!-- Instructions -->
                <div class="instructions">
                    <strong>📱 Complete Your Payment:</strong>
                    1. Check your phone for the M-Pesa payment prompt<br>
                    2. Enter your M-Pesa PIN to confirm payment<br>
                    3. Wait for the confirmation message<br>
                    4. Your internet will be activated automatically
                </div>

                <!-- Action Buttons -->
                <div id="actionButtons">
                    <button type="button" class="btn btn-orange" onclick="checkPaymentStatus()">
                        🔄 Check Payment Status
                    </button>
                    <a href="{{ route('portal.index') }}" class="btn btn-light">
                        ← Back to Packages
                    </a>
                </div>

                <!-- Success Actions (Hidden initially) -->
                <div id="successActions" style="display: none;">
                    <a href="{{ route('portal.status') }}" class="btn btn-green">
                        🌐 Go to Internet Status
                    </a>
                    <a href="{{ route('portal.index') }}" class="btn btn-light">
                        ← Back to Packages
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    @include('captive-portal.components.contact-section', [
        'message' => 'Having payment issues? Contact support',
        'fallbackMessage' => 'for assistance with your payment'
    ])

    <script>
        let checkoutRequestId = '{{ $checkoutRequestId }}';
        let statusCheckInterval;
        let countdownInterval;
        let timeRemaining = 300; // 5 minutes in seconds
        let isPaymentCompleted = false;

        document.addEventListener('DOMContentLoaded', function() {
            startStatusChecking();
            startCountdown();
        });

        function startStatusChecking() {
            // Check immediately
            checkPaymentStatus();
            
            // Then check every 5 seconds
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
            document.getElementById('timeRemaining').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        function checkPaymentStatus() {
            if (isPaymentCompleted) return;

            fetch('{{ route('portal.check-payment-status') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    checkout_request_id: checkoutRequestId
                })
            })
            .then(response => response.json())
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
            .catch(error => {
                console.error('Error checking payment status:', error);
            });
        }

        function updateStatusDisplay(status, message) {
            const indicator = document.getElementById('statusIndicator');
            const messageEl = document.getElementById('statusMessage');
            
            // Remove all status classes
            indicator.className = 'status-indicator';
            
            // Add appropriate status class
            switch(status) {
                case 'completed':
                    indicator.classList.add('status-completed');
                    indicator.innerHTML = '<span style="margin-right: 10px;">✅</span><span>' + message + '</span>';
                    break;
                case 'failed':
                    indicator.classList.add('status-failed');
                    indicator.innerHTML = '<span style="margin-right: 10px;">❌</span><span>' + message + '</span>';
                    break;
                case 'expired':
                    indicator.classList.add('status-expired');
                    indicator.innerHTML = '<span style="margin-right: 10px;">⏰</span><span>' + message + '</span>';
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
            
            // Auto-redirect after 3 seconds
            setTimeout(function() {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            }, 3000);
        }

        function handlePaymentFailure(data) {
            clearInterval(statusCheckInterval);
            clearInterval(countdownInterval);
            
            document.getElementById('countdown').style.display = 'none';
            
            // Update action buttons for retry
            const actionButtons = document.getElementById('actionButtons');
            actionButtons.innerHTML = `
                <a href="{{ route('portal.purchase', $package) }}" class="btn btn-orange">
                    🔄 Try Payment Again
                </a>
                <a href="{{ route('portal.index') }}" class="btn btn-light">
                    ← Back to Packages
                </a>
            `;
        }

        function handleTimeout() {
            clearInterval(statusCheckInterval);
            clearInterval(countdownInterval);
            
            updateStatusDisplay('expired', 'Payment request expired. Please try again.');
            
            document.getElementById('countdown').style.display = 'none';
            
            // Update action buttons for retry
            const actionButtons = document.getElementById('actionButtons');
            actionButtons.innerHTML = `
                <a href="{{ route('portal.purchase', $package) }}" class="btn btn-orange">
                    🔄 Try Payment Again
                </a>
                <a href="{{ route('portal.index') }}" class="btn btn-light">
                    ← Back to Packages
                </a>
            `;
        }

        // Clean up intervals when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (statusCheckInterval) clearInterval(statusCheckInterval);
            if (countdownInterval) clearInterval(countdownInterval);
        });
    </script>
</body>
</html>
