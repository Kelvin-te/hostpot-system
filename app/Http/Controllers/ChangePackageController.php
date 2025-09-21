<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RouterOS\Client;
use RouterOS\Query;

class ChangePackageController extends Controller
{
    public function edit(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        
        $router_name = $user->detail->router_name;
        $router = Router::where("name", $router_name)->firstOrFail();
        $packages = Package::where('router_id', $router->id)->orderBy('name')->get();
        return view('packages.change', compact('packages', 'user', 'router_name'));
    }

    public function update(Request $request, User $user)
    {
        $router_name = $user->detail->router_name;
        $router = Router::where("name", $router_name)->firstOrFail();
        $package = Package::where("id", $request->package_name)->firstOrFail();

        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            $query = new Query("/ppp/secret/set");
            $query->equal("numbers", $user->name);
            $query->equal("profile", $package->name);
            $client->query($query)->read();
        } catch (\Exception $e) {
            return back()->with("error", __("Mikrotik connection fails"));
        }

        $user->detail->update([
            "package_name" => $package->name,
            "package_price" => $package->price,
            "package_start" => Carbon::now(),
        ]);

        return redirect('users')->with("success", __("Package changed successfully"));
    }
}
