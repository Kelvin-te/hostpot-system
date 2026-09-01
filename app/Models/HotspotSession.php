<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class HotspotSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'mac_address',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'package_id',
        'authorization_id',
        'user_id',
        'username',
        'mikrotik_username',
        'mikrotik_password',
        'mikrotik_profile',
        'session_id',
        'started_at',
        'expires_at',
        'bytes_uploaded',
        'bytes_downloaded',
        'bytes_total',
        'status',
        'mikrotik_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'mikrotik_data' => 'array',
        'bytes_uploaded' => 'integer',
        'bytes_downloaded' => 'integer',
        'bytes_total' => 'integer',
    ];

    /**
     * Get the package associated with this session
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the authorization associated with this session
     */
    public function authorization(): BelongsTo
    {
        return $this->belongsTo(HotspotAuthorization::class, 'authorization_id');
    }

    /**
     * Get the user associated with this session (if any)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the captive portal session that triggered this hotspot session.
     */
    public function captivePortalSession()
    {
        return $this->hasOne(CaptivePortalSession::class, 'hotspot_session_id');
    }

    /**
     * Check if session is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    /**
     * Check if session has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now() || $this->status === 'expired';
    }

    /**
     * Check if session was disconnected by the user but still has
     * valid time remaining — can be reactivated on reconnect.
     */
    public function isReconnectable(): bool
    {
        return $this->status === 'disconnected' && $this->expires_at > now();
    }

    /**
     * Get remaining time in session
     */
    public function getRemainingTime(): ?Carbon
    {
        if ($this->isExpired()) {
            return null;
        }

        return $this->expires_at;
    }

    /**
     * Get remaining data (if package has data limit)
     */
    public function getRemainingData(): ?int
    {
        if (!$this->package->rate_limit) {
            return null; // Unlimited package
        }

        // Extract data limit from rate_limit (e.g., "1GB", "500MB")
        $limit = $this->parseDataLimit($this->package->rate_limit);
        if (!$limit) {
            return null;
        }

        return max(0, $limit - $this->bytes_total);
    }

    /**
     * Parse data limit from rate_limit string
     */
    private function parseDataLimit(string $rateLimit): ?int
    {
        // Convert common formats to bytes
        $rateLimit = strtoupper(trim($rateLimit));
        
        if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB|KB)/', $rateLimit, $matches)) {
            $value = floatval($matches[1]);
            $unit = $matches[2];
            
            switch ($unit) {
                case 'GB':
                    return intval($value * 1024 * 1024 * 1024);
                case 'MB':
                    return intval($value * 1024 * 1024);
                case 'KB':
                    return intval($value * 1024);
            }
        }
        
        return null;
    }

    /**
     * Update session usage data
     */
    public function updateUsage(int $uploaded = 0, int $downloaded = 0): void
    {
        $this->increment('bytes_uploaded', $uploaded);
        $this->increment('bytes_downloaded', $downloaded);
        $this->increment('bytes_total', $uploaded + $downloaded);
        
        // Check if data limit exceeded
        $remainingData = $this->getRemainingData();
        if ($remainingData !== null && $remainingData <= 0) {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for disconnected sessions that still have valid time
     */
    public function scopeReconnectable($query)
    {
        return $query->where('status', 'disconnected')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<=', now())
              ->orWhere('status', 'expired');
        });
    }

    /**
     * Find active session by device fingerprint
     */
    public static function findActiveByDevice(string $deviceFingerprint): ?self
    {
        return static::active()
                    ->where('device_fingerprint', $deviceFingerprint)
                    ->first();
    }

    /**
     * Find active session by MAC address
     */
    public static function findActiveByMac(string $macAddress): ?self
    {
        return static::active()
                    ->where('mac_address', $macAddress)
                    ->first();
    }

    /**
     * Find reconnectable (disconnected but still valid) session by device fingerprint
     */
    public static function findReconnectableByDevice(string $deviceFingerprint): ?self
    {
        return static::reconnectable()
                    ->where('device_fingerprint', $deviceFingerprint)
                    ->latest()
                    ->first();
    }

    /**
     * Find reconnectable (disconnected but still valid) session by MAC address
     */
    public static function findReconnectableByMac(string $macAddress): ?self
    {
        return static::reconnectable()
                    ->where('mac_address', $macAddress)
                    ->latest()
                    ->first();
    }

    /**
     * Create a new session
     */
    public static function createSession(array $data): self
    {
        $sessionData = array_merge($data, [
            'session_id' => static::generateSessionId(),
            'started_at' => now(),
            'status' => 'active',
        ]);

        return static::create($sessionData);
    }

    /**
     * Generate unique session ID
     */
    private static function generateSessionId(): string
    {
        do {
            $sessionId = 'hs_' . bin2hex(random_bytes(16));
        } while (static::where('session_id', $sessionId)->exists());

        return $sessionId;
    }
}
