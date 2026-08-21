<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\NetworkAuthorization;
use App\Models\NetworkClient;
use App\Models\NetworkPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthorizationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_id' => ['required', 'string', 'max:100'],
            'client_external_id' => ['required', 'string', 'max:100'],
            'package_external_id' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,revoked,expired,suspended'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $client = NetworkClient::where('tenant_id', $this->tenantId())
            ->where('external_id', $request->input('client_external_id'))
            ->where('source_system', 'api')
            ->first();

        if (! $client) {
            return $this->error('Client not found', 422);
        }

        $package = NetworkPackage::where('tenant_id', $this->tenantId())
            ->where('external_id', $request->input('package_external_id'))
            ->where('source_system', 'api')
            ->first();

        if (! $package) {
            return $this->error('Package not found', 422);
        }

        $revokedAt = null;
        if ($request->input('status') === 'revoked') {
            $revokedAt = now();
        }

        $authorization = NetworkAuthorization::updateOrCreate(
            [
                'tenant_id' => $this->tenantId(),
                'external_id' => $request->input('external_id'),
                'source_system' => 'api',
            ],
            [
                'client_id' => $client->id,
                'package_id' => $package->id,
                'source_type' => 'api',
                'source_id' => $request->input('external_id'),
                'username' => $client->username,
                'status' => $request->input('status'),
                'starts_at' => $request->input('starts_at'),
                'expires_at' => $request->input('expires_at'),
                'session_timeout' => $package->session_timeout,
                'download_speed' => $package->download_speed,
                'upload_speed' => $package->upload_speed,
                'data_limit_bytes' => $package->data_limit_bytes,
                'data_used_bytes' => 0,
                'simultaneous_sessions' => $package->simultaneous_sessions,
                'revoked_at' => $revokedAt,
            ]
        );

        return $this->success([
            'id' => $authorization->id,
            'external_id' => $authorization->external_id,
            'client_external_id' => $client->external_id,
            'package_external_id' => $package->external_id,
            'status' => $authorization->status,
            'starts_at' => $authorization->starts_at->toIso8601String(),
            'expires_at' => $authorization->expires_at->toIso8601String(),
            'attributes' => [
                'download_speed' => $authorization->download_speed,
                'upload_speed' => $authorization->upload_speed,
                'session_timeout' => $authorization->session_timeout,
                'data_limit_bytes' => $authorization->data_limit_bytes,
            ],
        ]);
    }

    public function update(Request $request, string $externalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:active,revoked,expired,suspended'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $authorization = NetworkAuthorization::where('tenant_id', $this->tenantId())
            ->where('external_id', $externalId)
            ->where('source_system', 'api')
            ->first();

        if (! $authorization) {
            return $this->error('Authorization not found', 404);
        }

        $revokedAt = $authorization->revoked_at;
        if ($request->input('status') === 'revoked' && $authorization->status !== 'revoked') {
            $revokedAt = now();
        }

        if ($request->input('status') !== 'revoked') {
            $revokedAt = null;
        }

        $authorization->update([
            'status' => $request->input('status'),
            'revoked_at' => $revokedAt,
        ]);

        return $this->success([
            'id' => $authorization->id,
            'external_id' => $authorization->external_id,
            'status' => $authorization->status,
            'starts_at' => $authorization->starts_at->toIso8601String(),
            'expires_at' => $authorization->expires_at->toIso8601String(),
            'attributes' => [
                'download_speed' => $authorization->download_speed,
                'upload_speed' => $authorization->upload_speed,
                'session_timeout' => $authorization->session_timeout,
                'data_limit_bytes' => $authorization->data_limit_bytes,
            ],
        ]);
    }
}
