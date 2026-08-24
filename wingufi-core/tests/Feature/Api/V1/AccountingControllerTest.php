<?php

namespace Tests\Feature\Api\V1;

use App\Models\RadiusAccounting;
use App\Models\RadiusNas;
use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountingControllerTest extends TestCase
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

    protected function createEvent(RadiusNas $nas, array $overrides = []): RadiusAccounting
    {
        return RadiusAccounting::create(array_merge([
            'tenant_id' => $nas->tenant_id,
            'nas_id' => $nas->id,
            'username' => 'guest_001',
            'acct_session_id' => 'sess-'.uniqid(),
            'acct_status_type' => 'Start',
            'event_time' => now(),
        ], $overrides));
    }

    public function test_accounting_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/accounting')->assertStatus(401);
    }

    public function test_accounting_endpoint_returns_only_the_tenants_own_events(): void
    {
        $nas = $this->createNas();
        $this->createEvent($nas, ['username' => 'mine']);

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
        $this->createEvent($otherNas, ['username' => 'not-mine']);

        $response = $this->withToken($this->plainTextToken)->getJson('/api/v1/accounting');

        $response->assertOk();
        $usernames = collect($response->json('data.accounting'))->pluck('username');
        $this->assertTrue($usernames->contains('mine'));
        $this->assertFalse($usernames->contains('not-mine'));
    }

    public function test_accounting_can_be_filtered_by_status_type(): void
    {
        $nas = $this->createNas();
        $this->createEvent($nas, ['username' => 'started', 'acct_status_type' => 'Start']);
        $this->createEvent($nas, ['username' => 'ended', 'acct_status_type' => 'Stop']);

        $response = $this->withToken($this->plainTextToken)
            ->getJson('/api/v1/accounting?acct_status_type=Stop');

        $response->assertOk();
        $usernames = collect($response->json('data.accounting'))->pluck('username');
        $this->assertTrue($usernames->contains('ended'));
        $this->assertFalse($usernames->contains('started'));
    }
}
