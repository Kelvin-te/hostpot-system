<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MikroTikService;

class Router extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'ip_address', 'username', 'password', 'api_port', 'ip'];

    protected $appends = ['is_online'];

    public function packages() {
        return $this->hasMany(Package::class);
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
