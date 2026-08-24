<?php

namespace Tests\Unit;

use App\Services\DeviceIdentificationService;
use Illuminate\Http\Request;
use Tests\TestCase;

class DeviceIdentificationServiceStableIdTest extends TestCase
{
    public function test_same_device_mac_produces_stable_client_identifier_across_requests(): void
    {
        $service = new DeviceIdentificationService();

        $requestOne = Request::create('/portal/', 'GET', ['mac' => 'AA:BB:CC:DD:EE:01']);
        $requestOne->server->set('REMOTE_ADDR', '10.0.0.5');

        $requestTwo = Request::create('/portal/', 'GET', ['mac' => 'AA:BB:CC:DD:EE:01']);
        // Simulate a different IP (DHCP/NAT change) on the second request.
        $requestTwo->server->set('REMOTE_ADDR', '10.0.0.99');

        $idOne = $service->getStableClientIdentifier($requestOne);
        $idTwo = $service->getStableClientIdentifier($requestTwo);

        $this->assertSame($idOne, $idTwo, 'Client identifier must remain stable across IP changes when MAC is known.');
        $this->assertStringNotContainsString('AA:BB:CC:DD:EE:01', $idOne, 'Raw MAC must never be exposed in the identifier.');
    }

    public function test_different_devices_produce_different_client_identifiers(): void
    {
        $service = new DeviceIdentificationService();

        $requestA = Request::create('/portal/', 'GET', ['mac' => 'AA:BB:CC:DD:EE:01']);
        $requestB = Request::create('/portal/', 'GET', ['mac' => 'AA:BB:CC:DD:EE:02']);

        $this->assertNotSame(
            $service->getStableClientIdentifier($requestA),
            $service->getStableClientIdentifier($requestB)
        );
    }
}
