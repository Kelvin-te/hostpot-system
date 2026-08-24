<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression test for the MikroTik -> external-portal handoff parameter names.
 *
 * The generated login.html redirects the client's browser to our external
 * portal via a plain GET form, forwarding MikroTik's session variables
 * ($(link-login), $(link-orig), $(chap-id), $(chap-challenge), $(mac), $(ip)).
 * CaptivePortalService::createSession() must read those forwarded values using
 * the EXACT same query-parameter names the template submits them under, or
 * MikroTik's login context is silently lost and the handoff can never
 * complete ("Handoff aborted: MikroTik login link is missing").
 */
class HotspotLoginTemplateParamsTest extends TestCase
{
    public function test_login_template_field_names_match_captive_portal_service_input_keys(): void
    {
        $template = file_get_contents(base_path('hotspot_router_files/hotspot/login.html'));
        $service = file_get_contents(app_path('Services/CaptivePortalService.php'));

        // Every MikroTik variable the template forwards (excluding mac/ip, which
        // are naturally hyphen/underscore-agnostic single words) must be
        // submitted under a hyphenated field name matching MikroTik's own
        // native convention and what CaptivePortalService expects.
        $expectedFieldNames = [
            'link-login',
            'link-orig',
            'chap-id',
            'chap-challenge',
        ];

        foreach ($expectedFieldNames as $fieldName) {
            $this->assertMatchesRegularExpression(
                '/name="' . preg_quote($fieldName, '/') . '"/',
                $template,
                "login.html must submit a hidden field named \"{$fieldName}\" so it is not silently dropped by CaptivePortalService."
            );

            $this->assertStringContainsString(
                "input('{$fieldName}')",
                $service,
                "CaptivePortalService must read the \"{$fieldName}\" parameter to match what login.html submits."
            );
        }

        // Guard against regressing back to the underscored field names, which
        // do not match anything CaptivePortalService reads.
        $this->assertStringNotContainsString('name="link_login"', $template);
        $this->assertStringNotContainsString('name="link_orig"', $template);
        $this->assertStringNotContainsString('name="chap_id"', $template);
        $this->assertStringNotContainsString('name="chap_challenge"', $template);
    }
}
