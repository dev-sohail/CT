<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SecretsPasswordAnd2FAVault\Models\SecretEntry;
use App\Domains\SecretsPasswordAnd2FAVault\Services\TotpService;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;

class SecretsVaultTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_secret_crud_encrypts_password_at_rest(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/secret-entries', [
            'name' => 'GitHub',
            'category' => 'dev',
            'username' => 'octocat',
            'password' => 'S3cret!pass',
            'url' => 'https://github.com',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('********', $created->json('data.password'));
        $this->assertEquals(5, $created->json('data.strength_score'));

        $stored = SecretEntry::find($id);
        $this->assertNotEquals('S3cret!pass', $stored->getAttributes()['password_encrypted']);
        $this->assertEquals('S3cret!pass', Crypt::decryptString($stored->getAttributes()['password_encrypted']));

        $this->deleteJson("/api/v1/secret-entries/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('secret_entries', ['id' => $id]);
    }

    public function test_reveal_returns_decrypted_password(): void
    {
        $entry = SecretEntry::create(['user_id' => $this->owner->id, 'name' => 'Email', 'username' => 'me@x.com']);
        $entry->password = 'hunter2';
        $entry->save();

        Sanctum::actingAs($this->owner);
        $hidden = $this->getJson("/api/v1/secret-entries/{$entry->id}");
        $this->assertEquals('********', $hidden->json('data.password'));

        $revealed = $this->getJson("/api/v1/secret-entries/{$entry->id}/reveal");
        $this->assertEquals('hunter2', $revealed->json('data.password'));

        $this->assertNotNull(SecretEntry::find($entry->id)->last_used_at);
    }

    public function test_password_strength_scoring(): void
    {
        Sanctum::actingAs($this->owner);

        $weak = $this->postJson('/api/v1/secret-entries', ['name' => 'W', 'password' => 'abc']);
        $this->assertEquals(1, $weak->json('data.strength_score'));

        $strong = $this->postJson('/api/v1/secret-entries', ['name' => 'S', 'password' => 'Str0ng!Passw0rd']);
        $this->assertEquals(5, $strong->json('data.strength_score'));
    }

    public function test_totp_generation_and_verification(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();
        $code = $totp->currentCode($secret);

        $this->assertTrue($totp->verify($secret, $code));
        $this->assertFalse($totp->verify($secret, '000000'));

        $entry = SecretEntry::create(['user_id' => $this->owner->id, 'name' => 'VPN']);
        $entry->totp_secret = $secret;
        $entry->totp_enabled = true;
        $entry->save();

        Sanctum::actingAs($this->owner);
        $res = $this->getJson("/api/v1/secret-entries/{$entry->id}/totp");
        $res->assertOk();
        $this->assertEquals($code, $res->json('data.code'));

        $verify = $this->postJson("/api/v1/secret-entries/{$entry->id}/totp/verify", ['code' => $code]);
        $verify->assertOk();
        $this->assertTrue($verify->json('data.valid'));

        $bad = $this->postJson("/api/v1/secret-entries/{$entry->id}/totp/verify", ['code' => '111111']);
        $bad->assertStatus(422);
    }

    public function test_stats_highlights_weak_entries(): void
    {
        $entry = SecretEntry::create(['user_id' => $this->owner->id, 'name' => 'Weak', 'category' => 'general', 'strength_score' => 1]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/secret-entries/stats');

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.total'));
        $this->assertEquals(1, $res->json('data.weak'));
        $this->assertEquals(1, $res->json('data.average_strength'));

        $weakList = $this->getJson('/api/v1/secret-entries?weak=1');
        $this->assertCount(1, $weakList->json('data'));
    }

    public function test_guest_cannot_access_secrets(): void
    {
        $this->getJson('/api/v1/secret-entries')->assertUnauthorized();
    }
}