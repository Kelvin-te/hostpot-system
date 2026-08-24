<?php

namespace Tests\Feature\Api\V1;

use App\Models\RadiusNas;
use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RouterControllerTest extends TestCase
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

    protected function routerPayload(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'router-abc123',
            'name' => 'Sterke Main',
            'identifier' => 'abc123',
            'nasname' => '10.50.0.2',
            'type' => 'mikrotik',
            'status' => 'active',
            'radius_secret' => 'a-real-shared-secret',
        ], $overrides);
    }

    public function test_radius_secret_is_required(): void
    {
        $response = $this->withToken($this->plainTextToken)->postJson(
            '/api/v1/routers',
            $this->routerPayload(['radius_secret' => null])
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('radius_secret');
    }

    public function test_radius_secret_is_stored_encrypted_and_never_returned(): void
    {
        $response = $this->withToken($this->plainTextToken)->postJson(
            '/api/v1/routers',
            $this->routerPayload()
        );

        $response->assertOk();
        $this->assertStringNotContainsString('a-real-shared-secret', $response->getContent());

        $nas = RadiusNas::where('tenant_id', $this->tenant->id)
            ->where('external_id', 'router-abc123')
            ->firstOrFail();

        $this->assertNotSame('placeholder', $nas->radius_secret_encrypted);
        $this->assertSame('a-real-shared-secret', Crypt::decryptString($nas->radius_secret_encrypted));
    }

    public function test_radius_secret_plain_is_populated_for_freeradius_but_never_returned(): void
    {
        $response = $this->withToken($this->plainTextToken)->postJson(
            '/api/v1/routers',
            $this->routerPayload()
        );

        $response->assertOk();
        $this->assertStringNotContainsString('a-real-shared-secret', $response->getContent());

        $nas = RadiusNas::where('tenant_id', $this->tenant->id)
            ->where('external_id', 'router-abc123')
            ->firstOrFail();

        $this->assertSame('a-real-shared-secret', $nas->radius_secret_plain);
        $this->assertArrayNotHasKey('radius_secret_plain', $nas->toArray());
        $this->assertArrayNotHasKey('radius_secret_encrypted', $nas->toArray());
    }

    public function test_router_sync_is_idempotent(): void
    {
        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', $this->routerPayload())->assertOk();
        $this->withToken($this->plainTextToken)->postJson('/api/v1/routers', $this->routerPayload([
            'radius_secret' => 'a-rotated-secret',
        ]))->assertOk();

        $this->assertSame(
            1,
            RadiusNas::where('tenant_id', $this->tenant->id)->where('external_id', 'router-abc123')->count()
        );

        $nas = RadiusNas::where('tenant_id', $this->tenant->id)->where('external_id', 'router-abc123')->firstOrFail();
        $this->assertSame('a-rotated-secret', Crypt::decryptString($nas->radius_secret_encrypted));
    }
}
