<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticIp extends Model
{
    protected $fillable = [
        'router_id',
        'ip_address',
        'mac_address',
        'user_id',
        'package_id',
        'status',
        'comment',
        'synced_to_router',
    ];

    protected $casts = [
        'synced_to_router' => 'boolean',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function scopeFree($query)
    {
        return $query->where('status', 'free');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }
}
