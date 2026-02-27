<?php

namespace Tests\Unit\Models;

use App\Models\ToolLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the ToolLead model.
 *
 * Tests model attributes, soft deletes, scopes, and configuration.
 *
 * @see TASK-16-6
 * @see TEST-16-6
 * @see FEAT-016
 */
class ToolLeadTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // MODEL CREATION TESTS
    // =========================================================================

    /**
     * Test that a lead can be created with valid data.
     * MODEL-001
     */
    public function test_lead_can_be_created(): void
    {
        $lead = ToolLead::factory()->create([
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
            'url' => 'https://example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $this->assertDatabaseHas('tool_leads', [
            'id' => $lead->id,
            'email' => 'user@example.com',
            'tool' => 'meta-tag-analyzer',
        ]);
    }

    /**
     * Test fillable attributes are correctly defined.
     * MODEL-002
     */
    public function test_fillable_attributes(): void
    {
        $lead = new ToolLead();
        $fillable = $lead->getFillable();

        $expectedFillable = [
            'email',
            'tool',
            'url',
            'ip_address',
            'user_agent',
        ];

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $fillable, "Field {$field} should be fillable");
        }
    }

    /**
     * Test that id is not fillable.
     */
    public function test_id_is_not_fillable(): void
    {
        $lead = new ToolLead();
        $fillable = $lead->getFillable();

        $this->assertNotContains('id', $fillable);
    }

    /**
     * Test timestamp cast for created_at.
     * MODEL-003
     */
    public function test_created_at_is_carbon_instance(): void
    {
        $lead = ToolLead::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $lead->created_at);
    }

    // =========================================================================
    // SOFT DELETE TESTS
    // =========================================================================

    /**
     * Test soft delete sets deleted_at timestamp.
     * MODEL-004
     */
    public function test_soft_delete_sets_deleted_at(): void
    {
        $lead = ToolLead::factory()->create();
        $lead->delete();

        $this->assertNotNull($lead->deleted_at);
        $this->assertSoftDeleted('tool_leads', ['id' => $lead->id]);
    }

    /**
     * Test restore clears deleted_at timestamp.
     * MODEL-005
     */
    public function test_restore_clears_deleted_at(): void
    {
        $lead = ToolLead::factory()->create();
        $lead->delete();
        $lead->restore();

        $this->assertNull($lead->fresh()->deleted_at);
    }

    /**
     * Test force delete permanently removes record.
     * MODEL-006
     */
    public function test_force_delete_removes_record(): void
    {
        $lead = ToolLead::factory()->create();
        $lead->forceDelete();

        $this->assertDatabaseMissing('tool_leads', ['id' => $lead->id]);
    }

    // =========================================================================
    // EMAIL NORMALIZATION TESTS
    // =========================================================================

    /**
     * Test email is stored in lowercase.
     * MODEL-007
     */
    public function test_email_stored_in_lowercase(): void
    {
        $lead = ToolLead::factory()->create([
            'email' => 'User@Example.COM',
        ]);

        $this->assertEquals('user@example.com', $lead->fresh()->email);
    }

    // =========================================================================
    // NULLABLE FIELD TESTS
    // =========================================================================

    /**
     * Test URL can be null.
     * MODEL-008
     */
    public function test_url_can_be_null(): void
    {
        $lead = ToolLead::factory()->create([
            'url' => null,
        ]);

        $this->assertNull($lead->url);
    }

    /**
     * Test IPv6 address is stored correctly.
     * MODEL-009
     */
    public function test_ipv6_address_stored_correctly(): void
    {
        $lead = ToolLead::factory()->create([
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ]);

        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $lead->fresh()->ip_address);
    }

    /**
     * Test long user agent is stored.
     * MODEL-010
     */
    public function test_long_user_agent_stored(): void
    {
        $longUserAgent = str_repeat('Mozilla/5.0 ', 50); // ~600 chars

        $lead = ToolLead::factory()->create([
            'user_agent' => $longUserAgent,
        ]);

        $this->assertEquals($longUserAgent, $lead->fresh()->user_agent);
    }

    // =========================================================================
    // VALIDATION TESTS
    // =========================================================================

    /**
     * Test valid tool names are accepted.
     * MODEL-011
     */
    public function test_valid_tool_names_accepted(): void
    {
        $validTools = ToolLead::VALID_TOOLS;

        foreach ($validTools as $tool) {
            $lead = ToolLead::factory()->create(['tool' => $tool]);
            $this->assertEquals($tool, $lead->tool);
        }
    }

    /**
     * Test timestamps are automatically set.
     * MODEL-012
     */
    public function test_timestamps_automatically_set(): void
    {
        $lead = ToolLead::factory()->create();

        $this->assertNotNull($lead->created_at);
        $this->assertNotNull($lead->updated_at);
    }

    // =========================================================================
    // SCOPE TESTS
    // =========================================================================

    /**
     * Test scope byTool filters by tool.
     */
    public function test_scope_by_tool(): void
    {
        ToolLead::factory()->create(['tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['tool' => 'meta-tag-analyzer']);
        ToolLead::factory()->create(['tool' => 'keyword-density']);

        $results = ToolLead::byTool('meta-tag-analyzer')->get();

        $this->assertCount(2, $results);
        $results->each(function ($lead) {
            $this->assertEquals('meta-tag-analyzer', $lead->tool);
        });
    }

    /**
     * Test scope byEmail filters by email.
     */
    public function test_scope_by_email(): void
    {
        ToolLead::factory()->create(['email' => 'user@example.com']);
        ToolLead::factory()->create(['email' => 'other@example.com']);

        $results = ToolLead::byEmail('user@example.com')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('user@example.com', $results->first()->email);
    }

    /**
     * Test scope notTrashed excludes soft deleted.
     */
    public function test_scope_not_trashed(): void
    {
        ToolLead::factory()->create(['email' => 'active@example.com']);
        ToolLead::factory()->softDeleted()->create(['email' => 'deleted@example.com']);

        $results = ToolLead::notTrashed()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('active@example.com', $results->first()->email);
    }

    // =========================================================================
    // FACTORY TESTS
    // =========================================================================

    /**
     * Test factory creates valid lead.
     */
    public function test_factory_creates_valid_lead(): void
    {
        $lead = ToolLead::factory()->create();

        $this->assertDatabaseHas('tool_leads', [
            'id' => $lead->id,
        ]);
        $this->assertNotNull($lead->email);
        $this->assertNotNull($lead->tool);
    }

    /**
     * Test factory forTool state.
     */
    public function test_factory_for_tool_state(): void
    {
        $lead = ToolLead::factory()->forTool('mobile-friendly')->create();

        $this->assertEquals('mobile-friendly', $lead->tool);
    }

    /**
     * Test factory metaTagAnalyzer state.
     */
    public function test_factory_meta_tag_analyzer_state(): void
    {
        $lead = ToolLead::factory()->metaTagAnalyzer()->create();

        $this->assertEquals('meta-tag-analyzer', $lead->tool);
    }

    /**
     * Test factory withEmail state.
     */
    public function test_factory_with_email_state(): void
    {
        $lead = ToolLead::factory()->withEmail('test@example.com')->create();

        $this->assertEquals('test@example.com', $lead->email);
    }

    /**
     * Test factory softDeleted state.
     */
    public function test_factory_soft_deleted_state(): void
    {
        $lead = ToolLead::factory()->softDeleted()->create();

        $this->assertNotNull($lead->deleted_at);
    }

    /**
     * Test factory withoutUrl state.
     */
    public function test_factory_without_url_state(): void
    {
        $lead = ToolLead::factory()->withoutUrl()->create();

        $this->assertNull($lead->url);
    }
}
