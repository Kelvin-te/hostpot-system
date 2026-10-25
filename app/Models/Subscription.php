<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'plan',
        'router_limit',
        'price',
        'currency',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
        'last_payment_at',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_payment_at' => 'datetime',
    ];

    // Pricing tiers
    const PRICING_TIERS = [
        'basic' => [
            'router_limit' => 5,
            'price' => 800.00,
            'name' => 'Basic Plan'
        ],
        'standard' => [
            'router_limit' => 20,
            'price' => 2000.00,
            'name' => 'Standard Plan'
        ],
        'premium' => [
            'router_limit' => 50,
            'price' => 5000.00,
            'name' => 'Premium Plan'
        ],
        'custom' => [
            'router_limit' => 999,
            'price' => 0.00, // Custom pricing
            'name' => 'Custom Plan'
        ],
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('ends_at', '>', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
                    ->where('ends_at', '<=', now()->addDays($days))
                    ->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now())
                    ->orWhere('status', 'expired');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial')
                    ->where('trial_ends_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->ends_at < now();
    }

    public function isExpiringSoon($days = 7): bool
    {
        return $this->status === 'active' && 
               $this->ends_at <= now()->addDays($days) && 
               $this->ends_at > now();
    }

    public function getDaysRemaining(): int
    {
        return max(0, now()->diffInDays($this->ends_at));
    }

    public function getTrialDaysRemaining(): int
    {
        if (!$this->trial_ends_at) return 0;
        return max(0, now()->diffInDays($this->trial_ends_at));
    }

    public function getPlanName(): string
    {
        return self::PRICING_TIERS[$this->plan]['name'] ?? ucfirst($this->plan);
    }

    public function canAddRouters(int $currentCount): bool
    {
        return $currentCount < $this->router_limit;
    }

    public function getRoutersRemaining(): int
    {
        $currentCount = $this->company->routers()->count();
        return max(0, $this->router_limit - $currentCount);
    }

    public static function calculatePlan(int $routerCount): string
    {
        if ($routerCount <= 5) return 'basic';
        if ($routerCount <= 20) return 'standard';
        if ($routerCount <= 50) return 'premium';
        return 'custom';
    }

    public static function calculatePrice(string $plan, int $routerCount = 0): float
    {
        if ($plan === 'custom') {
            // Custom pricing - calculate based on router count
            return (float) ($routerCount * 100); // 100 KES per router for custom
        }
        return (float) (self::PRICING_TIERS[$plan]['price'] ?? 800.00);
    }

    public function extend(int $months = 1): bool
    {
        $newEndDate = $this->ends_at->copy()->addMonths($months);
        return $this->update([
            'ends_at' => $newEndDate,
            'status' => 'active',
        ]);
    }

    public function upgrade(string $newPlan): bool
    {
        $this->plan = $newPlan;
        $this->router_limit = self::PRICING_TIERS[$newPlan]['router_limit'];
        $this->price = self::calculatePrice($newPlan);
        return $this->save();
    }
}
