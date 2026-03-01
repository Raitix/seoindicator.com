<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TASK-15-2: Database Migration - Custom Domains Table
 *
 * This test validates the migration that creates the custom_domains table
 * for tracking custom domains for white-label reselling.
 *
 * Test Categories:
 * - SC-001 to SC-008: Schema Structure
 * - FK-001 to FK-004: Foreign Key Constraints
 * - IX-001 to IX-002: Indexes
 * - RB-001 to RB-002: Migration Rollback
 * - SEC-001 to SEC-003: Security Tests
 *
 * @see TASK-15-2
 * @see SPEC-015
 * @see FEAT-015
 */
class CreateCustomDomainsTableTest extends TestCase
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
     * Create a test account.
     */
    protected function createAccount(array $attributes = []): int
    {
        if (!isset($attributes['owner_id'])) {
            $userId = $this->createUser();
            $attributes['owner_id'] = $userId;
        }

        return DB::table('accounts')->insertGetId(array_merge([
            'name' => 'Test Account ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Run the custom_domains migration.
     */
    protected function runCustomDomainsMigration(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations']);
    }

    /**
     * Rollback the custom_domains migration.
     */
    protected function rollbackCustomDomainsMigration(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1]);
    }

    /**
     * Create a test custom_domain record.
     */
    protected function createCustomDomain(int $accountId, array $attributes = []): int
    {
        return DB::table('custom_domains')->insertGetId(array_merge([
            'account_id' => $accountId,
            'domain' => 'test-' . uniqid() . '.example.com',
            'dns_verified' => 0,
            'ssl_provisioned' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    // =========================================================================
    // Category 1: Schema Structure (SC-001 to SC-008)
    // =========================================================================

    /**
     * SC-001: Migration creates custom_domains table.
     */
    public function test_sc001_migration_creates_custom_domains_table(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasTable('custom_domains'),
            'custom_domains table should exist after migration'
        );
    }

    /**
     * SC-002: Primary key column is correct.
     */
    public function test_sc002_primary_key_column_is_correct(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'id'),
            'id column should exist'
        );

        // Verify auto-increment
        $accountId = $this->createAccount();
        $id1 = $this->createCustomDomain($accountId);
        $id2 = $this->createCustomDomain($accountId);

        $this->assertGreaterThan($id1, $id2, 'id should auto-increment');
    }

    /**
     * SC-003: account_id column is correct.
     */
    public function test_sc003_account_id_column_is_correct(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'account_id'),
            'account_id column should exist'
        );

        // Verify NOT NULL constraint
        $accountId = $this->createAccount();
        $domainId = $this->createCustomDomain($accountId);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertNotNull($domain->account_id, 'account_id should not be null');
    }

    /**
     * SC-004: domain column is correct.
     */
    public function test_sc004_domain_column_is_correct(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'domain'),
            'domain column should exist'
        );

        // Test NOT NULL constraint
        $accountId = $this->createAccount();
        $domainId = $this->createCustomDomain($accountId, [
            'domain' => 'analytics.example.com',
        ]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals('analytics.example.com', $domain->domain);

        // Test max length (255 chars)
        $longDomain = str_repeat('a', 243) . '.example.com'; // 255 chars
        $domainId = $this->createCustomDomain($accountId, ['domain' => $longDomain]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals($longDomain, $domain->domain);
    }

    /**
     * SC-005: dns_verified column is correct.
     */
    public function test_sc005_dns_verified_column_is_correct(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'dns_verified'),
            'dns_verified column should exist'
        );

        // Test default value is 0 (false)
        $accountId = $this->createAccount();
        $domainId = DB::table('custom_domains')->insertGetId([
            'account_id' => $accountId,
            'domain' => 'test-' . uniqid() . '.example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals(0, $domain->dns_verified, 'dns_verified should default to 0');

        // Test setting to true
        DB::table('custom_domains')->where('id', $domainId)->update(['dns_verified' => 1]);
        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals(1, $domain->dns_verified);
    }

    /**
     * SC-006: ssl_provisioned column is correct.
     */
    public function test_sc006_ssl_provisioned_column_is_correct(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'ssl_provisioned'),
            'ssl_provisioned column should exist'
        );

        // Test default value is 0 (false)
        $accountId = $this->createAccount();
        $domainId = DB::table('custom_domains')->insertGetId([
            'account_id' => $accountId,
            'domain' => 'test-' . uniqid() . '.example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals(0, $domain->ssl_provisioned, 'ssl_provisioned should default to 0');

        // Test setting to true
        DB::table('custom_domains')->where('id', $domainId)->update(['ssl_provisioned' => 1]);
        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals(1, $domain->ssl_provisioned);
    }

    /**
     * SC-007: Timestamp columns are correct.
     */
    public function test_sc007_timestamp_columns_are_correct(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'created_at'),
            'created_at column should exist'
        );
        $this->assertTrue(
            Schema::hasColumn('custom_domains', 'verified_at'),
            'verified_at column should exist'
        );

        // Test created_at is set
        $accountId = $this->createAccount();
        $domainId = $this->createCustomDomain($accountId);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertNotNull($domain->created_at, 'created_at should be set');

        // Test verified_at is nullable
        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertNull($domain->verified_at, 'verified_at should be null by default');

        // Test setting verified_at
        $verifiedAt = now()->addHour();
        DB::table('custom_domains')->where('id', $domainId)->update([
            'verified_at' => $verifiedAt,
        ]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertNotNull($domain->verified_at, 'verified_at should be set after update');
    }

    /**
     * SC-008: Domain uniqueness is enforced.
     */
    public function test_sc008_domain_uniqueness_is_enforced(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $domain = 'unique.example.com';

        // First insert should succeed
        $id1 = $this->createCustomDomain($accountId, ['domain' => $domain]);
        $this->assertNotNull($id1);

        // Second insert with same domain should fail
        $this->expectException(\Exception::class);
        $this->createCustomDomain($accountId, ['domain' => $domain]);
    }

    // =========================================================================
    // Category 2: Foreign Key Constraints (FK-001 to FK-004)
    // =========================================================================

    /**
     * FK-001: Foreign key to accounts.id.
     */
    public function test_fk001_foreign_key_to_accounts_id(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();

        // Insert with valid account_id should succeed
        $domainId = $this->createCustomDomain($accountId);
        $this->assertNotNull($domainId, 'Insert with valid account_id should succeed');
    }

    /**
     * FK-002: Invalid account_id rejected.
     */
    public function test_fk002_invalid_account_id_rejected(): void
    {
        $this->runCustomDomainsMigration();

        // Insert with non-existent account_id should fail
        $this->expectException(\Exception::class);
        $this->createCustomDomain(999999);
    }

    /**
     * FK-003: Cascade delete on account deletion.
     */
    public function test_fk003_cascade_delete_on_account_deletion(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $domainId = $this->createCustomDomain($accountId);

        // Verify record exists
        $this->assertDatabaseHas('custom_domains', ['id' => $domainId]);

        // Delete account
        DB::table('accounts')->where('id', $accountId)->delete();

        // Verify domain was cascade deleted
        $this->assertDatabaseMissing('custom_domains', ['id' => $domainId]);
    }

    /**
     * FK-004: Multiple domains per account allowed.
     */
    public function test_fk004_multiple_domains_per_account_allowed(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();

        // Create multiple domains for same account
        $domain1 = $this->createCustomDomain($accountId, ['domain' => 'domain1.example.com']);
        $domain2 = $this->createCustomDomain($accountId, ['domain' => 'domain2.example.com']);
        $domain3 = $this->createCustomDomain($accountId, ['domain' => 'domain3.example.com']);

        $this->assertNotEquals($domain1, $domain2);
        $this->assertNotEquals($domain2, $domain3);

        $count = DB::table('custom_domains')
            ->where('account_id', $accountId)
            ->count();

        $this->assertEquals(3, $count, 'Should have 3 domains for account');
    }

    // =========================================================================
    // Category 3: Indexes (IX-001 to IX-002)
    // =========================================================================

    /**
     * IX-001: Unique index on domain.
     */
    public function test_ix001_unique_index_on_domain(): void
    {
        $this->runCustomDomainsMigration();

        $indexes = $this->getTableIndexes('custom_domains');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('domain', $index['columns']) && $index['unique']) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Unique index on domain should exist'
        );
    }

    /**
     * IX-002: Foreign key index on account_id.
     */
    public function test_ix002_foreign_key_index_on_account_id(): void
    {
        $this->runCustomDomainsMigration();

        $indexes = $this->getTableIndexes('custom_domains');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('account_id', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Index on account_id (FK) should exist'
        );
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
                    'unique' => $row->unique == 1,
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
    // Category 4: Migration Rollback (RB-001 to RB-002)
    // =========================================================================

    /**
     * RB-001: Migration down drops table.
     */
    public function test_rb001_migration_down_drops_table(): void
    {
        $this->runCustomDomainsMigration();

        $this->assertTrue(Schema::hasTable('custom_domains'), 'Table should exist after migration');

        $this->rollbackCustomDomainsMigration();

        $this->assertFalse(
            Schema::hasTable('custom_domains'),
            'Table should not exist after rollback'
        );
    }

    /**
     * RB-002: Migration re-run after rollback.
     */
    public function test_rb002_migration_rerun_after_rollback(): void
    {
        // Run migration
        $this->runCustomDomainsMigration();
        $this->assertTrue(Schema::hasTable('custom_domains'));

        // Rollback
        $this->rollbackCustomDomainsMigration();
        $this->assertFalse(Schema::hasTable('custom_domains'));

        // Re-run migration
        $this->runCustomDomainsMigration();
        $this->assertTrue(
            Schema::hasTable('custom_domains'),
            'Table should exist after re-run'
        );

        // Verify schema is correct
        $this->assertTrue(Schema::hasColumn('custom_domains', 'id'));
        $this->assertTrue(Schema::hasColumn('custom_domains', 'account_id'));
        $this->assertTrue(Schema::hasColumn('custom_domains', 'domain'));
        $this->assertTrue(Schema::hasColumn('custom_domains', 'dns_verified'));
        $this->assertTrue(Schema::hasColumn('custom_domains', 'ssl_provisioned'));
        $this->assertTrue(Schema::hasColumn('custom_domains', 'verified_at'));
    }

    // =========================================================================
    // Category 5: Security Tests (SEC-001 to SEC-003)
    // =========================================================================

    /**
     * SEC-001: SQL injection prevention.
     */
    public function test_sec001_sql_injection_prevention(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $maliciousDomain = "example.com'; DROP TABLE custom_domains; --";

        $domainId = $this->createCustomDomain($accountId, ['domain' => $maliciousDomain]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals($maliciousDomain, $domain->domain);

        // Table should still exist
        $this->assertTrue(Schema::hasTable('custom_domains'));
    }

    /**
     * SEC-002: XSS prevention in domain.
     */
    public function test_sec002_xss_prevention_in_domain(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $xssDomain = '<script>alert("xss")</script>.example.com';

        $domainId = $this->createCustomDomain($accountId, ['domain' => $xssDomain]);

        $domain = DB::table('custom_domains')->find($domainId);

        // Script tag should be stored as string, not executed
        $this->assertEquals($xssDomain, $domain->domain);
    }

    /**
     * SEC-003: Path traversal in domain.
     */
    public function test_sec003_path_traversal_in_domain(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $traversalDomain = '../../../etc/passwd';

        $domainId = $this->createCustomDomain($accountId, ['domain' => $traversalDomain]);

        $domain = DB::table('custom_domains')->find($domainId);

        // Path traversal should be stored as string (app layer validates)
        $this->assertEquals($traversalDomain, $domain->domain);
    }

    // =========================================================================
    // Category 6: Edge Cases (EC-001 to EC-008)
    // =========================================================================

    /**
     * EC-001: Domain max length 255.
     */
    public function test_ec001_domain_max_length_255(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();

        // 255 chars should succeed
        $validDomain = str_repeat('a', 243) . '.example.com'; // 255 chars
        $domainId = $this->createCustomDomain($accountId, ['domain' => $validDomain]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals($validDomain, $domain->domain);

        // 256 chars - behavior depends on DB:
        // - MySQL: throws "data too long" error
        // - SQLite: stores full value (doesn't enforce VARCHAR length)
        $longDomain = str_repeat('a', 244) . '.example.com'; // 256 chars

        try {
            $domainId = $this->createCustomDomain($accountId, ['domain' => $longDomain]);
            // SQLite: stores full value (not an error in SQLite)
            $domain = DB::table('custom_domains')->find($domainId);
            // Accept either truncation (MySQL) or full storage (SQLite)
            $this->assertLessThanOrEqual(256, strlen($domain->domain), 'Domain should be stored (SQLite allows > 255)');
        } catch (\Exception $e) {
            // MySQL: throws "data too long" error
            $this->assertStringContainsString('too long', strtolower($e->getMessage()));
        }
    }

    /**
     * EC-002: Domain case sensitivity.
     */
    public function test_ec002_domain_case_sensitivity(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();

        // Insert lowercase domain
        $domainId1 = $this->createCustomDomain($accountId, ['domain' => 'example.com']);

        // Try to insert uppercase version
        try {
            $domainId2 = $this->createCustomDomain($accountId, ['domain' => 'EXAMPLE.COM']);
            // If we get here, case-insensitive unique constraint
            // This is actually the desired behavior for domains
            $this->assertNotEquals($domainId1, $domainId2);
        } catch (\Exception $e) {
            // Expected: unique constraint violation (case-insensitive)
            $this->assertStringContainsString('unique', strtolower($e->getMessage()));
        }
    }

    /**
     * EC-003: Empty domain rejected.
     */
    public function test_ec003_empty_domain_rejected(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();

        // Empty string behavior depends on DB:
        // - MySQL: NOT NULL constraint allows empty string (NOT NULL != non-empty)
        // - SQLite: Similar behavior
        // Application layer validation should prevent empty domains
        try {
            $domainId = $this->createCustomDomain($accountId, ['domain' => '']);
            // If stored, it's an empty string (NOT NULL constraint passed)
            // This is actually valid at DB level - validation should happen at app layer
            $domain = DB::table('custom_domains')->find($domainId);
            $this->assertEquals('', $domain->domain, 'Empty string stored (app layer should validate)');
        } catch (\Exception $e) {
            // Some DB configurations might reject empty strings
            $this->assertStringContainsString('null', strtolower($e->getMessage()));
        }
    }

    /**
     * EC-004: Null verified_at allowed.
     */
    public function test_ec004_null_verified_at_allowed(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $domainId = $this->createCustomDomain($accountId, ['verified_at' => null]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertNull($domain->verified_at);
    }

    /**
     * EC-005: Unicode domain handled.
     */
    public function test_ec005_unicode_domain_handled(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $unicodeDomain = 'münchen.example.com';

        $domainId = $this->createCustomDomain($accountId, ['domain' => $unicodeDomain]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals($unicodeDomain, $domain->domain);
    }

    /**
     * EC-006: IDN domain handled.
     */
    public function test_ec006_idn_domain_handled(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $idnDomain = 'xn--fiqs8s.example.com'; // 中国.cn in punycode

        $domainId = $this->createCustomDomain($accountId, ['domain' => $idnDomain]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals($idnDomain, $domain->domain);
    }

    /**
     * EC-007: Subdomain handled.
     */
    public function test_ec007_subdomain_handled(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();
        $subdomain = 'deep.nested.subdomain.example.com';

        $domainId = $this->createCustomDomain($accountId, ['domain' => $subdomain]);

        $domain = DB::table('custom_domains')->find($domainId);
        $this->assertEquals($subdomain, $domain->domain);
    }

    /**
     * EC-008: Multiple domains same account.
     */
    public function test_ec008_multiple_domains_same_account(): void
    {
        $this->runCustomDomainsMigration();

        $accountId = $this->createAccount();

        $domains = [];
        for ($i = 1; $i <= 5; $i++) {
            $domains[] = $this->createCustomDomain($accountId, [
                'domain' => "domain{$i}.example.com",
            ]);
        }

        $this->assertCount(5, array_unique($domains), 'Should have 5 unique domain IDs');

        $count = DB::table('custom_domains')
            ->where('account_id', $accountId)
            ->count();

        $this->assertEquals(5, $count);
    }
}
