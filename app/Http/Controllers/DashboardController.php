<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (auth()->user()->isUser()) {
            $user = User::with('detail')->where('id', auth()->id())->firstOrFail();
            return view('dashboard2', compact('user'));
        }

        $totalPackages = Package::count();
        $totalRevenue = PaymentTransaction::where('status', 'completed')->sum('amount');
        $totalUsers = User::count();
        $openTickets = Ticket::where('status', 'Open')->count();
        $recentUsers = User::with('detail')->with(['billing', 'detail'])->latest()->take(5)->get();
        $recentTransactions = PaymentTransaction::with(['user', 'package'])->latest()->take(5)->get();
        $recentTickets = Ticket::latest()->take(5)->get();

        $revenueThisMonth = PaymentTransaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)->sum('amount');
        $revenueThisYear = PaymentTransaction::where('status', 'completed')
            ->whereYear('created_at', now()->year)->sum('amount');

        // Gateway breakdown
        $mpesaRevenue = PaymentTransaction::where('status', 'completed')->where('gateway', 'mpesa')->sum('amount');
        $paystackRevenue = PaymentTransaction::where('status', 'completed')->where('gateway', 'paystack')->sum('amount');
        $manualRevenue = PaymentTransaction::where('status', 'completed')->where('gateway', 'manual')->sum('amount');
        $walletRevenue = PaymentTransaction::where('status', 'completed')->where('gateway', 'wallet')->sum('amount');

        $usersWithDueCount = User::with('detail')->get()
            ->filter(function ($user) {
                return $user->due_amount($user->id) > 0;
            })->count();
        $usersWithDueList = User::with('detail')->get()
            ->filter(function ($user) {
                return $user->due_amount($user->id) > 0;
            });

        // Monthly revenue data for charts
        $revenueData = PaymentTransaction::where('status', 'completed')
            ->whereYear('created_at', Carbon::now()->year)
            ->get()->groupBy(function ($transaction) {
                return $transaction->created_at->format('F');
            })->map(function ($transactions) {
                return $transactions->sum('amount');
            });

        // Daily revenue data for current month
        $daysInMonth = Carbon::now()->daysInMonth;
        $dailyRevenueData = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dailyRevenueData[] = PaymentTransaction::where('status', 'completed')
                ->whereDate('created_at', Carbon::now()->year . '-' . Carbon::now()->month . '-' . $day)
                ->sum('amount');
        }

        return view('dashboard', compact(
            'totalUsers',
            'totalRevenue',
            'revenueThisMonth',
            'recentTransactions',
            'recentUsers',
            'totalPackages',
            'revenueThisYear',
            'usersWithDueCount',
            'usersWithDueList',
            'openTickets',
            'revenueData',
            'dailyRevenueData',
            'mpesaRevenue',
            'paystackRevenue',
            'manualRevenue',
            'walletRevenue',
            'recentTickets'
        ));
    }
}
