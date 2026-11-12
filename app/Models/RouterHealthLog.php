<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouterHealthLog extends Model
{
    protected $fillable = [
        'router_id',
        'online',
        'cpu_load',
        'memory_percentage',
        'uptime_seconds',
        'rx_rate_mbps',
        'tx_rate_mbps',
        'active_sessions',
        'error',
        'checked_at',
    ];

    protected $casts = [
        'online' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
