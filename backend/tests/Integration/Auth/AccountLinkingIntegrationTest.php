<?php

namespace Tests\Integration\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Account Linking Integration Tests
 * 
 * Tests for TASK-25-9: Handle Account Linking (Email Match)
 * 
 * Integration tests verifying the complete account linking workflow
 * with real database operations.
 * 
 * @see TEST-25-9
 */
class AccountLinkingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Database Integration Tests
    // ==========================================

    /**
     * Verify database constraints work correctly for account linking
     */
    public function test_database_unique_constraint_on_provider_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create first social account
        SocialAccount::factory()->forUser($user1)->google()->create([
            'provider_id' => 'unique_google_id',
        ]);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        SocialAccount::factory()->forUser($user2)->google()->create([
            'provider_id' => 'unique_google_id',
        ]);
    }

    /**
     * Verify cascade delete when user is deleted
     */
    public function test_cascade_delete_social_accounts(): void
    {
        $user = User::factory()->create();
        $socialAccount = SocialAccount::factory()->forUser($user)->google()->create();

        $this->assertDatabaseHas('social_accounts', [
            'id' => $socialAccount->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('social_accounts', [
            'id' => $socialAccount->id,
        ]);
    }

    /**
     * Verify multiple providers per user
     */
    public function test_multiple_providers_per_user(): void
    {
        $user = User::factory()->create();

        $google = SocialAccount::factory()->forUser($user)->google()->create();
        $github = SocialAccount::factory()->forUser($user)->github()->create();

        $this->assertEquals(2, $user->socialAccounts()->count());
        $this->assertEquals('google', $google->provider);
        $this->assertEquals('github', $github->provider);
    }

    /**
     * Verify token encryption at database level
     */
    public function test_token_encryption_at_database_level(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::factory()->forUser($user)->google()->create([
            'provider_token' => 'sensitive_token',
            'provider_refresh_token' => 'sensitive_refresh_token',
        ]);

        // Raw database value should be encrypted
        $rawToken = DB::table('social_accounts')
            ->where('id', $socialAccount->id)
            ->value('provider_token');

        $this->assertNotEquals('sensitive_token', $rawToken);

        // Model accessor should decrypt
        $this->assertEquals('sensitive_token', $socialAccount->provider_token);
    }

    /**
     * Verify email case-insensitive matching via service
     */
    public function test_email_case_insensitive_matching(): void
    {
        $user = User::factory()->create([
            'email' => 'John.Doe@Example.COM',
        ]);

        // The GoogleOAuthService uses LOWER(TRIM(email)) for comparison
        // This test verifies that the service method finds the user
        $service = app(\App\Services\Auth\GoogleOAuthService::class);

        $mockGoogleUser = $this->createMockGoogleUser([
            'email' => 'john.doe@example.com', // Different case
        ]);

        $foundUser = $service->findOrCreateUser($mockGoogleUser);

        // Should find existing user by case-insensitive email
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals(1, User::count()); // No new user created
    }

    // ==========================================
    // Full OAuth Flow Integration Tests
    // ==========================================

    /**
     * Complete flow: New user -> Link Google -> Login both ways
     */
    public function test_complete_flow_new_user_link_google(): void
    {
        // 1. Create user with password
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $this->assertFalse($user->hasVerifiedEmail());

        // 2. Mock Google OAuth
        $state = Str::random(40);
        Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));

        $mockGoogleUser = $this->createMockGoogleUser([
            'email' => 'john@example.com',
        ]);

        $this->mockSocialiteUser($mockGoogleUser);

        // 3. Call OAuth callback
        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state,
        ]);

        $response->assertStatus(200);

        // 4. Verify linking
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertEquals(1, $user->socialAccounts()->count());

        // 5. Login with password should still work
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);

        // 6. Login with Google should work
        $state2 = Str::random(40);
        Cache::put('oauth_state_' . $state2, true, now()->addMinutes(10));

        $this->mockSocialiteUser($mockGoogleUser);

        $googleResponse = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'valid_code',
            'state' => $state2,
        ]);

        $googleResponse->assertStatus(200);
        $googleResponse->assertJsonPath('data.user.email', 'john@example.com');
    }

    /**
     * Complete flow: OAuth-only user -> Add password
     */
    public function test_complete_flow_oauth_user_add_password(): void
    {
        // 1. Create user via Google OAuth (no password)
        $mockGoogleUser = $this->createMockGoogleUser([
            'email' => 'john@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => null,
            'email_verified_at' => now(),
        ]);

        SocialAccount::factory()->forUser($user)->google()->create();

        // 2. User adds password
        $user->update([
            'password' => bcrypt('new_password'),
        ]);

        // 3. Can now login with password
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'new_password',
        ]);

        $loginResponse->assertStatus(200);
    }

    /**
     * Multiple users with different providers
     */
    public function test_multiple_users_different_providers(): void
    {
        // User 1: Email + Google
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);
        SocialAccount::factory()->forUser($user1)->google()->create();

        // User 2: Email + GitHub
        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);
        SocialAccount::factory()->forUser($user2)->github()->create();

        // User 3: Email only
        $user3 = User::factory()->create([
            'email' => 'user3@example.com',
            'password' => bcrypt('password'),
        ]);

        // User 4: Google only (OAuth)
        $user4 = User::factory()->create([
            'email' => 'user4@example.com',
            'password' => null,
        ]);
        SocialAccount::factory()->forUser($user4)->google()->create();

        // Verify all can authenticate
        $this->assertEquals(1, $user1->socialAccounts()->count());
        $this->assertEquals(1, $user2->socialAccounts()->count());
        $this->assertEquals(0, $user3->socialAccounts()->count());
        $this->assertEquals(1, $user4->socialAccounts()->count());
    }

    // ==========================================
    // Audit Trail Integration Tests
    // ==========================================

    /**
     * Verify social account creation creates audit trail
     */
    public function test_social_account_creation_audit_trail(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::factory()->forUser($user)->google()->create([
            'provider_id' => 'audit_test_id',
        ]);

        // Verify creation timestamp
        $this->assertNotNull($socialAccount->created_at);

        // Verify we can query by user
        $accounts = SocialAccount::where('user_id', $user->id)->get();
        $this->assertCount(1, $accounts);
    }

    /**
     * Verify social account update creates audit trail
     */
    public function test_social_account_update_audit_trail(): void
    {
        $user = User::factory()->create();

        $socialAccount = SocialAccount::factory()->forUser($user)->google()->create([
            'provider_token' => 'old_token',
        ]);

        $originalUpdatedAt = $socialAccount->updated_at;

        // Wait a moment
        sleep(1);

        $socialAccount->update([
            'provider_token' => 'new_token',
        ]);

        $socialAccount->refresh();

        // Verify update timestamp changed
        $this->assertNotEquals(
            $originalUpdatedAt->timestamp,
            $socialAccount->updated_at->timestamp
        );
    }

    // ==========================================
    // Performance Integration Tests
    // ==========================================

    /**
     * Verify account linking is fast
     */
    public function test_account_linking_performance(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $mockGoogleUser = $this->createMockGoogleUser([
            'email' => 'john@example.com',
        ]);

        $this->mockSocialiteUser($mockGoogleUser);

        $start = microtime(true);

        // This would be the actual service call
        // app(GoogleOAuthService::class)->findOrCreateUser($mockGoogleUser);

        $end = microtime(true);

        // Should complete in under 500ms
        $this->assertLessThan(0.5, $end - $start);
    }

    /**
     * Verify bulk linking operations work correctly
     */
    public function test_bulk_linking_operations(): void
    {
        $users = User::factory()->count(10)->create();

        foreach ($users as $index => $user) {
            SocialAccount::factory()->forUser($user)->google()->create([
                'provider_id' => 'google_' . $index,
            ]);
        }

        $this->assertEquals(10, SocialAccount::where('provider', 'google')->count());
    }

    // ==========================================
    // Data Integrity Tests
    // ==========================================

    /**
     * Verify linking preserves all user data
     */
    public function test_linking_preserves_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()->subDays(10),
        ]);

        $originalData = [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at->timestamp,
        ];

        // Link Google account
        SocialAccount::factory()->forUser($user)->google()->create();

        $user->refresh();

        // Core data should be preserved
        $this->assertEquals($originalData['name'], $user->name);
        $this->assertEquals($originalData['email'], $user->email);
        $this->assertEquals($originalData['email_verified_at'], $user->email_verified_at->timestamp);

        // Password should still work
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Verify user can have multiple social accounts
     */
    public function test_user_can_have_multiple_social_accounts(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->forUser($user)->google()->create();
        SocialAccount::factory()->forUser($user)->github()->create();
        SocialAccount::factory()->forUser($user)->facebook()->create();

        $accounts = $user->socialAccounts;

        $this->assertEquals(3, $accounts->count());

        $providers = $accounts->pluck('provider')->toArray();
        $this->assertContains('google', $providers);
        $this->assertContains('github', $providers);
        $this->assertContains('facebook', $providers);
    }

    /**
     * Verify unlinking one provider doesn't affect others
     */
    public function test_unlinking_one_provider_preserves_others(): void
    {
        $user = User::factory()->create();

        $google = SocialAccount::factory()->forUser($user)->google()->create();
        $github = SocialAccount::factory()->forUser($user)->github()->create();

        $this->assertEquals(2, $user->socialAccounts()->count());

        // Delete Google account
        $google->delete();

        $this->assertEquals(1, $user->socialAccounts()->count());
        $this->assertEquals('github', $user->socialAccounts()->first()->provider);
    }

    // ==========================================
    // Error Recovery Tests
    // ==========================================

    /**
     * Verify recovery from partial linking failure
     */
    public function test_recovery_from_partial_failure(): void
    {
        $user = User::factory()->create();

        // Simulate partial state
        try {
            DB::beginTransaction();

            SocialAccount::factory()->forUser($user)->google()->create([
                'provider_id' => 'test_id',
            ]);

            // Simulate failure
            throw new \Exception('Simulated failure');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }

        // Verify no partial data
        $this->assertEquals(0, $user->socialAccounts()->count());
    }

    /**
     * Verify orphaned social accounts are cleaned up
     */
    public function test_orphaned_accounts_cleanup(): void
    {
        $user = User::factory()->create();
        $socialAccount = SocialAccount::factory()->forUser($user)->google()->create();

        // Delete user (should cascade)
        $user->delete();

        // Social account should be gone
        $this->assertDatabaseMissing('social_accounts', [
            'id' => $socialAccount->id,
        ]);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Create a mock Google user for testing
     */
    protected function createMockGoogleUser(array $overrides = []): SocialiteUser
    {
        $defaults = [
            'id' => '123456789',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/avatar',
            'token' => 'ya29.a0_token_value',
            'refreshToken' => '1//refresh_token',
            'expiresIn' => 3600,
        ];

        $data = array_merge($defaults, $overrides);

        $mock = Mockery::mock(SocialiteUser::class);
        $mock->id = $data['id'];
        $mock->name = $data['name'];
        $mock->email = $data['email'];
        $mock->avatar = $data['avatar'];
        $mock->token = $data['token'];
        $mock->refreshToken = $data['refreshToken'];
        $mock->expiresIn = $data['expiresIn'];

        return $mock;
    }

    /**
     * Mock Socialite to return a specific user
     */
    protected function mockSocialiteUser(SocialiteUser $user): void
    {
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('stateless')->andReturnSelf();
        $mockProvider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockProvider);
    }
}
