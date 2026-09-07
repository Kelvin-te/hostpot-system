<?php

namespace App\Http\Controllers;

use App\Models\HotspotSession;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\VintexSmsService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function show(User $customer)
    {
        $customer->load([
            'router',
            'sessions' => fn($q) => $q->latest()->limit(20),
            'sessions.package',
            'transactions' => fn($q) => $q->latest()->limit(20),
            'transactions.package',
        ]);

        $activeSession = $customer->activeSession;
        $totalSpent = $customer->transactions()->where('status', 'completed')->sum('amount');
        $totalSessions = $customer->sessions()->count();
        $totalDataUsed = $customer->sessions()->sum('bytes_total');

        return view('customers.show', compact('customer', 'activeSession', 'totalSpent', 'totalSessions', 'totalDataUsed'));
    }

    public function showByPhone(string $phone)
    {
        $customer = User::where('phone', $phone)->first();

        if (!$customer) {
            return redirect()->route('customers.index')->with('error', "No customer found with phone: {$phone}");
        }

        return redirect()->route('customers.show', $customer);
    }

    public function suspend(User $customer)
    {
        $customer->update(['status' => 'suspended']);

        return redirect()->back()->with('success', 'Customer suspended successfully.');
    }

    public function activate(User $customer)
    {
        $customer->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Customer activated successfully.');
    }

    public function sendSms(Request $request, User $customer)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        if (!$customer->phone) {
            return redirect()->back()->with('error', 'Customer has no phone number.');
        }

        $smsService = app(VintexSmsService::class);
        $result = $smsService->sendSms($customer->phone, $request->message);

        if ($result['success'] ?? false) {
            return redirect()->back()->with('success', 'SMS sent successfully.');
        }

        return redirect()->back()->with('error', 'SMS failed: ' . ($result['message'] ?? 'Unknown error'));
    }
}
