<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\TenantCredential;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use DatabaseTruncation;

    protected array $connectionsToTruncate = ['wingufi_core'];

    protected ?Tenant $tenant = null;
    protected ?TenantCredential $credential = null;
    protected string $plainTextToken = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'uuid' => 'tenant-a',
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'code' => 'TENA',
            'status' => 'active',
        ]);

        $secret = 'test-secret-'.uniqid();
        $this->plainTextToken = 'test_client_id:'.$secret;

        $this->credential = TenantCredential::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test credential',
            'client_id' => 'test_client_id',
            'client_secret_hash' => Hash::make($secret),
            'status' => 'active',
        ]);
    }

    public function test_password_is_hashed_and_stored(): void
    {
        $response = $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'test-user',
            'username' => 'test-user',
            'display_name' => 'Test User',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response->assertOk();

        $client = $this->tenant->clients()->where('external_id', 'test-user')->first();

        $this->assertNotNull($client);
        $this->assertNotNull($client->password_hash);
        $this->assertSame('bcrypt', $client->password_type);
        $this->assertTrue(Hash::check('password', $client->password_hash));
    }

    public function test_password_is_never_returned_in_api_response(): void
    {
        $response = $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'test-user',
            'username' => 'test-user',
            'display_name' => 'Test User',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('password_hash', $content);
        $this->assertStringNotContainsString('$2', $content);
    }

    public function test_synchronization_without_password_preserves_existing_hash(): void
    {
        $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'test-user',
            'username' => 'test-user',
            'display_name' => 'Test User',
            'status' => 'active',
            'password' => 'password',
        ])->assertOk();

        $originalHash = $this->tenant->clients()->where('external_id', 'test-user')->value('password_hash');
        $this->assertNotNull($originalHash);

        $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'test-user',
            'username' => 'test-user',
            'display_name' => 'Updated User',
            'status' => 'active',
        ])->assertOk();

        $client = $this->tenant->clients()->where('external_id', 'test-user')->first();

        $this->assertSame($originalHash, $client->password_hash);
        $this->assertTrue(Hash::check('password', $client->password_hash));
        $this->assertSame('Updated User', $client->display_name);
    }

    public function test_synchronization_with_new_password_updates_hash(): void
    {
        $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'test-user',
            'username' => 'test-user',
            'display_name' => 'Test User',
            'status' => 'active',
            'password' => 'password',
        ])->assertOk();

        $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'test-user',
            'username' => 'test-user',
            'display_name' => 'Test User',
            'status' => 'active',
            'password' => 'new-password',
        ])->assertOk();

        $client = $this->tenant->clients()->where('external_id', 'test-user')->first();

        $this->assertFalse(Hash::check('password', $client->password_hash));
        $this->assertTrue(Hash::check('new-password', $client->password_hash));
    }

    public function test_tenant_isolation_remains_intact_for_passwords(): void
    {
        $tenantB = Tenant::create([
            'uuid' => 'tenant-b',
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'code' => 'TENB',
            'status' => 'active',
        ]);

        $secretB = 'test-secret-b-'.uniqid();
        $credentialB = TenantCredential::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Test credential B',
            'client_id' => 'test_client_id_b',
            'client_secret_hash' => Hash::make($secretB),
            'status' => 'active',
        ]);
        $plainTextTokenB = 'test_client_id_b:'.$secretB;

        $this->withToken($this->plainTextToken)->postJson('/api/v1/clients', [
            'external_id' => 'shared-user',
            'username' => 'shared-user',
            'status' => 'active',
            'password' => 'tenant-a-password',
        ])->assertOk();

        $this->withToken($plainTextTokenB)->postJson('/api/v1/clients', [
            'external_id' => 'shared-user',
            'username' => 'shared-user',
            'status' => 'active',
            'password' => 'tenant-b-password',
        ])->assertOk();

        $clientA = $this->tenant->clients()->where('external_id', 'shared-user')->first();
        $clientB = $tenantB->clients()->where('external_id', 'shared-user')->first();

        $this->assertNotNull($clientA);
        $this->assertNotNull($clientB);
        $this->assertNotSame($clientA->id, $clientB->id);
        $this->assertTrue(Hash::check('tenant-a-password', $clientA->password_hash));
        $this->assertTrue(Hash::check('tenant-b-password', $clientB->password_hash));
    }
}
