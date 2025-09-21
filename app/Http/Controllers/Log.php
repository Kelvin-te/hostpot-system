<?php

namespace App\Http\Controllers;

use RouterOS\Client;
use RouterOS\Query;
use App\Models\Router;

class Log extends Controller
{
    public function __invoke($log)
    {
        $router = Router::where("id", $log)->firstOrFail();
        
        try {
            $client = new Client([
                "host" => $router->ip,
                "user" => $router->username,
                "pass" => $router->password,
            ]);

            $query = new Query("/log/print");
            $logs = $client->query($query)->read();
        } catch (\Exception $e) {
            return back()->with("error", __("Mikrotik connection fails"));
        }

        return view('log', compact('logs'));
    }
}
