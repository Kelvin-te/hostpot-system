<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingReportService
{
    /**
     * Get revenue summary for a date range.
     */
    public function getRevenueSummary(Carbon $from, Carbon $to): array
    {
        $transactions = PaymentTransaction::completed()
            ->whereBetween('created_at', [$from, $to])
            ->get();

        return [
            'total_revenue' => $transactions->sum('amount'),
            'total_transactions' => $transactions->count(),
            'average_transaction' => $transactions->count() > 0
                ? $transactions->sum('amount') / $transactions->count()
                : 0,
            'unique_customers' => $transactions->pluck('user_id')->filter()->unique()->count(),
            'by_gateway' => $this->groupByGateway($transactions),
            'by_router' => $this->groupByRouter($transactions),
            'by_type' => $this->groupByType($transactions),
            'daily_revenue' => $this->dailyRevenue($transactions),
        ];
    }

    /**
     * Group transactions by payment gateway.
     */
    public function groupByGateway(Collection $transactions): array
    {
        return $transactions->groupBy('gateway')
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('amount'),
            ])
            ->toArray();
    }

    /**
     * Group transactions by router.
     */
    public function groupByRouter(Collection $transactions): array
    {
        return $transactions->groupBy('router_id')
            ->map(function ($group) {
                $router = Router::find($group->first()->router_id);
                return [
                    'router_name' => $router?->name ?? 'Unknown',
                    'count' => $group->count(),
                    'revenue' => $group->sum('amount'),
                ];
            })
            ->toArray();
    }

    /**
     * Group transactions by type (subscription, topup, etc).
     */
    public function groupByType(Collection $transactions): array
    {
        return $transactions->groupBy('type')
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('amount'),
            ])
            ->toArray();
    }

    /**
     * Daily revenue breakdown for charting.
     */
    public function dailyRevenue(Collection $transactions): array
    {
        return $transactions->groupBy(function ($txn) {
            return $txn->created_at->format('Y-m-d');
        })
            ->map(fn($group) => [
                'date' => $group->first()->created_at->format('Y-m-d'),
                'revenue' => $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->sortKeys()
            ->values()
            ->toArray();
    }

    /**
     * Get top customers by revenue.
     */
    public function getTopCustomers(int $limit = 10): array
    {
        return PaymentTransaction::completed()
            ->select('user_id', DB::raw('SUM(amount) as total_spent'), DB::raw('COUNT(*) as transaction_count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $user = \App\Models\User::find($row->user_id);
                return [
                    'user_id' => $row->user_id,
                    'name' => $user?->name ?? $user?->phone ?? 'Unknown',
                    'phone' => $user?->phone ?? '—',
                    'total_spent' => $row->total_spent,
                    'transaction_count' => $row->transaction_count,
                ];
            })
            ->toArray();
    }

    /**
     * Generate an invoice number from a PaymentTransaction.
     */
    public function generateInvoiceNumber(PaymentTransaction $transaction): string
    {
        $prefix = 'INV';
        $date = $transaction->created_at->format('Ymd');
        $id = str_pad($transaction->id, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$id}";
    }
}
