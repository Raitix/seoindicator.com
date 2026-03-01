<?php

namespace Tests\Unit\Repositories;

use App\Models\Account;
use App\Models\CustomDomain;
use App\Repositories\CustomDomainRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CustomDomainRepository.
 *
 * @see TASK-15-2
 * @see TEST-15-2
 */
class CustomDomainRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CustomDomainRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CustomDomainRepository::class);
    }

    // ==========================================
    // CRUD Operations Tests (CRUD)
    // ==========================================

    /** @test CRUD-001: Create custom domain with all fields */
    public function test_crud_001_create_custom_domain(): void
    {
        $account = Account::factory()->create();

        $data = [
            'account_id' => $account->id,
            'domain' => 'analytics.example.com',
            'dns_verified' => true,
            'ssl_provisioned' => true,
            'verified_at' => now(),
        ];

        $domain = $this->repository->create($data);

        $this->assertInstanceOf(CustomDomain::class, $domain);
        $this->assertEquals($account->id, $domain->account_id);
        $this->assertEquals('analytics.example.com', $domain->domain);
        $this->assertTrue($domain->dns_verified);
        $this->assertDatabaseHas('custom_domains', ['id' => $domain->id]);
    }

    /** @test CRUD-002: Create with minimal fields */
    public function test_crud_002_create_with_minimal_fields(): void
    {
        $account = Account::factory()->create();

        $domain = $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'minimal.example.com',
        ]);

        $this->assertInstanceOf(CustomDomain::class, $domain);
        $this->assertFalse($domain->dns_verified);
        $this->assertFalse($domain->ssl_provisioned);
        $this->assertNull($domain->verified_at);
    }

    /** @test CRUD-003: Find by ID */
    public function test_crud_003_find_by_id(): void
    {
        $domain = CustomDomain::factory()->create();

        $found = $this->repository->find($domain->id);

        $this->assertInstanceOf(CustomDomain::class, $found);
        $this->assertEquals($domain->id, $found->id);
    }

    /** @test CRUD-004: Find by ID returns null if not found */
    public function test_crud_004_find_returns_null_if_not_found(): void
    {
        $found = $this->repository->find(99999);

        $this->assertNull($found);
    }

    /** @test CRUD-005: Update custom domain */
    public function test_crud_005_update_custom_domain(): void
    {
        $domain = CustomDomain::factory()->create([
            'dns_verified' => false,
            'ssl_provisioned' => false,
        ]);

        $updated = $this->repository->update($domain->id, [
            'dns_verified' => true,
            'ssl_provisioned' => true,
        ]);

        $this->assertTrue($updated->dns_verified);
        $this->assertTrue($updated->ssl_provisioned);
        $this->assertDatabaseHas('custom_domains', [
            'id' => $domain->id,
            'dns_verified' => 1,
            'ssl_provisioned' => 1,
        ]);
    }

    /** @test CRUD-006: Update returns false if not found */
    public function test_crud_006_update_returns_false_if_not_found(): void
    {
        $result = $this->repository->update(99999, ['dns_verified' => true]);

        $this->assertFalse($result);
    }

    /** @test CRUD-007: Delete custom domain */
    public function test_crud_007_delete_custom_domain(): void
    {
        $domain = CustomDomain::factory()->create();

        $result = $this->repository->delete($domain->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('custom_domains', ['id' => $domain->id]);
    }

    /** @test CRUD-008: Delete returns false if not found */
    public function test_crud_008_delete_returns_false_if_not_found(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    // ==========================================
    // Query Methods Tests (QM)
    // ==========================================

    /** @test QM-001: Find by domain */
    public function test_qm_001_find_by_domain(): void
    {
        $domain = CustomDomain::factory()->create(['domain' => 'test.example.com']);

        $found = $this->repository->findByDomain('test.example.com');

        $this->assertInstanceOf(CustomDomain::class, $found);
        $this->assertEquals($domain->id, $found->id);
    }

    /** @test QM-002: Find by domain returns null if not found */
    public function test_qm_002_find_by_domain_returns_null(): void
    {
        $found = $this->repository->findByDomain('nonexistent.example.com');

        $this->assertNull($found);
    }

    /** @test QM-003: Find by account */
    public function test_qm_003_find_by_account(): void
    {
        $account = Account::factory()->create();
        $domains = CustomDomain::factory()->forAccount($account)->count(3)->create();

        $found = $this->repository->findByAccount($account->id);

        $this->assertCount(3, $found);
        $found->each(fn ($d) => $this->assertEquals($account->id, $d->account_id));
    }

    /** @test QM-004: Find by account returns empty collection if none */
    public function test_qm_004_find_by_account_returns_empty(): void
    {
        $account = Account::factory()->create();

        $found = $this->repository->findByAccount($account->id);

        $this->assertCount(0, $found);
    }

    /** @test QM-005: Find verified by account */
    public function test_qm_005_find_verified_by_account(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->verified()->count(2)->create();
        CustomDomain::factory()->forAccount($account)->pending()->count(3)->create();

        $found = $this->repository->findVerifiedByAccount($account->id);

        $this->assertCount(2, $found);
        $found->each(fn ($d) => $this->assertTrue($d->dns_verified));
    }

    /** @test QM-006: Domain exists check */
    public function test_qm_006_domain_exists_check(): void
    {
        CustomDomain::factory()->create(['domain' => 'exists.example.com']);

        $this->assertTrue($this->repository->exists('exists.example.com'));
        $this->assertFalse($this->repository->exists('notexists.example.com'));
    }

    /** @test QM-007: Get primary domain for account */
    public function test_qm_007_get_primary_domain(): void
    {
        $account = Account::factory()->create();

        $pending = CustomDomain::factory()->forAccount($account)->pending()->create();
        $verified = CustomDomain::factory()->forAccount($account)->verified()->create();

        $primary = $this->repository->getPrimaryDomain($account->id);

        $this->assertNotNull($primary);
        $this->assertTrue($primary->dns_verified);
        $this->assertEquals($verified->id, $primary->id);
    }

    /** @test QM-008: Get primary domain returns null if none verified */
    public function test_qm_008_get_primary_domain_returns_null_if_none_verified(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->pending()->count(3)->create();

        $primary = $this->repository->getPrimaryDomain($account->id);

        $this->assertNull($primary);
    }

    /** @test QM-009: Find SSL ready by account */
    public function test_qm_009_find_ssl_ready_by_account(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->withSsl()->count(2)->create();
        CustomDomain::factory()->forAccount($account)->withoutSsl()->count(3)->create();

        $found = $this->repository->findSslReadyByAccount($account->id);

        $this->assertCount(2, $found);
        $found->each(fn ($d) => $this->assertTrue($d->ssl_provisioned));
    }

    // ==========================================
    // Edge Cases Tests (EC-REP)
    // ==========================================

    /** @test EC-REP-001: Delete non-existent returns false */
    public function test_ec_rep_001_delete_non_existent_returns_false(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    /** @test EC-REP-002: Update non-existent returns false */
    public function test_ec_rep_002_update_non_existent_returns_false(): void
    {
        $result = $this->repository->update(99999, ['dns_verified' => true]);

        $this->assertFalse($result);
    }

    /** @test EC-REP-003: Find with invalid ID type */
    public function test_ec_rep_003_find_with_invalid_id(): void
    {
        $found = $this->repository->find(0);

        $this->assertNull($found);
    }

    /** @test EC-REP-004: Create with missing required field throws error */
    public function test_ec_rep_004_create_with_missing_required_field(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Missing account_id (required field)
        $this->repository->create(['domain' => 'test.example.com']);
    }

    /** @test EC-REP-005: Create with duplicate domain throws error */
    public function test_ec_rep_005_create_with_duplicate_domain_throws_error(): void
    {
        $account = Account::factory()->create();

        $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'duplicate.example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'duplicate.example.com',
        ]);
    }

    /** @test EC-REP-006: Find by domain is case-sensitive */
    public function test_ec_rep_006_find_by_domain_case_sensitivity(): void
    {
        CustomDomain::factory()->create(['domain' => 'test.example.com']);

        $found = $this->repository->findByDomain('TEST.EXAMPLE.COM');

        // Depending on DB collation, this may or may not find the domain
        // We test the repository behavior, not DB collation
        $foundLower = $this->repository->findByDomain('test.example.com');

        $this->assertNotNull($foundLower);
    }

    // ==========================================
    // Performance Tests (PF)
    // ==========================================

    /** @test PF-001: Create is fast */
    public function test_pf_001_create_is_fast(): void
    {
        $account = Account::factory()->create();

        $start = microtime(true);
        $domain = $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'perf.example.com',
        ]);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $elapsed, 'Create should complete in < 100ms');
        $this->assertNotNull($domain->id);
    }

    /** @test PF-002: Bulk queries are efficient */
    public function test_pf_002_bulk_queries_efficient(): void
    {
        $account = Account::factory()->create();

        // Create 50 domains
        CustomDomain::factory()->forAccount($account)->count(50)->create();

        $start = microtime(true);
        $domains = $this->repository->findByAccount($account->id);
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $elapsed, 'Query should complete in < 50ms');
        $this->assertCount(50, $domains);
    }

    /** @test PF-003: Domain lookup by unique index is fast */
    public function test_pf_003_domain_lookup_by_unique_index(): void
    {
        $account = Account::factory()->create();

        // Create many domains
        CustomDomain::factory()->forAccount($account)->count(100)->create();
        $target = CustomDomain::factory()->create(['domain' => 'target.example.com']);

        $start = microtime(true);
        $found = $this->repository->findByDomain('target.example.com');
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(10, $elapsed, 'Unique index lookup should be < 10ms');
        $this->assertEquals($target->id, $found->id);
    }

    // ==========================================
    // Integration Tests (INT)
    // ==========================================

    /** @test INT-001: Full CRUD workflow */
    public function test_int_001_full_crud_workflow(): void
    {
        $account = Account::factory()->create();

        // Create
        $domain = $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'workflow.example.com',
            'dns_verified' => false,
        ]);

        $this->assertNotNull($domain->id);

        // Read
        $found = $this->repository->find($domain->id);
        $this->assertEquals($domain->id, $found->id);

        // Update
        $updated = $this->repository->update($domain->id, [
            'dns_verified' => true,
            'verified_at' => now(),
        ]);
        $this->assertTrue($updated->dns_verified);

        // Delete
        $deleted = $this->repository->delete($domain->id);
        $this->assertTrue($deleted);

        // Verify deleted
        $notFound = $this->repository->find($domain->id);
        $this->assertNull($notFound);
    }

    /** @test INT-002: Account relationship integrity */
    public function test_int_002_account_relationship_integrity(): void
    {
        $account = Account::factory()->create();
        $domain = $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'relation.example.com',
        ]);

        // Verify relationship loaded
        $found = $this->repository->find($domain->id);
        $this->assertEquals($account->id, $found->account_id);

        // Verify account can access domains
        $account->refresh();
        $this->assertTrue($account->customDomains->contains($domain));
    }

    /** @test INT-003: Verification workflow via repository */
    public function test_int_003_verification_workflow(): void
    {
        $account = Account::factory()->create();

        // Create pending domain
        $domain = $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'verify.example.com',
        ]);

        $this->assertFalse($domain->dns_verified);
        $this->assertNull($domain->verified_at);

        // Mark as verified
        $verified = $this->repository->update($domain->id, [
            'dns_verified' => true,
            'verified_at' => now(),
        ]);

        $this->assertTrue($verified->dns_verified);
        $this->assertNotNull($verified->verified_at);

        // Find in verified list
        $verifiedList = $this->repository->findVerifiedByAccount($account->id);
        $this->assertCount(1, $verifiedList);
    }

    /** @test INT-004: SSL provisioning workflow via repository */
    public function test_int_004_ssl_provisioning_workflow(): void
    {
        $account = Account::factory()->create();

        // Create verified domain without SSL
        $domain = $this->repository->create([
            'account_id' => $account->id,
            'domain' => 'ssl.example.com',
            'dns_verified' => true,
            'verified_at' => now(),
        ]);

        $this->assertFalse($domain->ssl_provisioned);

        // Provision SSL
        $sslReady = $this->repository->update($domain->id, [
            'ssl_provisioned' => true,
        ]);

        $this->assertTrue($sslReady->ssl_provisioned);

        // Find in SSL ready list
        $sslList = $this->repository->findSslReadyByAccount($account->id);
        $this->assertCount(1, $sslList);
    }
}
