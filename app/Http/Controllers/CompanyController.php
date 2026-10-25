<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with('subscription')->withCount('users', 'routers')->latest()->paginate(10);
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        $company = Company::create($validated);

        // Create trial subscription
        $subscription = new Subscription([
            'company_id' => $company->id,
            'plan' => 'basic',
            'router_limit' => Subscription::PRICING_TIERS['basic']['router_limit'],
            'price' => Subscription::PRICING_TIERS['basic']['price'],
            'currency' => 'KES',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'trial_ends_at' => now()->addDays(14),
            'status' => 'trial',
        ]);
        $subscription->save();

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully with 14-day trial.');
    }

    public function show(Company $company)
    {
        $company->load(['users', 'routers', 'settings', 'subscription']);
        return view('companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}
