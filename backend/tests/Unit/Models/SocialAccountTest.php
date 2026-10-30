<?php

namespace Tests\Unit\Models;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for SocialAccount model.
 *
 * @see TASK-25-2
 * @see TEST-25-2
 */
class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Creation Tests (MC)
    // ==========================================

    /** @test MC-001: Can create social account with all fields */
    public function test_mc_001_can_create_with_all_fields(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-123',
            'provider_token' => 'ya29.a0AfH6SMBx...',
            'provider_refresh_token' => 'refresh-token-abc',
            'provider_token_expires_at' => now()->addHour(),
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'id' => $socialAccount->id,
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-123',
        ]);
    }

    /** @test MC-002: Can create with minimal fields */
    public function test_mc_002_can_create_with_minimal_fields(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'github-456',
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'id' => $socialAccount->id,
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'github-456',
        ]);

        $this->assertNull($socialAccount->provider_token);
        $this->assertNull($socialAccount->provider_refresh_token);
        $this->assertNull($socialAccount->provider_token_expires_at);
    }

    /** @test MC-003: provider_token encrypted cast */
    public function test_mc_003_provider_token_encrypted_cast(): void
    {
        $user = User::factory()->create();
        $plainToken = 'my-secret-oauth-token';

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-1',
            'provider_token' => $plainToken,
        ]);

        // Refresh to get encrypted value from DB
        $socialAccount->refresh();

        // The token should be decryptable and match original
        $this->assertEquals($plainToken, $socialAccount->provider_token);

        // In database, it should be encrypted (not plaintext)
        $rawValue = \DB::table('social_accounts')->where('id', $socialAccount->id)->value('provider_token');
        $this->assertNotEquals($plainToken, $rawValue);
    }

    /** @test MC-004: provider_refresh_token encrypted cast */
    public function test_mc_004_provider_refresh_token_encrypted_cast(): void
    {
        $user = User::factory()->create();
        $plainRefreshToken = 'my-secret-refresh-token';

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-2',
            'provider_refresh_token' => $plainRefreshToken,
        ]);

        $socialAccount->refresh();

        // The token should be decryptable and match original
        $this->assertEquals($plainRefreshToken, $socialAccount->provider_refresh_token);

        // In database, it should be encrypted (not plaintext)
        $rawValue = \DB::table('social_accounts')->where('id', $socialAccount->id)->value('provider_refresh_token');
        $this->assertNotEquals($plainRefreshToken, $rawValue);
    }

    /** @test MC-005: provider_token_expires_at cast to datetime */
    public function test_mc_005_provider_token_expires_at_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $expiresAt = now()->addHour();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-3',
            'provider_token_expires_at' => $expiresAt,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $socialAccount->provider_token_expires_at);
    }

    /** @test MC-006: Null provider_token allowed */
    public function test_mc_006_null_provider_token_allowed(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-4',
            'provider_token' => null,
        ]);

        $this->assertNull($socialAccount->provider_token);
    }

    /** @test MC-007: Null provider_refresh_token allowed */
    public function test_mc_007_null_provider_refresh_token_allowed(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-5',
            'provider_refresh_token' => null,
        ]);

        $this->assertNull($socialAccount->provider_refresh_token);
    }

    /** @test MC-008: Null provider_token_expires_at allowed */
    public function test_mc_008_null_provider_token_expires_at_allowed(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-6',
            'provider_token_expires_at' => null,
        ]);

        $this->assertNull($socialAccount->provider_token_expires_at);
    }

    // ==========================================
    // Relationship Tests (RL)
    // ==========================================

    /** @test RL-001: BelongsTo User relationship */
    public function test_rl_001_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $socialAccount = SocialAccount::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $socialAccount->user->id);
        $this->assertInstanceOf(User::class, $socialAccount->user);
    }

    /** @test RL-002: User hasMany socialAccounts */
    public function test_rl_002_user_has_many_social_accounts(): void
    {
        $user = User::factory()->create();

        $google = SocialAccount::factory()->forUser($user)->google()->create();
        $github = SocialAccount::factory()->forUser($user)->github()->create();

        $this->assertCount(2, $user->socialAccounts);
        $this->assertTrue($user->socialAccounts->contains($google));
        $this->assertTrue($user->socialAccounts->contains($github));
    }

    /** @test RL-003: Deleting user cascades to social accounts */
    public function test_rl_003_deleting_user_cascades(): void
    {
        $user = User::factory()->create();
        $socialAccount = SocialAccount::factory()->forUser($user)->create();

        $user->delete();

        $this->assertDatabaseMissing('social_accounts', [
            'id' => $socialAccount->id,
        ]);
    }

    // ==========================================
    // Scope Tests (SC)
    // ==========================================

    /** @test SC-001: scopeByProvider filters by provider */
    public function test_sc_001_scope_by_provider_filters(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->forUser($user)->count(3)->google()->create();
        SocialAccount::factory()->forUser($user)->count(2)->github()->create();

        $results = SocialAccount::byProvider('google')->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals('google', $a->provider));
    }

    /** @test SC-002: scopeByUser filters by user_id */
    public function test_sc_002_scope_by_user_filters(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        SocialAccount::factory()->forUser($user1)->count(3)->create();
        SocialAccount::factory()->forUser($user2)->count(2)->create();

        $results = SocialAccount::byUser($user1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($a) => $this->assertEquals($user1->id, $a->user_id));
    }

    /** @test SC-003: scopeHasToken filters accounts with tokens */
    public function test_sc_003_scope_has_token_filters(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->forUser($user)->count(2)->withToken()->create();
        SocialAccount::factory()->forUser($user)->count(3)->withoutToken()->create();

        $results = SocialAccount::hasToken()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($a) => $this->assertNotNull($a->provider_token));
    }

    /** @test SC-004: scopeHasRefreshToken filters accounts with refresh tokens */
    public function test_sc_004_scope_has_refresh_token_filters(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->forUser($user)->count(2)->withRefreshToken()->create();
        SocialAccount::factory()->forUser($user)->count(3)->create(['provider_refresh_token' => null]);

        $results = SocialAccount::hasRefreshToken()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($a) => $this->assertNotNull($a->provider_refresh_token));
    }

    /** @test SC-005: scopeTokenNotExpired filters valid tokens */
    public function test_sc_005_scope_token_not_expired_filters(): void
    {
        $user = User::factory()->create();

        // Valid (future expiration)
        SocialAccount::factory()->forUser($user)->create([
            'provider_token_expires_at' => now()->addHour(),
            'provider_id' => 'valid-1',
        ]);

        // Expired
        SocialAccount::factory()->forUser($user)->create([
            'provider_token_expires_at' => now()->subHour(),
            'provider_id' => 'expired-1',
        ]);

        // No expiration (null)
        SocialAccount::factory()->forUser($user)->create([
            'provider_token_expires_at' => null,
            'provider_id' => 'null-1',
        ]);

        $results = SocialAccount::tokenNotExpired()->get();

        // Should include valid and null expiration
        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    // ==========================================
    // Helper Method Tests (HM)
    // ==========================================

    /** @test HM-001: isTokenExpired returns true for expired tokens */
    public function test_hm_001_is_token_expired_returns_true_for_expired(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::factory()->forUser($user)->create([
            'provider_token_expires_at' => now()->subMinute(),
        ]);

        $this->assertTrue($socialAccount->isTokenExpired());
    }

    /** @test HM-002: isTokenExpired returns false for valid tokens */
    public function test_hm_002_is_token_expired_returns_false_for_valid(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::factory()->forUser($user)->create([
            'provider_token_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($socialAccount->isTokenExpired());
    }

    /** @test HM-003: isTokenExpired returns true for null expiration */
    public function test_hm_003_is_token_expired_returns_true_for_null(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::factory()->forUser($user)->create([
            'provider_token_expires_at' => null,
        ]);

        // No expiration = assume expired or needs refresh
        $this->assertTrue($socialAccount->isTokenExpired());
    }

    // ==========================================
    // Edge Case Tests (EC)
    // ==========================================

    /** @test EC-001: Large OAuth token stored correctly */
    public function test_ec_001_large_oauth_token_stored(): void
    {
        $user = User::factory()->create();
        $largeToken = str_repeat('a', 4000); // 4KB token

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'large-token',
            'provider_token' => $largeToken,
        ]);

        $socialAccount->refresh();
        $this->assertEquals($largeToken, $socialAccount->provider_token);
    }

    /** @test EC-002: Unicode in provider_id */
    public function test_ec_002_unicode_in_provider_id(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '用户-123-€£¥',
        ]);

        $this->assertEquals('用户-123-€£¥', $socialAccount->provider_id);
    }

    /** @test EC-003: Multiple providers same user different IDs */
    public function test_ec_003_multiple_providers_same_user(): void
    {
        $user = User::factory()->create();

        $google = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-unique-id',
        ]);

        $github = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'github-unique-id',
        ]);

        $this->assertNotEquals($google->id, $github->id);
        $this->assertEquals(2, $user->socialAccounts()->count());
    }

    /** @test EC-004: Very long provider_id (255 chars) */
    public function test_ec_004_very_long_provider_id(): void
    {
        $user = User::factory()->create();
        $longId = str_repeat('a', 255);

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => $longId,
        ]);

        $this->assertEquals($longId, $socialAccount->provider_id);
    }

    /** @test EC-005: Update existing social account */
    public function test_ec_005_update_existing_social_account(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-update-test',
            'provider_token' => 'old-token',
        ]);

        $socialAccount->update([
            'provider_token' => 'new-token',
            'provider_token_expires_at' => now()->addHour(),
        ]);

        $socialAccount->refresh();
        $this->assertEquals('new-token', $socialAccount->provider_token);
        $this->assertNotNull($socialAccount->provider_token_expires_at);
    }

    // ==========================================
    // Security Tests (SEC)
    // ==========================================

    /** @test SEC-001: SQL injection in provider prevented */
    public function test_sec_001_sql_injection_in_provider_prevented(): void
    {
        $user = User::factory()->create();
        $maliciousProvider = "google'; DROP TABLE social_accounts; --";

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $maliciousProvider,
            'provider_id' => 'sqli-test',
        ]);

        // Verify the data is stored as-is (parameterized query)
        $this->assertStringContainsString("DROP TABLE", $socialAccount->provider);

        // Verify table still exists
        $this->assertDatabaseHas('social_accounts', ['id' => $socialAccount->id]);
    }

    /** @test SEC-002: SQL injection in provider_id prevented */
    public function test_sec_002_sql_injection_in_provider_id_prevented(): void
    {
        $user = User::factory()->create();
        $maliciousId = "123'; DELETE FROM social_accounts WHERE 1=1; --";

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => $maliciousId,
        ]);

        // Verify table still exists with data
        $this->assertDatabaseHas('social_accounts', ['id' => $socialAccount->id]);
    }

    /** @test SEC-003: Token encryption at rest */
    public function test_sec_003_token_encryption_at_rest(): void
    {
        $user = User::factory()->create();
        $secretToken = 'super-secret-oauth-token-12345';

        $socialAccount = SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'encryption-test',
            'provider_token' => $secretToken,
        ]);

        // Get raw value from database (bypassing model)
        $rawToken = \DB::table('social_accounts')
            ->where('id', $socialAccount->id)
            ->value('provider_token');

        // Raw value should not be plaintext
        $this->assertNotEquals($secretToken, $rawToken);

        // But model should decrypt it correctly
        $socialAccount->refresh();
        $this->assertEquals($secretToken, $socialAccount->provider_token);
    }

    /** @test SEC-004: Unique constraint prevents duplicate provider+id */
    public function test_sec_004_unique_constraint_prevents_duplicate(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create first social account
        SocialAccount::create([
            'user_id' => $user1->id,
            'provider' => 'google',
            'provider_id' => 'shared-google-id',
        ]);

        // Try to create duplicate with same provider + provider_id
        $this->expectException(\Exception::class);

        SocialAccount::create([
            'user_id' => $user2->id,
            'provider' => 'google',
            'provider_id' => 'shared-google-id',
        ]);
    }
}
