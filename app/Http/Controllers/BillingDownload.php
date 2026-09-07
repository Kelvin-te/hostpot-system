<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class BillingDownload extends Controller
{
    public function __invoke()
    {
        $bills = PaymentTransaction::with(['user', 'package', 'router'])
            ->completed()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($bills->isEmpty()) {
            return redirect()->back()->with('error', 'No billing records available to download.');
        }

        $pdf = PDF::loadview('reports.billing', compact('bills'));
        return $pdf->download( config('app.name') . ' Billing History ' . date('dmY') . ('.pdf'));
    }
}
