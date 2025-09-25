<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $routerName }} - Internet Packages</title>
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

        .packages {
            margin-bottom: 30px;
            min-width: 400px;
            max-width: 400px;
        }

        .package-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        .package-table tr:hover {
            background: #f8f9fa;
            cursor: pointer;
        }

        .package-table tr.selected {
            background: #e8f5e8 !important;
        }

        .package-name {
            color: #2c3e50;
        }

        .package-desc {
            font-size: 85%;
            color: #626d77;
            display: block;
            margin-top: 2px;
            line-height: 1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .package-price {
            text-align: right;
            font-weight: 500;
            color: #555 !important;
        }


        .btn {
            padding: 10px 20px;
            min-width: 120px;
            font-size: 1em;
            border-radius: 5px;
            font-weight: 500 !important;
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

        .contact-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            margin: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-content {
            padding: 2em 30px 0 30px;
            text-align: center;
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 0px;
            font-size: 0.9em;
        }

        .contact-content a {
            color: #0e770e;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-content a:hover {
            color: #ff8800;
            text-decoration: underline;
        }

        .signup-promo {
            position: relative;
            display: inline-block;
        }

        .promo-badge {
            position: absolute;
            font-weight: bold;
            top: -35px;
            left: 70%;
            transform: translateX(-50%);
            min-width: max-content;
            padding: 4px 8px;
            border-radius: 4px 4px 4px 0;
            z-index: 10;
        }

        .promo-badge::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0px;
            width: 0;
            height: 0;
        }

        .promo-badge-top {
            font-size: .7em;
            top: -62px;
            color: #175c17;
            background: #ffd82a;
        }

        .promo-badge-top::after {
            border-left: 18px solid transparent;
            border-top: 8px solid #aa8206;
        }

        .promo-badge-bottom {
            font-size: .8em;
            padding: 5px 12px;
            top: -43px;
            background: #c82333;
            left: 103px;
            letter-spacing: 0.5px;
            color: white;
        }

        .promo-badge-bottom::after {
            border-right: 8px solid transparent;
            border-top: 8px solid #c44315;
        }

        /* Mobile styles */
        @media (max-width: 480px) {
            .container {
                padding: 15px;
                max-width: 380px;
            }
            
            .packages {
                min-width: 300px;
            }
            
            .package-table th {
                padding: 11px 12px;
            }
            
            .package-table td {
                padding: 6px 12px;
            }
                        
            .contact-content {
                padding: 12px 15px;
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
            <!-- Packages Table -->
            <div class="packages mb-8" id="packages">
                @if($packages->count() > 0)
                    @php
                        // Group packages by type: unlimited (no rate limit) vs regular (with rate limit)
                        $unlimitedPackages = $packages->filter(function($package) {
                            return empty($package->rate_limit) || $package->rate_limit === null;
                        });
                        $regularPackages = $packages->filter(function($package) {
                            return !empty($package->rate_limit) && $package->rate_limit !== null;
                        });
                    @endphp
                    
                    <table class="package-table mb-8">
                        @if($unlimitedPackages->count() > 0)
                            <thead>
                                <tr>
                                    <th colspan="2">Unlimited Plans</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unlimitedPackages as $package)
                                    <tr data-package="{{ $package->id }}" 
                                        data-price="{{ $package->price }}" 
                                        data-duration="{{ $package->validity_days ?? 1 }}" 
                                        data-speed="{{ $package->bandwidth_download ?? 0 }}">
                                        <td class="package-name">
                                            {{ $package->name }}
                                            <span class="package-desc">
                                                @if($package->bandwidth_download)
                                                    {{ round($package->bandwidth_download) }} Mbps
                                                @endif
                                                <!-- @if($package->getValidityHours())
                                                    - {{ $package->getValidityDisplay() }}
                                                @endif -->
                                                @if($package->shared_users)
                                                    - {{ $package->shared_users }} device{{ $package->shared_users > 1 ? 's' : '' }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="package-price"><span style="font-size: 0.8em;">{{ config('app.currency') }}</span>{{ number_format($package->price, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endif
                        
                        @if($regularPackages->count() > 0)
                            <thead>
                                <tr>
                                    <th colspan="2">Regular Plans</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($regularPackages as $package)
                                    <tr data-package="{{ $package->id }}" 
                                        data-price="{{ $package->price }}" 
                                        data-duration="{{ $package->validity_days ?? 1 }}" 
                                        data-speed="{{ $package->bandwidth_download ?? 0 }}">
                                        <td class="package-name">
                                            {{ $package->name }}
                                            <!-- @if($package->bandwidth_download)
                                                <span class="package-desc">{{ $package->bandwidth_download }} Mbps</span>
                                            @endif -->
                                        </td>
                                        <td class="package-price"><span style="font-size: 0.8em;">{{ config('app.currency') }}</span>{{ number_format($package->price, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endif
                    </table>
                @else
                    <table class="package-table mb-8">
                        <thead>
                            <tr>
                                <th colspan="2">Internet Packages</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 20px; color: #6c757d;">
                                    No packages available at the moment.<br>
                                    Please contact support for assistance.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-4 justify-between w-full mt-8" id="actionButtons">
                <div class="signup-promo">
                    <div class="promo-badge promo-badge-top">SIGN UP & ENJOY</div>
                    <div class="promo-badge promo-badge-bottom">500 MB <span style="color: #ffd82a;">FREE</span></div>
                    <button type="button" class="btn btn-light border mr-1" onclick="window.location.href='{{ route('portal.signup') }}'"> Sign Up</button>
                </div>
                <button type="button" class="btn bg-orange-600 w-full" onclick="window.location.href='{{ route('portal.login') }}'"> Log In</button>
            </div>
            

        </div>
    </div>

    <!-- Contact Section -->
    <div class="contact-section">
        <div class="contact-content">
            Need help? Contact support 
            @if(isset($router) && $router->support_contact)
                via <a href="{{ $router->support_contact }}" target="_blank">{{ $router->support_contact }}</a>
            @else
                for assistance with your internet connection
            @endif
            @if(isset($router))
                <br><small>Connected through: {{ $router->name }} ({{ $router->ip }})</small>
            @endif
        </div>
    </div>

    <script>
        // Package selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to package rows
            document.querySelectorAll('.package-table tbody tr').forEach(row => {
                // Skip if this is the "no packages" row
                if (!row.dataset.package) return;
                
                row.addEventListener('click', function() {
                    // Get package ID and redirect directly to package page
                    const packageId = this.dataset.package;
                    window.location.href = `{{url('/')}}/portal/package/${packageId}`;
                });
            });

            // Handle signup form submission
            const signupForm = document.getElementById('signupForm');
            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = 'Creating Account...';
                    submitBtn.disabled = true;
                });
            }
        });
    </script>
</body>
</html>
