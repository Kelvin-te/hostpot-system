<!-- Contact Section Component -->
<div class="contact-section">
    <div class="contact-content">
        {{ $message ?? 'Need help? Contact support' }}
        @if(isset($router) && $router->support_contact)
            via <a href="{{ $router->support_contact }}" target="_blank">{{ $router->support_contact }}</a>
        @else
            {{ $fallbackMessage ?? 'for assistance' }}
        @endif
        @if(isset($router))
            <br><small>Connected through: {{ $router->name }} ({{ $router->ip }})</small>
        @endif
    </div>
</div>

