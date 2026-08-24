<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RadiusAccounting;
use App\Models\RadiusNas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountingController extends Controller
{
    /**
     * List raw RADIUS accounting events (Start/Interim-Update/Stop) for the
     * authenticated tenant. Populated by FreeRADIUS's sql module writing
     * directly into wingufi_core.radius_accounting.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'router_external_id' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'acct_status_type' => ['nullable', 'in:Start,Interim-Update,Stop'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $query = RadiusAccounting::where('tenant_id', $this->tenantId());

        if ($request->filled('router_external_id')) {
            $nas = RadiusNas::where('tenant_id', $this->tenantId())
                ->where('external_id', $request->input('router_external_id'))
                ->where('source_system', 'api')
                ->first();

            if (! $nas) {
                return $this->error('Router not found', 404);
            }

            $query->where('nas_id', $nas->id);
        }

        if ($request->filled('username')) {
            $query->where('username', $request->input('username'));
        }

        if ($request->filled('acct_status_type')) {
            $query->where('acct_status_type', $request->input('acct_status_type'));
        }

        if ($request->filled('from')) {
            $query->where('event_time', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('event_time', '<=', $request->input('to'));
        }

        $perPage = (int) $request->input('per_page', 25);

        $events = $query->orderByDesc('event_time')->paginate($perPage);

        return $this->success([
            'accounting' => $events->getCollection()->map(fn (RadiusAccounting $event) => [
                'acct_session_id' => $event->acct_session_id,
                'acct_status_type' => $event->acct_status_type,
                'username' => $event->username,
                'client_mac' => $event->client_mac,
                'client_ip' => $event->client_ip,
                'framed_ip' => $event->framed_ip,
                'session_time' => $event->session_time,
                'input_octets' => $event->input_octets,
                'output_octets' => $event->output_octets,
                'input_packets' => $event->input_packets,
                'output_packets' => $event->output_packets,
                'terminate_cause' => $event->terminate_cause,
                'event_time' => optional($event->event_time)->toIso8601String(),
            ])->values(),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }
}
