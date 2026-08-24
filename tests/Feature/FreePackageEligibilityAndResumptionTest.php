<?php

namespace Tests\Feature;

use App\Models\HotspotAuthorization;
use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\Router;
use App\Services\WinguFiCoreService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FreePackageEligibilityAndResumptionTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected Router $router;
    protected Package $freePackageA;
    protected Package $freePackageB;
    protected Package $paidPackage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = Router::create([
            'name' => 'Test Router',
            'identifier' => 'test-router-1',
            'location' => 'Nairobi',
            'ip' => '10.50.0.1',
            'username' => 'admin',
            'password' => 'secret',
            'api_port' => 8728,
            'is_active' => true,
        ]);

        $this->freePackageA = Package::create([
            'name' => 'Free Package A',
            'price' => 0,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);

        $this->freePackageB = Package::create([
            'name' => 'Free Package B',
            'price' => 0,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);

        $this->paidPackage = Package::create([
            'name' => 'Paid Package',
            'price' => 50,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);

        $this->mockCoreSync();
    }

    protected function mockCoreSync(): void
    {
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('syncAuthorization')->andReturn(['data' => ['id' => 999]]);
        });
    }

    /** Device fixed MAC used for identification across requests */
    protected function withDeviceMac(string $mac = 'AA:BB:CC:DD:EE:01'): array
    {
        return ['mac' => $mac];
    }

    public function test_using_free_package_a_does_not_hide_free_package_b(): void
    {
        // Activate free package A for this device.
        $this->get('/portal/package/' . $this->freePackageA->id . '/purchase?router=' . $this->router->identifier . '&' . http_build_query($this->withDeviceMac()));

        // Terminate the session so we can view the package list again (index()
        // redirects to status while a session is active).
        HotspotSession::query()->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);

        $response = $this->get('/portal/?router=' . $this->router->identifier . '&' . http_build_query($this->withDeviceMac()));

        $packages = $response->viewData('packages');
        $ids = $packages->pluck('id')->all();

        $this->assertNotContains($this->freePackageA->id, $ids, 'Package A should be hidden after use.');
        $this->assertContains($this->freePackageB->id, $ids, 'Package B must remain available after using Package A.');
    }

    public function test_active_session_on_free_package_is_resumed_not_duplicated(): void
    {
        $query = '?router=' . $this->router->identifier . '&' . http_build_query($this->withDeviceMac());

        $this->get('/portal/package/' . $this->freePackageA->id . '/purchase' . $query);
        $this->assertSame(1, HotspotSession::count());
        $this->assertSame(1, HotspotAuthorization::count());

        // Selecting the SAME package again must resume, not duplicate.
        $this->get('/portal/package/' . $this->freePackageA->id . '/purchase' . $query);

        $this->assertSame(1, HotspotSession::count());
        $this->assertSame(1, HotspotAuthorization::count());
    }

    public function test_selecting_different_package_while_active_does_not_create_second_session(): void
    {
        $query = '?router=' . $this->router->identifier . '&' . http_build_query($this->withDeviceMac());

        $this->get('/portal/package/' . $this->freePackageA->id . '/purchase' . $query);
        $this->assertSame(1, HotspotSession::count());

        // Attempt to switch to a different free package while A is still active.
        $this->get('/portal/package/' . $this->freePackageB->id . '/purchase' . $query);

        $this->assertSame(1, HotspotSession::count(), 'No second concurrent session should be created.');
        $this->assertSame(1, HotspotAuthorization::count());
        $this->assertSame($this->freePackageA->id, HotspotSession::first()->package_id);
    }
}
