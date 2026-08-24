<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MikroTikService;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'identifier',
        'location',
        'ip_address',
        'username',
        'password',
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

    public function packages() {
        return $this->hasMany(Package::class);
    }

    public function radiusNas()
    {
        return $this->hasOne(RadiusNas::class);
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
}
