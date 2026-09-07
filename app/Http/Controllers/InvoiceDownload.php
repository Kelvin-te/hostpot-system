<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;

class InvoiceDownload extends Controller
{
    public function __invoke(Request $request)
    {
        $invoice = PaymentTransaction::where('id', $request->row)
            ->with(['user', 'package'])
            ->firstOrFail();

        if (Setting::doesntExist()) {
            return redirect()->back()->with('error','Insert ISP information first');
        }

        $company = Setting::firstOrFail();

        $pdf = PDF::loadview('reports.invoice', compact('invoice', 'company'));

        return $pdf->download( config('app.name') . ' Invoice ' . date('dmY') . ('.pdf'));
    }
}
