<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use DatabaseTruncation;

    protected array $connectionsToTruncate = ['wingufi_core'];

    protected ?Tenant $tenant = null;
    protected ?TenantCredential $credential = null;
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

        $this->credential = TenantCredential::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test credential',
            'client_id' => 'test_client_id',
            'client_secret_hash' => Hash::make($secret),
            'status' => 'active',
        ]);
    }

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_valid_token_returns_200(): void
    {
        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', [
            'external_id' => 'router-1',
            'name' => 'Router 1',
            'identifier' => 'router-1',
            'nasname' => '203.0.113.10',
            'type' => 'mikrotik',
            'status' => 'active',
        ])->assertOk();
    }

    public function test_missing_token_returns_401(): void
    {
        $this->postJson('/api/v1/routers', [
            'external_id' => 'router-1',
            'name' => 'Router 1',
            'identifier' => 'router-1',
            'nasname' => '203.0.113.10',
            'type' => 'mikrotik',
            'status' => 'active',
        ])->assertUnauthorized();
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->withHeader('Authorization', 'Bearer test_client_id:wrong-secret')
            ->postJson('/api/v1/routers', [
                'external_id' => 'router-1',
                'name' => 'Router 1',
                'identifier' => 'router-1',
                'nasname' => '203.0.113.10',
                'type' => 'mikrotik',
                'status' => 'active',
            ])->assertUnauthorized();
    }

    public function test_revoked_token_returns_401(): void
    {
        $this->credential->update(['status' => 'revoked', 'revoked_at' => now()]);

        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', [
            'external_id' => 'router-1',
            'name' => 'Router 1',
            'identifier' => 'router-1',
            'nasname' => '203.0.113.10',
            'type' => 'mikrotik',
            'status' => 'active',
        ])->assertUnauthorized();
    }

    public function test_expired_token_returns_401(): void
    {
        $this->credential->update(['expires_at' => now()->subMinute()]);

        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', [
            'external_id' => 'router-1',
            'name' => 'Router 1',
            'identifier' => 'router-1',
            'nasname' => '203.0.113.10',
            'type' => 'mikrotik',
            'status' => 'active',
        ])->assertUnauthorized();
    }

    public function test_tenant_a_cannot_access_tenant_b_resources(): void
    {
        $tenantB = Tenant::create([
            'uuid' => 'tenant-b',
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'code' => 'TENB',
            'status' => 'active',
        ]);

        $tenantB->clients()->create([
            'uuid' => 'client-b',
            'username' => 'client-b',
            'external_id' => 'client-b',
            'source_system' => 'api',
            'status' => 'active',
        ]);

        $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'client-b',
            'username' => 'client-b-again',
            'status' => 'active',
        ])->assertOk();

        $tenantBClient = $tenantB->clients()->where('external_id', 'client-b')->first();
        $this->assertNotNull($tenantBClient);
        $this->assertSame('client-b', $tenantBClient->username);

        $tenantAClient = $this->tenant->clients()->where('external_id', 'client-b')->first();
        $this->assertNotNull($tenantAClient);
        $this->assertSame('client-b-again', $tenantAClient->username);
    }

    public function test_authorization_for_other_tenants_client_is_forbidden(): void
    {
        $tenantB = Tenant::create([
            'uuid' => 'tenant-b',
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'code' => 'TENB',
            'status' => 'active',
        ]);

        $clientB = $tenantB->clients()->create([
            'uuid' => 'client-b',
            'username' => 'client-b',
            'external_id' => 'client-b',
            'source_system' => 'api',
            'status' => 'active',
        ]);

        $packageA = $this->tenant->packages()->create([
            'name' => 'Package A',
            'code' => 'PCK-A',
            'external_id' => 'package-a',
            'source_system' => 'api',
            'status' => 'active',
        ]);

        $this->withToken($this->plainTextToken)->postJson('/api/v1/authorizations', [
            'external_id' => 'auth-b',
            'client_external_id' => $clientB->external_id,
            'package_external_id' => $packageA->external_id,
            'status' => 'active',
            'starts_at' => now()->toIso8601String(),
            'expires_at' => now()->addDay()->toIso8601String(),
        ])->assertStatus(422);
    }

    public function test_repeated_sync_is_idempotent(): void
    {
        $payload = [
            'external_id' => 'router-1',
            'name' => 'Router 1',
            'identifier' => 'router-1',
            'nasname' => '203.0.113.10',
            'type' => 'mikrotik',
            'status' => 'active',
        ];

        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', $payload)->assertOk();
        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', $payload)->assertOk();

        $this->assertCount(1, $this->tenant->nasDevices()->where('external_id', 'router-1')->get());
    }

    public function test_validation_errors_return_422(): void
    {
        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', [
            'external_id' => '',
        ])->assertUnprocessable();
    }
}
