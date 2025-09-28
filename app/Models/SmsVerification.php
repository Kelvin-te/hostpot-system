<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SmsVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'otp',
        'verified_at',
        'expires_at',
        'attempts',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Check if OTP is valid and not expired
     */
    public function isValid(): bool
    {
        return $this->verified_at === null && 
               $this->expires_at > now() && 
               $this->attempts < 3;
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Check if OTP is already verified
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    /**
     * Increment attempt count
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Generate a new OTP
     */
    public static function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP verification record
     */
    public static function createForPhone(string $phone, string $ipAddress = null, string $userAgent = null): self
    {
        // Invalidate any existing unverified OTPs for this phone
        static::where('phone', $phone)
              ->whereNull('verified_at')
              ->update(['expires_at' => now()]);

        return static::create([
            'phone' => $phone,
            'otp' => static::generateOtp(),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Find valid OTP for phone and code
     */
    public static function findValidOtp(string $phone, string $otp): ?self
    {
        return static::where('phone', $phone)
                    ->where('otp', $otp)
                    ->whereNull('verified_at')
                    ->where('expires_at', '>', now())
                    ->where('attempts', '<', 3)
                    ->first();
    }

    /**
     * Clean up expired OTP records
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now()->subDays(7))->delete();
    }

    /**
     * Check if phone has recent verified OTP (within last 24 hours)
     */
    public static function hasRecentVerification(string $phone): bool
    {
        return static::where('phone', $phone)
                    ->whereNotNull('verified_at')
                    ->where('verified_at', '>', now()->subDay())
                    ->exists();
    }
}
