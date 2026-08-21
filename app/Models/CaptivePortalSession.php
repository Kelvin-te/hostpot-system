<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptivePortalSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'router_id',
        'client_mac',
        'client_ip',
        'link_login',
        'link_orig',
        'chap_id',
        'chap_challenge',
        'status',
        'package_id',
        'voucher_id',
        'payment_id',
        'authorization_id',
        'hotspot_session_id',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(HotspotAuthorization::class, 'authorization_id');
    }

    public function hotspotSession(): BelongsTo
    {
        return $this->belongsTo(HotspotSession::class, 'hotspot_session_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->orWhere('status', 'expired');
    }
}
