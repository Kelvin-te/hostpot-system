<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RadiusNas;
use App\Models\RadiusSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    /**
     * List RADIUS sessions (active and historical) belonging to the
     * authenticated tenant. Populated by FreeRADIUS's sql module writing
     * directly into wingufi_core.radius_sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'router_external_id' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,stopped'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $query = RadiusSession::where('tenant_id', $this->tenantId());

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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from')) {
            $query->where('start_time', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('start_time', '<=', $request->input('to'));
        }

        $perPage = (int) $request->input('per_page', 25);

        $sessions = $query->orderByDesc('start_time')->paginate($perPage);

        return $this->success([
            'sessions' => $sessions->getCollection()->map(fn (RadiusSession $session) => [
                'acct_session_id' => $session->acct_session_id,
                'username' => $session->username,
                'client_mac' => $session->client_mac,
                'client_ip' => $session->client_ip,
                'framed_ip' => $session->framed_ip,
                'start_time' => optional($session->start_time)->toIso8601String(),
                'last_update_time' => optional($session->last_update_time)->toIso8601String(),
                'stop_time' => optional($session->stop_time)->toIso8601String(),
                'session_time' => $session->session_time,
                'input_octets' => $session->input_octets,
                'output_octets' => $session->output_octets,
                'input_packets' => $session->input_packets,
                'output_packets' => $session->output_packets,
                'terminate_cause' => $session->terminate_cause,
                'status' => $session->status,
            ])->values(),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
        ]);
    }
}
