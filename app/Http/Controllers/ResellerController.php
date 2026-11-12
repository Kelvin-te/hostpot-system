<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = Reseller::withCount(['routers', 'packages', 'users'])->latest()->get();

        return view('resellers.index', compact('resellers'));
    }

    public function create()
    {
        return view('resellers.form', ['reseller' => new Reseller()]);
    }

    public function store(Request $request)
    {
        Reseller::create($this->validatedWithCredentials($request));

        return redirect()->route('resellers.index')->with('success', 'Reseller created.');
    }

    public function edit(Reseller $reseller)
    {
        return view('resellers.form', compact('reseller'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $data = $this->validatedWithCredentials($request);

        // Merge M-Pesa credentials so partial form submits don't wipe keys
        if (isset($data['mpesa_credentials'])) {
            $data['mpesa_credentials'] = array_merge(
                $reseller->mpesa_credentials ?? [],
                $data['mpesa_credentials']
            );
        }

        $reseller->update($data);

        return redirect()->route('resellers.index')->with('success', 'Reseller updated.');
    }

    public function toggleStatus(Reseller $reseller)
    {
        $reseller->update([
            'status' => $reseller->status === 'active' ? 'suspended' : 'active',
        ]);

        return back()->with('success', 'Reseller status updated.');
    }

    public function destroy(Reseller $reseller)
    {
        // Only allow delete when nothing is attached
        if ($reseller->routers()->exists() || $reseller->packages()->exists() || $reseller->users()->exists() || $reseller->staff()->exists()) {
            return back()->with('error', 'Cannot delete: reseller has routers, packages, subscribers, or staff assigned. Reassign them first.');
        }

        $reseller->delete();

        return redirect()->route('resellers.index')->with('success', 'Reseller deleted.');
    }

    /**
     * Merge flat mpesa_* form fields into the encrypted mpesa_credentials
     * JSON column; only overwrite keys that were actually submitted.
     */
    private function validatedWithCredentials(Request $request): array
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'brand_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,suspended',
            'mpesa_shortcode' => 'nullable|string|max:20',
            'mpesa_passkey' => 'nullable|string|max:255',
            'mpesa_consumer_key' => 'nullable|string|max:255',
            'mpesa_consumer_secret' => 'nullable|string|max:255',
            'mpesa_initiator_name' => 'nullable|string|max:255',
            'mpesa_initiator_password' => 'nullable|string|max:255',
            'mpesa_callback_url' => 'nullable|url|max:255',
        ]);

        $mpesaKeys = ['shortcode', 'passkey', 'consumer_key', 'consumer_secret', 'initiator_name', 'initiator_password', 'callback_url'];
        $credentials = [];
        foreach ($mpesaKeys as $key) {
            $field = 'mpesa_' . $key;
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $credentials[$key] = $data[$field];
            }
            unset($data[$field]);
        }

        if ($credentials !== []) {
            $data['mpesa_credentials'] = $credentials;
        }

        return $data;
    }
}
