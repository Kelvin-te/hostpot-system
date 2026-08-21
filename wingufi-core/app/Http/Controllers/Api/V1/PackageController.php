<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\NetworkPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_id' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'download_speed' => ['nullable', 'integer'],
            'upload_speed' => ['nullable', 'integer'],
            'session_timeout' => ['nullable', 'integer'],
            'validity_seconds' => ['nullable', 'integer'],
            'data_limit_bytes' => ['nullable', 'integer'],
            'simultaneous_sessions' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $package = NetworkPackage::updateOrCreate(
            [
                'tenant_id' => $this->tenantId(),
                'external_id' => $request->input('external_id'),
                'source_system' => 'api',
            ],
            [
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'description' => $request->input('description'),
                'download_speed' => $request->input('download_speed'),
                'upload_speed' => $request->input('upload_speed'),
                'session_timeout' => $request->input('session_timeout'),
                'validity_seconds' => $request->input('validity_seconds'),
                'data_limit_bytes' => $request->input('data_limit_bytes'),
                'simultaneous_sessions' => $request->input('simultaneous_sessions', 1),
                'status' => $request->input('status'),
            ]
        );

        return $this->success([
            'id' => $package->id,
            'external_id' => $package->external_id,
            'name' => $package->name,
            'code' => $package->code,
            'attributes' => [
                'download_speed' => $package->download_speed,
                'upload_speed' => $package->upload_speed,
                'session_timeout' => $package->session_timeout,
                'data_limit_bytes' => $package->data_limit_bytes,
            ],
            'status' => $package->status,
        ]);
    }
}
