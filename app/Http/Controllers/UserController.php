<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Detail;
use App\Models\Package;
use App\Models\User;
use App\Services\HotspotAuthorizationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        protected HotspotAuthorizationService $authorizationService
    ) {
        //
    }

    public function index()
    {
        if (auth()->user()->isUser()) {
            return redirect('/');
        }

        $users = User::with('detail')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $packages = Package::orderBy('name')->get();
        return view('users.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "phone" => "required|unique:users,phone",
            "package_id" => "required|exists:packages,id",
        ]);

        $package = Package::findOrFail($request->package_id);

        try {
            $user = DB::transaction(function () use ($request, $package) {
                $user = new User();
                $user->name = __('Customer ') . $request->phone;
                $user->email = null;
                $user->password = Hash::make(Str::random(32));
                $user->phone = $request->phone;
                $user->save();

                $details = new Detail();
                $details->phone = $request->phone;
                $details->address = '-';
                $details->dob = '2000-01-01';
                $details->pin = $request->pin;
                $details->package_name = $package->name;
                $details->router_name = $package->router->name ?? '';
                $details->package_price = $package->price;
                $details->due = $package->price;
                $details->status = 'active';
                $details->package_start = Carbon::now();
                $details->user_id = $user->id;
                $details->save();

                $billing = new Billing();
                $billing->invoice = $billing->generateRandomNumber();
                $billing->package_name = $details->package_name;
                $billing->package_price = $details->package_price;
                $billing->package_start = $details->package_start;
                $billing->user_id = $user->id;
                $billing->save();

                $this->authorizationService->createFromPackage($package, $user, $user->phone);

                return $user;
            });
        } catch (\Exception $e) {
            return back()->with("error", __("Failed to create user: ") . $e->getMessage());
        }

        return redirect("users")->with("success", __("User added successfully"));
    }

    public function show(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        $packages = Package::orderBy('name')->get();
        $currentPackage = Package::where('name', $user->detail?->package_name)->first();
        return view('users.edit', compact('user', 'packages', 'currentPackage'));
    }

    public function update(Request $request, User $user)
    {
        $this->validate($request, [
            "package_id" => "required|exists:packages,id",
        ]);

        $package = Package::findOrFail($request->package_id);

        if ($user->detail?->package_name === $package->name) {
            return redirect("users")->with("success", __("No changes made."));
        }

        try {
            DB::transaction(function () use ($user, $package) {
                $details = Detail::firstWhere('user_id', $user->id);
                $details->package_name = $package->name;
                $details->package_price = $package->price;
                $details->router_name = $package->router->name ?? '';
                $details->due = $package->price;
                $details->package_start = Carbon::now();
                $details->save();

                $this->authorizationService->createFromPackage($package, $user, $user->phone);
            });
        } catch (\Exception $e) {
            return back()->with("error", __("Failed to update user: ") . $e->getMessage());
        }

        return redirect("users")->with("success", __("User updated successfully"));
    }
}
