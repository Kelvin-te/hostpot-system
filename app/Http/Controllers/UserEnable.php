<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\User;
use RouterOS\Client;
use RouterOS\Query;

class UserEnable extends Controller
{
    public function __invoke(User $user)
    {
        $router_name = $user->detail->router_name;
        $router = Router::where("name", $router_name)->firstOrFail();
        
        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            $query = new Query("/ppp/secret/enable");
            $query->equal("numbers", $user->name);
            $client->query($query)->read();
        } catch (\Exception $e) {
            return back()->with("error", __("Mikrotik connection fails"));
        }

        $user->detail->update(["status" => 'active']);
        return back()->with("success", __("User enabled successfully"));
    }
}
