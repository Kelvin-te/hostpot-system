<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Package;
use App\Services\VintexSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class VoucherController extends Controller
{
    protected VintexSmsService $smsService;

    public function __construct(VintexSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function index(Request $request)
    {
        $vouchers = Voucher::with('package')
            ->orderByDesc('id')
            ->paginate(50);

        return view('vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $packages = Package::orderBy('name')->get();
        return view('vouchers.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'expires_at' => 'nullable|date',
            'send_to' => 'nullable|string|min:9|max:20',
        ]);

        $expiresAt = $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null;

        $created = Voucher::createBatch((int)$validated['package_id'], (int)$validated['quantity'], $expiresAt);

        // Optionally SMS the voucher if quantity == 1 and send_to provided
        if (!empty($validated['send_to']) && count($created) === 1) {
            try {
                $pkg = $created[0]->package()->first();
                $this->smsService->sendVoucherSms(
                    $validated['send_to'],
                    $created[0]->code,
                    $pkg?->name ?? 'Internet Package',
                    $expiresAt?->format('j M Y')
                );
            } catch (\Exception $e) {
                Log::error('Failed to send voucher via SMS', ['error' => $e->getMessage()]);
                return redirect()->route('vouchers.index')->with('error', 'Vouchers created, but SMS sending failed.');
            }
        }

        return redirect()->route('vouchers.index')->with('success', count($created) . ' voucher(s) created successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'vouchers_' . now()->format('Ymd_His') . '.csv';
        $query = Voucher::with('package')->orderByDesc('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Package', 'Status', 'Expires At', 'Used At', 'Created At']);
            $query->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $v) {
                    fputcsv($out, [
                        $v->code,
                        optional($v->package)->name,
                        $v->status,
                        optional($v->expires_at)?->format('Y-m-d H:i'),
                        optional($v->used_at)?->format('Y-m-d H:i'),
                        optional($v->created_at)?->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
