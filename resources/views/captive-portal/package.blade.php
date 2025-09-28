@extends('captive-portal.layout')
@section('title', $package->name . ' - Package Details')

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; padding: 20px; }
    .package-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .package-table th { background: #0e770e; color: #fff; letter-spacing: .5px; padding: 10px 12px; text-align: center; font-weight: 500; }
    .package-table td { padding: 8px 12px; border-bottom: 1px solid #e1e8ed; color: #444; }
    .package-table tr:last-child td { border-bottom: none; }
    .package-name { color: #2c3e50; font-weight: 500; }
    .package-price { text-align: right; font-weight: 500; color: #0e770e !important; font-size: 1.2em; }
    .package-desc { font-size: 80%; color: #6c757d; display: block; margin-top: 2px; line-height: 1; font-weight: 300; }
    .btn { width: 100%; margin-bottom: 10px; }
    .btn-light { background: #fff; color: #0e770e; border: 1px solid #e1e8ed; }
    .back-btn { margin-bottom: 20px; min-width: fit-content; width: auto; }
    @media (max-width: 480px) { .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
        <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.index') }}'">← Back to Packages</button>
        <table class="package-table">
            <thead>
                <tr><th colspan="2">{{ $package->name }}</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="package-name">Package Price</td>
                    <td class="package-price"><span style="font-size: .8em;">{{ config('app.currency') }}</span> {{ number_format($package->price, 0) }}</td>
                </tr>
                @if($package->bandwidth_download)
                <tr>
                    <td class="package-name">Speed</td>
                    <td style="text-align: right; color: #444;">{{ intval($package->bandwidth_download) }}@if($package->bandwidth_upload) / {{ intval($package->bandwidth_upload) }}@endif Mbps</td>
                </tr>
                @endif
                @if($package->session_timeout)
                <tr>
                    <td class="package-name">Session</td>
                    <td style="text-align: right; color: #444;">{{ $package->session_timeout }} hours</td>
                </tr>
                @endif
                @if($package->validity_days)
                <tr>
                    <td class="package-name">Validity</td>
                    <td style="text-align: right; color: #444;">{{ $package->validity_days }} day{{ $package->validity_days > 1 ? 's' : '' }}</td>
                </tr>
                @endif
                @if($package->shared_users && $package->shared_users > 1)
                <tr>
                    <td class="package-name">Users</td>
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
        <div class="flex">
            <a href="{{ route('portal.purchase', $package) }}" class="btn bg-green-700 text-center">💳 Subscribe</a>
        </div>
    </div>
@endsection

@section('after')
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help? Contact support',
        'fallbackMessage' => 'for assistance with your internet package'
    ])
@endsection
