<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MikroTikService;
use Illuminate\Support\Str;

class Router extends Model
{
    use HasFactory;

    protected $hidden = [
        'password',
        'api_password',
        'hotspot_api_token',
    ];

    protected static function booted()
    {
        static::creating(function (Router $router) {
            if (empty($router->hotspot_api_token)) {
                $router->hotspot_api_token = Str::random(48);
            }
        });
    }

    protected $fillable = [
        'name',
        'reseller_id',
        'identifier',
        'location',
        'ip_address',
        'username',
        'password',
        'api_username',
        'api_password',
        'api_port',
        'ip',
        'hotspot_enabled',
        'hotspot_interface',
        'hotspot_server_ip',
        'is_active',
        'last_synced_at',
        'packages_sync_count',
        'packages_unsync_count'
    ];

    // NOTE: is_online is intentionally NOT appended. Calling checkStatus() performs
    // a live network request to the router, so it must only be invoked explicitly.

    protected $casts = [
        'password' => 'encrypted',
        'api_password' => 'encrypted',
        'is_active' => 'boolean',
        'hotspot_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function packages() {
        return $this->hasMany(Package::class);
    }

    public function sessions() {
        return $this->hasManyThrough(HotspotSession::class, Package::class);
    }

    public function transactions() {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Check if router is currently online using MikroTikService testConnection
     */
    public function checkStatus(): array
    {
        try {
            $mikrotikService = new MikroTikService();
            $result = $mikrotikService->testConnection($this);
            
            return [
                'online' => $result['success'],
                'status' => $result['success'] ? 'online' : 'offline',
                'message' => $result['message'],
                'diagnostics' => $result['diagnostics'] ?? null,
                'data' => $result['data'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'status' => 'error',
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'diagnostics' => null,
            ];
        }
    }

    /**
     * Accessor for is_online attribute
     */
    public function getIsOnlineAttribute(): bool
    {
        $status = $this->checkStatus();
        return $status['online'] ?? false;
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
