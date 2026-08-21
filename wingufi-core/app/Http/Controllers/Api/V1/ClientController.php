<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\NetworkClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_id' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,suspended,disabled'],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $attributes = [
            'uuid' => Str::uuid(),
            'username' => $request->input('username'),
            'display_name' => $request->input('display_name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'mac_address' => $request->input('mac_address'),
            'status' => $request->input('status'),
        ];

        if ($request->filled('password')) {
            $attributes['password_hash'] = Hash::make($request->input('password'));
            $attributes['password_type'] = 'bcrypt';
        }

        $client = NetworkClient::updateOrCreate(
            [
                'tenant_id' => $this->tenantId(),
                'external_id' => $request->input('external_id'),
                'source_system' => 'api',
            ],
            $attributes
        );

        return $this->success([
            'id' => $client->id,
            'external_id' => $client->external_id,
            'username' => $client->username,
            'display_name' => $client->display_name,
            'phone' => $client->phone,
            'email' => $client->email,
            'mac_address' => $client->mac_address,
            'status' => $client->status,
        ]);
    }
}
