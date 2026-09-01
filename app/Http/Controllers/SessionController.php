<?php

namespace App\Http\Controllers;

use App\Models\HotspotSession;
use App\Models\Router;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    protected MikroTikService $mikrotikService;

    public function __construct(MikroTikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Display active sessions (Admin only)
     */
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = HotspotSession::with(['package', 'user'])
            ->orderBy('started_at', 'desc');

        // Filter by status (default: active)
        $status = $request->get('status', 'active');
        if ($status !== 'all') {
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by router
        if ($request->has('router_id') && $request->router_id !== '') {
            $query->whereHas('package', function ($q) use ($request) {
                $q->where('router_id', $request->router_id);
            });
        }

        // Search by username, MAC, or IP
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('mac_address', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%")
                  ->orWhere('session_id', 'LIKE', "%{$search}%");
            });
        }

        $sessions = $query->paginate(20)->withQueryString();
        $routers = Router::all();

        // Get statistics
        $stats = [
            'active' => HotspotSession::active()->count(),
            'expired' => HotspotSession::expired()->count(),
            'total_today' => HotspotSession::whereDate('started_at', today())->count(),
            'data_used_today' => HotspotSession::whereDate('started_at', today())->sum('bytes_total'),
        ];

        return view('sessions.index', compact('sessions', 'routers', 'stats', 'status'));
    }

    /**
     * Show individual session details (Admin only)
     */
    public function show($id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $session = HotspotSession::with(['package.router', 'user'])->findOrFail($id);

        // Try to get live RADIUS data from WinguFi Core if session is active
        $liveData = null;
        if ($session->isActive() && $session->package->router) {
            try {
                $coreService = app(\App\Services\WinguFiCoreService::class);
                $routerExternalId = 'router-' . $session->package->router->identifier;
                $username = $session->mikrotik_username ?? $session->username;

                $result = $coreService->fetchSessions($routerExternalId, 'active');

                if ($result && isset($result['data']['sessions'])) {
                    foreach ($result['data']['sessions'] as $radiusSession) {
                        if (($radiusSession['username'] ?? null) === $username) {
                            $liveData = $radiusSession;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch live RADIUS data', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('sessions.show', compact('session', 'liveData'));
    }

    /**
     * Disconnect a session (Admin only)
     */
    public function disconnect(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $session = HotspotSession::with('package.router')->findOrFail($id);

        if (!$session->isActive()) {
            return back()->with('error', 'Session is not active.');
        }

        try {
            // Disconnect from MikroTik if available
            if ($session->package->router) {
                $this->mikrotikService->disconnectUser($session);
            }

            // Update session status
            $session->update([
                'status' => 'expired',
                'expires_at' => now()
            ]);

            Log::info('Session disconnected by admin', [
                'session_id' => $session->id,
                'admin_user' => auth()->user()->name,
                'username' => $session->username
            ]);

            return back()->with('success', 'Session disconnected successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to disconnect session', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to disconnect session: ' . $e->getMessage());
        }
    }

    /**
     * Show all sessions history (Admin only)
     */
    public function history(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = HotspotSession::with(['package', 'user'])
            ->orderBy('started_at', 'desc');

        // Date range filter
        if ($request->has('from_date') && $request->from_date !== '') {
            $query->whereDate('started_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date !== '') {
            $query->whereDate('started_at', '<=', $request->to_date);
        }

        // Filter by package
        if ($request->has('package_id') && $request->package_id !== '') {
            $query->where('package_id', $request->package_id);
        }

        // Search
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('mac_address', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        $sessions = $query->paginate(50)->withQueryString();
        $packages = \App\Models\Package::all();

        // Statistics
        $stats = [
            'total_sessions' => $query->count(),
            'total_data_used' => $query->sum('bytes_total'),
            'average_duration' => $this->calculateAverageDuration($query->get()),
        ];

        return view('sessions.history', compact('sessions', 'packages', 'stats'));
    }

    /**
     * Get live session data (AJAX) (Admin only)
     */
    public function liveData(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $sessions = HotspotSession::active()
            ->with(['package'])
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'username' => $session->username,
                    'mac_address' => $session->mac_address,
                    'ip_address' => $session->ip_address,
                    'package_name' => $session->package->name,
                    'data_used' => $this->formatBytes($session->bytes_total),
                    'time_remaining' => $session->expires_at->diffForHumans(),
                    'status' => $session->status,
                ];
            });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
            'count' => $sessions->count()
        ]);
    }

    /**
     * Export sessions to CSV (Admin only)
     */
    public function export(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = HotspotSession::with(['package', 'user'])
            ->orderBy('started_at', 'desc');

        // Apply same filters as history
        if ($request->has('from_date') && $request->from_date !== '') {
            $query->whereDate('started_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date !== '') {
            $query->whereDate('started_at', '<=', $request->to_date);
        }

        $sessions = $query->get();

        $filename = 'hotspot_sessions_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($sessions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Session ID',
                'Username',
                'Package',
                'MAC Address',
                'IP Address',
                'Started At',
                'Expires At',
                'Data Uploaded',
                'Data Downloaded',
                'Total Data',
                'Status'
            ]);

            // Data rows
            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->session_id,
                    $session->username,
                    $session->package->name,
                    $session->mac_address,
                    $session->ip_address,
                    $session->started_at->format('Y-m-d H:i:s'),
                    $session->expires_at->format('Y-m-d H:i:s'),
                    $this->formatBytes($session->bytes_uploaded),
                    $this->formatBytes($session->bytes_downloaded),
                    $this->formatBytes($session->bytes_total),
                    $session->status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculate average session duration
     */
    private function calculateAverageDuration($sessions): string
    {
        if ($sessions->isEmpty()) {
            return '0 minutes';
        }

        $totalMinutes = $sessions->sum(function ($session) {
            return $session->started_at->diffInMinutes($session->expires_at);
        });

        $avgMinutes = $totalMinutes / $sessions->count();

        if ($avgMinutes < 60) {
            return round($avgMinutes) . ' minutes';
        } elseif ($avgMinutes < 1440) {
            return round($avgMinutes / 60, 1) . ' hours';
        } else {
            return round($avgMinutes / 1440, 1) . ' days';
        }
    }

    /**
     * Format bytes to human-readable
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Sync sessions with WinguFi Core RADIUS accounting
     */
    public function syncWithRouter(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $sessionService = app(\App\Services\HotspotSessionService::class);
            $result = $sessionService->syncSessionsWithCore();

            if (!$result['success']) {
                return response()->json($result, 500);
            }

            $message = "Synced {$result['synced']} sessions";
            if ($result['stopped'] > 0) {
                $message .= ", marked {$result['stopped']} as disconnected";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'synced' => $result['synced'],
                'stopped' => $result['stopped'],
                'not_found' => $result['not_found'],
            ]);
        } catch (\Exception $e) {
            Log::error('Session sync failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
