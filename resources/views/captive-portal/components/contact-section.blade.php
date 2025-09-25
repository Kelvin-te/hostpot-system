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

<style>
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

    /* Mobile styles for contact section */
    @media (max-width: 480px) {
        .contact-content {
            padding: 12px 15px;
        }
    }
</style>
