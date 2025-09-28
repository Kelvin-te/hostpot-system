@extends('captive-portal.layout')
@section('title', $routerName . ' - Internet Packages')

@push('styles')
    .packages { margin-bottom: 30px; min-width: 400px; max-width: 400px; }
    .package-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .package-table th { background: #0e770e; color: #fff; letter-spacing: .5px; padding: 10px 12px; text-align: center; font-weight: 500; }
    .package-table td { padding: 8px 12px; border-bottom: 1px solid #e1e8ed; color: #444; }
    .package-table tr:last-child td { border-bottom: none; }
    .package-table tr:hover { background: #f8f9fa; cursor: pointer; }
    .package-table tr.selected { background: #e8f5e8 !important; }
    .package-name { color: #2c3e50; }
    .package-desc { font-size: 85%; color: #626d77; display: block; margin-top: 2px; line-height: 1; }
    .form-label { display: block; margin-bottom: .5rem; font-weight: 500; color: #374151; font-size: .875rem; }
    .package-price { text-align: right; font-weight: 500; color: #555 !important; }
    .signup-promo { position: relative; display: inline-block; }
    .promo-badge { position: absolute; font-weight: bold; top: -35px; left: 70%; transform: translateX(-50%); min-width: max-content; padding: 4px 8px; border-radius: 4px 4px 4px 0; z-index: 10; }
    .promo-badge::after { content: ''; position: absolute; bottom: -8px; left: 0; width: 0; height: 0; }
    .promo-badge-top { font-size: .7em; top: -62px; color: #175c17; background: #ffd82a; }
    .promo-badge-top::after { border-left: 18px solid transparent; border-top: 8px solid #aa8206; }
    .promo-badge-bottom { font-size: .8em; padding: 5px 12px; top: -43px; background: #c82333; left: 103px; letter-spacing: .5px; color: #fff; }
    .promo-badge-bottom::after { border-right: 8px solid transparent; border-top: 8px solid #c44315; }
    @media (max-width: 480px) {
        .packages { min-width: 300px; }
        .package-table th { padding: 11px 12px; }
        .package-table td { padding: 6px 12px; }
    }
@endpush

@section('content')
    <div class="packages mb-8" id="packages">
        @if($packages->count() > 0)
            @php
                $unlimitedPackages = $packages->filter(function($package) { return empty($package->rate_limit) || $package->rate_limit === null; });
                $regularPackages = $packages->filter(function($package) { return !empty($package->rate_limit) && $package->rate_limit !== null; });
            @endphp
            <table class="package-table mb-8">
                @if($unlimitedPackages->count() > 0)
                    <thead><tr><th colspan="2">Unlimited Plans</th></tr></thead>
                    <tbody>
                        @foreach($unlimitedPackages as $package)
                            <tr data-package="{{ $package->id }}" data-price="{{ $package->price }}" data-duration="{{ $package->validity_days ?? 1 }}" data-speed="{{ $package->bandwidth_download ?? 0 }}">
                                <td class="package-name">
                                    {{ $package->name }}
                                    <span class="package-desc">
                                        @if($package->bandwidth_download)
                                            {{ round($package->bandwidth_download) }} Mbps
                                        @endif
                                        @if($package->shared_users)
                                            - {{ $package->shared_users }} device{{ $package->shared_users > 1 ? 's' : '' }}
                                        @endif
                                    </span>
                                </td>
                                <td class="package-price"><span style="font-size: .8em;">{{ config('app.currency') }}</span> {{ number_format($package->price, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif

                @if($regularPackages->count() > 0)
                    <thead><tr><th colspan="2">Regular Plans</th></tr></thead>
                    <tbody>
                        @foreach($regularPackages as $package)
                            <tr data-package="{{ $package->id }}" data-price="{{ $package->price }}" data-duration="{{ $package->validity_days ?? 1 }}" data-speed="{{ $package->bandwidth_download ?? 0 }}">
                                <td class="package-name">{{ $package->name }}</td>
                                <td class="package-price"><span style="font-size: .8em;">{{ config('app.currency') }}</span> {{ number_format($package->price, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        @else
            <table class="package-table mb-8">
                <thead><tr><th colspan="2">Internet Packages</th></tr></thead>
                <tbody>
                    <tr>
                        <td colspan="2" style="text-align:center; padding:20px; color:#6c757d;">No packages available at the moment.<br>Please contact support for assistance.</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="flex gap-4 justify-between w-full mt-8" id="actionButtons">
        @if(!isset($hasUsedFreePackage) || !$hasUsedFreePackage)
            <div class="signup-promo">
                <div class="promo-badge promo-badge-top">SIGN UP & ENJOY</div>
                <div class="promo-badge promo-badge-bottom">500 MB <span style="color:#ffd82a;">FREE</span></div>
                <button type="button" class="btn btn-light border mr-1" onclick="window.location.href='{{ route('portal.signup') }}'"> Sign Up</button>
            </div>
            <button type="button" class="btn bg-orange-600 w-full" onclick="window.location.href='{{ route('portal.login') }}'"> Log In</button>
        @else
            <button type="button" class="btn bg-orange-600 w-full" onclick="window.location.href='{{ route('portal.login') }}'"> Log In</button>
        @endif
    </div>
@endsection

@section('after')
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help? Contact support',
        'fallbackMessage' => 'for assistance with your internet connection'
    ])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.package-table tbody tr').forEach(row => {
        if (!row.dataset.package) return;
        row.addEventListener('click', function() {
            const packageId = this.dataset.package;
            window.location.href = `{{ url('/') }}/portal/package/${packageId}`;
        });
    });
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
@endpush
