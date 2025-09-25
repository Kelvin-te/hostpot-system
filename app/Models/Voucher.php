<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'package_id',
        'status',
        'used_at',
        'used_by_mac',
        'used_by_ip',
        'session_id',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the package associated with this voucher
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the session that used this voucher
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(HotspotSession::class, 'session_id');
    }

    /**
     * Check if voucher is valid and can be used
     */
    public function isValid(): bool
    {
        return $this->status === 'active' 
            && ($this->expires_at === null || $this->expires_at > now());
    }

    /**
     * Check if voucher is already used
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Check if voucher has expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' 
            || ($this->expires_at !== null && $this->expires_at <= now());
    }

    /**
     * Mark voucher as used
     */
    public function markAsUsed(string $macAddress, string $ipAddress, int $sessionId): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_by_mac' => $macAddress,
            'used_by_ip' => $ipAddress,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Generate a random voucher code
     */
    public static function generateCode(int $length = 12): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            // Add hyphens for readability (e.g., ABCD-EFGH-IJKL)
            if ($length >= 8) {
                $code = chunk_split($code, 4, '-');
                $code = rtrim($code, '-');
            }
            
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create vouchers in batch
     */
    public static function createBatch(int $packageId, int $quantity, ?Carbon $expiresAt = null): array
    {
        $vouchers = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            $vouchers[] = static::create([
                'code' => static::generateCode(),
                'package_id' => $packageId,
                'expires_at' => $expiresAt,
                'status' => 'active',
            ]);
        }
        
        return $vouchers;
    }

    /**
     * Scope for active vouchers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for used vouchers
     */
    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    /**
     * Scope for expired vouchers
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('expires_at')
                          ->where('expires_at', '<=', now());
                    });
    }

    /**
     * Find voucher by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }
}
