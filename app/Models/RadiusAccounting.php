<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusAccounting extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'authorization_id',
        'hotspot_session_id',
        'username',
        'nas_ip_address',
        'nas_port',
        'nas_identifier',
        'session_id',
        'framed_ip_address',
        'calling_station_id',
        'called_station_id',
        'start_time',
        'last_update',
        'stop_time',
        'session_time',
        'input_octets',
        'output_octets',
        'terminate_cause',
        'status',
        'accounting_attributes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'last_update' => 'datetime',
        'stop_time' => 'datetime',
        'accounting_attributes' => 'array',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(HotspotAuthorization::class, 'authorization_id');
    }

    public function hotspotSession(): BelongsTo
    {
        return $this->belongsTo(HotspotSession::class, 'hotspot_session_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['start', 'interim-update']);
    }

    public function scopeStopped($query)
    {
        return $query->where('status', 'stop');
    }
}
