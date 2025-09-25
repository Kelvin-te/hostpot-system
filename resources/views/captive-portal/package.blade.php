<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $package->name }} - Package Details</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
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

        .form-widget {
            margin-bottom: 30px;
            min-width: 350px;
            max-width: 350px;
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

        .package-desc {
            font-size: 80%;
            color: #6c757d;
            display: block;
            margin-top: 2px;
            line-height: 1;
            font-weight: 300;
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
<body>
    <div class="container">
        <div class="content">
            <!-- Logo Section -->
            <div style="width:100%; display:flex; justify-content:center; margin-bottom: 28px;">
                <img src="/wifi/logo.png" alt="Sterke Hotspot Logo" style="max-width:150px;">
            </div>

            <!-- Package Details Widget -->
            <div class="form-widget">
                <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.index') }}'">← Back to Packages</button>
                <!-- Package Details Table -->
                <table class="package-table">
                    <thead>
                        <tr>
                            <th colspan="2">{{ $package->name }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="package-name">Package Price</td>
                            <td class="package-price"><span style="font-size: 0.8em;">{{ config('app.currency') }}</span>{{ number_format($package->price, 0) }}</td>
                        </tr>
                        @if($package->bandwidth_download)
                        <tr>
                            <td class="package-name">Download Speed</td>
                            <td style="text-align: right; color: #444;">{{ intval($package->bandwidth_download) }}
                                @if($package->bandwidth_upload) 
                                    / {{ intval($package->bandwidth_upload) }}
                                @endif
                                Mbps
                            </td>
                        </tr>
                        @endif
                        @if($package->session_timeout)
                        <tr>
                            <td class="package-name">Session Duration</td>
                            <td style="text-align: right; color: #444;">{{ $package->session_timeout }} hours</td>
                        </tr>
                        @endif
                        @if($package->validity_days)
                        <tr>
                            <td class="package-name">Package Validity</td>
                            <td style="text-align: right; color: #444;">{{ $package->validity_days }} day{{ $package->validity_days > 1 ? 's' : '' }}</td>
                        </tr>
                        @endif
                        @if($package->shared_users && $package->shared_users > 1)
                        <tr>
                            <td class="package-name">Simultaneous Users</td>
                            <td style="text-align: right; color: #444;">{{ $package->shared_users }} users</td>
                        </tr>
                        @endif
                        @if($package->idle_timeout)
                        <tr>
                            <td class="package-name">Idle Timeout</td>
                            <td style="text-align: right; color: #444;">{{ $package->idle_timeout }} minutes</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <div class="flex justify-center">
                    <a href="{{ route('portal.purchase', $package) }}" class="btn bg-orange-600 w-full">💳 Subscribe</a>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help? Contact support',
        'fallbackMessage' => 'for assistance with your internet package'
    ])
</body>
</html>
