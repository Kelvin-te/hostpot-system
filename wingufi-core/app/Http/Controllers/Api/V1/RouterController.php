<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RadiusNas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_id' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:100'],
            'nasname' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $router = RadiusNas::updateOrCreate(
            [
                'tenant_id' => $this->tenantId(),
                'external_id' => $request->input('external_id'),
                'source_system' => 'api',
            ],
            [
                'name' => $request->input('name'),
                'identifier' => $request->input('identifier'),
                'nasname' => $request->input('nasname'),
                'type' => $request->input('type'),
                'status' => $request->input('status'),
                'radius_secret_encrypted' => 'placeholder', // Phase 3 will manage real secrets
            ]
        );

        return $this->success([
            'id' => $router->id,
            'external_id' => $router->external_id,
            'name' => $router->name,
            'identifier' => $router->identifier,
            'nasname' => $router->nasname,
            'type' => $router->type,
            'status' => $router->status,
        ]);
    }
}
