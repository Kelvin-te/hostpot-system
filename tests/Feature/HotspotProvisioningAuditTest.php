<?php

namespace Tests\Feature;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\Router;
use App\Services\MikroTikService;
use App\Services\HotspotSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;

class HotspotProvisioningAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createRouter(): Router
    {
        return Router::factory()->create([
            'ip' => '192.168.88.1',
            'ip_address' => '192.168.88.1',
            'username' => 'admin',
            'password' => 'admin',
            'api_port' => 8728,
            'is_active' => true,
        ]);
    }

    private function createPackage(Router $router, array $overrides = []): Package
    {
        return Package::factory()->create(array_merge([
            'router_id' => $router->id,
            'name' => 'Test Package',
            'price' => 10,
            'bandwidth_download' => 10,
            'bandwidth_upload' => 5,
            'validity_minutes' => 1440, // 1 day
            'session_timeout' => 24,
            'shared_users' => 1,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Test: limit-uptime is set on user creation from package validity.
     */
    public function test_limit_uptime_is_set_from_package_validity(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, ['validity_minutes' => 1440]); // 1 day

        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatLimitUptime');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);
        $result = $reflection->invoke($service, $package);

        $this->assertSame('1d', $result);
    }

    public function test_limit_uptime_for_weekly_package(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, ['validity_minutes' => 10080]); // 1 week

        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatLimitUptime');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);
        $result = $reflection->invoke($service, $package);

        $this->assertSame('1w', $result);
    }

    public function test_limit_uptime_for_4w2d_package(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, ['validity_minutes' => 43200]); // 4w2d = 30 days

        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatLimitUptime');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);
        $result = $reflection->invoke($service, $package);

        $this->assertSame('4w2d', $result);
    }

    public function test_limit_uptime_empty_when_no_validity(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, [
            'validity_minutes' => null,
            'session_timeout' => null,
        ]);

        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatLimitUptime');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);
        $result = $reflection->invoke($service, $package);

        $this->assertSame('', $result);
    }

    /**
     * Test: shared-users=unlimited for 0 or null.
     */
    public function test_format_shared_users_returns_unlimited_for_null(): void
    {
        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatSharedUsers');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);

        $this->assertSame('unlimited', $reflection->invoke($service, null));
        $this->assertSame('unlimited', $reflection->invoke($service, 0));
    }

    public function test_format_shared_users_returns_empty_for_one(): void
    {
        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatSharedUsers');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);

        $this->assertSame('', $reflection->invoke($service, 1));
    }

    public function test_format_shared_users_returns_number_for_multiple(): void
    {
        $reflection = new \ReflectionMethod(MikroTikService::class, 'formatSharedUsers');
        $reflection->setAccessible(true);

        $service = app(MikroTikService::class);

        $this->assertSame('2', $reflection->invoke($service, 2));
        $this->assertSame('5', $reflection->invoke($service, 5));
    }

    /**
     * Test: provisioning_failed status when MikroTik API fails.
     */
    public function test_session_marked_provisioning_failed_when_api_fails(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router);

        $this->mock(MikroTikService::class, function ($mock) {
            $mock->shouldReceive('createHotspotSession')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('isSessionActiveOnRouter')->andReturn(false);
        });

        $request = Request::create('/', 'GET', [
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
        ]);

        $service = app(HotspotSessionService::class);
        $session = $service->createSessionForPackage($request, $package, null, 'test_user_123');

        $this->assertSame('provisioning_failed', $session->fresh()->status);
    }

    /**
     * Test: session stays active when API succeeds.
     */
    public function test_session_stays_active_when_api_succeeds(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router);

        $this->mock(MikroTikService::class, function ($mock) {
            $mock->shouldReceive('createHotspotSession')
                ->once()
                ->andReturn(true);
            $mock->shouldReceive('isSessionActiveOnRouter')->andReturn(false);
        });

        $request = Request::create('/', 'GET', [
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
        ]);

        $service = app(HotspotSessionService::class);
        $session = $service->createSessionForPackage($request, $package, null, 'test_user_456');

        $this->assertSame('active', $session->fresh()->status);
    }

    /**
     * Test: expiration is authoritative from DB, not from MikroTik comment.
     */
    public function test_expiration_comes_from_database_not_mikrotik(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, ['validity_minutes' => 120]); // 2 hours

        $expiresAt = now()->addHours(2);

        $session = HotspotSession::create([
            'session_id' => 'hs_exp_test_' . uniqid(),
            'package_id' => $package->id,
            'mac_address' => '00:11:22:33:44:55',
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        $this->assertTrue($session->expires_at->isFuture());
        $this->assertFalse($session->isExpired());
    }

    /**
     * Test: expired session is detected by application, not MikroTik.
     */
    public function test_expired_session_is_detected_by_application(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router);

        $session = HotspotSession::create([
            'session_id' => 'hs_exp_detect_' . uniqid(),
            'package_id' => $package->id,
            'started_at' => now()->subHours(3),
            'expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $this->assertTrue($session->fresh()->isExpired());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
