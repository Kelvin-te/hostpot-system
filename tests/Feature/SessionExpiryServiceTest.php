<?php

namespace Tests\Feature;

use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Services\SessionExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionExpiryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enforce_expiry_marks_expired_sessions(): void
    {
        $router = Router::factory()->create();
        $package = Package::factory()->create(['router_id' => $router->id]);

        $expiredSession = HotspotSession::create([
            'session_id' => 'hs_test1' . uniqid(),
            'package_id' => $package->id,
            'started_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $activeSession = HotspotSession::create([
            'session_id' => 'hs_test2' . uniqid(),
            'package_id' => $package->id,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'status' => 'active',
        ]);

        $service = app(SessionExpiryService::class);
        $count = $service->enforceExpiry();

        $this->assertEquals(1, $count);
        $this->assertEquals('expired', $expiredSession->fresh()->status);
        $this->assertEquals('active', $activeSession->fresh()->status);
    }

    public function test_check_expiring_sessions_returns_correct_sessions(): void
    {
        $router = Router::factory()->create();
        $package = Package::factory()->create(['router_id' => $router->id]);

        $expiringSoon = HotspotSession::create([
            'session_id' => 'hs_test3' . uniqid(),
            'package_id' => $package->id,
            'started_at' => now()->subHour(),
            'expires_at' => now()->addMinutes(10),
            'status' => 'active',
        ]);

        $notExpiring = HotspotSession::create([
            'session_id' => 'hs_test4' . uniqid(),
            'package_id' => $package->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(5),
            'status' => 'active',
        ]);

        $service = app(SessionExpiryService::class);
        $expiring = $service->checkExpiringSessions(15);

        $this->assertTrue($expiring->contains('id', $expiringSoon->id));
        $this->assertFalse($expiring->contains('id', $notExpiring->id));
    }
}
