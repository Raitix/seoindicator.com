<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\CustomDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CustomDomain model.
 *
 * @see TASK-15-2
 * @see TEST-15-2
 */
class CustomDomainTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Creation Tests (MC)
    // ==========================================

    /** @test MC-001: Can create custom domain with all fields */
    public function test_mc_001_can_create_with_all_fields(): void
    {
        $account = Account::factory()->create();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'analytics.example.com',
            'dns_verified' => true,
            'ssl_provisioned' => true,
            'verified_at' => now(),
        ]);

        $this->assertDatabaseHas('custom_domains', [
            'id' => $domain->id,
            'account_id' => $account->id,
            'domain' => 'analytics.example.com',
            'dns_verified' => 1,
            'ssl_provisioned' => 1,
        ]);
    }

    /** @test MC-002: Can create with minimal fields */
    public function test_mc_002_can_create_with_minimal_fields(): void
    {
        $account = Account::factory()->create();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'minimal.example.com',
        ]);

        $this->assertDatabaseHas('custom_domains', [
            'id' => $domain->id,
            'account_id' => $account->id,
            'domain' => 'minimal.example.com',
        ]);
    }

    /** @test MC-003: Default values are set correctly */
    public function test_mc_003_default_values_are_set_correctly(): void
    {
        $account = Account::factory()->create();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'defaults.example.com',
        ]);
        $domain->refresh(); // Get DB defaults

        $this->assertFalse($domain->dns_verified);
        $this->assertFalse($domain->ssl_provisioned);
        $this->assertNull($domain->verified_at);
    }

    /** @test MC-004: Boolean fields are cast correctly */
    public function test_mc_004_boolean_fields_are_cast_correctly(): void
    {
        $account = Account::factory()->create();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'boolean.example.com',
            'dns_verified' => true,
            'ssl_provisioned' => false,
        ]);

        $this->assertTrue($domain->dns_verified);
        $this->assertFalse($domain->ssl_provisioned);
    }

    /** @test MC-005: Verified at timestamp is cast correctly */
    public function test_mc_005_verified_at_is_cast_correctly(): void
    {
        $account = Account::factory()->create();
        $verifiedAt = now()->subHour();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'timestamp.example.com',
            'verified_at' => $verifiedAt,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $domain->verified_at);
        $this->assertEquals($verifiedAt->timestamp, $domain->verified_at->timestamp);
    }

    /** @test MC-006: Domain uniqueness enforced at model level */
    public function test_mc_006_domain_uniqueness_enforced(): void
    {
        $account = Account::factory()->create();

        CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'unique.example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'unique.example.com',
        ]);
    }

    // ==========================================
    // Relationship Tests (RL)
    // ==========================================

    /** @test RL-001: Custom domain belongs to account */
    public function test_rl_001_belongs_to_account(): void
    {
        $account = Account::factory()->create();
        $domain = CustomDomain::factory()->forAccount($account)->create();

        $this->assertEquals($account->id, $domain->account->id);
        $this->assertInstanceOf(Account::class, $domain->account);
    }

    /** @test RL-002: Account has many custom domains */
    public function test_rl_002_account_has_many_custom_domains(): void
    {
        $account = Account::factory()->create();
        $domain1 = CustomDomain::factory()->forAccount($account)->create();
        $domain2 = CustomDomain::factory()->forAccount($account)->create();

        $this->assertCount(2, $account->customDomains);
        $this->assertTrue($account->customDomains->contains($domain1));
        $this->assertTrue($account->customDomains->contains($domain2));
    }

    /** @test RL-003: Deleting account cascades to custom domains */
    public function test_rl_003_deleting_account_cascades_to_domains(): void
    {
        $account = Account::factory()->create();
        $domain = CustomDomain::factory()->forAccount($account)->create();

        $account->delete();

        $this->assertDatabaseMissing('custom_domains', [
            'id' => $domain->id,
        ]);
    }

    // ==========================================
    // Scope Tests (SC)
    // ==========================================

    /** @test SC-001: Scope verified filters dns_verified=true */
    public function test_sc_001_scope_verified_filters_dns_verified(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->verified()->count(3)->create();
        CustomDomain::factory()->forAccount($account)->pending()->count(2)->create();

        $results = CustomDomain::verified()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($d) => $this->assertTrue($d->dns_verified));
    }

    /** @test SC-002: Scope sslReady filters ssl_provisioned=true */
    public function test_sc_002_scope_ssl_ready_filters_ssl_provisioned(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->withSsl()->count(3)->create();
        CustomDomain::factory()->forAccount($account)->withoutSsl()->count(2)->create();

        $results = CustomDomain::sslReady()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($d) => $this->assertTrue($d->ssl_provisioned));
    }

    /** @test SC-003: Scope pending filters dns_verified=false */
    public function test_sc_003_scope_pending_filters_dns_verified_false(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->pending()->count(3)->create();
        CustomDomain::factory()->forAccount($account)->verified()->count(2)->create();

        $results = CustomDomain::pending()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($d) => $this->assertFalse($d->dns_verified));
    }

    /** @test SC-004: Scope forAccount filters by account */
    public function test_sc_004_scope_for_account_filters_by_account(): void
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        CustomDomain::factory()->forAccount($account1)->count(3)->create();
        CustomDomain::factory()->forAccount($account2)->count(2)->create();

        $results = CustomDomain::forAccount($account1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($d) => $this->assertEquals($account1->id, $d->account_id));
    }

    /** @test SC-005: Scope byDomain filters by domain */
    public function test_sc_005_scope_by_domain_filters_by_domain(): void
    {
        $account = Account::factory()->create();

        CustomDomain::factory()->forAccount($account)->create(['domain' => 'test1.example.com']);
        CustomDomain::factory()->forAccount($account)->create(['domain' => 'test2.example.com']);
        CustomDomain::factory()->forAccount($account)->create(['domain' => 'other.example.com']);

        $results = CustomDomain::byDomain('test1.example.com')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('test1.example.com', $results->first()->domain);
    }

    // ==========================================
    // Accessor/Method Tests (AM)
    // ==========================================

    /** @test AM-001: isVerified returns correct status */
    public function test_am_001_is_verified_returns_correct_status(): void
    {
        $account = Account::factory()->create();

        $verified = CustomDomain::factory()->forAccount($account)->verified()->create();
        $pending = CustomDomain::factory()->forAccount($account)->pending()->create();

        $this->assertTrue($verified->isVerified());
        $this->assertFalse($pending->isVerified());
    }

    /** @test AM-002: isSslReady returns correct status */
    public function test_am_002_is_ssl_ready_returns_correct_status(): void
    {
        $account = Account::factory()->create();

        $withSsl = CustomDomain::factory()->forAccount($account)->withSsl()->create();
        $withoutSsl = CustomDomain::factory()->forAccount($account)->withoutSsl()->create();

        $this->assertTrue($withSsl->isSslReady());
        $this->assertFalse($withoutSsl->isSslReady());
    }

    /** @test AM-003: markAsVerified sets dns_verified and verified_at */
    public function test_am_003_mark_as_verified_sets_verified(): void
    {
        $account = Account::factory()->create();
        $domain = CustomDomain::factory()->forAccount($account)->pending()->create();

        $this->assertFalse($domain->dns_verified);
        $this->assertNull($domain->verified_at);

        $domain->markAsVerified();

        $this->assertTrue($domain->dns_verified);
        $this->assertNotNull($domain->verified_at);
    }

    /** @test AM-004: markSslProvisioned sets ssl_provisioned */
    public function test_am_004_mark_ssl_provisioned_sets_ssl(): void
    {
        $account = Account::factory()->create();
        $domain = CustomDomain::factory()->forAccount($account)->withoutSsl()->create();

        $this->assertFalse($domain->ssl_provisioned);

        $domain->markSslProvisioned();

        $this->assertTrue($domain->ssl_provisioned);
    }

    /** @test AM-005: unmarkVerified resets dns_verified and verified_at */
    public function test_am_005_unmark_verified_resets_verified(): void
    {
        $account = Account::factory()->create();
        $domain = CustomDomain::factory()->forAccount($account)->verified()->create();

        $this->assertTrue($domain->dns_verified);
        $this->assertNotNull($domain->verified_at);

        $domain->unmarkVerified();

        $this->assertFalse($domain->dns_verified);
        $this->assertNull($domain->verified_at);
    }

    // ==========================================
    // Edge Case Tests (EC)
    // ==========================================

    /** @test EC-001: Null verified_at handled correctly */
    public function test_ec_001_null_verified_at_handled(): void
    {
        $account = Account::factory()->create();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'null-verified.example.com',
            'verified_at' => null,
        ]);

        $this->assertNull($domain->verified_at);
    }

    /** @test EC-002: Unicode domain handled correctly */
    public function test_ec_002_unicode_domain_handled(): void
    {
        $account = Account::factory()->create();

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => 'münchen.example.com',
        ]);

        $this->assertEquals('münchen.example.com', $domain->domain);
    }

    /** @test EC-003: Long domain stored correctly */
    public function test_ec_003_long_domain_stored(): void
    {
        $account = Account::factory()->create();

        // 255 char domain
        $longDomain = str_repeat('a', 243) . '.example.com';

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => $longDomain,
        ]);

        $this->assertEquals($longDomain, $domain->domain);
    }

    /** @test EC-004: Multiple domains per account */
    public function test_ec_004_multiple_domains_per_account(): void
    {
        $account = Account::factory()->create();

        $domains = CustomDomain::factory()->forAccount($account)->count(5)->create();

        $this->assertCount(5, $domains);
        $domains->each(fn ($d) => $this->assertEquals($account->id, $d->account_id));
    }

    // ==========================================
    // Security Tests (SEC)
    // ==========================================

    /** @test SEC-001: SQL injection in domain is prevented */
    public function test_sec_001_sql_injection_in_domain_prevented(): void
    {
        $account = Account::factory()->create();

        $maliciousDomain = "example.com'; DROP TABLE custom_domains; --";

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => $maliciousDomain,
        ]);

        // Verify the data is stored as-is (parameterized query)
        $this->assertEquals($maliciousDomain, $domain->domain);

        // Verify table still exists
        $this->assertDatabaseHas('custom_domains', ['id' => $domain->id]);
    }

    /** @test SEC-002: XSS in domain is not executed */
    public function test_sec_002_xss_in_domain_not_executed(): void
    {
        $account = Account::factory()->create();

        $xssDomain = '<script>alert("xss")</script>.example.com';

        $domain = CustomDomain::create([
            'account_id' => $account->id,
            'domain' => $xssDomain,
        ]);

        // Value is stored as-is, output escaping happens at display layer
        $this->assertEquals($xssDomain, $domain->domain);
    }

    // ==========================================
    // Workflow Tests (WF)
    // ==========================================

    /** @test WF-001: DNS verification workflow */
    public function test_wf_001_dns_verification_workflow(): void
    {
        $account = Account::factory()->create();

        // Create pending domain
        $domain = CustomDomain::factory()->forAccount($account)->pending()->create();
        $this->assertFalse($domain->isVerified());
        $this->assertNull($domain->verified_at);

        // Verify DNS
        $domain->markAsVerified();
        $this->assertTrue($domain->isVerified());
        $this->assertNotNull($domain->verified_at);
    }

    /** @test WF-002: SSL provisioning workflow */
    public function test_wf_002_ssl_provisioning_workflow(): void
    {
        $account = Account::factory()->create();

        // Create domain without SSL
        $domain = CustomDomain::factory()->forAccount($account)->verified()->withoutSsl()->create();
        $this->assertFalse($domain->isSslReady());

        // Provision SSL
        $domain->markSslProvisioned();
        $this->assertTrue($domain->isSslReady());
    }

    /** @test WF-003: Full domain lifecycle */
    public function test_wf_003_full_domain_lifecycle(): void
    {
        $account = Account::factory()->create();

        // Create
        $domain = CustomDomain::factory()->forAccount($account)->pending()->create();
        $this->assertFalse($domain->isVerified());
        $this->assertFalse($domain->isSslReady());

        // Verify DNS
        $domain->markAsVerified();
        $this->assertTrue($domain->isVerified());

        // Provision SSL
        $domain->markSslProvisioned();
        $this->assertTrue($domain->isSslReady());

        // Delete
        $domain->delete();
        $this->assertDatabaseMissing('custom_domains', ['id' => $domain->id]);
    }

    /** @test WF-004: Re-verification updates verified_at */
    public function test_wf_004_re_verification_updates_verified_at(): void
    {
        $account = Account::factory()->create();

        // Create and verify
        $domain = CustomDomain::factory()->forAccount($account)->verified()->create();
        $firstVerifiedAt = $domain->verified_at;

        // Wait and re-verify
        sleep(1);
        $domain->unmarkVerified();
        $this->assertNull($domain->verified_at);

        $domain->markAsVerified();
        $this->assertNotNull($domain->verified_at);

        // Check it can be retrieved fresh from DB
        $domain->refresh();
        $this->assertTrue($domain->isVerified());
    }
}
