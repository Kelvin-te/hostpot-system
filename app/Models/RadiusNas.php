<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusNas extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'nas_identifier',
        'nas_ip_address',
        'nas_secret',
        'nas_port',
        'nas_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
