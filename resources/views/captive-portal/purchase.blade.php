@extends('captive-portal.layout')
@section('title', 'Purchase ' . $package->name . ' - Payment')

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 350px; border-radius: 5px; padding: 20px; }
    .package-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .package-table th { background: #0e770e; color: #fff; letter-spacing: .5px; padding: 10px 12px; text-align: center; font-weight: 500; }
    .package-table td { padding: 8px 12px; border-bottom: 1px solid #e1e8ed; color: #444; }
    .package-table tr:last-child td { border-bottom: none; }
    .package-name { color: #2c3e50; font-weight: 500; }
    .package-price { text-align: right; font-weight: 500; color: #0e770e !important; font-size: 1.2em; }
    .form-group { margin-bottom: 24px; }
    .form-label { display: block; margin-bottom: .5rem; font-weight: 500; color: #374151; font-size: .875rem; }
    .back-btn { margin-bottom: 20px; min-width: fit-content; width: auto; }
    .info-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #856404; font-size: .85em; line-height: 1.4; }
    .info-box strong { color: #856404; margin-bottom: 8px; display: block; }
    @media (max-width: 480px) { .form-widget { min-width: 300px; max-width: 300px; } }
@endpush

@section('content')
    <div class="form-widget">
        <button type="button" class="btn btn-light back-btn" onclick="window.location.href='{{ route('portal.index') }}'">← Back to Packages</button>
        <div style="margin-bottom: 20px;">
            <div class="text-lg font-medium text-gray-900 mb-0">Complete Your Purchase</div>
            <div class="text-green-800 font-bold text-lg">{{ $package->name }} - <span style="font-size: .8em;">KES</span> {{ number_format($package->price, 0) }}</div>
        </div>

        @if ($errors->any())
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: .9em;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #155724; font-size: .9em;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #0c5460; font-size: .9em;">
                {{ session('info') }}
            </div>
        @endif

        @if (session('error'))
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px; color: #721c24; font-size: .9em;">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('portal.process-payment', $package->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <x-input-label for="paymentPhone" :value="__('Mpesa Number')" />
                <x-text-input id="paymentPhone" name="phone" type="tel" class="mt-1 block w-full" required placeholder="e.g 0712345678" pattern="[0-9]{10,12}" />
            </div>
            <div class="form-group">
                <x-input-label for="customerName" :value="__('Full Name')" />
                <x-text-input id="customerName" name="name" type="text" class="mt-1 block w-full" required placeholder="Your full name" />
            </div>
            <div class="info-box">
                <strong>📱 Payment Instructions:</strong>
                1. Enter your mpesa phone number<br>
                2. Click "Complete Payment"<br>
                3. Follow the prompt on your phone<br>
                4. Wait for confirmation message
            </div>
            <button type="submit" class="btn btn-green">💳 Complete Payment</button>
        </form>
    </div>
@endsection

@section('after')
    @include('captive-portal.components.contact-section', [
        'message' => 'Need help with payment? Contact support',
        'fallbackMessage' => 'for assistance with your payment'
    ])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('button[type="submit"]');
    form.addEventListener('submit', function(e) {
        submitBtn.innerHTML = 'Processing Payment...';
        submitBtn.disabled = true;
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
@endpush
