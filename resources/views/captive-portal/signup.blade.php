<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Get 500MB FREE</title>
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
        }

        .content {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            position: relative;
            z-index: 2;
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
            border-radius: 5px;
            font-weight: 500 !important;
            font-size: .85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
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
        }

        .btn-light:hover {
            background: #f8f9fa;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            min-width: auto;
            padding: 8px 16px;
            font-size: 0.9em;
        }

        .signup-promo {
            margin-top: -3rem;
            position: absolute;
            display: inline-block;
        }

        .promo-badge {
            position: absolute;
            font-size: .7em;
            font-weight: bold;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            min-width: max-content;
            padding: 4px 8px;
            border-radius: 4px 4px 4px 0;
            z-index: 10;
        }

        .promo-badge-top {
            font-size: .7em;
            top: -20px;
            color: #175c17;
            background: #ffd82a;
        }

        .promo-badge-top::after {
            border-left: 18px solid transparent;
            border-top: 8px solid #aa8206;
            content: "";
            position: absolute;
            left: 0px;
            top: 100%;
        }

        .promo-badge-bottom {
            padding: 5px 12px;
            top: 0;
            background: #c82333;
            left: 11px;
            letter-spacing: 0.5px;
            color: white;
        }

        .promo-badge-bottom::after {
            border-right: 18px solid transparent;
            border-top: 8px solid #8b1c29;
            content: "";
            position: absolute;
            left: 0px;
            top: 100%;
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px;
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
        <div class="">
            <!-- Logo Section -->
            <div style="width:100%; display:flex; justify-content:center; margin-bottom: 38px;">
                <img src="/wifi/logo.png" alt="Sterke Hotspot Logo" style="max-width:150px;">
            </div>

            <!-- Signup Widget -->
            <div class="form-widget">
                
                <div class="text-center mt-6">
                    <div class="signup-promo">
                        <div class="promo-badge promo-badge-top">SIGN UP & ENJOY</div>
                        <div class="promo-badge promo-badge-bottom">500 MB <span style="color: #ffd82a;">FREE</span></div>
                    </div>
                    <div class="text-sm text-gray-600 my-6">Sign up and enjoy free internet access</div>
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

                <form action="{{ route('portal.process-signup') }}" method="POST" id="signupForm">
                    @csrf
                    <div class="form-group">
                        <x-input-label for="signupPhone" :value="__('Phone Number')" />
                        <x-text-input id="signupPhone" name="phone" type="tel" class="mt-1 block w-full text-sm text-green-900" required 
                                     placeholder="Enter your phone number" pattern="[0-9]{10,12}" />
                        <div class="text-sm text-gray-400" style="margin-top: 5px;">
                            We'll send you a verification code
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <x-input-label for="signupName" :value="__('Full Name')" />
                        <x-text-input id="signupName" name="name" type="text" class="mt-1 block w-full text-sm text-green-900" required 
                                     placeholder="Your full name" />
                    </div>

                    <div class="form-group">
                        <x-input-label for="signupPassword" :value="__('Password')" />
                        <x-text-input id="signupPassword" name="password" type="password" class="mt-1 text-green-900 block w-full text-sm" required 
                                     placeholder="Create a password" minlength="6" />
                        <div class="text-sm text-gray-400" style="margin-top: 5px;">
                            Minimum 6 characters
                        </div>
                    </div>

                    <div style="background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #2d5a2d; font-size: 0.85em; line-height: 1.4;">
                        <strong style="color: #1a4a1a; margin-bottom: 8px; display: block;">🎉 Free 500MB Package Includes:</strong>
                        • 500MB of high-speed internet<br>
                        • Valid for 24 hours<br>
                        • Instant activation
                    </div>

                    <div class="flex gap-4 justify-end">
                        <button type="button" class="btn bg-red-500 uppercase" onclick="window.location.href='{{ route('portal.index') }}'">← Back</button>
                        <button type="submit" class="btn btn-green uppercase">Sign Up</button>
                    </div>
                </form>

                @if($errors->any())
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-top: 15px; color: #721c24; font-size: 0.9em;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin: 8px 0 0 20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('success'))
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-top: 15px; color: #155724; font-size: 0.9em;">
                        <strong>Success!</strong> {{ session('success') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
