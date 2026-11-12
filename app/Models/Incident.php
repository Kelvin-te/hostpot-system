<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'router_id',
        'type',
        'title',
        'description',
        'affected_subscribers',
        'status',
        'opened_at',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'acknowledged']);
    }
}
