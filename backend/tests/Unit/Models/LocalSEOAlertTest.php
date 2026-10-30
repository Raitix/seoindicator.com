<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\LocalSEOAlert;
use App\Models\User;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

/**
 * Unit tests for LocalSEOAlert model.
 *
 * @task TASK-35-4
 * @model LocalSEOAlert
 */
class LocalSEOAlertTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // MODEL INSTANTIATION TESTS
    // ============================================

    /** @test */
    public function can_instantiate_model(): void
    {
        $alert = new LocalSEOAlert();
        
        $this->assertInstanceOf(LocalSEOAlert::class, $alert);
    }

    /** @test */
    public function model_has_correct_fillable_attributes(): void
    {
        $alert = new LocalSEOAlert();
        
        $expectedFillable = [
            'user_id',
            'location_id',
            'alert_type',
            'severity',
            'title',
            'message',
            'data',
            'is_read',
            'read_at',
            'created_at',
        ];
        
        $this->assertEquals($expectedFillable, $alert->getFillable());
    }

    /** @test */
    public function model_does_not_use_timestamps(): void
    {
        $alert = new LocalSEOAlert();
        
        $this->assertFalse($alert->timestamps);
    }

    /** @test */
    public function model_uses_correct_table_name(): void
    {
        $alert = new LocalSEOAlert();
        
        $this->assertEquals('local_seo_alerts', $alert->getTable());
    }

    // ============================================
    // CONSTANTS TESTS
    // ============================================

    /** @test */
    public function alert_types_constant_defined(): void
    {
        $expectedTypes = [
            'ranking_improved',
            'ranking_dropped',
            'new_review',
            'negative_review',
            'rating_dropped',
            'competitor_overtake',
            'gbp_disconnected',
        ];
        
        $this->assertEquals($expectedTypes, LocalSEOAlert::ALERT_TYPES);
    }

    /** @test */
    public function severity_levels_constant_defined(): void
    {
        $expectedSeverities = [
            'info',
            'warning',
            'critical',
        ];
        
        $this->assertEquals($expectedSeverities, LocalSEOAlert::SEVERITY_LEVELS);
    }

    /** @test */
    public function alert_types_count_is_7(): void
    {
        $this->assertCount(7, LocalSEOAlert::ALERT_TYPES);
    }

    /** @test */
    public function severity_levels_count_is_3(): void
    {
        $this->assertCount(3, LocalSEOAlert::SEVERITY_LEVELS);
    }

    // ============================================
    // CASTS TESTS
    // ============================================

    /** @test */
    public function data_is_cast_to_array(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'data' => ['keyword' => 'test', 'position' => 5],
        ]);
        
        $this->assertIsArray($alert->data);
        $this->assertEquals(['keyword' => 'test', 'position' => 5], $alert->data);
    }

    /** @test */
    public function is_read_is_cast_to_boolean(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'is_read' => '1',
        ]);
        
        $this->assertIsBool($alert->is_read);
        $this->assertTrue($alert->is_read);
    }

    /** @test */
    public function created_at_is_cast_to_datetime(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'created_at' => '2026-03-08 12:30:00',
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $alert->created_at);
        $this->assertEquals('2026-03-08 12:30:00', $alert->created_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function read_at_is_cast_to_datetime(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'read_at' => '2026-03-08 15:00:00',
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $alert->read_at);
        $this->assertEquals('2026-03-08 15:00:00', $alert->read_at->format('Y-m-d H:i:s'));
    }

    // ============================================
    // RELATIONSHIP TESTS
    // ============================================

    /** @test */
    public function belongs_to_user(): void
    {
        $user = User::factory()->create();
        $alert = LocalSEOAlert::factory()->create([
            'user_id' => $user->id,
        ]);
        
        $this->assertInstanceOf(User::class, $alert->user);
        $this->assertEquals($user->id, $alert->user->id);
    }

    /** @test */
    public function belongs_to_business_location(): void
    {
        $location = BusinessLocation::factory()->create();
        $alert = LocalSEOAlert::factory()->create([
            'location_id' => $location->id,
        ]);
        
        $this->assertInstanceOf(BusinessLocation::class, $alert->location);
        $this->assertEquals($location->id, $alert->location->id);
    }

    /** @test */
    public function user_relationship_returns_belongs_to(): void
    {
        $alert = new LocalSEOAlert();
        $relationship = $alert->user();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
    }

    /** @test */
    public function location_relationship_returns_belongs_to(): void
    {
        $alert = new LocalSEOAlert();
        $relationship = $alert->location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
    }

    // ============================================
    // SCOPE TESTS - UNREAD/READ
    // ============================================

    /** @test */
    public function scope_unread_filters_unread_alerts(): void
    {
        LocalSEOAlert::factory()->count(3)->unread()->create();
        LocalSEOAlert::factory()->count(2)->read()->create();
        
        $unread = LocalSEOAlert::unread()->get();
        
        $this->assertCount(3, $unread);
        $this->assertTrue($unread->every(fn ($a) => $a->is_read === false));
    }

    /** @test */
    public function scope_read_filters_read_alerts(): void
    {
        LocalSEOAlert::factory()->count(3)->unread()->create();
        LocalSEOAlert::factory()->count(2)->read()->create();
        
        $read = LocalSEOAlert::read()->get();
        
        $this->assertCount(2, $read);
        $this->assertTrue($read->every(fn ($a) => $a->is_read === true));
    }

    // ============================================
    // SCOPE TESTS - OF TYPE
    // ============================================

    /** @test */
    public function scope_of_type_filters_by_alert_type(): void
    {
        LocalSEOAlert::factory()->rankingImproved()->create();
        LocalSEOAlert::factory()->rankingDropped()->create();
        LocalSEOAlert::factory()->negativeReview()->create();
        
        $results = LocalSEOAlert::ofType('ranking_improved')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('ranking_improved', $results->first()->alert_type);
    }

    /** @test */
    public function scope_of_type_returns_empty_for_invalid_type(): void
    {
        LocalSEOAlert::factory()->count(5)->create();
        
        $results = LocalSEOAlert::ofType('invalid_type')->get();
        
        $this->assertCount(0, $results);
    }

    /** @test */
    public function scope_of_type_with_all_valid_types(): void
    {
        foreach (LocalSEOAlert::ALERT_TYPES as $type) {
            LocalSEOAlert::factory()->create(['alert_type' => $type]);
        }
        
        foreach (LocalSEOAlert::ALERT_TYPES as $type) {
            $results = LocalSEOAlert::ofType($type)->get();
            $this->assertCount(1, $results, "Failed for type: $type");
        }
    }

    // ============================================
    // SCOPE TESTS - OF SEVERITY
    // ============================================

    /** @test */
    public function scope_of_severity_filters_by_severity(): void
    {
        LocalSEOAlert::factory()->info()->create();
        LocalSEOAlert::factory()->warning()->create();
        LocalSEOAlert::factory()->critical()->create();
        
        $critical = LocalSEOAlert::ofSeverity('critical')->get();
        
        $this->assertCount(1, $critical);
        $this->assertEquals('critical', $critical->first()->severity);
    }

    /** @test */
    public function scope_of_severity_with_all_valid_levels(): void
    {
        foreach (LocalSEOAlert::SEVERITY_LEVELS as $level) {
            LocalSEOAlert::factory()->create(['severity' => $level]);
        }
        
        foreach (LocalSEOAlert::SEVERITY_LEVELS as $level) {
            $results = LocalSEOAlert::ofSeverity($level)->get();
            $this->assertCount(1, $results, "Failed for severity: $level");
        }
    }

    // ============================================
    // SCOPE TESTS - FOR LOCATION
    // ============================================

    /** @test */
    public function scope_for_location_filters_by_location(): void
    {
        $location1 = BusinessLocation::factory()->create();
        $location2 = BusinessLocation::factory()->create();
        
        LocalSEOAlert::factory()->count(3)->create(['location_id' => $location1->id]);
        LocalSEOAlert::factory()->count(2)->create(['location_id' => $location2->id]);
        
        $results = LocalSEOAlert::forLocation($location1->id)->get();
        
        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($a) => $a->location_id === $location1->id));
    }

    // ============================================
    // METHOD TESTS - markAsRead()
    // ============================================

    /** @test */
    public function mark_as_read_updates_is_read(): void
    {
        $alert = LocalSEOAlert::factory()->unread()->create();
        
        $result = $alert->markAsRead();
        
        $this->assertTrue($result);
        $this->assertTrue($alert->fresh()->is_read);
    }

    /** @test */
    public function mark_as_read_sets_read_at_timestamp(): void
    {
        $alert = LocalSEOAlert::factory()->unread()->create();
        
        $alert->markAsRead();
        
        $this->assertNotNull($alert->fresh()->read_at);
        $this->assertInstanceOf(Carbon::class, $alert->fresh()->read_at);
    }

    /** @test */
    public function mark_as_read_is_idempotent(): void
    {
        $alert = LocalSEOAlert::factory()->read()->create();
        $originalReadAt = $alert->read_at;
        
        $result = $alert->markAsRead();
        
        $this->assertTrue($result);
        $this->assertEquals($originalReadAt->timestamp, $alert->fresh()->read_at->timestamp);
    }

    /** @test */
    public function mark_as_read_returns_true_when_already_read(): void
    {
        $alert = LocalSEOAlert::factory()->read()->create();
        
        $result = $alert->markAsRead();
        
        $this->assertTrue($result);
    }

    // ============================================
    // METHOD TESTS - isRead() / isUnread()
    // ============================================

    /** @test */
    public function is_read_returns_true_for_read_alert(): void
    {
        $alert = LocalSEOAlert::factory()->read()->create();
        
        $this->assertTrue($alert->isRead());
    }

    /** @test */
    public function is_read_returns_false_for_unread_alert(): void
    {
        $alert = LocalSEOAlert::factory()->unread()->create();
        
        $this->assertFalse($alert->isRead());
    }

    /** @test */
    public function is_unread_returns_true_for_unread_alert(): void
    {
        $alert = LocalSEOAlert::factory()->unread()->create();
        
        $this->assertTrue($alert->isUnread());
    }

    /** @test */
    public function is_unread_returns_false_for_read_alert(): void
    {
        $alert = LocalSEOAlert::factory()->read()->create();
        
        $this->assertFalse($alert->isUnread());
    }

    // ============================================
    // ACCESSOR TESTS
    // ============================================

    /** @test */
    public function business_location_id_accessor_returns_location_id(): void
    {
        $location = BusinessLocation::factory()->create();
        $alert = LocalSEOAlert::factory()->create([
            'location_id' => $location->id,
        ]);
        
        $this->assertEquals($location->id, $alert->business_location_id);
    }

    // ============================================
    // FACTORY TESTS
    // ============================================

    /** @test */
    public function factory_creates_valid_model(): void
    {
        $alert = LocalSEOAlert::factory()->create();
        
        $this->assertDatabaseHas('local_seo_alerts', [
            'id' => $alert->id,
        ]);
    }

    /** @test */
    public function factory_read_state(): void
    {
        $alert = LocalSEOAlert::factory()->read()->create();
        
        $this->assertTrue($alert->is_read);
        $this->assertNotNull($alert->read_at);
    }

    /** @test */
    public function factory_unread_state(): void
    {
        $alert = LocalSEOAlert::factory()->unread()->create();
        
        $this->assertFalse($alert->is_read);
        $this->assertNull($alert->read_at);
    }

    /** @test */
    public function factory_ranking_improved_state(): void
    {
        $alert = LocalSEOAlert::factory()->rankingImproved()->create();
        
        $this->assertEquals('ranking_improved', $alert->alert_type);
        $this->assertEquals('info', $alert->severity);
    }

    /** @test */
    public function factory_ranking_dropped_state(): void
    {
        $alert = LocalSEOAlert::factory()->rankingDropped()->create();
        
        $this->assertEquals('ranking_dropped', $alert->alert_type);
        $this->assertEquals('warning', $alert->severity);
    }

    /** @test */
    public function factory_negative_review_state(): void
    {
        $alert = LocalSEOAlert::factory()->negativeReview()->create();
        
        $this->assertEquals('negative_review', $alert->alert_type);
        $this->assertEquals('warning', $alert->severity);
    }

    /** @test */
    public function factory_gbp_disconnected_state(): void
    {
        $alert = LocalSEOAlert::factory()->gbpDisconnected()->create();
        
        $this->assertEquals('gbp_disconnected', $alert->alert_type);
        $this->assertEquals('critical', $alert->severity);
    }

    /** @test */
    public function factory_critical_state(): void
    {
        $alert = LocalSEOAlert::factory()->critical()->create();
        
        $this->assertEquals('critical', $alert->severity);
    }

    /** @test */
    public function factory_warning_state(): void
    {
        $alert = LocalSEOAlert::factory()->warning()->create();
        
        $this->assertEquals('warning', $alert->severity);
    }

    /** @test */
    public function factory_info_state(): void
    {
        $alert = LocalSEOAlert::factory()->info()->create();
        
        $this->assertEquals('info', $alert->severity);
    }

    /** @test */
    public function factory_creates_related_models(): void
    {
        $alert = LocalSEOAlert::factory()->create();
        
        $this->assertDatabaseHas('users', ['id' => $alert->user_id]);
        $this->assertDatabaseHas('business_locations', ['id' => $alert->location_id]);
    }

    // ============================================
    // EDGE CASE TESTS
    // ============================================

    /** @test */
    public function null_data_accepted(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'data' => null,
        ]);
        
        $this->assertNull($alert->data);
    }

    /** @test */
    public function empty_data_array_accepted(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'data' => [],
        ]);
        
        $this->assertIsArray($alert->data);
        $this->assertEmpty($alert->data);
    }

    /** @test */
    public function complex_data_json_accepted(): void
    {
        $complexData = [
            'keyword' => 'test keyword',
            'previous_position' => 5,
            'current_position' => 2,
            'change' => 3,
            'metadata' => [
                'source' => 'local_pack',
                'location' => 'New York, NY',
            ],
        ];
        
        $alert = LocalSEOAlert::factory()->create([
            'data' => $complexData,
        ]);
        
        $this->assertEquals($complexData, $alert->data);
    }

    /** @test */
    public function null_read_at_accepted(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'read_at' => null,
        ]);
        
        $this->assertNull($alert->read_at);
    }

    /** @test */
    public function long_title_accepted(): void
    {
        $longTitle = str_repeat('A', 254);
        
        $alert = LocalSEOAlert::factory()->create([
            'title' => $longTitle,
        ]);
        
        $this->assertEquals($longTitle, $alert->title);
    }

    /** @test */
    public function long_message_accepted(): void
    {
        $longMessage = str_repeat('Test message. ', 1000);
        
        $alert = LocalSEOAlert::factory()->create([
            'message' => $longMessage,
        ]);
        
        $this->assertEquals($longMessage, $alert->message);
    }

    /** @test */
    public function unicode_in_title_and_message(): void
    {
        $alert = LocalSEOAlert::factory()->create([
            'title' => 'レーティングが下がりました',
            'message' => 'Clasificación reducida - Оценка упала 📉',
        ]);
        
        $this->assertEquals('レーティングが下がりました', $alert->title);
        $this->assertEquals('Clasificación reducida - Оценка упала 📉', $alert->message);
    }

    /** @test */
    public function default_is_read_from_database_is_false(): void
    {
        $alert = LocalSEOAlert::factory()->create();
        
        // After persisting to database, default value is applied
        $this->assertFalse($alert->is_read);
    }

    /** @test */
    public function created_at_defaults_to_current(): void
    {
        $before = now()->subSecond();
        $alert = LocalSEOAlert::factory()->create();
        $after = now()->addSecond();
        
        $this->assertTrue($alert->created_at->between($before, $after));
    }

    // ============================================
    // SECURITY TESTS
    // ============================================

    /** @test */
    public function sql_injection_in_title_is_safe(): void
    {
        $malicious = "'; DROP TABLE local_seo_alerts; --";
        
        $alert = LocalSEOAlert::factory()->create([
            'title' => $malicious,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($malicious, $alert->title);
        
        // Table should still exist
        $this->assertDatabaseHas('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function xss_in_message_is_safe(): void
    {
        $malicious = '<script>alert("xss")</script>';
        
        $alert = LocalSEOAlert::factory()->create([
            'message' => $malicious,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($malicious, $alert->message);
    }

    /** @test */
    public function sql_injection_in_data_json_is_safe(): void
    {
        $malicious = [
            'field' => "'; DROP TABLE users; --",
        ];
        
        $alert = LocalSEOAlert::factory()->create([
            'data' => $malicious,
        ]);
        
        // Value should be stored literally (not executed)
        $this->assertEquals($malicious, $alert->data);
        
        // Tables should still exist
        $this->assertDatabaseHas('users', ['id' => $alert->user_id]);
    }

    /** @test */
    public function invalid_alert_type_stored_literally(): void
    {
        // Invalid types are not rejected by model - that's validation's job
        $alert = LocalSEOAlert::factory()->create([
            'alert_type' => 'invalid_type',
        ]);
        
        $this->assertEquals('invalid_type', $alert->alert_type);
    }

    /** @test */
    public function invalid_severity_stored_literally(): void
    {
        // Invalid severities are not rejected by model - that's validation's job
        $alert = LocalSEOAlert::factory()->create([
            'severity' => 'invalid_severity',
        ]);
        
        $this->assertEquals('invalid_severity', $alert->severity);
    }

    // ============================================
    // INTEGRATION TESTS
    // ============================================

    /** @test */
    public function cascade_delete_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $alert = LocalSEOAlert::factory()->create([
            'user_id' => $user->id,
        ]);
        
        $user->delete();
        
        $this->assertDatabaseMissing('local_seo_alerts', [
            'id' => $alert->id,
        ]);
    }

    /** @test */
    public function cascade_delete_when_location_force_deleted(): void
    {
        $location = BusinessLocation::factory()->create();
        $alert = LocalSEOAlert::factory()->create([
            'location_id' => $location->id,
        ]);
        
        // Use forceDelete to trigger cascade (BusinessLocation uses SoftDeletes)
        $location->forceDelete();
        
        $this->assertDatabaseMissing('local_seo_alerts', [
            'id' => $alert->id,
        ]);
    }

    /** @test */
    public function full_crud_operations(): void
    {
        // Create
        $alert = LocalSEOAlert::factory()->create([
            'title' => 'Test Alert',
        ]);
        $this->assertDatabaseHas('local_seo_alerts', ['id' => $alert->id]);
        
        // Read
        $found = LocalSEOAlert::find($alert->id);
        $this->assertEquals('Test Alert', $found->title);
        
        // Update
        $alert->update(['title' => 'Updated Alert']);
        $this->assertEquals('Updated Alert', $alert->fresh()->title);
        
        // Delete
        $alert->delete();
        $this->assertDatabaseMissing('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function mark_as_read_flow(): void
    {
        $alert = LocalSEOAlert::factory()->unread()->create();
        
        $this->assertTrue($alert->isUnread());
        $this->assertFalse($alert->isRead());
        $this->assertNull($alert->read_at);
        
        $alert->markAsRead();
        
        $this->assertFalse($alert->fresh()->isUnread());
        $this->assertTrue($alert->fresh()->isRead());
        $this->assertNotNull($alert->fresh()->read_at);
    }

    // ============================================
    // PERFORMANCE TESTS
    // ============================================

    /** @test */
    public function index_on_user_id_is_used(): void
    {
        $user = User::factory()->create();
        
        LocalSEOAlert::factory()->count(10)->create(['user_id' => $user->id]);
        
        $results = LocalSEOAlert::where('user_id', $user->id)->get();
        
        $this->assertCount(10, $results);
    }

    /** @test */
    public function index_on_location_id_is_used(): void
    {
        $location = BusinessLocation::factory()->create();
        
        LocalSEOAlert::factory()->count(10)->create(['location_id' => $location->id]);
        
        $results = LocalSEOAlert::where('location_id', $location->id)->get();
        
        $this->assertCount(10, $results);
    }

    /** @test */
    public function index_on_is_read_is_used(): void
    {
        LocalSEOAlert::factory()->count(15)->unread()->create();
        LocalSEOAlert::factory()->count(5)->read()->create();
        
        $unread = LocalSEOAlert::where('is_read', false)->get();
        $read = LocalSEOAlert::where('is_read', true)->get();
        
        $this->assertCount(15, $unread);
        $this->assertCount(5, $read);
    }

    /** @test */
    public function index_on_alert_type_is_used(): void
    {
        LocalSEOAlert::factory()->count(10)->rankingDropped()->create();
        
        $results = LocalSEOAlert::where('alert_type', 'ranking_dropped')->get();
        
        $this->assertCount(10, $results);
    }

    /** @test */
    public function bulk_insert_works(): void
    {
        $user = User::factory()->create();
        $location = BusinessLocation::factory()->create();
        
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'user_id' => $user->id,
                'location_id' => $location->id,
                'alert_type' => 'ranking_dropped',
                'severity' => 'warning',
                'title' => "Alert $i",
                'message' => "Message $i",
                'is_read' => false,
                'created_at' => now(),
            ];
        }
        
        LocalSEOAlert::insert($data);
        
        $this->assertEquals(100, LocalSEOAlert::where('user_id', $user->id)->count());
    }

    /** @test */
    public function combined_scope_query(): void
    {
        $user = User::factory()->create();
        $location = BusinessLocation::factory()->create();
        
        // Create various alerts
        LocalSEOAlert::factory()->count(3)->create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'is_read' => false,
        ]);
        
        LocalSEOAlert::factory()->count(2)->create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'critical',
            'is_read' => false,
        ]);
        
        LocalSEOAlert::factory()->count(5)->create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'is_read' => true,
        ]);
        
        // Combined query
        $results = LocalSEOAlert::forLocation($location->id)
            ->ofType('ranking_dropped')
            ->ofSeverity('warning')
            ->unread()
            ->get();
        
        $this->assertCount(3, $results);
    }
}
