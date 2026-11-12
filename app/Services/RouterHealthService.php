<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\Router;
use App\Models\RouterHealthLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Polls each router's health/traffic via MikroTik API, records a snapshot,
 * tracks online/offline transitions, and alerts on state changes.
 */
class RouterHealthService
{
    public function __construct(private MikroTikService $mikroTik) {}

    public function poll(Router $router): RouterHealthLog
    {
        $health = $this->mikroTik->getRouterHealth($router);
        $traffic = $this->mikroTik->getInterfaceTraffic($router);

        $online = ($health['success'] ?? false) || ($traffic['success'] ?? false);

        $primaryInterface = null;
        if (($traffic['success'] ?? false) && !empty($traffic['interfaces'])) {
            $primaryInterface = collect($traffic['interfaces'])
                ->firstWhere('name', $router->hotspot_interface)
                ?? $traffic['interfaces'][0];
        }

        $activeSessions = \App\Models\HotspotSession::active()
            ->whereHas('package', fn ($q) => $q->where('router_id', $router->id))
            ->count();

        $log = RouterHealthLog::create([
            'router_id' => $router->id,
            'online' => $online,
            'cpu_load' => $health['success'] ? ($health['cpu_load'] ?? null) : null,
            'memory_percentage' => $health['success'] ? ($health['memory_percentage'] ?? null) : null,
            'uptime_seconds' => $health['success'] ? ($health['uptime_seconds'] ?? null) : null,
            'rx_rate_mbps' => $primaryInterface['rx_rate_mbps'] ?? null,
            'tx_rate_mbps' => $primaryInterface['tx_rate_mbps'] ?? null,
            'active_sessions' => $activeSessions,
            'error' => $online ? null : ($health['message'] ?? $traffic['message'] ?? 'unreachable'),
            'checked_at' => now(),
        ]);

        $this->handleTransition($router, $online);

        return $log;
    }

    private function handleTransition(Router $router, bool $online): void
    {
        $wasOnline = $router->is_online;

        $router->update([
            'is_online' => $online,
            'last_health_at' => now(),
            'went_offline_at' => $online ? null : ($wasOnline === false ? $router->went_offline_at : now()),
        ]);

        // Alert only on transitions (first poll establishes baseline silently)
        if ($wasOnline === null) {
            return;
        }

        if ($wasOnline && !$online) {
            Log::warning('Router went offline', ['router_id' => $router->id, 'name' => $router->name]);
            $this->openIncident($router);
            $this->alert("Router {$router->name} ({$router->ip_address}) is OFFLINE.");
        } elseif (!$wasOnline && $online) {
            Log::info('Router back online', ['router_id' => $router->id, 'name' => $router->name]);
            $this->resolveIncidents($router);
            $this->alert("Router {$router->name} ({$router->ip_address}) is back ONLINE.");
        }
    }

    /**
     * Open a blast-radius incident for a router going down, unless one is
     * already open. Captures the number of active subscribers affected.
     */
    private function openIncident(Router $router): void
    {
        $existing = \App\Models\Incident::where('router_id', $router->id)->open()->exists();
        if ($existing) {
            return;
        }

        $affected = \App\Models\HotspotSession::active()
            ->whereHas('package', fn ($q) => $q->where('router_id', $router->id))
            ->count();

        \App\Models\Incident::create([
            'router_id' => $router->id,
            'type' => 'router_down',
            'title' => "Router {$router->name} offline",
            'description' => "Router {$router->name} ({$router->ip_address}) stopped responding at " . now()->toDateTimeString() . ". {$affected} active session(s) affected.",
            'affected_subscribers' => $affected,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    private function resolveIncidents(Router $router): void
    {
        \App\Models\Incident::where('router_id', $router->id)
            ->open()
            ->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    private function alert(string $message): void
    {
        $settings = Setting::first();
        $phone = $settings?->alert_phone ?? $settings?->company_phone;

        if ($phone) {
            SendSmsJob::dispatch($phone, $message);
        }
    }
}
