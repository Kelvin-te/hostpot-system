<?php

namespace Tests\Unit;

use App\Services\MikroTikService;
use Tests\TestCase;

/**
 * Regression coverage for the HotSpot gateway/server IP detection logic.
 *
 * Production incident: MikroTik profile had `hotspot-address=192.168.30.1`
 * and `dns-name=""`, generating `link-login=http://192.168.30.1/login...`.
 * The router's management/API/WireGuard IP is a distinct address
 * (e.g. 10.50.0.2) and must never be confused with the HotSpot gateway IP.
 */
class MikroTikServiceHotspotAddressResolutionTest extends TestCase
{
    private function service(): MikroTikService
    {
        return new MikroTikService();
    }

    public function test_uses_hotspot_address_from_profile_when_present(): void
    {
        $profileEntry = ['name' => 'default', 'hotspot-address' => '192.168.30.1'];
        $interfaceAddressEntry = ['address' => '10.50.0.2/24'];

        $result = $this->service()->resolveHotspotServerIp($profileEntry, $interfaceAddressEntry);

        $this->assertSame('192.168.30.1', $result);
    }

    public function test_strips_cidr_suffix_from_hotspot_address(): void
    {
        $profileEntry = ['hotspot-address' => '192.168.30.1/24'];

        $result = $this->service()->resolveHotspotServerIp($profileEntry, null);

        $this->assertSame('192.168.30.1', $result);
    }

    public function test_falls_back_to_interface_address_when_hotspot_address_empty(): void
    {
        // dns-name="" and hotspot-address unset on the profile: RouterOS
        // still resolves link-login using the HotSpot interface's own IP.
        $profileEntry = ['hotspot-address' => ''];
        $interfaceAddressEntry = ['address' => '192.168.30.1/24'];

        $result = $this->service()->resolveHotspotServerIp($profileEntry, $interfaceAddressEntry);

        $this->assertSame('192.168.30.1', $result);
    }

    public function test_falls_back_to_interface_address_when_profile_entry_missing(): void
    {
        $interfaceAddressEntry = ['address' => '192.168.30.1/24'];

        $result = $this->service()->resolveHotspotServerIp(null, $interfaceAddressEntry);

        $this->assertSame('192.168.30.1', $result);
    }

    public function test_returns_null_when_nothing_is_detected(): void
    {
        $result = $this->service()->resolveHotspotServerIp(null, null);

        $this->assertNull($result);
    }

    public function test_hotspot_address_never_resolves_to_management_ip_by_default(): void
    {
        // Sanity check that a router's management IP (10.50.0.2) is never
        // returned unless it literally IS the configured hotspot-address.
        $profileEntry = ['hotspot-address' => '192.168.30.1'];
        $interfaceAddressEntry = ['address' => '10.50.0.2/24'];

        $result = $this->service()->resolveHotspotServerIp($profileEntry, $interfaceAddressEntry);

        $this->assertNotSame('10.50.0.2', $result);
        $this->assertSame('192.168.30.1', $result);
    }
}
