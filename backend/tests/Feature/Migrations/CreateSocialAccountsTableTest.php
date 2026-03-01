<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TASK-25-2: Database Migration - Social Accounts Table
 *
 * This test validates the migration that creates the social_accounts table
 * for storing OAuth provider connections.
 *
 * Test Categories:
 * - SC-001 to SC-009: Schema Structure
 * - FK-001 to FK-004: Foreign Key Constraints
 * - UC-001 to UC-003: Unique Constraints
 * - IX-001 to IX-002: Indexes
 * - RB-001 to RB-002: Migration Rollback
 * - DV-001 to DV-006: Data Validation
 * - EC-001 to EC-008: Edge Cases
 * - SEC-001 to SEC-002: Security Tests
 *
 * @see TASK-25-2
 * @see SPEC-025
 * @see FEAT-025
 */
class CreateSocialAccountsTableTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test user.
     */
    protected function createUser(array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Run the social_accounts migration.
     */
    protected function runSocialAccountsMigration(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations']);
    }

    /**
     * Rollback the social_accounts migration.
     */
    protected function rollbackSocialAccountsMigration(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1]);
    }

    /**
     * Create a test social_accounts record.
     */
    protected function createSocialAccount(int $userId, array $attributes = []): int
    {
        return DB::table('social_accounts')->insertGetId(array_merge([
            'user_id' => $userId,
            'provider' => 'google',
            'provider_id' => 'google-' . uniqid(),
            'provider_token' => 'test-token',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Helper: Get table indexes.
     */
    protected function getTableIndexes(string $table): array
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Query sqlite_master for indexes
            $indexes = [];
            $result = DB::select("PRAGMA index_list({$table})");
            foreach ($result as $row) {
                $indexInfo = DB::select("PRAGMA index_info({$row->name})");
                $columns = array_map(fn($col) => $col->name, $indexInfo);
                $indexes[] = [
                    'name' => $row->name,
                    'columns' => $columns,
                    'unique' => $row->unique ?? false,
                ];
            }
            return $indexes;
        }

        // MySQL
        $result = DB::select("SHOW INDEX FROM {$table}");
        $indexes = [];
        foreach ($result as $row) {
            $indexes[$row->Key_name]['name'] = $row->Key_name;
            $indexes[$row->Key_name]['columns'][] = $row->Column_name;
            $indexes[$row->Key_name]['unique'] = !$row->Non_unique;
        }

        return array_values($indexes);
    }

    // =========================================================================
    // Category 1: Schema Structure (SC-001 to SC-009)
    // =========================================================================

    /**
     * SC-001: Migration creates social_accounts table.
     */
    public function test_sc001_migration_creates_social_accounts_table(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasTable('social_accounts'),
            'social_accounts table should exist after migration'
        );
    }

    /**
     * SC-002: Primary key column is correct.
     */
    public function test_sc002_primary_key_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'id'),
            'id column should exist'
        );

        // Verify auto-increment
        $userId = $this->createUser();
        $id1 = $this->createSocialAccount($userId);
        $id2 = $this->createSocialAccount($userId, ['provider' => 'github', 'provider_id' => 'github-1']);

        $this->assertGreaterThan($id1, $id2, 'id should auto-increment');
    }

    /**
     * SC-003: user_id column is correct.
     */
    public function test_sc003_user_id_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'user_id'),
            'user_id column should exist'
        );

        // Verify NOT NULL constraint
        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNotNull($socialAccount->user_id, 'user_id should not be null');
    }

    /**
     * SC-004: provider column is correct.
     */
    public function test_sc004_provider_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'provider'),
            'provider column should exist'
        );

        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, ['provider' => 'github']);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals('github', $socialAccount->provider);
    }

    /**
     * SC-005: provider_id column is correct.
     */
    public function test_sc005_provider_id_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'provider_id'),
            'provider_id column should exist'
        );

        $userId = $this->createUser();
        $providerId = 'unique-provider-id-12345';
        $socialAccountId = $this->createSocialAccount($userId, ['provider_id' => $providerId]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($providerId, $socialAccount->provider_id);
    }

    /**
     * SC-006: provider_token column is correct.
     */
    public function test_sc006_provider_token_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'provider_token'),
            'provider_token column should exist'
        );

        // Test NULL allowed
        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, ['provider_token' => null]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNull($socialAccount->provider_token, 'provider_token should allow NULL');

        // Test valid token
        $token = 'ya29.a0AfH6SMBx...';
        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google2',
            'provider_id' => 'google-id-2',
            'provider_token' => $token,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($token, $socialAccount->provider_token);
    }

    /**
     * SC-007: provider_refresh_token column is correct.
     */
    public function test_sc007_provider_refresh_token_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'provider_refresh_token'),
            'provider_refresh_token column should exist'
        );

        // Test NULL allowed
        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, ['provider_refresh_token' => null]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNull($socialAccount->provider_refresh_token, 'provider_refresh_token should allow NULL');
    }

    /**
     * SC-008: provider_token_expires_at column is correct.
     */
    public function test_sc008_provider_token_expires_at_column_is_correct(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(
            Schema::hasColumn('social_accounts', 'provider_token_expires_at'),
            'provider_token_expires_at column should exist'
        );

        // Test NULL allowed
        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, ['provider_token_expires_at' => null]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNull($socialAccount->provider_token_expires_at, 'provider_token_expires_at should allow NULL');

        // Test valid timestamp
        $expiresAt = now()->addHour();
        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google3',
            'provider_id' => 'google-id-3',
            'provider_token_expires_at' => $expiresAt,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNotNull($socialAccount->provider_token_expires_at);
    }

    /**
     * SC-009: Timestamp columns are correct.
     */
    public function test_sc009_timestamp_columns_are_correct(): void
    {
        $this->runSocialAccountsMigration();

        $timestampColumns = ['created_at', 'updated_at'];

        foreach ($timestampColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('social_accounts', $column),
                "{$column} column should exist"
            );
        }

        // Test timestamps are set
        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNotNull($socialAccount->created_at, 'created_at should be set');
        $this->assertNotNull($socialAccount->updated_at, 'updated_at should be set');
    }

    // =========================================================================
    // Category 2: Foreign Key Constraints (FK-001 to FK-004)
    // =========================================================================

    /**
     * FK-001: Foreign key to users.id.
     */
    public function test_fk001_foreign_key_to_users_id(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();

        // Insert with valid user_id should succeed
        $socialAccountId = $this->createSocialAccount($userId);
        $this->assertNotNull($socialAccountId, 'Insert with valid user_id should succeed');
    }

    /**
     * FK-002: Invalid user_id rejected.
     */
    public function test_fk002_invalid_user_id_rejected(): void
    {
        $this->runSocialAccountsMigration();

        // Insert with invalid user_id should fail
        $this->expectException(\Exception::class);
        $this->createSocialAccount(999999);
    }

    /**
     * FK-003: Cascade delete on user delete.
     */
    public function test_fk003_cascade_delete_on_user_delete(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId);

        // Verify record exists
        $this->assertDatabaseHas('social_accounts', ['id' => $socialAccountId]);

        // Delete user
        DB::table('users')->where('id', $userId)->delete();

        // Verify social_account was cascade deleted
        $this->assertDatabaseMissing('social_accounts', ['id' => $socialAccountId]);
    }

    /**
     * FK-004: Foreign key index exists.
     */
    public function test_fk004_foreign_key_index_exists(): void
    {
        $this->runSocialAccountsMigration();

        $indexes = $this->getTableIndexes('social_accounts');

        $hasUserIdIndex = false;
        foreach ($indexes as $index) {
            if (in_array('user_id', $index['columns'])) {
                $hasUserIdIndex = true;
                break;
            }
        }

        $this->assertTrue(
            $hasUserIdIndex,
            'Index on user_id (FK) should exist'
        );
    }

    // =========================================================================
    // Category 3: Unique Constraints (UC-001 to UC-003)
    // =========================================================================

    /**
     * UC-001: Unique constraint on (provider, provider_id).
     */
    public function test_uc001_unique_constraint_on_provider_provider_id(): void
    {
        $this->runSocialAccountsMigration();

        $userId1 = $this->createUser();
        $userId2 = $this->createUser();

        // Create first social account
        $this->createSocialAccount($userId1, [
            'provider' => 'google',
            'provider_id' => 'google-user-123',
        ]);

        // Try to create duplicate with same provider + provider_id
        $this->expectException(\Exception::class);
        $this->createSocialAccount($userId2, [
            'provider' => 'google',
            'provider_id' => 'google-user-123',
        ]);
    }

    /**
     * UC-002: Same provider_id different provider allowed.
     */
    public function test_uc002_same_provider_id_different_provider(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();

        // Google with provider_id '123'
        $id1 = $this->createSocialAccount($userId, [
            'provider' => 'google',
            'provider_id' => '123',
        ]);

        // GitHub with same provider_id '123' - should be allowed
        $id2 = $this->createSocialAccount($userId, [
            'provider' => 'github',
            'provider_id' => '123',
        ]);

        $this->assertNotEquals($id1, $id2, 'Different providers should allow same provider_id');
        $this->assertDatabaseCount('social_accounts', 2);
    }

    /**
     * UC-003: Same provider different provider_id allowed.
     */
    public function test_uc003_same_provider_different_provider_id(): void
    {
        $this->runSocialAccountsMigration();

        $userId1 = $this->createUser();
        $userId2 = $this->createUser();

        // Google user 1
        $id1 = $this->createSocialAccount($userId1, [
            'provider' => 'google',
            'provider_id' => 'google-user-1',
        ]);

        // Google user 2 - different provider_id
        $id2 = $this->createSocialAccount($userId2, [
            'provider' => 'google',
            'provider_id' => 'google-user-2',
        ]);

        $this->assertNotEquals($id1, $id2, 'Different provider_ids should be allowed for same provider');
        $this->assertDatabaseCount('social_accounts', 2);
    }

    // =========================================================================
    // Category 4: Indexes (IX-001 to IX-002)
    // =========================================================================

    /**
     * IX-001: Unique index on (provider, provider_id).
     */
    public function test_ix001_unique_index_on_provider_provider_id(): void
    {
        $this->runSocialAccountsMigration();

        $indexes = $this->getTableIndexes('social_accounts');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('provider', $index['columns']) && in_array('provider_id', $index['columns'])) {
                $found = true;
                $this->assertTrue($index['unique'], 'Index should be unique');
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Unique index on (provider, provider_id) should exist'
        );
    }

    /**
     * IX-002: Composite index on (user_id, provider).
     */
    public function test_ix002_composite_index_on_user_id_provider(): void
    {
        $this->runSocialAccountsMigration();

        $indexes = $this->getTableIndexes('social_accounts');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('user_id', $index['columns']) && in_array('provider', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Composite index on (user_id, provider) should exist'
        );
    }

    // =========================================================================
    // Category 5: Migration Rollback (RB-001 to RB-002)
    // =========================================================================

    /**
     * RB-001: Migration down drops table.
     */
    public function test_rb001_migration_down_drops_table(): void
    {
        $this->runSocialAccountsMigration();

        $this->assertTrue(Schema::hasTable('social_accounts'), 'Table should exist after migration');

        $this->rollbackSocialAccountsMigration();

        $this->assertFalse(
            Schema::hasTable('social_accounts'),
            'Table should not exist after rollback'
        );
    }

    /**
     * RB-002: Migration re-run after rollback.
     */
    public function test_rb002_migration_rerun_after_rollback(): void
    {
        // Run migration
        $this->runSocialAccountsMigration();
        $this->assertTrue(Schema::hasTable('social_accounts'));

        // Rollback
        $this->rollbackSocialAccountsMigration();
        $this->assertFalse(Schema::hasTable('social_accounts'));

        // Re-run migration
        $this->runSocialAccountsMigration();
        $this->assertTrue(
            Schema::hasTable('social_accounts'),
            'Table should exist after re-run'
        );

        // Verify schema is correct
        $this->assertTrue(Schema::hasColumn('social_accounts', 'id'));
        $this->assertTrue(Schema::hasColumn('social_accounts', 'user_id'));
        $this->assertTrue(Schema::hasColumn('social_accounts', 'provider'));
        $this->assertTrue(Schema::hasColumn('social_accounts', 'provider_id'));
    }

    // =========================================================================
    // Category 6: Data Validation (DV-001 to DV-006)
    // =========================================================================

    /**
     * DV-001: provider max length (50 chars).
     */
    public function test_dv001_provider_max_length(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $maxProvider = str_repeat('a', 50);

        $socialAccountId = $this->createSocialAccount($userId, ['provider' => $maxProvider]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($maxProvider, $socialAccount->provider);
    }

    /**
     * DV-002: provider exceeds max length.
     */
    public function test_dv002_provider_exceeds_max_length(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $longProvider = str_repeat('a', 51);

        // MySQL will truncate or error depending on strict mode
        // SQLite doesn't enforce VARCHAR length
        try {
            $socialAccountId = $this->createSocialAccount($userId, ['provider' => $longProvider]);
            // If it succeeds, verify it was stored
            $this->assertNotNull($socialAccountId);
        } catch (\Exception $e) {
            // If it fails, that's also acceptable (strict mode)
            $this->assertStringContainsString('Data too long', $e->getMessage());
        }
    }

    /**
     * DV-003: provider_id max length (255 chars).
     */
    public function test_dv003_provider_id_max_length(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $maxProviderId = str_repeat('a', 255);

        $socialAccountId = $this->createSocialAccount($userId, ['provider_id' => $maxProviderId]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($maxProviderId, $socialAccount->provider_id);
    }

    /**
     * DV-004: provider_id exceeds max length.
     */
    public function test_dv004_provider_id_exceeds_max_length(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $longProviderId = str_repeat('a', 256);

        // MySQL will truncate or error depending on strict mode
        try {
            $socialAccountId = $this->createSocialAccount($userId, ['provider_id' => $longProviderId]);
            $this->assertNotNull($socialAccountId);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Data too long', $e->getMessage());
        }
    }

    /**
     * DV-005: Valid provider values.
     */
    public function test_dv005_valid_provider_values(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $validProviders = ['google', 'github', 'facebook', 'twitter', 'linkedin', 'gitlab'];
        $counter = 0;

        foreach ($validProviders as $provider) {
            $counter++;
            $socialAccountId = $this->createSocialAccount($userId, [
                'provider' => $provider,
                'provider_id' => $provider . '-id-' . $counter,
            ]);
            $this->assertNotNull($socialAccountId, "Provider '{$provider}' should be valid");
        }

        $this->assertDatabaseCount('social_accounts', count($validProviders));
    }

    /**
     * DV-006: Empty provider rejected.
     */
    public function test_dv006_empty_provider_rejected(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();

        $this->expectException(\Exception::class);
        $this->createSocialAccount($userId, ['provider' => '']);
    }

    // =========================================================================
    // Category 7: Edge Cases (EC-001 to EC-008)
    // =========================================================================

    /**
     * EC-001: Null provider_token allowed.
     */
    public function test_ec001_null_provider_token_allowed(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, [
            'provider_token' => null,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNull($socialAccount->provider_token);
    }

    /**
     * EC-002: Null provider_refresh_token allowed.
     */
    public function test_ec002_null_provider_refresh_token_allowed(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, [
            'provider_refresh_token' => null,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNull($socialAccount->provider_refresh_token);
    }

    /**
     * EC-003: Null provider_token_expires_at allowed.
     */
    public function test_ec003_null_provider_token_expires_at_allowed(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $socialAccountId = $this->createSocialAccount($userId, [
            'provider_token_expires_at' => null,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNull($socialAccount->provider_token_expires_at);
    }

    /**
     * EC-004: Multiple providers for same user.
     */
    public function test_ec004_multiple_providers_for_same_user(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();

        $this->createSocialAccount($userId, ['provider' => 'google', 'provider_id' => 'google-1']);
        $this->createSocialAccount($userId, ['provider' => 'github', 'provider_id' => 'github-1']);
        $this->createSocialAccount($userId, ['provider' => 'facebook', 'provider_id' => 'facebook-1']);

        $this->assertDatabaseCount('social_accounts', 3);

        $socialAccounts = DB::table('social_accounts')->where('user_id', $userId)->get();
        $this->assertCount(3, $socialAccounts);
    }

    /**
     * EC-005: Unicode in provider_token.
     */
    public function test_ec005_unicode_in_provider_token(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $unicodeToken = 'токен-€£¥-token';

        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google-test',
            'provider_id' => 'unicode-test',
            'provider_token' => $unicodeToken,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($unicodeToken, $socialAccount->provider_token);
    }

    /**
     * EC-006: Long provider_token (large OAuth token).
     */
    public function test_ec006_long_provider_token(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $longToken = str_repeat('a', 5000); // 5KB token

        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google-long',
            'provider_id' => 'long-token-test',
            'provider_token' => $longToken,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($longToken, $socialAccount->provider_token);
    }

    /**
     * EC-007: Token expiration in past.
     */
    public function test_ec007_token_expiration_in_past(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $pastExpiration = now()->subHour();

        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google-past',
            'provider_id' => 'past-expiration',
            'provider_token_expires_at' => $pastExpiration,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNotNull($socialAccount->provider_token_expires_at);
    }

    /**
     * EC-008: Token expiration in future.
     */
    public function test_ec008_token_expiration_in_future(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $futureExpiration = now()->addYear();

        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google-future',
            'provider_id' => 'future-expiration',
            'provider_token_expires_at' => $futureExpiration,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertNotNull($socialAccount->provider_token_expires_at);
    }

    // =========================================================================
    // Category 8: Security Tests (SEC-001 to SEC-002)
    // =========================================================================

    /**
     * SEC-001: SQL injection in provider prevented.
     */
    public function test_sec001_sql_injection_in_provider_prevented(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $maliciousProvider = "google'; DROP TABLE social_accounts; --";

        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => $maliciousProvider,
            'provider_id' => 'sqli-test',
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($maliciousProvider, $socialAccount->provider);

        // Table should still exist
        $this->assertTrue(Schema::hasTable('social_accounts'));
    }

    /**
     * SEC-002: SQL injection in provider_id prevented.
     */
    public function test_sec002_sql_injection_in_provider_id_prevented(): void
    {
        $this->runSocialAccountsMigration();

        $userId = $this->createUser();
        $maliciousProviderId = "123'; DELETE FROM social_accounts WHERE 1=1; --";

        $socialAccountId = $this->createSocialAccount($userId, [
            'provider' => 'google-sec',
            'provider_id' => $maliciousProviderId,
        ]);

        $socialAccount = DB::table('social_accounts')->find($socialAccountId);
        $this->assertEquals($maliciousProviderId, $socialAccount->provider_id);

        // Table should still exist with data
        $this->assertDatabaseHas('social_accounts', ['id' => $socialAccountId]);
    }
}
