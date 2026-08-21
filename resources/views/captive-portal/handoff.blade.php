@extends('captive-portal.layout')

@section('title', 'Connecting to Internet')

@push('styles')
    .form-widget { margin-bottom: 30px; min-width: 350px; max-width: 400px; padding: 20px; text-align: center; }
    .spinner { display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #0e770e; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@endpush

@section('content')
    <div class="form-widget">
        <div class="spinner"></div>
        <h2 style="color: #2c3e50; margin-bottom: 12px;">Connecting you to the internet...</h2>
        <p style="color: #6c757d; margin-bottom: 24px;">Please wait while we finish the login process.</p>

        <form id="handoffForm" method="POST" action="{{ $linkLogin }}">
            <input type="hidden" name="username" value="{{ $username }}">
            <input type="hidden" name="password" value="{{ $password }}">
            @if($linkOrig)
                <input type="hidden" name="dst" value="{{ $linkOrig }}">
            @endif
            @if($chapId)
                <input type="hidden" name="chap-id" value="{{ $chapId }}">
            @endif
            @if($chapChallenge)
                <input type="hidden" name="chap-challenge" value="{{ $chapChallenge }}">
            @endif
            <button type="submit" class="btn btn-green">Click here if you are not redirected automatically</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('handoffForm').submit();
</script>
@endpush
