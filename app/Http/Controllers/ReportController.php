<?php

namespace App\Http\Controllers;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Analytics Dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30days');
        $startDate = $this->getStartDate($period);

        // Revenue Statistics
        $totalRevenue = PaymentTransaction::where('status', 'completed')
            ->sum('amount');
        
        $periodRevenue = PaymentTransaction::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $todayRevenue = PaymentTransaction::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        // Session Statistics
        $totalSessions = HotspotSession::count();
        $activeSessions = HotspotSession::active()->count();
        $periodSessions = HotspotSession::where('started_at', '>=', $startDate)->count();

        // Data Usage Statistics
        $totalDataUsed = HotspotSession::sum('bytes_total');
        $periodDataUsed = HotspotSession::where('started_at', '>=', $startDate)
            ->sum('bytes_total');

        // Top Packages
        $topPackages = Package::withCount(['sessions' => function ($query) use ($startDate) {
                $query->where('started_at', '>=', $startDate);
            }])
            ->having('sessions_count', '>', 0)
            ->orderBy('sessions_count', 'desc')
            ->take(5)
            ->get();

        // Revenue by Package
        $revenueByPackage = PaymentTransaction::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select('package_id', DB::raw('SUM(amount) as total_revenue'), DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('package_id')
            ->with('package')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Daily revenue chart data (last 30 days)
        $dailyRevenue = PaymentTransaction::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date');

        // Daily sessions chart data (last 30 days)
        $dailySessions = HotspotSession::where('started_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(started_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Fill missing dates with 0
        $last30Days = collect(range(0, 29))->map(function ($day) {
            return now()->subDays(29 - $day)->format('Y-m-d');
        });

        $revenueChartData = $last30Days->map(fn($date) => $dailyRevenue->get($date, 0));
        $sessionsChartData = $last30Days->map(fn($date) => $dailySessions->get($date, 0));

        return view('reports.index', compact(
            'totalRevenue',
            'periodRevenue',
            'todayRevenue',
            'totalSessions',
            'activeSessions',
            'periodSessions',
            'totalDataUsed',
            'periodDataUsed',
            'topPackages',
            'revenueByPackage',
            'last30Days',
            'revenueChartData',
            'sessionsChartData',
            'period'
        ));
    }

    /**
     * Revenue Report
     */
    public function revenue(Request $request)
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        // Revenue summary
        $totalRevenue = PaymentTransaction::where('status', 'completed')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('amount');

        $transactionCount = PaymentTransaction::where('status', 'completed')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->count();

        $averageTransaction = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;

        $failedTransactions = PaymentTransaction::where('status', 'failed')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->count();

        // Revenue by package
        $revenueByPackage = PaymentTransaction::where('status', 'completed')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select('package_id', 
                DB::raw('SUM(amount) as total_revenue'), 
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('AVG(amount) as avg_amount'))
            ->groupBy('package_id')
            ->with('package')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Revenue by day
        $revenueByDay = PaymentTransaction::where('status', 'completed')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select(DB::raw('DATE(created_at) as date'), 
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue by payment method (if tracked)
        $revenueByMethod = PaymentTransaction::where('status', 'completed')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select(DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->first();

        $packages = Package::all();

        return view('reports.revenue', compact(
            'totalRevenue',
            'transactionCount',
            'averageTransaction',
            'failedTransactions',
            'revenueByPackage',
            'revenueByDay',
            'fromDate',
            'toDate',
            'packages'
        ));
    }

    /**
     * Usage Statistics Report
     */
    public function usage(Request $request)
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        // Data usage summary
        $totalDataUsed = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('bytes_total');

        $totalUploaded = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('bytes_uploaded');

        $totalDownloaded = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->sum('bytes_downloaded');

        $sessionCount = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->count();

        $averagePerSession = $sessionCount > 0 ? $totalDataUsed / $sessionCount : 0;

        // Data usage by package
        $usageByPackage = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select('package_id',
                DB::raw('COUNT(*) as session_count'),
                DB::raw('SUM(bytes_total) as total_data'),
                DB::raw('AVG(bytes_total) as avg_data'))
            ->groupBy('package_id')
            ->with('package')
            ->orderBy('total_data', 'desc')
            ->get();

        // Daily data usage
        $dailyUsage = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select(DB::raw('DATE(started_at) as date'),
                DB::raw('SUM(bytes_total) as data_used'),
                DB::raw('COUNT(*) as sessions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Peak usage times (by hour)
        $peakHours = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select(DB::raw('HOUR(started_at) as hour'),
                DB::raw('COUNT(*) as session_count'),
                DB::raw('SUM(bytes_total) as data_used'))
            ->groupBy('hour')
            ->orderBy('session_count', 'desc')
            ->get();

        // Top users by data usage
        $topUsers = HotspotSession::whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->select('username',
                DB::raw('COUNT(*) as session_count'),
                DB::raw('SUM(bytes_total) as total_data'))
            ->whereNotNull('username')
            ->groupBy('username')
            ->orderBy('total_data', 'desc')
            ->take(10)
            ->get();

        return view('reports.usage', compact(
            'totalDataUsed',
            'totalUploaded',
            'totalDownloaded',
            'sessionCount',
            'averagePerSession',
            'usageByPackage',
            'dailyUsage',
            'peakHours',
            'topUsers',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Package Performance Report
     */
    public function packages(Request $request)
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        $packages = Package::with('router')
            ->withCount(['sessions' => function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59']);
            }])
            ->get()
            ->map(function ($package) use ($fromDate, $toDate) {
                // Get revenue for this package
                $revenue = PaymentTransaction::where('package_id', $package->id)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->sum('amount');

                // Get data usage for this package
                $dataUsed = HotspotSession::where('package_id', $package->id)
                    ->whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->sum('bytes_total');

                // Calculate average session duration
                $sessions = HotspotSession::where('package_id', $package->id)
                    ->whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->get();

                $avgDuration = 0;
                if ($sessions->count() > 0) {
                    $totalMinutes = $sessions->sum(function ($session) {
                        return $session->started_at->diffInMinutes($session->expires_at);
                    });
                    $avgDuration = $totalMinutes / $sessions->count();
                }

                $package->period_revenue = $revenue;
                $package->period_data_used = $dataUsed;
                $package->avg_duration_minutes = $avgDuration;
                $package->conversion_rate = 0; // Can be calculated based on your logic

                return $package;
            })
            ->sortByDesc('sessions_count');

        return view('reports.packages', compact('packages', 'fromDate', 'toDate'));
    }

    /**
     * Export report to CSV
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'revenue');
        $fromDate = $request->get('from_date', now()->subDays(30)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        $filename = "{$type}_report_" . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($type, $fromDate, $toDate) {
            $file = fopen('php://output', 'w');
            
            if ($type === 'revenue') {
                $this->exportRevenueReport($file, $fromDate, $toDate);
            } elseif ($type === 'usage') {
                $this->exportUsageReport($file, $fromDate, $toDate);
            } elseif ($type === 'packages') {
                $this->exportPackagesReport($file, $fromDate, $toDate);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper: Get start date based on period
     */
    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };
    }

    /**
     * Helper: Export revenue report
     */
    private function exportRevenueReport($file, $fromDate, $toDate)
    {
        fputcsv($file, ['Date', 'Package', 'Amount', 'Transaction ID', 'Status', 'Phone']);

        $transactions = PaymentTransaction::with('package')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($transactions as $transaction) {
            fputcsv($file, [
                $transaction->created_at->format('Y-m-d H:i:s'),
                $transaction->package->name ?? 'N/A',
                $transaction->amount,
                $transaction->mpesa_receipt_number ?? 'N/A',
                $transaction->status,
                $transaction->phone_number
            ]);
        }
    }

    /**
     * Helper: Export usage report
     */
    private function exportUsageReport($file, $fromDate, $toDate)
    {
        fputcsv($file, ['Date', 'Username', 'Package', 'Data Used (MB)', 'Duration (mins)', 'IP Address']);

        $sessions = HotspotSession::with('package')
            ->whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
            ->orderBy('started_at', 'desc')
            ->get();

        foreach ($sessions as $session) {
            fputcsv($file, [
                $session->started_at->format('Y-m-d H:i:s'),
                $session->username ?? 'Guest',
                $session->package->name,
                number_format($session->bytes_total / (1024 * 1024), 2),
                $session->started_at->diffInMinutes($session->expires_at),
                $session->ip_address
            ]);
        }
    }

    /**
     * Helper: Export packages report
     */
    private function exportPackagesReport($file, $fromDate, $toDate)
    {
        fputcsv($file, ['Package', 'Price', 'Sessions', 'Revenue', 'Data Used (GB)', 'Avg Duration']);

        $packages = Package::all();

        foreach ($packages as $package) {
            $sessions = $package->sessions()
                ->whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
                ->count();

            $revenue = PaymentTransaction::where('package_id', $package->id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                ->sum('amount');

            $dataUsed = HotspotSession::where('package_id', $package->id)
                ->whereBetween('started_at', [$fromDate, $toDate . ' 23:59:59'])
                ->sum('bytes_total');

            fputcsv($file, [
                $package->name,
                $package->price,
                $sessions,
                $revenue,
                number_format($dataUsed / (1024 * 1024 * 1024), 2),
                'N/A'
            ]);
        }
    }
}
