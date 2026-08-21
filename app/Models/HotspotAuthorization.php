<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotspotAuthorization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'authorization_key',
        'wingufi_core_authorization_id',
        'router_id',
        'package_id',
        'user_id',
        'voucher_id',
        'payment_transaction_id',
        'client_identifier',
        'radius_username',
        'radius_password_encrypted',
        'client_mac',
        'status',
        'authorized_at',
        'starts_at',
        'expires_at',
        'session_timeout',
        'idle_timeout',
        'rate_limit',
        'simultaneous_sessions',
        'authorization_attributes',
        'revoke_reason',
    ];

    protected $casts = [
        'authorized_at' => 'datetime',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'authorization_attributes' => 'array',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function sessions()
    {
        return $this->hasMany(HotspotSession::class, 'authorization_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['authorized', 'active']) &&
            (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Decrypt the RADIUS password for the captive-portal handoff.
     */
    public function radiusPassword(): ?string
    {
        if (!$this->radius_password_encrypted) {
            return null;
        }

        return \Illuminate\Support\Facades\Crypt::decryptString($this->radius_password_encrypted);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['authorized', 'active'])
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
