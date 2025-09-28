<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internet Status - Connected</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
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

        .status-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .status-table th {
            background: #0e770e;
            color: white;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: center;
            font-weight: 500;
        }

        .status-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e1e8ed;
            color: #444;
        }

        .status-table tr:last-child td {
            border-bottom: none;
        }

        .status-name {
            color: #2c3e50;
            font-weight: 500;
        }

        .status-value {
            text-align: right;
            font-weight: 500;
            color: #0e770e !important;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-expired {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            padding: 2em 30px;
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

        .refresh-timer {
            text-align: center;
            color: #6c757d;
            font-size: 0.8em;
            margin-top: 10px;
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
            
            .contact-content {
                padding: 12px 15px;
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

            <!-- Status Widget -->
            <div class="form-widget">
                <!-- Status Header -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 1.2em; font-weight: 500; color: #2c3e50; margin-bottom: 8px;">Internet Status</div>
                    <div class="status-indicator {{ $sessionStatus['is_active'] ? 'status-active' : 'status-expired' }}">
                        @if($sessionStatus['is_active'])
                            🟢 Connected & Active
                        @else
                            🔴 Session Expired
                        @endif
                    </div>
                </div>

                <!-- Session Details Table -->
                <table class="status-table">
                    <thead>
                        <tr>
                            <th colspan="2">Session Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="status-name">Package</td>
                            <td class="status-value">{{ $sessionStatus['package_name'] }}</td>
                        </tr>
                        <tr>
                            <td class="status-name">Status</td>
                            <td class="status-value" style="color: {{ $sessionStatus['is_active'] ? '#0e770e' : '#dc3545' }} !important;">
                                {{ ucfirst($sessionStatus['status']) }}
                            </td>
                        </tr>
                        @if($sessionStatus['remaining_time'])
                        <tr>
                            <td class="status-name">Ends in</td>
                            <td class="status-value">{{ $sessionStatus['remaining_time'] }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="status-name">Data Remaining</td>
                            <td class="status-value">{{ $sessionStatus['remaining_data'] }}</td>
                        </tr>
                        <tr>
                            <td class="status-name">Data Used</td>
                            <td style="text-align: right; color: #444;">{{ $sessionStatus['bytes_used'] }}</td>
                        </tr>
                        <tr>
                            <td class="status-name">Session Started</td>
                            <td class="status-value">{{ $activeSession->started_at->format('j M. g:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="status-name">Expires At</td>
                            <td class="status-value">{{ $activeSession->expires_at->format('j M. g:i A') }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Action Buttons -->
                @if($sessionStatus['is_active'])
                    <button type="button" class="btn btn-green" onclick="window.open('https://google.com', '_blank')">
                        🌐 Browse Internet
                    </button>
                    
                    <form action="{{ route('portal.disconnect') }}" method="POST" style="margin-top: 10px;">
                        @csrf
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to disconnect?')">
                            🚪 Disconnect
                        </button>
                    </form>
                @else
                    <button type="button" class="btn btn-light" onclick="window.location.href='{{ route('portal.index') }}'">
                        📦 Select New Package
                    </button>
                @endif

                <!-- Refresh Timer -->
                <div class="refresh-timer">
                    Page will refresh in <span id="countdown">30</span> seconds
                </div>
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
                for assistance with your connection
            @endif
            @if(isset($router))
                <br><small>Connected through: {{ $router->name }} ({{ $router->ip }})</small>
            @endif
        </div>
    </div>

    <script>
        // Auto-refresh page every 30 seconds to update session status
        let countdown = 30;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                window.location.reload();
            }
        }, 1000);

        // Clear timer if user navigates away
        window.addEventListener('beforeunload', () => {
            clearInterval(timer);
        });

        // Show success/error messages
        @if(session('success'))
            setTimeout(() => {
                alert('{{ session('success') }}');
            }, 500);
        @endif

        @if(session('error'))
            setTimeout(() => {
                alert('{{ session('error') }}');
            }, 500);
        @endif

        @if(session('info'))
            setTimeout(() => {
                alert('{{ session('info') }}');
            }, 500);
        @endif
    </script>
</body>
</html>
