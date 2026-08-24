<?php

namespace Tests\Feature;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression test: MikroTik routers commonly have TWO distinct addresses:
 *
 * - routers.ip / ip_address: the management/API/WireGuard address used by
 *   the Laravel server to reach the router (e.g. 10.50.0.2). This must
 *   never be used to validate the HotSpot login URL.
 * - routers.hotspot_server_ip: the HotSpot gateway/LAN address that
 *   MikroTik actually uses to generate `link-login` when dns-name is
 *   empty (e.g. 192.168.30.1).
 *
 * handoff() previously compared link-login's host against routers.ip,
 * which incorrectly rejected legitimate MikroTik-generated login URLs
 * ("Handoff link_login host does not match router").
 */
class CaptivePortalHotspotGatewayValidationTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wingufi.base_url', 'https://wingufi-core.test/api/v1');
        config()->set('services.wingufi.token', 'wc_test:secret');
        config()->set('services.wingufi.enabled', true);
        config()->set('services.wingufi.source_system', 'test');

        Http::fake([
            'https://wingufi-core.test/api/v1/*' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'wf-auth-123',
                    'external_id' => 'auth-test-ptx-1',
                    'status' => 'active',
                ],
            ], 200),
        ]);
    }

    private function createRouterWithDualIps(): Router
    {
        return Router::create([
            'name' => 'Test Router ' . Str::random(8),
            'identifier' => 'rtr-' . Str::random(8),
            'location' => 'Test Location',
            // Management/API/WireGuard IP - must remain unused for link-login validation.
            'ip' => '10.50.0.2',
            'ip_address' => '10.50.0.2',
            // HotSpot gateway IP - what MikroTik actually uses in link-login.
            'hotspot_server_ip' => '192.168.30.1',
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createPackage(Router $router): Package
    {
        return Package::create([
            'name' => 'Free Package',
            'price' => 0,
            'router_id' => $router->id,
            'bandwidth_download' => 10,
            'bandwidth_upload' => 5,
            'validity_minutes' => 60,
            'session_timeout' => 1,
            'shared_users' => 1,
            'is_active' => true,
        ]);
    }

    public function test_handoff_accepts_link_login_using_hotspot_gateway_ip_not_management_ip(): void
    {
        $router = $this->createRouterWithDualIps();
        $package = $this->createPackage($router);

        $this->assertSame('10.50.0.2', $router->ip);
        $this->assertSame('192.168.30.1', $router->hotspot_server_ip);

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.30.200',
            'link-login' => 'http://192.168.30.1/login?dst=http://example.com',
            'link-orig' => 'http://example.com',
        ]));

        $session = HotspotSession::first();
        $this->assertNotNull($session);

        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $session->session_id], now()->addMinutes(5));

        $response = $this->get($handoffUrl);

        $response->assertStatus(200);
        $response->assertSee('Connecting you to the internet');
        $response->assertSee('http://192.168.30.1/login?dst=http://example.com', false);
    }

    public function test_handoff_still_rejects_untrusted_link_login_host(): void
    {
        $router = $this->createRouterWithDualIps();
        $package = $this->createPackage($router);

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:66',
            'ip' => '192.168.30.201',
            'link-login' => 'http://evil.example.com/login',
            'link-orig' => 'http://example.com',
        ]));

        $session = HotspotSession::first();
        $this->assertNotNull($session);

        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $session->session_id], now()->addMinutes(5));

        $this->get($handoffUrl)->assertRedirect(route('portal.index'));
    }

    public function test_handoff_falls_back_to_management_ip_when_hotspot_server_ip_not_yet_detected(): void
    {
        $router = Router::create([
            'name' => 'Test Router ' . Str::random(8),
            'identifier' => 'rtr-' . Str::random(8),
            'location' => 'Test Location',
            'ip' => '192.168.88.1',
            'ip_address' => '192.168.88.1',
            'hotspot_server_ip' => null,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);
        $package = $this->createPackage($router);

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:77',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]));

        $session = HotspotSession::first();
        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $session->session_id], now()->addMinutes(5));

        $this->get($handoffUrl)->assertStatus(200);
    }
}
