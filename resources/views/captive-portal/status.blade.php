@extends('captive-portal.layout')
@section('title', 'Internet Status')

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; background: #ffffff88; border-radius: 5px; padding: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .status-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .status-table th { background: #0e770e; color: #fff; letter-spacing: .5px; padding: 10px 12px; text-align: center; font-weight: 500; }
    .status-table td { padding: 8px 12px; border-bottom: 1px solid #e1e8ed; color: #444; }
    .status-table tr:last-child td { border-bottom: none; }
    .status-name { color: #2c3e50; font-weight: 500; }
    .status-value { text-align: right; font-weight: 500; color: #0e770e !important; }
    .btn-danger { background: #dc3545; color: #fff; }
    .btn-danger:hover { background: #c82333; box-shadow: 0 8px 25px rgba(220,53,69,.4); }
    .status-indicator { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 20px; font-size: .9em; font-weight: 500; margin-bottom: 20px; }
    .status-active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-expired { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .refresh-timer { text-align: center; color: #6c757d; font-size: .8em; margin-top: 10px; }
    @media (max-width: 480px) { .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
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

        <table class="status-table">
            <thead>
                <tr><th colspan="2">Session Details</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="status-name">Package</td>
                    <td class="status-value">{{ $sessionStatus['package_name'] }}</td>
                </tr>
                <tr>
                    <td class="status-name">Status</td>
                    <td class="status-value" style="color: {{ $sessionStatus['is_active'] ? '#0e770e' : '#dc3545' }} !important;">{{ ucfirst($sessionStatus['status']) }}</td>
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

        @if($sessionStatus['is_active'])
            <button type="button" class="btn btn-green" onclick="window.open('https://google.com', '_blank')">🌐 Browse Internet</button>
            <form action="{{ route('portal.disconnect') }}" method="POST" style="margin-top: 10px;">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to disconnect?')">🚪 Disconnect</button>
            </form>
        @else
            <button type="button" class="btn btn-light" onclick="window.location.href='{{ route('portal.index') }}'">📦 Select New Package</button>
        @endif

        <div class="refresh-timer">Page will refresh in <span id="countdown">30</span> seconds</div>
    </div>
@endsection

@section('after')
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help? Contact support',
        'fallbackMessage' => 'for assistance with your connection'
    ])
@endsection

@push('scripts')
<script>
let countdown = 30;
const countdownElement = document.getElementById('countdown');
const timer = setInterval(() => {
    countdown--;
    countdownElement.textContent = countdown;
    if (countdown <= 0) { window.location.reload(); }
}, 1000);
window.addEventListener('beforeunload', () => { clearInterval(timer); });
@if(session('success'))
    setTimeout(() => { alert(@json(session('success'))); }, 500);
@endif
@if(session('error'))
    setTimeout(() => { alert(@json(session('error'))); }, 500);
@endif
@if(session('info'))
    setTimeout(() => { alert(@json(session('info'))); }, 500);
@endif
</script>
@endpush
