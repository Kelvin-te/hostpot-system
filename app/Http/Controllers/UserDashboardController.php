<?php

namespace App\Http\Controllers;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserDashboardController extends Controller
{
    /**
     * User Dashboard Home
     */
    public function index()
    {
        $user = Auth::user();

        // Get active sessions
        $activeSessions = HotspotSession::where('user_id', $user->id)
            ->orWhere('username', $user->phone)
            ->active()
            ->with('package')
            ->get();

        // Get recent sessions
        $recentSessions = HotspotSession::where('user_id', $user->id)
            ->orWhere('username', $user->phone)
            ->orderBy('started_at', 'desc')
            ->take(5)
            ->with('package')
            ->get();

        // Get purchase statistics
        $totalSpent = PaymentTransaction::where('phone_number', $user->phone)
            ->where('status', 'completed')
            ->sum('amount');

        $totalSessions = HotspotSession::where('user_id', $user->id)
            ->orWhere('username', $user->phone)
            ->count();

        $totalDataUsed = HotspotSession::where('user_id', $user->id)
            ->orWhere('username', $user->phone)
            ->sum('bytes_total');

        // Get recent transactions
        $recentTransactions = PaymentTransaction::where('phone_number', $user->phone)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->with('package')
            ->get();

        return view('user.dashboard', compact(
            'activeSessions',
            'recentSessions',
            'totalSpent',
            'totalSessions',
            'totalDataUsed',
            'recentTransactions'
        ));
    }

    /**
     * My Sessions
     */
    public function sessions(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = HotspotSession::where('user_id', $user->id)
            ->orWhere('username', $user->phone)
            ->with('package')
            ->orderBy('started_at', 'desc');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'expired') {
            $query->expired();
        }

        $sessions = $query->paginate(20)->withQueryString();

        // Statistics
        $stats = [
            'active' => HotspotSession::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('username', $user->phone);
                })->active()->count(),
            'total' => HotspotSession::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('username', $user->phone);
                })->count(),
            'data_used' => HotspotSession::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('username', $user->phone);
                })->sum('bytes_total'),
        ];

        return view('user.sessions', compact('sessions', 'stats', 'status'));
    }

    /**
     * Purchase History
     */
    public function purchases(Request $request)
    {
        $user = Auth::user();

        $transactions = PaymentTransaction::where('phone_number', $user->phone)
            ->orderBy('created_at', 'desc')
            ->with('package')
            ->paginate(20);

        // Statistics
        $stats = [
            'total_spent' => PaymentTransaction::where('phone_number', $user->phone)
                ->where('status', 'completed')
                ->sum('amount'),
            'total_transactions' => PaymentTransaction::where('phone_number', $user->phone)
                ->where('status', 'completed')
                ->count(),
            'failed_transactions' => PaymentTransaction::where('phone_number', $user->phone)
                ->where('status', 'failed')
                ->count(),
        ];

        return view('user.purchases', compact('transactions', 'stats'));
    }

    /**
     * Quick Recharge Page
     */
    public function recharge()
    {
        $packages = Package::where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('price')
            ->get();

        return view('user.recharge', compact('packages'));
    }

    /**
     * Account Settings
     */
    public function settings()
    {
        $user = Auth::user();
        return view('user.settings', compact('user'));
    }

    /**
     * Update Account Settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        // Update basic info
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update password if provided
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect']);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', 'Account settings updated successfully!');
    }

    /**
     * Get Active Session Details (AJAX)
     */
    public function activeSessionData(Request $request)
    {
        $user = Auth::user();

        $sessions = HotspotSession::where('user_id', $user->id)
            ->orWhere('username', $user->phone)
            ->active()
            ->with('package')
            ->get()
            ->map(function ($session) {
                $dataUsed = $session->bytes_total / (1024 * 1024); // MB
                $limitMB = 0;
                if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $session->package->rate_limit, $matches)) {
                    $limitMB = $matches[2] === 'GB' ? $matches[1] * 1024 : $matches[1];
                }

                return [
                    'id' => $session->id,
                    'package_name' => $session->package->name,
                    'data_used' => number_format($dataUsed, 2) . ' MB',
                    'data_limit' => $limitMB > 0 ? number_format($limitMB, 0) . ' MB' : 'Unlimited',
                    'percentage' => $limitMB > 0 ? min(100, ($dataUsed / $limitMB) * 100) : 0,
                    'expires_at' => $session->expires_at->diffForHumans(),
                    'status' => $session->status,
                ];
            });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
            'count' => $sessions->count()
        ]);
    }
}
