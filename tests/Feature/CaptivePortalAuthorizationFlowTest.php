<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Router;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class CaptivePortalAuthorizationFlowTest extends TestCase
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
    }

    private function fakeSuccessfulWinguFi(): void
    {
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

    private function fakeFailingWinguFi(): void
    {
        Http::fake([
            'https://wingufi-core.test/api/v1/*' => Http::response([
                'success' => false,
                'message' => 'WinguFi Core unavailable',
            ], 503),
        ]);
    }

    private function createRouter(): Router
    {
        return Router::create([
            'name' => 'Test Router ' . Str::random(8),
            'identifier' => 'rtr-' . Str::random(8),
            'location' => 'Test Location',
            'ip' => '192.168.88.1',
            'ip_address' => '192.168.88.1',
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createPackage(Router $router, int $price = 10): Package
    {
        return Package::create([
            'name' => 'Test Package',
            'price' => $price,
            'router_id' => $router->id,
            'bandwidth_download' => 10,
            'bandwidth_upload' => 5,
            'validity_minutes' => 60,
            'session_timeout' => 1,
            'shared_users' => 1,
            'is_active' => true,
        ]);
    }

    public function test_portal_resolves_router_and_creates_session(): void
    {
        $router = $this->createRouter();

        $this->fakeSuccessfulWinguFi();

        $response = $this->get(route('portal.index', [
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://mtik/login',
            'link-orig' => 'http://example.com',
        ]));

        $response->assertStatus(200);

        $this->assertDatabaseHas('captive_portal_sessions', [
            'router_id' => $router->id,
            'client_mac' => '00:11:22:33:44:55',
            'client_ip' => '192.168.88.10',
            'link_login' => 'http://mtik/login',
            'link_orig' => 'http://example.com',
        ]);
    }

    public function test_portal_returns_200_when_router_identifier_is_invalid(): void
    {
        $this->fakeSuccessfulWinguFi();

        $response = $this->get(route('portal.index', [
            'router' => 'non-existent-router',
            'mac' => '00:11:22:33:44:55',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Available Packages');
    }

    public function test_free_package_purchase_creates_and_syncs_authorization(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 0);

        $this->fakeSuccessfulWinguFi();

        $response = $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]));

        $session = \App\Models\HotspotSession::first();
        $this->assertNotNull($session);
        $response->assertStatus(302);
        $this->assertStringContainsString('/portal/handoff/' . $session->session_id, $response->headers->get('Location'));

        $this->assertDatabaseHas('hotspot_authorizations', [
            'package_id' => $package->id,
            'router_id' => $router->id,
            'client_mac' => '00:11:22:33:44:55',
            'wingufi_core_authorization_id' => 'wf-auth-123',
            'status' => 'authorized',
        ]);

        $this->assertNotNull($session->authorization->radius_username);
        $this->assertNotNull($session->authorization->radius_password_encrypted);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://wingufi-core.test/api/v1/authorizations';
        });
    }

    public function test_paid_package_payment_is_idempotent(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 10);
        $unique = Str::random(8);
        $user = User::factory()->create([
            'email' => 'test-' . $unique . '@example.com',
            'phone' => '07' . $unique,
        ]);

        $transaction = PaymentTransaction::create([
            'checkout_request_id' => 'ws_CO_12345',
            'phone_number' => $user->phone,
            'amount' => 10,
            'account_reference' => 'test-ref',
            'transaction_desc' => 'Test payment',
            'status' => 'completed',
            'package_id' => $package->id,
            'user_id' => $user->id,
        ]);

        session([
            'payment_transaction_id' => $transaction->id,
            'checkout_request_id' => $transaction->checkout_request_id,
            'package_id' => $package->id,
            'customer_phone' => $user->phone,
            'customer_name' => $user->name,
            'purchase_mode' => 'activate',
        ]);

        $this->fakeSuccessfulWinguFi();

        $payload = [
            'checkout_request_id' => $transaction->checkout_request_id,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
        ];

        $this->postJson(route('portal.check-payment-status'), $payload)
            ->assertJsonPath('success', true);

        $this->postJson(route('portal.check-payment-status'), $payload)
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('hotspot_authorizations', 1);
        $this->assertDatabaseCount('hotspot_sessions', 1);
    }

    public function test_failed_wingufi_sync_does_not_create_duplicate_authorization(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 10);

        $transaction = PaymentTransaction::create([
            'checkout_request_id' => 'ws_CO_54321',
            'phone_number' => '0712345678',
            'amount' => 10,
            'account_reference' => 'test-ref',
            'transaction_desc' => 'Test payment',
            'status' => 'completed',
            'package_id' => $package->id,
            'user_id' => null,
        ]);

        session([
            'payment_transaction_id' => $transaction->id,
            'checkout_request_id' => $transaction->checkout_request_id,
            'package_id' => $package->id,
            'customer_phone' => '0712345678',
            'customer_name' => 'Guest',
            'purchase_mode' => 'activate',
        ]);

        $this->fakeFailingWinguFi();

        $payload = [
            'checkout_request_id' => $transaction->checkout_request_id,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
        ];

        $this->postJson(route('portal.check-payment-status'), $payload)
            ->assertJsonPath('success', false);

        $this->postJson(route('portal.check-payment-status'), $payload)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('hotspot_authorizations', 1);
        $this->assertDatabaseCount('hotspot_sessions', 0);
    }

    public function test_purchase_with_invalid_package_returns_404(): void
    {
        $router = $this->createRouter();

        $this->fakeSuccessfulWinguFi();

        $this->get(route('portal.purchase', [
            'package' => 999999,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:99',
            'ip' => '192.168.88.99',
        ]))->assertStatus(404);
    }

    public function test_session_expired_after_package_validity_period(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 0);

        $this->fakeSuccessfulWinguFi();

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
        ]));

        $session = \App\Models\HotspotSession::first();

        $this->travel(2)->hours();

        $this->assertTrue($session->fresh()->isExpired());
    }

    public function test_paid_package_redirects_to_signed_handoff_url(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 10);
        $unique = Str::random(8);
        $user = User::factory()->create([
            'email' => 'test-' . $unique . '@example.com',
            'phone' => '07' . $unique,
        ]);

        $transaction = PaymentTransaction::create([
            'checkout_request_id' => 'ws_CO_99999',
            'phone_number' => $user->phone,
            'amount' => 10,
            'account_reference' => 'test-ref',
            'transaction_desc' => 'Test payment',
            'status' => 'completed',
            'package_id' => $package->id,
            'user_id' => $user->id,
        ]);

        session([
            'payment_transaction_id' => $transaction->id,
            'checkout_request_id' => $transaction->checkout_request_id,
            'package_id' => $package->id,
            'customer_phone' => $user->phone,
            'customer_name' => $user->name,
            'purchase_mode' => 'activate',
        ]);

        $this->fakeSuccessfulWinguFi();

        $response = $this->postJson(route('portal.check-payment-status'), [
            'checkout_request_id' => $transaction->checkout_request_id,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]);

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'completed');
        $this->assertStringContainsString('/portal/handoff/', $response->json('redirect_url'));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://wingufi-core.test/api/v1/clients'
                && !empty($body['password']);
        });
    }

    public function test_handoff_page_renders_mikrotik_login_form(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 0);

        $this->fakeSuccessfulWinguFi();

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]));

        $session = \App\Models\HotspotSession::first();
        $this->assertNotNull($session);

        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $session->session_id], now()->addMinutes(5));
        $response = $this->get($handoffUrl);

        $response->assertStatus(200);
        $response->assertSee('Connecting you to the internet');
        $response->assertSee('http://192.168.88.1/login', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
    }

    public function test_handoff_rejects_invalid_signature(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 0);
        $session = \App\Models\HotspotSession::createSession([
            'mac_address' => '00:11:22:33:44:55',
            'ip_address' => '192.168.88.10',
            'user_agent' => 'PHPUnit',
            'device_fingerprint' => 'fp',
            'package_id' => $package->id,
            'authorization_id' => null,
            'user_id' => null,
            'username' => 'test',
            'expires_at' => now()->addHour(),
        ]);

        $this->get(route('portal.handoff', ['session' => $session->session_id]))
            ->assertStatus(401);
    }

    public function test_handoff_rejects_expired_session(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 0);

        $this->fakeSuccessfulWinguFi();

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
        ]));

        $session = \App\Models\HotspotSession::first();
        $session->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);

        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $session->session_id], now()->addMinutes(5));
        $this->get($handoffUrl)
            ->assertRedirect(route('portal.index'));
    }

    public function test_handoff_rejects_foreign_link_login_host(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 0);

        $this->fakeSuccessfulWinguFi();

        $this->get(route('portal.purchase', [
            'package' => $package->id,
            'router' => $router->identifier,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://evil.example.com/login',
            'link-orig' => 'http://example.com',
        ]));

        $session = \App\Models\HotspotSession::first();
        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $session->session_id], now()->addMinutes(5));

        $this->get($handoffUrl)
            ->assertRedirect(route('portal.index'));
    }

    public function test_voucher_login_redirects_to_signed_handoff(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 10);
        $vouchers = Voucher::createBatch($package->id, 1, now()->addDay());
        $voucher = $vouchers[0];

        $this->fakeSuccessfulWinguFi();

        $response = $this->postJson(route('portal.authenticate'), [
            'username' => $voucher->code,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]);

        $session = \App\Models\HotspotSession::first();
        $this->assertNotNull($session);
        $response->assertStatus(302);
        $this->assertStringContainsString('/portal/handoff/' . $session->session_id, $response->headers->get('Location'));

        $this->assertDatabaseHas('hotspot_authorizations', [
            'voucher_id' => $voucher->id,
            'router_id' => $router->id,
            'radius_username' => $voucher->code,
            'wingufi_core_authorization_id' => 'wf-auth-123',
            'status' => 'authorized',
        ]);
    }

    public function test_voucher_login_rejects_reuse_on_different_device(): void
    {
        $router = $this->createRouter();
        $package = $this->createPackage($router, 10);
        $vouchers = Voucher::createBatch($package->id, 1, now()->addDay());
        $voucher = $vouchers[0];

        $this->fakeSuccessfulWinguFi();

        $first = $this->postJson(route('portal.authenticate'), [
            'username' => $voucher->code,
            'mac' => '00:11:22:33:44:55',
            'ip' => '192.168.88.10',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]);
        $first->assertStatus(302);

        $second = $this->postJson(route('portal.authenticate'), [
            'username' => $voucher->code,
            'mac' => '00:11:22:33:44:66',
            'ip' => '192.168.88.11',
            'link-login' => 'http://192.168.88.1/login',
            'link-orig' => 'http://example.com',
        ]);
        $second->assertRedirect();
        $second->assertSessionHas('error');

        $this->assertSame(1, \App\Models\HotspotAuthorization::where('voucher_id', $voucher->id)->count());
        $this->assertSame(1, \App\Models\HotspotSession::whereHas('authorization', function ($q) use ($voucher) {
            $q->where('voucher_id', $voucher->id);
        })->count());
    }
}
