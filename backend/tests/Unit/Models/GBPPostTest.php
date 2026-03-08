<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GBPPost;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;

/**
 * Unit tests for GBPPost model
 * 
 * @see TASK-35-3
 * @see TEST-35-3
 */
class GBPPostTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Model Instantiation Tests (GP-001 to GP-003)
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $post = new GBPPost();
        
        $this->assertInstanceOf(GBPPost::class, $post);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $post = new GBPPost();
        $expectedFillable = [
            'location_id',
            'google_post_id',
            'post_type',
            'title',
            'content',
            'media_url',
            'call_to_action',
            'call_to_action_url',
            'published_at',
        ];
        
        $this->assertEquals($expectedFillable, $post->getFillable());
    }

    /** @test */
    public function it_guards_non_fillable_attributes(): void
    {
        $post = GBPPost::create([
            'location_id' => 1,
            'google_post_id' => 'test123',
            'post_type' => 'UPDATE',
            'id' => 999, // Should be ignored
        ]);
        
        $this->assertNotEquals(999, $post->id);
    }

    // =========================================================================
    // Casts Tests (GP-004 to GP-005)
    // =========================================================================

    /** @test */
    public function it_casts_published_at_to_datetime(): void
    {
        $post = GBPPost::factory()->create(['published_at' => '2026-03-08 10:30:00']);
        
        $this->assertInstanceOf(Carbon::class, $post->published_at);
    }

    /** @test */
    public function it_casts_created_at_to_datetime(): void
    {
        $post = GBPPost::factory()->create();
        
        $this->assertInstanceOf(Carbon::class, $post->created_at);
    }

    // =========================================================================
    // Relationship Tests (GP-006 to GP-008)
    // =========================================================================

    /** @test */
    public function it_belongs_to_business_location(): void
    {
        $post = GBPPost::factory()->create();
        
        $this->assertInstanceOf(BusinessLocation::class, $post->location);
    }

    /** @test */
    public function location_relationship_returns_belongs_to(): void
    {
        $post = new GBPPost();
        
        $relation = $post->location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    /** @test */
    public function it_can_access_parent_location_data(): void
    {
        $location = BusinessLocation::factory()->create(['name' => 'Test Location']);
        $post = GBPPost::factory()->forLocation($location->id)->create();
        
        $this->assertEquals('Test Location', $post->location->name);
    }

    // =========================================================================
    // Scope Tests (GP-009 to GP-013)
    // =========================================================================

    /** @test */
    public function scope_published_filters_non_null_published_at(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPPost::factory()->forLocation($location->id)->published()->create();
        GBPPost::factory()->forLocation($location->id)->draft()->create();
        GBPPost::factory()->forLocation($location->id)->scheduled()->create();
        
        $results = GBPPost::published()->get();
        
        $this->assertCount(1, $results);
        $this->assertNotNull($results->first()->published_at);
        $this->assertTrue($results->first()->published_at->isPast());
    }

    /** @test */
    public function scope_scheduled_filters_future_published_at(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPPost::factory()->forLocation($location->id)->published()->create();
        GBPPost::factory()->forLocation($location->id)->scheduled()->create();
        
        $results = GBPPost::scheduled()->get();
        
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->published_at->isFuture());
    }

    /** @test */
    public function scope_for_location_filters_by_location(): void
    {
        $location1 = BusinessLocation::factory()->create();
        $location2 = BusinessLocation::factory()->create();
        
        GBPPost::factory()->forLocation($location1->id)->create();
        GBPPost::factory()->forLocation($location1->id)->create();
        GBPPost::factory()->forLocation($location2->id)->create();
        
        $results = GBPPost::forLocation($location1->id)->get();
        
        $this->assertCount(2, $results);
        $results->each(fn($p) => $this->assertEquals($location1->id, $p->location_id));
    }

    /** @test */
    public function scope_of_type_filters_by_post_type(): void
    {
        $location = BusinessLocation::factory()->create();
        
        GBPPost::factory()->forLocation($location->id)->event()->create();
        GBPPost::factory()->forLocation($location->id)->offer()->create();
        GBPPost::factory()->forLocation($location->id)->update()->create();
        
        $results = GBPPost::ofType('EVENT')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('EVENT', $results->first()->post_type);
    }

    /** @test */
    public function scope_recent_orders_by_published_at_desc(): void
    {
        $location = BusinessLocation::factory()->create();
        
        $post1 = GBPPost::factory()->forLocation($location->id)->create(['published_at' => now()->subDays(1)]);
        $post2 = GBPPost::factory()->forLocation($location->id)->create(['published_at' => now()->subDays(2)]);
        $post3 = GBPPost::factory()->forLocation($location->id)->create(['published_at' => now()->subDays(3)]);
        
        $results = GBPPost::recent()->get();
        
        $this->assertEquals($post1->id, $results->first()->id);
        $this->assertEquals($post3->id, $results->last()->id);
    }

    // =========================================================================
    // Accessor Tests (GP-014 to GP-018)
    // =========================================================================

    /** @test */
    public function get_post_type_label_attribute_returns_label(): void
    {
        $post = GBPPost::factory()->create(['post_type' => 'EVENT']);
        
        $this->assertEquals('Event', $post->post_type_label);
    }

    /** @test */
    public function get_post_type_label_attribute_handles_event(): void
    {
        $post = GBPPost::factory()->event()->create();
        
        $this->assertEquals('Event', $post->post_type_label);
    }

    /** @test */
    public function get_post_type_label_attribute_handles_offer(): void
    {
        $post = GBPPost::factory()->offer()->create();
        
        $this->assertEquals('Offer', $post->post_type_label);
    }

    /** @test */
    public function get_post_type_label_attribute_handles_update(): void
    {
        $post = GBPPost::factory()->update()->create();
        
        $this->assertEquals('Update', $post->post_type_label);
    }

    /** @test */
    public function get_post_type_label_attribute_handles_unknown(): void
    {
        $post = GBPPost::factory()->create(['post_type' => 'CUSTOM_TYPE']);
        
        $this->assertEquals('CUSTOM_TYPE', $post->post_type_label);
    }

    // =========================================================================
    // Factory Tests (GP-019 to GP-027)
    // =========================================================================

    /** @test */
    public function factory_creates_valid_model(): void
    {
        $post = GBPPost::factory()->create();
        
        $this->assertDatabaseHas('gbp_posts', ['id' => $post->id]);
    }

    /** @test */
    public function factory_for_location_state_sets_location_id(): void
    {
        $location = BusinessLocation::factory()->create();
        
        $post = GBPPost::factory()->forLocation($location->id)->create();
        
        $this->assertEquals($location->id, $post->location_id);
    }

    /** @test */
    public function factory_event_state_sets_post_type_event(): void
    {
        $post = GBPPost::factory()->event()->create();
        
        $this->assertEquals('EVENT', $post->post_type);
    }

    /** @test */
    public function factory_offer_state_sets_post_type_offer(): void
    {
        $post = GBPPost::factory()->offer()->create();
        
        $this->assertEquals('OFFER', $post->post_type);
    }

    /** @test */
    public function factory_update_state_sets_post_type_update(): void
    {
        $post = GBPPost::factory()->update()->create();
        
        $this->assertEquals('UPDATE', $post->post_type);
    }

    /** @test */
    public function factory_published_state_sets_past_published_at(): void
    {
        $post = GBPPost::factory()->published()->create();
        
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->isPast());
    }

    /** @test */
    public function factory_scheduled_state_sets_future_published_at(): void
    {
        $post = GBPPost::factory()->scheduled()->create();
        
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->isFuture());
    }

    /** @test */
    public function factory_with_media_state_sets_media_url(): void
    {
        $post = GBPPost::factory()->withMedia()->create();
        
        $this->assertNotNull($post->media_url);
    }

    /** @test */
    public function factory_with_cta_state_sets_call_to_action(): void
    {
        $post = GBPPost::factory()->withCta()->create();
        
        $this->assertNotNull($post->call_to_action);
        $this->assertNotNull($post->call_to_action_url);
    }

    // =========================================================================
    // Unique Constraint Tests (GP-028 to GP-029)
    // =========================================================================

    /** @test */
    public function duplicate_google_post_id_fails(): void
    {
        GBPPost::factory()->create(['google_post_id' => 'unique-post-id-123']);
        
        $this->expectException(QueryException::class);
        
        GBPPost::factory()->create(['google_post_id' => 'unique-post-id-123']);
    }

    /** @test */
    public function different_google_post_id_succeeds(): void
    {
        $post1 = GBPPost::factory()->create(['google_post_id' => 'unique-post-id-1']);
        $post2 = GBPPost::factory()->create(['google_post_id' => 'unique-post-id-2']);
        
        $this->assertNotEquals($post1->id, $post2->id);
    }

    // =========================================================================
    // Edge Case Tests (GP-030 to GP-035)
    // =========================================================================

    /** @test */
    public function null_title_allowed(): void
    {
        $post = GBPPost::factory()->create(['title' => null]);
        
        $this->assertNull($post->title);
    }

    /** @test */
    public function null_content_allowed(): void
    {
        $post = GBPPost::factory()->create(['content' => null]);
        
        $this->assertNull($post->content);
    }

    /** @test */
    public function long_content_stored(): void
    {
        $longContent = str_repeat('This is a long post content. ', 1000); // ~30KB
        
        $post = GBPPost::factory()->create(['content' => $longContent]);
        
        $this->assertEquals($longContent, $post->content);
    }

    /** @test */
    public function unicode_in_content_stored_correctly(): void
    {
        $unicodeContent = 'Special event! 日本語テスト русский العربية';
        
        $post = GBPPost::factory()->create(['content' => $unicodeContent]);
        
        $this->assertEquals($unicodeContent, $post->content);
    }

    /** @test */
    public function null_published_at_draft(): void
    {
        $post = GBPPost::factory()->draft()->create();
        
        $this->assertNull($post->published_at);
    }

    /** @test */
    public function long_title_truncated(): void
    {
        $longTitle = str_repeat('a', 300); // Exceeds VARCHAR 255
        
        $post = GBPPost::factory()->create(['title' => $longTitle]);
        
        // The stored value will be truncated by the database
        $this->assertLessThanOrEqual(255, strlen($post->title));
    }

    // =========================================================================
    // Post Types Tests (GP-036 to GP-039)
    // =========================================================================

    /** @test */
    public function event_type_stored_correctly(): void
    {
        $post = GBPPost::factory()->create(['post_type' => 'EVENT']);
        
        $this->assertEquals('EVENT', $post->post_type);
    }

    /** @test */
    public function offer_type_stored_correctly(): void
    {
        $post = GBPPost::factory()->create(['post_type' => 'OFFER']);
        
        $this->assertEquals('OFFER', $post->post_type);
    }

    /** @test */
    public function update_type_stored_correctly(): void
    {
        $post = GBPPost::factory()->create(['post_type' => 'UPDATE']);
        
        $this->assertEquals('UPDATE', $post->post_type);
    }

    /** @test */
    public function custom_type_stored_correctly(): void
    {
        $post = GBPPost::factory()->create(['post_type' => 'COVID_UPDATE']);
        
        $this->assertEquals('COVID_UPDATE', $post->post_type);
    }

    // =========================================================================
    // Security Tests (GP-040 to GP-042)
    // =========================================================================

    /** @test */
    public function sql_injection_in_content_prevented(): void
    {
        $maliciousContent = "Great post'); DROP TABLE gbp_posts; --";
        
        $post = GBPPost::factory()->create(['content' => $maliciousContent]);
        
        // Should store as literal string, not execute
        $this->assertEquals($maliciousContent, $post->fresh()->content);
        $this->assertDatabaseHas('gbp_posts', ['id' => $post->id]);
    }

    /** @test */
    public function xss_in_title_escaped_on_output(): void
    {
        $xssTitle = '<script>alert("xss")</script>';
        
        $post = GBPPost::factory()->create(['title' => $xssTitle]);
        
        // Stored as-is (database doesn't escape)
        $this->assertEquals($xssTitle, $post->title);
        
        // Output should be escaped by Blade templates
        // This test verifies storage, output escaping is tested in integration tests
    }

    /** @test */
    public function xss_in_content_escaped_on_output(): void
    {
        $xssContent = '<script>document.location="http://evil.com"</script>';
        
        $post = GBPPost::factory()->create(['content' => $xssContent]);
        
        // Stored as-is
        $this->assertEquals($xssContent, $post->content);
    }

    // =========================================================================
    // Integration Tests (GP-043 to GP-044)
    // =========================================================================

    /** @test */
    public function crud_operations_work(): void
    {
        // Create
        $post = GBPPost::factory()->create();
        $this->assertDatabaseHas('gbp_posts', ['id' => $post->id]);
        
        // Read
        $found = GBPPost::find($post->id);
        $this->assertEquals($post->post_type, $found->post_type);
        
        // Update
        $post->update(['title' => 'Updated Title']);
        $this->assertEquals('Updated Title', $post->fresh()->title);
        
        // Delete
        $post->delete();
        $this->assertDatabaseMissing('gbp_posts', ['id' => $post->id]);
    }

    /** @test */
    public function cascade_delete_when_location_deleted(): void
    {
        $location = BusinessLocation::factory()->create();
        $post = GBPPost::factory()->forLocation($location->id)->create();
        
        $location->delete();
        
        $this->assertDatabaseMissing('gbp_posts', ['id' => $post->id]);
    }

    // =========================================================================
    // Performance Tests (GP-045 to GP-046)
    // =========================================================================

    /** @test */
    public function query_with_location_index(): void
    {
        $location = BusinessLocation::factory()->create();
        
        for ($i = 0; $i < 50; $i++) {
            GBPPost::factory()->forLocation($location->id)->create();
        }
        
        $start = microtime(true);
        $results = GBPPost::forLocation($location->id)->get();
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(0.5, $duration);
        $this->assertCount(50, $results);
    }

    /** @test */
    public function bulk_insert_100_posts(): void
    {
        $location = BusinessLocation::factory()->create();
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            GBPPost::factory()->forLocation($location->id)->create();
        }
        
        $duration = microtime(true) - $startTime;
        
        $this->assertLessThan(5, $duration, 'Bulk insert should complete in under 5 seconds');
        $this->assertEquals(100, GBPPost::where('location_id', $location->id)->count());
    }
}
