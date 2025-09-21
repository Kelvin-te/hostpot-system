<?php

namespace App\Http\Controllers;

use App\Models\User;
use RouterOS\Client;
use RouterOS\Query;
use App\Models\Router;

class DisableDueUser extends Controller
{
    public function __invoke()
    {
        $users = User::all();

        foreach ($users as $user) {
            if ($user->due_amount($user->id) > 0) {
                $router_name = $user->detail->router_name;
                $router = Router::where("name", $router_name)->firstOrFail();

                try {
                    $client = new Client([
                        "host" => $router->ip,
                        "user" => $router->username,
                        "pass" => $router->password,
                    ]);
            
                    $query = new Query("/ppp/secret/disable");
                    $query->equal("numbers", $user->name);
                    $client->query($query)->read();
                } catch (\Exception $e) {
                    return back()->with("error", __("Mikrotik connection fails"));
                }

                $user->detail->update(["status" => 'inactive']);
            }
        }

        return back()->with("success", __("User disabled successfully"));
    }
}
