<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\BillingReportService;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $reportService = app(BillingReportService::class);

        $from = Carbon::now()->subDays(30)->startOfDay();
        $to = Carbon::now()->endOfDay();

        $summary = $reportService->getRevenueSummary($from, $to);
        $topCustomers = $reportService->getTopCustomers(10);

        return view('billing.index', compact('summary', 'topCustomers'));
    }

    /**
     * Show invoice for a specific transaction.
     */
    public function invoice(PaymentTransaction $transaction)
    {
        $reportService = app(BillingReportService::class);
        $invoiceNumber = $reportService->generateInvoiceNumber($transaction);

        $transaction->load(['user', 'package', 'router']);

        return view('billing.invoice', compact('transaction', 'invoiceNumber'));
    }

    /**
     * Revenue dashboard with charts.
     */
    public function dashboard()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $reportService = app(BillingReportService::class);

        $from = Carbon::now()->subDays(30)->startOfDay();
        $to = Carbon::now()->endOfDay();

        $summary = $reportService->getRevenueSummary($from, $to);
        $topCustomers = $reportService->getTopCustomers(10);

        return view('billing.dashboard', compact('summary', 'topCustomers'));
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $users = User::with('detail')
            ->whereHas('detail', function (Builder $query) {
                $query->where('status', 'active');
            })->orderBy('name')->get();

        return view('billing.create', compact('users'));
    }

    public function store(Request $request)
    {
        if (is_array($request->user_id) || is_object($request->user_id))
        {
            foreach ($request->user_id as $key => $val) {
                $billing = new Billing();
                if (is_array($request->checked) && in_array($val, $request->checked, true)) {
                    $user = User::where('id', $val)->first();
                    $billing->invoice = $billing->generateRandomNumber();
                    $billing->package_name = $user->detail->package_name;
                    $billing->package_price = $user->detail->package_price;
                    $billing->package_start = Carbon::now();
                    $billing->user_id = $user->id;
                    $billing->save();
                }
            }
        }
        return redirect('/billing');
    }
}
