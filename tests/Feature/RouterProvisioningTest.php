<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Staff;
use App\Services\MikroTikService;
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
        });
    }

    public function test_router_creation_succeeds_without_radius_provisioning(): void
    {
        $this->mockSuccessfulMikroTik();

        $staff = Staff::factory()->admin()->create();

        $response = $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload());

        $response->assertRedirect('router');
        $response->assertSessionHas('success');

        $router = Router::where('name', 'Test Router')->firstOrFail();
        $this->assertSame('10.50.0.2', $router->ip);
        $this->assertSame(1, $router->hotspot_enabled);
    }

    public function test_router_creation_reports_hotspot_warning(): void
    {
        $this->mock(MikroTikService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn(['success' => true, 'message' => 'ok']);
            $mock->shouldReceive('testHotspotService')->andReturn([
                'success' => true,
                'enabled' => true,
                'interface' => 'hotspot1',
                'server_ip' => '10.50.0.1',
                'server_ip_warning' => 'Hotspot gateway IP could not be detected',
            ]);
        });

        $staff = Staff::factory()->admin()->create();

        $response = $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload());

        $response->assertRedirect('router');
        $response->assertSessionHas('warning', function ($value) {
            return str_contains($value, 'Hotspot gateway IP could not be detected');
        });
    }

    public function test_router_update_succeeds(): void
    {
        $this->mockSuccessfulMikroTik();

        $staff = Staff::factory()->admin()->create();

        $this->actingAs($staff, 'staff')
            ->post('/router', $this->validRouterPayload())
            ->assertSessionHas('success');

        $router = Router::where('name', 'Test Router')->firstOrFail();

        $response = $this->actingAs($staff, 'staff')
            ->put("/router/{$router->id}", [
                'location' => 'Updated Location',
                'ip' => '10.50.0.3',
                'username' => $router->username,
                'password' => 'secret',
                'api_port' => $router->api_port,
            ]);

        $response->assertRedirect('router');
        $response->assertSessionHas('success');

        $router->refresh();
        $this->assertSame('10.50.0.3', $router->ip);
    }
}
