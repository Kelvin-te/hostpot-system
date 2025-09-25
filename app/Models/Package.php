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
        'validity_hours'
    ];

    public function router() {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get the effective validity period in hours
     * Priority: validity_hours > validity_days
     */
    public function getValidityHours()
    {
        if ($this->validity_hours) {
            return $this->validity_hours;
        }
        
        if ($this->validity_days) {
            return $this->validity_days * 24;
        }
        
        return null;
    }

    /**
     * Get human readable validity period
     */
    public function getValidityDisplay()
    {
        $hours = $this->getValidityHours();
        
        if (!$hours) {
            return 'No expiry';
        }
        
        if ($hours < 24) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        
        $days = $hours / 24;
        return $days . ' day' . ($days > 1 ? 's' : '');
    }
}
