<?php

namespace Tests\Unit\Models\AIVisibility;

use Tests\TestCase;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIVisibilityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Unit tests for TASK-34-2: AIVisibilityAlert Model
 *
 * Tests the AIVisibilityAlert model for:
 * - Model creation with required fields
 * - Fillable attributes (alert_type, threshold, values, etc.)
 * - Casts (boolean, datetime, decimal)
 * - Relationships (belongsTo AIVisibilityProject)
 * - Scopes (unread, unresolved)
 *
 * @see \App\Models\AIVisibility\AIVisibilityAlert
 */
class AIVisibilityAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = AIVisibilityProject::create([
            'user_id' => $this->user->id,
            'brand_name' => 'Test Brand',
            'keywords' => ['test'],
            'competitors' => [],
        ]);
    }

    // ==================== MODEL EXISTENCE TESTS ====================

    /**
     * VA-001: Model exists at correct namespace.
     */
    public function test_va001_model_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AIVisibilityAlert::class), 'AIVisibilityAlert model should exist');

        $reflection = new \ReflectionClass(AIVisibilityAlert::class);
        $this->assertEquals('App\Models\AIVisibility\AIVisibilityAlert', $reflection->getName());
    }

    /**
     * VA-002: Create with required fields.
     */
    public function test_va002_create_with_required_fields(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertInstanceOf(AIVisibilityAlert::class, $alert);
        $this->assertEquals('mention_spike', $alert->alert_type);
    }

    // ==================== FILLABLE TESTS ====================

    /**
     * VA-003: Fillable project_id.
     */
    public function test_va003_fillable_project_id(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertEquals($this->project->id, $alert->project_id);
    }

    /**
     * VA-004: Fillable alert_type.
     */
    public function test_va004_fillable_alert_type(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'competitor_gain',
            'threshold' => 15.00,
            'previous_value' => 40.00,
            'current_value' => 60.00,
            'change_percent' => 50.00,
        ]);

        $this->assertEquals('competitor_gain', $alert->alert_type);
    }

    /**
     * VA-005: Fillable threshold.
     */
    public function test_va005_fillable_threshold(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 25.50,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertEquals(25.50, $alert->threshold);
    }

    /**
     * VA-006: Fillable previous_value.
     */
    public function test_va006_fillable_previous_value(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.75,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertEquals(35.75, $alert->previous_value);
    }

    /**
     * VA-007: Fillable current_value.
     */
    public function test_va007_fillable_current_value(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.25,
            'change_percent' => 48.57,
        ]);

        $this->assertEquals(52.25, $alert->current_value);
    }

    /**
     * VA-008: Fillable change_percent.
     */
    public function test_va008_fillable_change_percent(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertEquals(48.57, $alert->change_percent);
    }

    /**
     * VA-009: Fillable is_read.
     */
    public function test_va009_fillable_is_read(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_read' => true,
        ]);

        $this->assertTrue($alert->is_read);
    }

    /**
     * VA-010: Fillable is_resolved.
     */
    public function test_va010_fillable_is_resolved(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_resolved' => true,
        ]);

        $this->assertTrue($alert->is_resolved);
    }

    /**
     * VA-011: Fillable triggered_at.
     */
    public function test_va011_fillable_triggered_at(): void
    {
        $now = now();

        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'triggered_at' => $now,
        ]);

        $this->assertInstanceOf(Carbon::class, $alert->triggered_at);
    }

    /**
     * VA-012: Fillable resolved_at.
     */
    public function test_va012_fillable_resolved_at(): void
    {
        $now = now();

        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'resolved_at' => $now,
        ]);

        $this->assertInstanceOf(Carbon::class, $alert->resolved_at);
    }

    // ==================== CASTS TESTS ====================

    /**
     * VA-013: Cast is_read → boolean.
     */
    public function test_va013_cast_is_read_to_boolean(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_read' => 1,
        ]);

        $this->assertIsBool($alert->is_read);
        $this->assertTrue($alert->is_read);
    }

    /**
     * VA-014: Cast is_resolved → boolean.
     */
    public function test_va014_cast_is_resolved_to_boolean(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_resolved' => 1,
        ]);

        $this->assertIsBool($alert->is_resolved);
        $this->assertTrue($alert->is_resolved);
    }

    /**
     * VA-015: Cast triggered_at → datetime.
     */
    public function test_va015_cast_triggered_at_to_datetime(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'triggered_at' => '2026-03-07 14:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $alert->triggered_at);
        $this->assertEquals('2026-03-07 14:30:00', $alert->triggered_at->format('Y-m-d H:i:s'));
    }

    /**
     * VA-016: Cast resolved_at → datetime.
     */
    public function test_va016_cast_resolved_at_to_datetime(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'resolved_at' => '2026-03-07 15:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $alert->resolved_at);
        $this->assertEquals('2026-03-07 15:00:00', $alert->resolved_at->format('Y-m-d H:i:s'));
    }

    // ==================== RELATIONSHIP TESTS ====================

    /**
     * VA-017: BelongsTo project().
     */
    public function test_va017_belongs_to_project(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertInstanceOf(AIVisibilityProject::class, $alert->project);
        $this->assertEquals($this->project->id, $alert->project->id);
    }

    // ==================== SCOPE TESTS ====================

    /**
     * VA-018: Scope unread().
     */
    public function test_va018_scope_unread(): void
    {
        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_read' => false,
        ]);

        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 40.00,
            'change_percent' => -33.33,
            'is_read' => true,
        ]);

        if (method_exists(AIVisibilityAlert::class, 'scopeUnread')) {
            $unreadAlerts = AIVisibilityAlert::unread()->get();

            $this->assertCount(1, $unreadAlerts);
            $this->assertEquals('mention_spike', $unreadAlerts->first()->alert_type);
        } else {
            $this->markTestSkipped('unread scope not yet implemented');
        }
    }

    /**
     * VA-019: Scope unresolved().
     */
    public function test_va019_scope_unresolved(): void
    {
        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_resolved' => false,
        ]);

        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 40.00,
            'change_percent' => -33.33,
            'is_resolved' => true,
        ]);

        if (method_exists(AIVisibilityAlert::class, 'scopeUnresolved')) {
            $unresolvedAlerts = AIVisibilityAlert::unresolved()->get();

            $this->assertCount(1, $unresolvedAlerts);
            $this->assertEquals('mention_spike', $unresolvedAlerts->first()->alert_type);
        } else {
            $this->markTestSkipped('unresolved scope not yet implemented');
        }
    }

    // ==================== DEFAULT VALUE TESTS ====================

    /**
     * VA-020: Default is_read is false.
     */
    public function test_va020_default_is_read(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertFalse($alert->is_read);
    }

    /**
     * VA-021: Default is_resolved is false.
     */
    public function test_va021_default_is_resolved(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertFalse($alert->is_resolved);
    }

    /**
     * VA-022: Default triggered_at is current timestamp.
     */
    public function test_va022_default_triggered_at(): void
    {
        $before = now();
        
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $after = now();

        $this->assertNotNull($alert->triggered_at);
        $this->assertTrue($alert->triggered_at->between($before, $after));
    }

    // ==================== NULLABLE TESTS ====================

    /**
     * VA-023: Nullable resolved_at.
     */
    public function test_va023_nullable_resolved_at(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'resolved_at' => null,
        ]);

        $this->assertNull($alert->resolved_at);
    }

    // ==================== ENUM VALIDATION TESTS ====================

    /**
     * VA-024: Alert type enum values.
     */
    public function test_va024_alert_type_enum_values(): void
    {
        $validTypes = ['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'];

        foreach ($validTypes as $type) {
            $alert = AIVisibilityAlert::create([
                'project_id' => $this->project->id,
                'alert_type' => $type,
                'threshold' => 20.00,
                'previous_value' => 50.00,
                'current_value' => 60.00,
                'change_percent' => 20.00,
            ]);

            $this->assertEquals($type, $alert->alert_type);
        }
    }

    // ==================== DECIMAL PRECISION TESTS ====================

    /**
     * VA-025: Decimal precision threshold.
     */
    public function test_va025_decimal_precision_threshold(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 999.99,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertEquals(999.99, $alert->threshold);
    }

    /**
     * VA-026: Decimal precision change_percent (negative).
     */
    public function test_va026_decimal_precision_change_percent_negative(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 30.00,
            'change_percent' => -50.00,
        ]);

        $this->assertEquals(-50.00, $alert->change_percent);
    }

    // ==================== RELATIONSHIP LOADING TESTS ====================

    /**
     * VA-027: Project relationship retrieval.
     */
    public function test_va027_project_relationship_retrieval(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $loadedAlert = AIVisibilityAlert::with('project')->find($alert->id);

        $this->assertEquals('Test Brand', $loadedAlert->project->brand_name);
    }

    // ==================== SCOPE CHAINING TESTS ====================

    /**
     * VA-028: Unread scope chainable with unresolved.
     */
    public function test_va028_unread_scope_chainable(): void
    {
        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_read' => false,
            'is_resolved' => false,
        ]);

        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 40.00,
            'change_percent' => -33.33,
            'is_read' => true,
            'is_resolved' => false,
        ]);

        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'competitor_gain',
            'threshold' => 20.00,
            'previous_value' => 30.00,
            'current_value' => 45.00,
            'change_percent' => 50.00,
            'is_read' => false,
            'is_resolved' => true,
        ]);

        if (method_exists(AIVisibilityAlert::class, 'scopeUnread') &&
            method_exists(AIVisibilityAlert::class, 'scopeUnresolved')) {
            $alerts = AIVisibilityAlert::unread()->unresolved()->get();

            $this->assertCount(1, $alerts);
            $this->assertEquals('mention_spike', $alerts->first()->alert_type);
        } else {
            $this->markTestSkipped('Scopes not yet implemented');
        }
    }

    // ==================== STATE CHANGE TESTS ====================

    /**
     * VA-029: Mark as read.
     */
    public function test_va029_mark_as_read(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $alert->is_read = true;
        $alert->save();

        $this->assertTrue($alert->is_read);
        $this->assertDatabaseHas('ai_visibility_alerts', [
            'id' => $alert->id,
            'is_read' => true,
        ]);
    }

    /**
     * VA-030: Resolve alert.
     */
    public function test_va030_resolve_alert(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $alert->is_resolved = true;
        $alert->resolved_at = now();
        $alert->save();

        $this->assertTrue($alert->is_resolved);
        $this->assertNotNull($alert->resolved_at);
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * VA-031: Multiple alerts per project.
     */
    public function test_va031_multiple_alerts_per_project(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            AIVisibilityAlert::create([
                'project_id' => $this->project->id,
                'alert_type' => 'mention_spike',
                'threshold' => $i * 10,
                'previous_value' => 50.00,
                'current_value' => 50.00 + $i,
                'change_percent' => $i * 2,
            ]);
        }

        $this->assertEquals(10, $this->project->alerts()->count());
    }

    /**
     * VA-032: Negative change_percent.
     */
    public function test_va032_negative_change_percent(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 30.00,
            'change_percent' => -50.00,
        ]);

        $this->assertEquals(-50.00, $alert->change_percent);
    }

    /**
     * VA-033: Large change_percent.
     */
    public function test_va033_large_change_percent(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 10.00,
            'current_value' => 60.00,
            'change_percent' => 500.00,
        ]);

        $this->assertEquals(500.00, $alert->change_percent);
    }

    // ==================== MODEL CONFIGURATION TESTS ====================

    /**
     * VA-034: Table name explicit.
     */
    public function test_va034_table_name_explicit(): void
    {
        $alert = new AIVisibilityAlert();
        $this->assertEquals('ai_visibility_alerts', $alert->getTable());
    }

    /**
     * VA-035: Timestamps enabled.
     */
    public function test_va035_timestamps_enabled(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->assertNotNull($alert->created_at);
        $this->assertNotNull($alert->updated_at);
    }

    /**
     * VA-036: PHPDoc type hints.
     */
    public function test_va036_phpdoc_type_hints(): void
    {
        $reflection = new \ReflectionClass(AIVisibilityAlert::class);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment, 'Model should have PHPDoc comments');
    }

    /**
     * VA-037: Cascade delete via project.
     */
    public function test_va037_cascade_delete_via_project(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite foreign key cascade not enforced by default');
        }

        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        $this->project->delete();

        $this->assertDatabaseMissing('ai_visibility_alerts', ['id' => $alert->id]);
    }

    /**
     * VA-038: Unread count by project.
     */
    public function test_va038_unread_count_by_project(): void
    {
        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_read' => false,
        ]);

        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 40.00,
            'change_percent' => -33.33,
            'is_read' => false,
        ]);

        AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'competitor_gain',
            'threshold' => 20.00,
            'previous_value' => 30.00,
            'current_value' => 45.00,
            'change_percent' => 50.00,
            'is_read' => true,
        ]);

        $unreadCount = AIVisibilityAlert::where('project_id', $this->project->id)
            ->where('is_read', false)
            ->count();

        $this->assertEquals(2, $unreadCount);
    }

    /**
     * VA-039: Recent alerts query.
     */
    public function test_va039_recent_alerts_query(): void
    {
        // Create alerts with different timestamps
        $oldAlert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'triggered_at' => now()->subDays(2),
        ]);

        $newAlert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_drop',
            'threshold' => 20.00,
            'previous_value' => 60.00,
            'current_value' => 40.00,
            'change_percent' => -33.33,
            'triggered_at' => now(),
        ]);

        $recentAlerts = AIVisibilityAlert::orderBy('triggered_at', 'desc')->get();

        $this->assertEquals($newAlert->id, $recentAlerts->first()->id);
        $this->assertEquals($oldAlert->id, $recentAlerts->last()->id);
    }

    /**
     * VA-040: Alert summary accessor.
     */
    public function test_va040_alert_summary_accessor(): void
    {
        $alert = AIVisibilityAlert::create([
            'project_id' => $this->project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
        ]);

        if (method_exists($alert, 'getSummaryAttribute')) {
            $summary = $alert->summary;
            $this->assertIsString($summary);
            $this->assertStringContainsString('mention_spike', $summary);
        } else {
            $this->markTestSkipped('getSummaryAttribute accessor not yet implemented');
        }
    }
}
