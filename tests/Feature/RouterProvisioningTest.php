<?php

namespace Tests\Feature;

use App\Models\RadiusNas;
use App\Models\Router;
use App\Models\Staff;
use App\Services\MikroTikService;
use App\Services\WinguFiCoreService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RouterProvisioningTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function validRouterPayload(string $name = 'Test Router'): array
    {
        return [
            'name' => $name,
            'location' => 'Nairobi',
            'ip' => '10.50.0.2',
            'username' => 'admin',
            'password' => 'secret',
            'api_port' => 8728,
        ];
    }

    protected function mockSuccessfulMikroTik(): void
    {
        $this->mock(MikroTikService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn(['success' => true, 'message' => 'ok']);
            $mock->shouldReceive('testHotspotService')->andReturn([
                'success' => true,
                'enabled' => true,
                'interface' => 'hotspot1',
                'server_ip' => '10.50.0.1',
            ]);
            $mock->shouldReceive('provisionRadiusClient')->andReturn(['success' => true, 'message' => 'RADIUS client provisioned successfully']);
        });
    }

    public function test_router_creation_triggers_local_radius_provisioning(): void
    {
        $this->mockSuccessfulMikroTik();
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('syncRouter')->once()->andReturn(['data' => ['id' => 1]]);
        });

        $staff = Staff::factory()->admin()->create();

        $response = $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload());

        $response->assertRedirect('router');
        $response->assertSessionHas('success');

        $router = Router::where('name', 'Test Router')->firstOrFail();
        $nas = RadiusNas::where('router_id', $router->id)->first();

        $this->assertNotNull($nas);
        $this->assertTrue($nas->is_active);
        $this->assertSame($router->identifier, $nas->nas_identifier);
    }

    public function test_router_creation_calls_wingufi_core_sync_with_the_provisioned_nas(): void
    {
        $this->mockSuccessfulMikroTik();

        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('syncRouter')
                ->once()
                ->withArgs(function (Router $router, ?RadiusNas $nas) {
                    return $nas !== null && $nas->router_id === $router->id && !empty($nas->nas_secret);
                })
                ->andReturn(['data' => ['id' => 1]]);
        });

        $staff = Staff::factory()->admin()->create();

        $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload())
            ->assertSessionHas('success');
    }

    public function test_router_creation_reports_failure_when_local_radius_provisioning_fails(): void
    {
        $this->mock(MikroTikService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn(['success' => true, 'message' => 'ok']);
            $mock->shouldReceive('testHotspotService')->andReturn([
                'success' => true,
                'enabled' => true,
                'interface' => 'hotspot1',
                'server_ip' => '10.50.0.1',
            ]);
            $mock->shouldReceive('provisionRadiusClient')->andReturn(['success' => false, 'message' => 'Cannot connect to router']);
        });

        // Core sync must never be attempted if local provisioning failed.
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldNotReceive('syncRouter');
        });

        $staff = Staff::factory()->admin()->create();

        $response = $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload());

        $response->assertRedirect('router');
        $response->assertSessionMissing('success');
        $response->assertSessionHas('warning', function ($value) {
            return str_contains($value, 'Cannot connect to router');
        });

        $router = Router::where('name', 'Test Router')->firstOrFail();
        $this->assertNull(RadiusNas::where('router_id', $router->id)->where('is_active', true)->first());
    }

    public function test_router_creation_reports_failure_when_core_sync_fails(): void
    {
        $this->mockSuccessfulMikroTik();

        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('syncRouter')->once()->andThrow(new \RuntimeException('WinguFi Core API error (500): boom'));
        });

        $staff = Staff::factory()->admin()->create();

        $response = $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload());

        $response->assertRedirect('router');
        $response->assertSessionMissing('success');
        $response->assertSessionHas('warning', function ($value) {
            return str_contains($value, 'WinguFi Core sync failed');
        });

        // Local provisioning already succeeded and must not be undone because Core sync failed.
        $router = Router::where('name', 'Test Router')->firstOrFail();
        $nas = RadiusNas::where('router_id', $router->id)->first();
        $this->assertNotNull($nas);
        $this->assertTrue($nas->is_active);
    }

    public function test_repeated_provisioning_is_idempotent(): void
    {
        $this->mockSuccessfulMikroTik();
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('syncRouter')->twice()->andReturn(['data' => ['id' => 1]]);
        });

        $staff = Staff::factory()->admin()->create();

        $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload())
            ->assertSessionHas('success');

        $router = Router::where('name', 'Test Router')->firstOrFail();
        $firstNas = RadiusNas::where('router_id', $router->id)->firstOrFail();
        $originalSecret = $firstNas->nas_secret;

        // Manually re-trigger provisioning (as the "Provision RADIUS" button would).
        $response = $this->actingAs($staff, 'staff')
            ->postJson("/router/{$router->id}/provision-radius");

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertSame(1, RadiusNas::where('router_id', $router->id)->count());

        $secondNas = RadiusNas::where('router_id', $router->id)->firstOrFail();
        $this->assertSame($originalSecret, $secondNas->nas_secret);
        $this->assertTrue($secondNas->is_active);
    }

    public function test_router_update_reprovisions_radius_and_core_when_ip_changes(): void
    {
        $this->mockSuccessfulMikroTik();
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            // Once for creation, once for the IP-change re-provisioning.
            $mock->shouldReceive('syncRouter')->twice()->andReturn(['data' => ['id' => 1]]);
        });

        $staff = Staff::factory()->admin()->create();

        $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload())
            ->assertSessionHas('success');

        $router = Router::where('name', 'Test Router')->firstOrFail();
        $originalNas = RadiusNas::where('router_id', $router->id)->firstOrFail();

        $response = $this->actingAs($staff, 'staff')
            ->put("/router/{$router->id}", [
                'location' => $router->location,
                'ip' => '10.50.0.3',
                'username' => $router->username,
                'password' => 'secret',
                'api_port' => $router->api_port,
            ]);

        $response->assertRedirect('router');
        $response->assertSessionHas('success', function ($value) {
            return str_contains($value, 're-provisioned');
        });

        $router->refresh();
        $this->assertSame('10.50.0.3', $router->ip);

        $nas = RadiusNas::where('router_id', $router->id)->firstOrFail();
        $this->assertSame($originalNas->id, $nas->id, 'Update must not create a duplicate NAS record.');
        $this->assertSame('10.50.0.3', $nas->nas_ip_address);
        $this->assertSame($originalNas->nas_secret, $nas->nas_secret, 'The RADIUS secret must be preserved across re-provisioning.');
    }

    public function test_router_update_does_not_reprovision_when_ip_is_unchanged(): void
    {
        $this->mockSuccessfulMikroTik();
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            // Only once, for creation. Update must not trigger a second sync.
            $mock->shouldReceive('syncRouter')->once()->andReturn(['data' => ['id' => 1]]);
        });

        $staff = Staff::factory()->admin()->create();

        $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload())
            ->assertSessionHas('success');

        $router = Router::where('name', 'Test Router')->firstOrFail();

        $response = $this->actingAs($staff, 'staff')
            ->put("/router/{$router->id}", [
                'location' => 'Updated Location',
                'ip' => $router->ip,
                'username' => $router->username,
                'password' => 'secret',
                'api_port' => $router->api_port,
            ]);

        $response->assertRedirect('router');
        $response->assertSessionHas('success', __('Router updated successfully'));
    }
}
