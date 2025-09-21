<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use App\Models\TimeZone;
use RouterOS\Client;
use RouterOS\Query;

class SettingController extends Controller
{
    public function edit()
    {
        return redirect('/');
    }

    public function update(SettingRequest $request)
    {
        try {
            $client = new Client([
                'host' => $request->router_ip,
                'user' => $request->router_username,
                'pass' => $request->router_password,
            ]);
            $client->query(new Query('/system/resource/print'));

            $settings = Setting::firstOrNew();
            $settings->fill($request->validated());
            $settings->save();
        } catch (\Exception $e) {
            return back()->with('error', __('Unable to connect to Mikrotik router'));
        }

        return back()->with('success', __('Update successful'));
    }
}
