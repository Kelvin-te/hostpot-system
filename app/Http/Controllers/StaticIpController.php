<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use App\Models\StaticIp;
use App\Models\User;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaticIpController extends Controller
{
    public function index(Request $request)
    {
        $query = StaticIp::with(['router', 'user', 'package'])->latest();

        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $staticIps = $query->paginate(25);
        $routers = Router::orderBy('name')->get();

        $stats = [
            'total' => StaticIp::count(),
            'free' => StaticIp::free()->count(),
            'assigned' => StaticIp::assigned()->count(),
            'reserved' => StaticIp::where('status', 'reserved')->count(),
        ];

        return view('static-ips.index', compact('staticIps', 'routers', 'stats'));
    }

    public function create()
    {
        return view('static-ips.create', [
            'routers' => Router::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'packages' => Package::with('router')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, MikroTikService $mikroTik)
    {
        $validated = $request->validate([
            'router_id' => 'required|exists:routers,id',
            'ip_address' => 'required|ip',
            'mac_address' => 'nullable|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'user_id' => 'nullable|exists:users,id',
            'package_id' => 'nullable|exists:packages,id',
            'status' => 'required|in:free,assigned,reserved',
            'comment' => 'nullable|string|max:255',
            'sync_to_router' => 'nullable|boolean',
        ]);

        $router = Router::findOrFail($validated['router_id']);

        $staticIp = DB::transaction(function () use ($validated, $router, $mikroTik) {
            $staticIp = StaticIp::create([
                'router_id' => $router->id,
                'ip_address' => $validated['ip_address'],
                'mac_address' => $validated['mac_address'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'package_id' => $validated['package_id'] ?? null,
                'status' => $validated['status'],
                'comment' => $validated['comment'] ?? null,
                'synced_to_router' => false,
            ]);

            if ($request->boolean('sync_to_router') && $validated['mac_address']) {
                $result = $mikroTik->provisionStaticLease(
                    $router,
                    $validated['ip_address'],
                    $validated['mac_address'],
                    $validated['comment'] ?? null
                );

                if ($result['success']) {
                    $staticIp->update(['synced_to_router' => true]);
                }
            }

            return $staticIp;
        });

        return redirect()->route('static-ips.index')->with('success', 'Static IP added.');
    }

    public function sync(StaticIp $staticIp, MikroTikService $mikroTik)
    {
        if ($staticIp->status === 'free' || !$staticIp->mac_address) {
            return back()->with('error', 'Only assigned/reserved IPs with a MAC can be synced to the router.');
        }

        $result = $mikroTik->provisionStaticLease(
            $staticIp->router,
            $staticIp->ip_address,
            $staticIp->mac_address,
            $staticIp->comment
        );

        if ($result['success']) {
            $staticIp->update(['synced_to_router' => true]);
            return back()->with('success', 'Lease synced to router.');
        }

        return back()->with('error', $result['message']);
    }

    public function destroy(StaticIp $staticIp, MikroTikService $mikroTik)
    {
        if ($staticIp->synced_to_router) {
            $mikroTik->removeStaticLease($staticIp->router, $staticIp->ip_address);
        }

        $staticIp->delete();

        return redirect()->route('static-ips.index')->with('success', 'Static IP removed.');
    }
}
