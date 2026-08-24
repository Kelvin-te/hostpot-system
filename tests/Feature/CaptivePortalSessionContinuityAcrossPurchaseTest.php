<?php

namespace Tests\Feature;

use App\Models\CaptivePortalSession;
use App\Models\HotspotAuthorization;
use App\Models\HotspotSession;
use App\Models\Package;
use App\Models\Router;
use App\Services\WinguFiCoreService;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Regression test for a production incident: the initial MikroTik landing
 * request captured link-login/link-orig/chap-id/chap-challenge into
 * CaptivePortalSession #15 (real MAC + real IP), but the subsequent package
 * purchase request arrived with a NULL mac and a DIFFERENT apparent IP
 * (CGNAT/mobile network change). Because CaptivePortalService only matched
 * sessions by router+mac/ip, it could not find #15 and silently created a
 * brand new CaptivePortalSession (#16) with no MikroTik parameters, which
 * later made handoff() abort with "Handoff aborted: MikroTik login link is
 * missing".
 *
 * The fix carries an opaque session_token through Laravel's session so the
 * SAME CaptivePortalSession row is reused across landing -> purchase
 * -> handoff, regardless of MAC/IP changes.
 */
class CaptivePortalSessionContinuityAcrossPurchaseTest extends TestCase
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

    public function test_purchase_reuses_existing_portal_session_even_when_mac_and_ip_change(): void
    {
        $mikrotikParams = [
            'link-login' => 'http://10.50.0.1/login',
            'link-orig' => 'http://example.com/',
            'chap-id' => '42',
            'chap-challenge' => 'deadbeefcafebabe',
        ];

        // Step 1: real MikroTik landing request - real MAC, real IP.
        $this->get('/portal/?router=' . $this->router->identifier
            . '&mac=72:25:2B:79:3F:33&ip=192.168.30.200&' . http_build_query($mikrotikParams));

        $this->assertSame(1, CaptivePortalSession::count());
        $originalSession = CaptivePortalSession::first();
        $this->assertNotNull($originalSession->link_login);

        // Step 2: package purchase request - NO mac param at all, and a
        // completely different apparent IP (simulating CGNAT/mobile network
        // change), exactly like the production trace. Only `router` survives
        // via the Laravel session, matching what the real "Buy" button sends.
        $this->withServerVariables(['REMOTE_ADDR' => '102.0.33.236'])
            ->get('/portal/package/' . $this->freePackage->id . '/purchase?router=' . $this->router->identifier);

        // No second CaptivePortalSession should have been created.
        $this->assertSame(
            1,
            CaptivePortalSession::count(),
            'Purchase must reuse the existing captive portal session instead of creating a new one when MAC/IP changed.'
        );

        $originalSession->refresh();

        $this->assertSame($mikrotikParams['link-login'], $originalSession->link_login);
        $this->assertSame($mikrotikParams['link-orig'], $originalSession->link_orig);
        $this->assertSame($mikrotikParams['chap-id'], $originalSession->chap_id);
        $this->assertSame($mikrotikParams['chap-challenge'], $originalSession->chap_challenge);

        $hotspotSession = HotspotSession::first();
        $this->assertNotNull($hotspotSession, 'Purchase must still create a HotspotSession.');
        $this->assertSame($originalSession->id, $hotspotSession->captivePortalSession?->id);

        $this->assertSame(1, HotspotAuthorization::count());

        // Step 3: handoff must retrieve link-login from the ORIGINAL session.
        $handoffUrl = URL::signedRoute('portal.handoff', ['session' => $hotspotSession->session_id], now()->addMinutes(5));
        $response = $this->get($handoffUrl);

        $response->assertStatus(200);
        $response->assertSee($mikrotikParams['link-login'], false);
    }
}
