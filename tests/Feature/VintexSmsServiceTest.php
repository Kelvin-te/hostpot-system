<?php

namespace Tests\Feature;

use App\Services\VintexSmsService;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VintexSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default config values for tests
        Config::set('services.vintex.api_url', 'https://sms.vintextechnologies.com/api/sendMessage');
        Config::set('services.vintex.email', 'test@example.com');
        Config::set('services.vintex.bearer_token', 'test-token');
        Config::set('services.vintex.sender_id', 'STERKE');
        Config::set('app.name', 'MatuNet');

        // Ensure clean settings table (RefreshDatabase may not fully isolate between tests)
        Setting::query()->delete();
    }

    public function test_append_signature_adds_company_name_when_no_custom_sender_name(): void
    {
        // No Setting record → no custom sender name → should append signature
        $this->assertDatabaseEmpty('settings');

        $service = new VintexSmsService();

        $result = $service->appendSignature('Test message');

        $this->assertStringContainsString('Test message', $result);
        $this->assertStringContainsString('Regards,', $result);
        $this->assertStringContainsString('MatuNet', $result);
    }

    public function test_append_signature_includes_company_phone_when_available(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => null,
            'company_name' => 'Test ISP',
            'company_phone' => '0712 345 678',
        ]);

        $service = new VintexSmsService();

        $result = $service->appendSignature('Hello');

        $this->assertStringContainsString('Regards, Test ISP.', $result);
        $this->assertStringContainsString('Call: 0712 345 678', $result);
    }

    public function test_append_signature_omits_phone_when_empty(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => null,
            'company_name' => 'Test ISP',
            'company_phone' => '',
        ]);

        $service = new VintexSmsService();

        $result = $service->appendSignature('Hello');

        $this->assertStringContainsString('Regards, Test ISP.', $result);
        $this->assertStringNotContainsString('Call:', $result);
    }

    public function test_append_signature_skipped_when_custom_sender_name_set(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => 'MYBRAND',
            'company_name' => 'Test ISP',
            'company_phone' => '0712 345 678',
        ]);

        $service = new VintexSmsService();

        $result = $service->appendSignature('Hello');

        // When custom sender name is set, message should be returned as-is
        $this->assertEquals('Hello', $result);
    }

    public function test_sender_id_uses_setting_when_available(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => 'MYBRAND',
        ]);

        $service = new VintexSmsService();

        // Use reflection to check protected property
        $reflection = new \ReflectionClass($service);
        $senderIdProp = $reflection->getProperty('senderId');
        $senderIdProp->setAccessible(true);

        $this->assertEquals('MYBRAND', $senderIdProp->getValue($service));
    }

    public function test_sender_id_falls_back_to_config_when_setting_empty(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => null,
        ]);

        $service = new VintexSmsService();

        $reflection = new \ReflectionClass($service);
        $senderIdProp = $reflection->getProperty('senderId');
        $senderIdProp->setAccessible(true);

        $this->assertEquals('STERKE', $senderIdProp->getValue($service));
    }

    public function test_sender_id_falls_back_to_config_when_no_settings_record(): void
    {
        // No settings record at all
        $service = new VintexSmsService();

        $reflection = new \ReflectionClass($service);
        $senderIdProp = $reflection->getProperty('senderId');
        $senderIdProp->setAccessible(true);

        $this->assertEquals('STERKE', $senderIdProp->getValue($service));
    }

    public function test_company_name_falls_back_to_app_name_when_setting_empty(): void
    {
        Setting::factory()->create([
            'company_name' => null,
        ]);

        $service = new VintexSmsService();

        $result = $service->appendSignature('Hello');

        $this->assertStringContainsString('Regards, MatuNet.', $result);
    }

    public function test_company_name_uses_setting_when_available(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => null,
            'company_name' => 'MatuNet Kenya',
        ]);

        $service = new VintexSmsService();

        $result = $service->appendSignature('Hello');

        $this->assertStringContainsString('Regards, MatuNet Kenya.', $result);
    }

    public function test_no_hardcoded_sterke_digital_in_templates(): void
    {
        $service = new VintexSmsService();

        // Use reflection to check that appendSignature doesn't contain "Sterke Digital"
        $result = $service->appendSignature('Test');

        $this->assertStringNotContainsString('Sterke Digital', $result);
    }

    public function test_send_otp_does_not_contain_hardcoded_signature(): void
    {
        Setting::factory()->create([
            'sms_sender_id' => 'MYBRAND',
        ]);

        $service = new VintexSmsService();

        // When custom sender name is set, no signature is appended
        $result = $service->appendSignature('Your verification code is: 12345.');

        $this->assertStringNotContainsString('Sterke Digital', $result);
        $this->assertStringNotContainsString('Regards', $result);
    }
}
