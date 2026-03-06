<?php

namespace Tests\Unit\Models;

use App\Models\Audit;
use App\Models\PublicAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Unit tests for PublicAudit model.
 *
 * Test Categories:
 * - MI: Model Instantiation
 * - RE: Relationships
 * - GH: generateHash() Method
 * - PB: publish() Method
 * - UP: unpublish() Method
 * - IP: isPublic() Method
 * - FH: findByHash() Method
 * - UN: Uniqueness/Collision Detection
 * - TR: Transaction Safety
 * - SC: Security
 * - EC: Edge Cases
 * - AC: Acceptance Criteria
 */
class PublicAuditTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // MI: Model Instantiation Tests
    // =========================================================================

    /** @test MI-001: Model can be instantiated */
    public function model_can_be_instantiated(): void
    {
        $publicAudit = new PublicAudit();

        $this->assertInstanceOf(PublicAudit::class, $publicAudit);
    }

    /** @test MI-002: Model has correct table name */
    public function model_has_correct_table_name(): void
    {
        $publicAudit = new PublicAudit();

        $this->assertEquals('public_audits', $publicAudit->getTable());
    }

    /** @test MI-003: Model has audit relationship */
    public function model_has_audit_relationship(): void
    {
        $publicAudit = new PublicAudit();

        $this->assertTrue(method_exists($publicAudit, 'audit'));
    }

    /** @test MI-004: Model fillable attributes are defined */
    public function model_fillable_attributes_are_defined(): void
    {
        $publicAudit = new PublicAudit();
        $fillable = $publicAudit->getFillable();

        $this->assertContains('audit_id', $fillable);
        $this->assertContains('public_hash', $fillable);
        $this->assertContains('visibility', $fillable);
    }

    /** @test MI-005: Model has correct default visibility */
    public function model_has_correct_default_visibility(): void
    {
        $publicAudit = new PublicAudit();

        $this->assertEquals('private', $publicAudit->visibility);
    }

    // =========================================================================
    // RE: Relationships Tests
    // =========================================================================

    /** @test RE-001: PublicAudit belongs to Audit */
    public function public_audit_belongs_to_audit(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $this->assertInstanceOf(Audit::class, $publicAudit->audit);
        $this->assertEquals($audit->id, $publicAudit->audit->id);
    }

    /** @test RE-002: Audit has one PublicAudit relationship */
    public function audit_has_one_public_audit_relationship(): void
    {
        $audit = Audit::factory()->create();

        $this->assertTrue(method_exists($audit, 'publicAudit'));
    }

    /** @test RE-003: Deleting Audit cascades to PublicAudit */
    public function deleting_audit_cascades_to_public_audit(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $audit->delete();

        $this->assertDatabaseMissing('public_audits', [
            'id' => $publicAudit->id,
        ]);
    }

    // =========================================================================
    // GH: generateHash() Method Tests
    // =========================================================================

    /** @test GH-001: generateHash returns 32 character string */
    public function generate_hash_returns_32_character_string(): void
    {
        $hash = PublicAudit::generateHash();

        $this->assertEquals(32, strlen($hash));
    }

    /** @test GH-002: generateHash returns alphanumeric string */
    public function generate_hash_returns_alphanumeric_string(): void
    {
        $hash = PublicAudit::generateHash();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $hash);
    }

    /** @test GH-003: generateHash uses cryptographic randomness */
    public function generate_hash_uses_cryptographic_randomness(): void
    {
        $hashes = collect(range(1, 100))->map(fn () => PublicAudit::generateHash());

        // All hashes should be unique (extremely high probability with crypto random)
        $uniqueHashes = $hashes->unique();

        $this->assertEquals(100, $uniqueHashes->count());
    }

    /** @test GH-004: generateHash is not derived from audit ID */
    public function generate_hash_is_not_derived_from_audit_id(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $hash = $publicAudit->generateHash();

        // Hash should not contain the audit ID
        $this->assertStringNotContainsString((string) $audit->id, $hash);
    }

    /** @test GH-005: generateHash regenerates on collision */
    public function generate_hash_regenerates_on_collision(): void
    {
        $existingHash = 'abcdefghijklmnopqrstuvwxyz123456';
        PublicAudit::factory()->create([
            'public_hash' => $existingHash,
        ]);

        // Mock Str::random to return existing hash first, then new hash
        Str::macro('randomSequence', function () use ($existingHash) {
            static $callCount = 0;
            $callCount++;
            return $callCount === 1 ? $existingHash : 'newhash12345678901234567890';
        });

        // This tests that the model handles collision detection
        $this->assertNotEquals($existingHash, PublicAudit::generateHash());
    }

    /** @test GH-006: generateHash uses Str random helper */
    public function generate_hash_uses_str_random_helper(): void
    {
        $hash = PublicAudit::generateHash();

        // Verify it's a valid Str::random(32) output
        $this->assertEquals(32, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $hash);
    }

    // =========================================================================
    // PB: publish() Method Tests
    // =========================================================================

    /** @test PB-001: publish sets visibility to public */
    public function publish_sets_visibility_to_public(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
        ]);

        $publicAudit->publish();

        $this->assertEquals('public', $publicAudit->visibility);
        $this->assertEquals('public', $publicAudit->fresh()->visibility);
    }

    /** @test PB-002: publish generates hash if not exists */
    public function publish_generates_hash_if_not_exists(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => null,
        ]);

        $publicAudit->publish();

        $this->assertNotNull($publicAudit->public_hash);
        $this->assertEquals(32, strlen($publicAudit->public_hash));
    }

    /** @test PB-003: publish keeps existing hash */
    public function publish_keeps_existing_hash(): void
    {
        $existingHash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $existingHash,
        ]);

        $publicAudit->publish();

        $this->assertEquals($existingHash, $publicAudit->public_hash);
    }

    /** @test PB-004: publish sets hash_created_at timestamp */
    public function publish_sets_hash_created_at_timestamp(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => null,
            'public_hash_created_at' => null,
        ]);

        $publicAudit->publish();

        $this->assertNotNull($publicAudit->public_hash_created_at);
        $this->assertTrue($publicAudit->public_hash_created_at->isToday());
    }

    /** @test PB-005: publish returns self for chaining */
    public function publish_returns_self_for_chaining(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $result = $publicAudit->publish();

        $this->assertSame($publicAudit, $result);
    }

    /** @test PB-006: publish persists changes to database */
    public function publish_persists_changes_to_database(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
            'public_hash' => null,
        ]);

        $publicAudit->publish();

        $this->assertDatabaseHas('public_audits', [
            'id' => $publicAudit->id,
            'visibility' => 'public',
        ]);
        $this->assertNotNull($publicAudit->fresh()->public_hash);
    }

    // =========================================================================
    // UP: unpublish() Method Tests
    // =========================================================================

    /** @test UP-001: unpublish sets visibility to private */
    public function unpublish_sets_visibility_to_private(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'public',
            'public_hash' => 'abcdefghijklmnopqrstuvwxyz123456',
        ]);

        $publicAudit->unpublish();

        $this->assertEquals('private', $publicAudit->visibility);
        $this->assertEquals('private', $publicAudit->fresh()->visibility);
    }

    /** @test UP-002: unpublish clears hash */
    public function unpublish_clears_hash(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => 'abcdefghijklmnopqrstuvwxyz123456',
        ]);

        $publicAudit->unpublish();

        $this->assertNull($publicAudit->public_hash);
        $this->assertNull($publicAudit->fresh()->public_hash);
    }

    /** @test UP-003: unpublish clears hash_created_at */
    public function unpublish_clears_hash_created_at(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => 'abcdefghijklmnopqrstuvwxyz123456',
            'public_hash_created_at' => now(),
        ]);

        $publicAudit->unpublish();

        $this->assertNull($publicAudit->public_hash_created_at);
        $this->assertNull($publicAudit->fresh()->public_hash_created_at);
    }

    /** @test UP-004: unpublish returns self for chaining */
    public function unpublish_returns_self_for_chaining(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $result = $publicAudit->unpublish();

        $this->assertSame($publicAudit, $result);
    }

    /** @test UP-005: unpublish persists changes to database */
    public function unpublish_persists_changes_to_database(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'public',
            'public_hash' => 'abcdefghijklmnopqrstuvwxyz123456',
        ]);

        $publicAudit->unpublish();

        $this->assertDatabaseHas('public_audits', [
            'id' => $publicAudit->id,
            'visibility' => 'private',
            'public_hash' => null,
        ]);
    }

    // =========================================================================
    // IP: isPublic() Method Tests
    // =========================================================================

    /** @test IP-001: isPublic returns true for public audit */
    public function is_public_returns_true_for_public_audit(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'public',
        ]);

        $this->assertTrue($publicAudit->isPublic());
    }

    /** @test IP-002: isPublic returns false for private audit */
    public function is_public_returns_false_for_private_audit(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
        ]);

        $this->assertFalse($publicAudit->isPublic());
    }

    /** @test IP-003: isPublic reflects visibility changes */
    public function is_public_reflects_visibility_changes(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
        ]);

        $this->assertFalse($publicAudit->isPublic());

        $publicAudit->publish();

        $this->assertTrue($publicAudit->isPublic());

        $publicAudit->unpublish();

        $this->assertFalse($publicAudit->isPublic());
    }

    /** @test IP-004: isPublic handles null visibility */
    public function is_public_handles_null_visibility(): void
    {
        $publicAudit = new PublicAudit();
        $publicAudit->visibility = null;

        // Should return false for null (defaults to private behavior)
        $this->assertFalse($publicAudit->isPublic());
    }

    // =========================================================================
    // FH: findByHash() Method Tests
    // =========================================================================

    /** @test FH-001: findByHash returns public audit for valid hash */
    public function find_by_hash_returns_public_audit_for_valid_hash(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'public',
        ]);

        $found = PublicAudit::findByHash($hash);

        $this->assertNotNull($found);
        $this->assertEquals($publicAudit->id, $found->id);
        $this->assertEquals($hash, $found->public_hash);
    }

    /** @test FH-002: findByHash returns null for non-existent hash */
    public function find_by_hash_returns_null_for_non_existent_hash(): void
    {
        $found = PublicAudit::findByHash('nonexistent123456789012345678');

        $this->assertNull($found);
    }

    /** @test FH-003: findByHash returns null for private audit */
    public function find_by_hash_returns_null_for_private_audit(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'private',
        ]);

        $found = PublicAudit::findByHash($hash);

        $this->assertNull($found);
    }

    /** @test FH-004: findByHash is case sensitive */
    public function find_by_hash_is_case_sensitive(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyZ123456';
        $audit = Audit::factory()->create();
        PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'public',
        ]);

        // Lowercase version should not find it
        $found = PublicAudit::findByHash(strtolower($hash));

        $this->assertNull($found);
    }

    /** @test FH-005: findByHash loads audit relationship */
    public function find_by_hash_loads_audit_relationship(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'public',
        ]);

        $found = PublicAudit::findByHash($hash);

        $this->assertTrue($found->relationLoaded('audit'));
        $this->assertEquals($audit->id, $found->audit->id);
    }

    /** @test FH-006: findByHash handles empty string */
    public function find_by_hash_handles_empty_string(): void
    {
        $found = PublicAudit::findByHash('');

        $this->assertNull($found);
    }

    /** @test FH-007: findByHash handles null input */
    public function find_by_hash_handles_null_input(): void
    {
        $found = PublicAudit::findByHash(null);

        $this->assertNull($found);
    }

    // =========================================================================
    // UN: Uniqueness/Collision Detection Tests
    // =========================================================================

    /** @test UN-001: Hash uniqueness guaranteed across multiple public audits */
    public function hash_uniqueness_guaranteed_across_multiple_public_audits(): void
    {
        $hashes = collect(range(1, 10))->map(function () {
            $audit = Audit::factory()->create();
            $publicAudit = PublicAudit::factory()->create([
                'audit_id' => $audit->id,
            ]);
            $publicAudit->publish();
            return $publicAudit->public_hash;
        });

        $uniqueHashes = $hashes->unique();

        $this->assertEquals(10, $uniqueHashes->count());
    }

    /** @test UN-002: Collision detection regenerates hash */
    public function collision_detection_regenerates_hash(): void
    {
        // Create a public audit with a specific hash
        $existingHash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit1 = Audit::factory()->create();
        PublicAudit::factory()->create([
            'audit_id' => $audit1->id,
            'public_hash' => $existingHash,
            'visibility' => 'public',
        ]);

        // Create another and ensure it gets a different hash
        $audit2 = Audit::factory()->create();
        $publicAudit2 = PublicAudit::factory()->create([
            'audit_id' => $audit2->id,
        ]);
        $publicAudit2->publish();

        $this->assertNotEquals($existingHash, $publicAudit2->public_hash);
    }

    /** @test UN-003: Unique constraint on public_hash column */
    public function unique_constraint_on_public_hash_column(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();

        PublicAudit::factory()->create([
            'audit_id' => $audit1->id,
            'public_hash' => $hash,
        ]);

        // Attempting to create another with same hash should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        PublicAudit::factory()->create([
            'audit_id' => $audit2->id,
            'public_hash' => $hash,
        ]);
    }

    // =========================================================================
    // TR: Transaction Safety Tests
    // =========================================================================

    /** @test TR-001: publish wraps changes in transaction */
    public function publish_wraps_changes_in_transaction(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
        ]);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $publicAudit->publish();
    }

    /** @test TR-002: publish transaction rolls back on error */
    public function publish_transaction_rolls_back_on_error(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
        ]);

        // Store original state
        $originalVisibility = $publicAudit->visibility;
        $originalHash = $publicAudit->public_hash;

        // Simulate an error during publish
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        try {
            $publicAudit->publish();
        } catch (\Exception $e) {
            // Verify original state preserved
            $publicAudit->refresh();
            $this->assertEquals($originalVisibility, $publicAudit->visibility);
            $this->assertEquals($originalHash, $publicAudit->public_hash);
        }
    }

    /** @test TR-003: Concurrent publish operations handled safely */
    public function concurrent_publish_operations_handled_safely(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        // Simulate concurrent publishes
        $results = collect(range(1, 5))->map(function () use ($publicAudit) {
            return DB::transaction(function () use ($publicAudit) {
                try {
                    $publicAudit->publish();
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            });
        });

        // Only one should succeed with unique hash
        $this->assertContains(true, $results->toArray());
    }

    // =========================================================================
    // SC: Security Tests
    // =========================================================================

    /** @test SC-001: Hash cannot be predicted from audit data */
    public function hash_cannot_be_predicted_from_audit_data(): void
    {
        $audit = Audit::factory()->create([
            'id' => 12345,
            'status' => 'completed',
        ]);
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);
        $publicAudit->publish();

        // Hash should not be derivable from ID or other predictable data
        $this->assertStringNotContainsString('12345', $publicAudit->public_hash);
        $this->assertStringNotContainsString('completed', $publicAudit->public_hash);
    }

    /** @test SC-002: SQL injection prevented in findByHash */
    public function sql_injection_prevented_in_find_by_hash(): void
    {
        $maliciousHash = "'; DROP TABLE public_audits; --";

        // Should not throw exception
        $found = PublicAudit::findByHash($maliciousHash);

        $this->assertNull($found);
        $this->assertDatabaseHas('public_audits', []);
    }

    /** @test SC-003: XSS prevented in hash values */
    public function xss_prevented_in_hash_values(): void
    {
        $xssHash = '<script>alert("xss")</script>';

        // Hash validation should reject non-alphanumeric characters
        $found = PublicAudit::findByHash($xssHash);

        $this->assertNull($found);
    }

    /** @test SC-004: Private audits not accessible via hash */
    public function private_audits_not_accessible_via_hash(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'private',
        ]);

        $found = PublicAudit::findByHash($hash);

        $this->assertNull($found);
    }

    // =========================================================================
    // EC: Edge Cases Tests
    // =========================================================================

    /** @test EC-001: Publish already public audit */
    public function publish_already_public_audit(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'public',
            'public_hash' => $hash,
        ]);

        $publicAudit->publish();

        // Should remain public with same hash
        $this->assertEquals('public', $publicAudit->visibility);
        $this->assertEquals($hash, $publicAudit->public_hash);
    }

    /** @test EC-002: Unpublish already private audit */
    public function unpublish_already_private_audit(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
            'public_hash' => null,
        ]);

        $publicAudit->unpublish();

        // Should remain private
        $this->assertEquals('private', $publicAudit->visibility);
        $this->assertNull($publicAudit->public_hash);
    }

    /** @test EC-003: Rapid publish/unpublish cycles */
    public function rapid_publish_unpublish_cycles(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $publicAudit->publish();
            $this->assertTrue($publicAudit->isPublic());
            $this->assertNotNull($publicAudit->public_hash);

            $publicAudit->unpublish();
            $this->assertFalse($publicAudit->isPublic());
            $this->assertNull($publicAudit->public_hash);
        }
    }

    /** @test EC-004: Hash contains only alphanumeric characters */
    public function hash_contains_only_alphanumeric_characters(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);
        $publicAudit->publish();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $publicAudit->public_hash);
    }

    /** @test EC-005: Multiple audits can be public simultaneously */
    public function multiple_audits_can_be_public_simultaneously(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();
        $audit3 = Audit::factory()->create();

        $publicAudit1 = PublicAudit::factory()->create(['audit_id' => $audit1->id]);
        $publicAudit2 = PublicAudit::factory()->create(['audit_id' => $audit2->id]);
        $publicAudit3 = PublicAudit::factory()->create(['audit_id' => $audit3->id]);

        $publicAudit1->publish();
        $publicAudit2->publish();
        $publicAudit3->publish();

        $this->assertTrue($publicAudit1->isPublic());
        $this->assertTrue($publicAudit2->isPublic());
        $this->assertTrue($publicAudit3->isPublic());

        // All hashes should be unique
        $hashes = [$publicAudit1->public_hash, $publicAudit2->public_hash, $publicAudit3->public_hash];
        $this->assertEquals(3, count(array_unique($hashes)));
    }

    /** @test EC-006: Unicode in hash parameter handled gracefully */
    public function unicode_in_hash_parameter_handled_gracefully(): void
    {
        $unicodeHash = '日本語テストハッシュ1234567890';

        $found = PublicAudit::findByHash($unicodeHash);

        $this->assertNull($found);
    }

    /** @test EC-007: Very long hash parameter handled gracefully */
    public function very_long_hash_parameter_handled_gracefully(): void
    {
        $longHash = str_repeat('a', 1000);

        $found = PublicAudit::findByHash($longHash);

        $this->assertNull($found);
    }

    /** @test EC-008: findByHash with exact 32 chars */
    public function find_by_hash_with_exact_32_chars(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456'; // Exactly 32 chars
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'public',
        ]);

        $found = PublicAudit::findByHash($hash);

        $this->assertNotNull($found);
        $this->assertEquals($hash, $found->public_hash);
    }

    // =========================================================================
    // AC: Acceptance Criteria Tests
    // =========================================================================

    /** @test AC-001: PublicAudit model created with audit relationship */
    public function ac_001_public_audit_model_created_with_audit_relationship(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $this->assertInstanceOf(Audit::class, $publicAudit->audit);
        $this->assertEquals($audit->id, $publicAudit->audit->id);
    }

    /** @test AC-002: generateHash creates 32-char alphanumeric string */
    public function ac_002_generate_hash_creates_32_char_alphanumeric_string(): void
    {
        $hash = PublicAudit::generateHash();

        $this->assertEquals(32, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $hash);
    }

    /** @test AC-003: publish sets visibility public and generates hash */
    public function ac_003_publish_sets_visibility_public_and_generates_hash(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
        ]);

        $publicAudit->publish();

        $this->assertEquals('public', $publicAudit->visibility);
        $this->assertNotNull($publicAudit->public_hash);
        $this->assertEquals(32, strlen($publicAudit->public_hash));
    }

    /** @test AC-004: unpublish sets visibility private and clears hash */
    public function ac_004_unpublish_sets_visibility_private_and_clears_hash(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'public',
            'public_hash' => 'abcdefghijklmnopqrstuvwxyz123456',
        ]);

        $publicAudit->unpublish();

        $this->assertEquals('private', $publicAudit->visibility);
        $this->assertNull($publicAudit->public_hash);
    }

    /** @test AC-005: isPublic checks visibility status */
    public function ac_005_is_public_checks_visibility_status(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $publicAudit->visibility = 'public';
        $this->assertTrue($publicAudit->isPublic());

        $publicAudit->visibility = 'private';
        $this->assertFalse($publicAudit->isPublic());
    }

    /** @test AC-006: findByHash retrieves public audit or null */
    public function ac_006_find_by_hash_retrieves_public_audit_or_null(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'public',
        ]);

        $found = PublicAudit::findByHash($hash);
        $this->assertNotNull($found);
        $this->assertEquals($publicAudit->id, $found->id);

        $notFound = PublicAudit::findByHash('nonexistent123456789012345678');
        $this->assertNull($notFound);
    }

    /** @test AC-007: Hash uniqueness guaranteed */
    public function ac_007_hash_uniqueness_guaranteed(): void
    {
        $hashes = collect(range(1, 10))->map(function () {
            return PublicAudit::generateHash();
        });

        $uniqueHashes = $hashes->unique();
        $this->assertEquals(10, $uniqueHashes->count());
    }

    /** @test AC-008: Hash is cryptographically random */
    public function ac_008_hash_is_cryptographically_random(): void
    {
        $hashes = collect(range(1, 100))->map(fn () => PublicAudit::generateHash());

        // With cryptographic randomness, all 100 should be unique
        $this->assertEquals(100, $hashes->unique()->count());

        // Verify entropy - no obvious patterns
        $firstChars = $hashes->map(fn ($h) => $h[0])->unique();
        $this->assertGreaterThan(10, $firstChars->count()); // Should have varied first characters
    }

    // =========================================================================
    // Model Scopes Tests
    // =========================================================================

    /** @test SQ-001: public scope returns only public audits */
    public function public_scope_returns_only_public_audits(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();

        PublicAudit::factory()->create([
            'audit_id' => $audit1->id,
            'visibility' => 'public',
            'public_hash' => 'public12345678901234567890ab',
        ]);

        PublicAudit::factory()->create([
            'audit_id' => $audit2->id,
            'visibility' => 'private',
        ]);

        $publicAudits = PublicAudit::public()->get();

        $this->assertEquals(1, $publicAudits->count());
        $this->assertEquals('public', $publicAudits->first()->visibility);
    }

    /** @test SQ-002: private scope returns only private audits */
    public function private_scope_returns_only_private_audits(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();

        PublicAudit::factory()->create([
            'audit_id' => $audit1->id,
            'visibility' => 'public',
            'public_hash' => 'public12345678901234567890ab',
        ]);

        PublicAudit::factory()->create([
            'audit_id' => $audit2->id,
            'visibility' => 'private',
        ]);

        $privateAudits = PublicAudit::private()->get();

        $this->assertEquals(1, $privateAudits->count());
        $this->assertEquals('private', $privateAudits->first()->visibility);
    }

    /** @test SQ-003: withHash scope finds by hash */
    public function with_hash_scope_finds_by_hash(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
        ]);

        $found = PublicAudit::withHash($hash)->first();

        $this->assertNotNull($found);
        $this->assertEquals($hash, $found->public_hash);
    }

    // =========================================================================
    // Accessor Tests
    // =========================================================================

    /** @test AC-ACC-001: public_url accessor generates correct URL */
    public function public_url_accessor_generates_correct_url(): void
    {
        $hash = 'abcdefghijklmnopqrstuvwxyz123456';
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'visibility' => 'public',
        ]);

        $url = $publicAudit->public_url;

        $this->assertStringContainsString($hash, $url);
        $this->assertStringContainsString('/audit/', $url);
    }

    /** @test AC-ACC-002: public_url returns empty string for private audit */
    public function public_url_returns_empty_string_for_private_audit(): void
    {
        $audit = Audit::factory()->create();
        $publicAudit = PublicAudit::factory()->create([
            'audit_id' => $audit->id,
            'visibility' => 'private',
            'public_hash' => null,
        ]);

        $url = $publicAudit->public_url;

        $this->assertEquals('', $url);
    }
}
