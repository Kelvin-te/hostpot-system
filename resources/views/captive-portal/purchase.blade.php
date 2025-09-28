<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase {{ $package->name }} - Payment</title>
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
            border-radius: 5px;
            padding: 20px;
        }

        .package-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .package-table th {
            background: #0e770e;
            color: white;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: center;
            font-weight: 500;
        }

        .package-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e1e8ed;
            color: #444;
        }

        .package-table tr:last-child td {
            border-bottom: none;
        }

        .package-name {
            color: #2c3e50;
            font-weight: 500;
        }

        .package-price {
            text-align: right;
            font-weight: 500;
            color: #0e770e !important;
            font-size: 1.2em;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
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

        .back-btn {
            margin-bottom: 20px;
            min-width: fit-content;
            width: auto;
        }


        .info-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
            color: #856404;
            font-size: 0.85em;
            line-height: 1.4;
        }

        .info-box strong {
            color: #856404;
            margin-bottom: 8px;
            display: block;
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

            <!-- Payment Widget -->
            <div class="form-widget">
                <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.index') }}'">← Back to Packages</button>
                <!-- Purchase Header -->
                <div style="margin-bottom: 20px;">
                    <div class="text-lg font-medium text-gray-900 mb-0">Complete Your Purchase</div>
                    <div class="text-green-800 font-bold text-lg">{{ $package->name }} - <span style="font-size: 0.8em;">KES</span> {{ number_format($package->price, 0) }}</div>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: 0.9em;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin: 8px 0 0 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Success Messages -->
                @if (session('success'))
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #155724; font-size: 0.9em;">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Info Messages -->
                @if (session('info'))
                    <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #0c5460; font-size: 0.9em;">
                        {{ session('info') }}
                    </div>
                @endif

                <!-- Error Messages -->
                @if (session('error'))
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: 0.9em;">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Payment Form -->
                <form action="{{ route('portal.process-payment', $package->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <x-input-label for="paymentPhone" :value="__('Mpesa Number')" />
                        <x-text-input id="paymentPhone" name="phone" type="tel" class="mt-1 block w-full" required 
                                     placeholder="e.g 0712345678" pattern="[0-9]{10,12}" />
                    </div>
                    
                    <div class="form-group">
                        <x-input-label for="customerName" :value="__('Full Name')" />
                        <x-text-input id="customerName" name="name" type="text" class="mt-1 block w-full" required 
                                     placeholder="Your full name" />
                    </div>

                    <div class="info-box">
                        <strong>📱 Payment Instructions:</strong>
                        1. Enter your mpesa phone number<br>
                        2. Click "Complete Payment"<br>
                        3. Follow the prompt on your phone<br>
                        4. Wait for confirmation message
                    </div>

                    <button type="submit" class="btn btn-green">
                        💳 Complete Payment
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- Contact Section -->
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help with payment? Contact support',
        'fallbackMessage' => 'for assistance with your payment'
    ])

    <script>
        // Add form validation and loading states
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function(e) {
                // Add loading state
                submitBtn.innerHTML = 'Processing Payment...';
                submitBtn.disabled = true;
                
                // You can add additional validation here
                const phone = document.getElementById('paymentPhone').value;
                const name = document.getElementById('customerName').value;
                
                if (!phone || !name) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    submitBtn.innerHTML = '💳 Complete Payment';
                    submitBtn.disabled = false;
                    return;
                }
            });
        });
    </script>
</body>
</html>
