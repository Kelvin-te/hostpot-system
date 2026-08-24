<?php

namespace Tests\Feature\Api\V1;

use App\Models\RadiusNas;
use App\Models\RadiusSession;
use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use DatabaseTruncation;

    protected array $connectionsToTruncate = ['wingufi_core'];

    protected ?Tenant $tenant = null;
    protected string $plainTextToken = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'uuid' => 'tenant-a',
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'code' => 'TENA',
            'status' => 'active',
        ]);

        $secret = 'test-secret-'.uniqid();
        $this->plainTextToken = 'test_client_id:'.$secret;

        TenantCredential::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test credential',
            'client_id' => 'test_client_id',
            'client_secret_hash' => Hash::make($secret),
            'status' => 'active',
        ]);
    }

    protected function createNas(array $overrides = []): RadiusNas
    {
        return RadiusNas::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Router A',
            'nasname' => '10.50.0.2',
            'type' => 'mikrotik',
            'identifier' => 'router-a',
            'status' => 'active',
            'radius_secret_encrypted' => 'placeholder',
            'radius_secret_plain' => 'placeholder',
            'external_id' => 'router-abc123',
            'source_system' => 'api',
        ], $overrides));
    }

    protected function createSession(RadiusNas $nas, array $overrides = []): RadiusSession
    {
        return RadiusSession::create(array_merge([
            'tenant_id' => $nas->tenant_id,
            'nas_id' => $nas->id,
            'username' => 'guest_001',
            'acct_session_id' => 'sess-'.uniqid(),
            'start_time' => now()->subMinutes(10),
            'last_update_time' => now(),
            'status' => 'active',
        ], $overrides));
    }

    public function test_sessions_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/sessions')->assertStatus(401);
    }

    public function test_sessions_endpoint_returns_only_the_tenants_own_sessions(): void
    {
        $nas = $this->createNas();
        $this->createSession($nas, ['username' => 'mine']);

        $otherTenant = Tenant::create([
            'uuid' => 'tenant-b', 'name' => 'Tenant B', 'slug' => 'tenant-b', 'code' => 'TENB', 'status' => 'active',
        ]);
        $otherNas = RadiusNas::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Router B',
            'nasname' => '10.50.0.9',
            'type' => 'mikrotik',
            'identifier' => 'router-b',
            'status' => 'active',
            'radius_secret_encrypted' => 'placeholder',
            'external_id' => 'router-other',
            'source_system' => 'api',
        ]);
        $this->createSession($otherNas, ['username' => 'not-mine']);

        $response = $this->withToken($this->plainTextToken)->getJson('/api/v1/sessions');

        $response->assertOk();
        $usernames = collect($response->json('data.sessions'))->pluck('username');
        $this->assertTrue($usernames->contains('mine'));
        $this->assertFalse($usernames->contains('not-mine'));
    }

    public function test_sessions_can_be_filtered_by_router_external_id(): void
    {
        $nasA = $this->createNas(['external_id' => 'router-a', 'identifier' => 'router-a', 'nasname' => '10.50.0.2']);
        $nasB = $this->createNas(['external_id' => 'router-b', 'identifier' => 'router-b', 'nasname' => '10.50.0.3']);

        $this->createSession($nasA, ['username' => 'on-a']);
        $this->createSession($nasB, ['username' => 'on-b']);

        $response = $this->withToken($this->plainTextToken)
            ->getJson('/api/v1/sessions?router_external_id=router-a');

        $response->assertOk();
        $usernames = collect($response->json('data.sessions'))->pluck('username');
        $this->assertTrue($usernames->contains('on-a'));
        $this->assertFalse($usernames->contains('on-b'));
    }

    public function test_sessions_can_be_filtered_by_status(): void
    {
        $nas = $this->createNas();
        $this->createSession($nas, ['username' => 'still-active', 'status' => 'active']);
        $this->createSession($nas, ['username' => 'finished', 'status' => 'stopped', 'stop_time' => now()]);

        $response = $this->withToken($this->plainTextToken)
            ->getJson('/api/v1/sessions?status=stopped');

        $response->assertOk();
        $usernames = collect($response->json('data.sessions'))->pluck('username');
        $this->assertTrue($usernames->contains('finished'));
        $this->assertFalse($usernames->contains('still-active'));
    }

    public function test_unknown_router_external_id_returns_404(): void
    {
        $response = $this->withToken($this->plainTextToken)
            ->getJson('/api/v1/sessions?router_external_id=does-not-exist');

        $response->assertStatus(404);
    }
}
