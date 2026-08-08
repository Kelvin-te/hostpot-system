<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Package
 *
 * @mixin Eloquent
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'price', 
        'router_id',
        'bandwidth_upload',
        'bandwidth_download', 
        'session_timeout',
        'idle_timeout',
        'shared_users',
        'rate_limit',
        'validity_days',
        'validity_hours',
        'validity_minutes',
        'is_active'
    ];

    public function router() {
        return $this->belongsTo(Router::class);
    }

    public function sessions() {
        return $this->hasMany(HotspotSession::class);
    }

    public function transactions() {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the effective validity period in minutes
     * Priority: validity_minutes > validity_hours > validity_days
     */
    public function getValidityMinutes()
    {
        if ($this->validity_minutes) {
            return $this->validity_minutes;
        }

        if ($this->validity_hours) {
            return $this->validity_hours * 60;
        }

        if ($this->validity_days) {
            return $this->validity_days * 24 * 60;
        }

        return null;
    }

    /**
     * Get the effective validity period in hours
     * Priority: validity_minutes > validity_hours > validity_days
     */
    public function getValidityHours()
    {
        $minutes = $this->getValidityMinutes();

        return $minutes ? $minutes / 60 : null;
    }

    /**
     * Get human readable validity period
     */
    public function getValidityDisplay()
    {
        $minutes = $this->getValidityMinutes();

        if (!$minutes) {
            return 'No expiry';
        }

        if ($minutes < 60) {
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        if ($minutes < 1440) {
            $hours = $minutes / 60;
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }

        $days = $minutes / 1440;
        return $days . ' day' . ($days > 1 ? 's' : '');
    }
}
