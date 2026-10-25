<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function hotspotSessions()
    {
        return $this->hasMany(HotspotSession::class);
    }

    public function hotspotAuthorizations()
    {
        return $this->hasMany(HotspotAuthorization::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription && $this->subscription->isActive();
    }

    public function getSubscriptionStatusAttribute(): string
    {
        if (!$this->subscription) return 'No Subscription';
        return ucfirst($this->subscription->status);
    }
}
