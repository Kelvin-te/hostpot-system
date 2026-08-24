<?php

namespace Tests\Feature;

use App\Models\CaptivePortalSession;
use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\Router;
use App\Services\WinguFiCoreService;
use Tests\TestCase;

/**
 * Regression test for the MikroTik -> package-selection parameter loss bug.
 *
 * MikroTik's login.html redirects the guest's browser to portal.index with
 * link-login/link-orig/chap-id/chap-challenge in the query string. These are
 * captured into a CaptivePortalSession by CaptivePortalService::createSession().
 *
 * When the guest then selects a package, the browser navigates to
 * portal.purchase WITHOUT those MikroTik parameters (see the "Buy" button in
 * resources/views/captive-portal/index.blade.php, which builds a bare URL).
 * purchase() calls CaptivePortalService::createSession() again for the SAME
 * device, which must not silently null out the already-captured MikroTik
 * parameters - otherwise the later MikroTik handoff aborts with
 * "MikroTik login link is missing" even though the package was "activated".
 */
class CaptivePortalHandoffParameterPreservationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseMigrations;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected Router $router;
    protected Package $freePackage;

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

        $this->freePackage = Package::create([
            'name' => 'Free Package',
            'price' => 0,
            'router_id' => $this->router->id,
            'is_active' => true,
            'session_timeout' => 24,
        ]);

        $this->mock(WinguFiCoreService::class, function ($mock) {
            $mock->shouldReceive('syncAuthorization')->andReturn(['data' => ['id' => 999]]);
        });
    }

    public function test_mikrotik_parameters_survive_package_selection(): void
    {
        $mac = 'AA:BB:CC:DD:EE:01';

        $mikrotikParams = [
            'link-login' => 'http://10.50.0.1/login',
            'link-orig' => 'http://example.com/',
            'chap-id' => '42',
            'chap-challenge' => 'deadbeefcafebabe',
        ];

        // Step 1: guest's browser lands on the portal via MikroTik's redirect,
        // carrying the MikroTik session parameters.
        $this->get('/portal/?router=' . $this->router->identifier . '&mac=' . $mac . '&' . http_build_query($mikrotikParams));

        $portalSessionAfterLanding = CaptivePortalSession::where('router_id', $this->router->id)
            ->where('client_mac', $mac)
            ->first();

        $this->assertNotNull($portalSessionAfterLanding, 'Landing on the portal must create a CaptivePortalSession.');
        $this->assertSame($mikrotikParams['link-login'], $portalSessionAfterLanding->link_login);
        $this->assertSame($mikrotikParams['link-orig'], $portalSessionAfterLanding->link_orig);
        $this->assertSame($mikrotikParams['chap-id'], $portalSessionAfterLanding->chap_id);
        $this->assertSame($mikrotikParams['chap-challenge'], $portalSessionAfterLanding->chap_challenge);

        // Step 2: guest clicks "Buy" on a free package. In production this
        // request carries NO link-login/link-orig/chap-id/chap-challenge
        // params (only router/mac are included here for router/device
        // resolution determinism in this test).
        $this->get('/portal/package/' . $this->freePackage->id . '/purchase?router=' . $this->router->identifier . '&mac=' . $mac);

        $hotspotSession = HotspotSession::where('mac_address', $mac)->first();
        $this->assertNotNull($hotspotSession, 'Selecting the free package must create a HotspotSession.');

        $portalSessionAfterPurchase = $hotspotSession->captivePortalSession
            ?? CaptivePortalSession::where('router_id', $this->router->id)->where('client_mac', $mac)->first();

        $this->assertNotNull($portalSessionAfterPurchase);

        $this->assertNotNull(
            $portalSessionAfterPurchase->link_login,
            'link-login must survive package selection so the MikroTik handoff can complete.'
        );
        $this->assertSame($mikrotikParams['link-login'], $portalSessionAfterPurchase->link_login);
        $this->assertSame($mikrotikParams['link-orig'], $portalSessionAfterPurchase->link_orig);
        $this->assertSame($mikrotikParams['chap-id'], $portalSessionAfterPurchase->chap_id);
        $this->assertSame($mikrotikParams['chap-challenge'], $portalSessionAfterPurchase->chap_challenge);
    }
}
