<?php

namespace Tests\Feature;

use App\Models\HotspotAuthorization;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Router;
use App\Services\HotspotAuthorizationService;
use App\Services\WinguFiCoreService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HotspotAuthorizationReuseTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected Router $router;
    protected Package $freePackage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = Router::create([
            'name' => 'Test Router',
            'identifier' => 'test-router-2',
            'location' => 'Nairobi',
            'ip' => '10.50.0.2',
            'username' => 'admin',
            'password' => 'secret',
            'api_port' => 8728,
            'is_active' => true,
        ]);

        $this->freePackage = Package::create([
            'name' => 'Free Package',
            'price' => 0,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);
    }

    public function test_repeated_free_authorization_request_reuses_existing_authorization(): void
    {
        $this->mock(WinguFiCoreService::class, function ($mock) {
            // Sync must only happen once: the second call reuses the already
            // synced authorization (ensureSynced short-circuits).
            $mock->shouldReceive('syncAuthorization')->once()->andReturn(['data' => ['id' => 111]]);
        });

        $service = app(HotspotAuthorizationService::class);
        $clientIdentifier = 'device-stable-identifier';

        $first = $service->createFromPackage($this->freePackage, null, $clientIdentifier);
        $second = $service->createFromPackage($this->freePackage, null, $clientIdentifier);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, HotspotAuthorization::count());
    }

    public function test_expired_authorization_is_not_reused_and_a_new_one_is_created(): void
    {
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('syncAuthorization')->twice()->andReturn(['data' => ['id' => 222]]);
        });

        $service = app(HotspotAuthorizationService::class);
        $clientIdentifier = 'device-stable-identifier';

        $first = $service->createFromPackage($this->freePackage, null, $clientIdentifier);
        $first->update(['expires_at' => now()->subMinute()]);

        $second = $service->createFromPackage($this->freePackage, null, $clientIdentifier);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, HotspotAuthorization::count());
    }

    public function test_revoked_authorization_is_not_silently_reused(): void
    {
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('syncAuthorization')->twice()->andReturn(['data' => ['id' => 333]]);
        });

        $service = app(HotspotAuthorizationService::class);
        $clientIdentifier = 'device-stable-identifier';

        $first = $service->createFromPackage($this->freePackage, null, $clientIdentifier);
        $first->update(['status' => 'revoked']);

        $second = $service->createFromPackage($this->freePackage, null, $clientIdentifier);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('revoked', $first->fresh()->status);
    }

    public function test_using_free_package_a_does_not_block_free_package_b_authorization(): void
    {
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('syncAuthorization')->twice()->andReturn(['data' => ['id' => 444]]);
        });

        $freePackageB = Package::create([
            'name' => 'Free Package B',
            'price' => 0,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);

        $service = app(HotspotAuthorizationService::class);
        $clientIdentifier = 'device-stable-identifier';

        $authA = $service->createFromPackage($this->freePackage, null, $clientIdentifier);
        $authB = $service->createFromPackage($freePackageB, null, $clientIdentifier);

        $this->assertNotSame($authA->id, $authB->id);
        $this->assertSame($this->freePackage->id, $authA->package_id);
        $this->assertSame($freePackageB->id, $authB->package_id);
    }

    public function test_paid_package_behavior_is_unchanged(): void
    {
        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('externalAuthorizationIdForPayment')->andReturnUsing(
                fn ($paymentId) => 'auth-admin-ptx-' . $paymentId
            );
            $mock->shouldReceive('syncAuthorization')->once()->andReturn(['data' => ['id' => 555]]);
        });

        $paidPackage = Package::create([
            'name' => 'Paid Package',
            'price' => 50,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);

        $transaction = PaymentTransaction::create([
            'checkout_request_id' => 'ws_CO_123',
            'phone_number' => '254700000000',
            'amount' => 50,
            'account_reference' => 'HSP-' . $paidPackage->id,
            'transaction_desc' => 'Paid Package - Internet Package',
            'package_id' => $paidPackage->id,
            'status' => 'completed',
        ]);

        $service = app(HotspotAuthorizationService::class);

        // Same payment transaction id requested twice must reuse (existing
        // firstOrCreate behavior), unaffected by the new free-package reuse logic.
        $first = $service->createFromPackage($paidPackage, null, '254700000000', null, $transaction->id);
        $second = $service->createFromPackage($paidPackage, null, '254700000000', null, $transaction->id);

        $this->assertSame($first->id, $second->id);
    }
}
