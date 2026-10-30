<?php

namespace Tests\Unit\Services;

use App\Services\AlertEvaluator;
use App\Models\Keyword;
use App\Models\KeywordAlertSetting;
use App\Models\AlertSetting;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected AlertEvaluator $evaluator;
    protected User $user;
    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new AlertEvaluator();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Create default alert settings
        AlertSetting::factory()->create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
        ]);
        
        // Create keyword alert settings
        KeywordAlertSetting::factory()->create([
            'keyword_id' => $this->keyword->id,
            'enabled' => true,
            'alert_events' => ['position_drop', 'position_gain', 'entered_top_10', 'left_top_10'],
        ]);
    }

    // ==================== Core Functionality ====================

    /** @test */
    public function position_drop_exceeding_threshold_returns_event(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 12);
        
        $this->assertCount(1, $events);
        $this->assertEquals('position_drop', $events[0]['event']);
        $this->assertEquals(7, $events[0]['change']);
    }

    /** @test */
    public function position_drop_within_threshold_returns_empty(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 7);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function position_gain_exceeding_threshold_returns_event(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 15, 8);
        
        $this->assertCount(2, $events); // position_gain + entered_top_10
        
        $gainEvent = collect($events)->firstWhere('event', 'position_gain');
        $this->assertNotNull($gainEvent);
        $this->assertEquals(7, $gainEvent['change']);
    }

    /** @test */
    public function position_gain_within_threshold_returns_empty(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 3);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function entered_top_10_returns_event(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 15, 8);
        
        $enteredEvent = collect($events)->firstWhere('event', 'entered_top_10');
        $this->assertNotNull($enteredEvent);
        $this->assertEquals(15, $enteredEvent['old_position']);
        $this->assertEquals(8, $enteredEvent['new_position']);
    }

    /** @test */
    public function stayed_in_top_10_no_entered_event(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 8);
        
        $enteredEvent = collect($events)->firstWhere('event', 'entered_top_10');
        $this->assertNull($enteredEvent);
    }

    /** @test */
    public function left_top_10_returns_event(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 8, 12);
        
        $leftEvent = collect($events)->firstWhere('event', 'left_top_10');
        $this->assertNotNull($leftEvent);
        $this->assertEquals(8, $leftEvent['old_position']);
        $this->assertEquals(12, $leftEvent['new_position']);
    }

    /** @test */
    public function stayed_below_top_10_no_left_event(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 15, 20);
        
        $leftEvent = collect($events)->firstWhere('event', 'left_top_10');
        $this->assertNull($leftEvent);
    }

    /** @test */
    public function multiple_events_triggered(): void
    {
        // Drop from 5 to 15: position_drop (10 positions) + left_top_10
        $events = $this->evaluator->evaluate($this->keyword, 5, 15);
        
        $this->assertCount(2, $events);
        $eventTypes = collect($events)->pluck('event')->toArray();
        $this->assertContains('position_drop', $eventTypes);
        $this->assertContains('left_top_10', $eventTypes);
    }

    /** @test */
    public function no_events_triggered_returns_empty(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 15, 16);
        
        $this->assertEmpty($events);
    }

    // ==================== Threshold Resolution ====================

    /** @test */
    public function uses_custom_threshold_when_set(): void
    {
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['custom_threshold' => 1]);
        
        $events = $this->evaluator->evaluate($this->keyword, 5, 7);
        
        // With threshold 1, a 2-position change should trigger
        $this->assertCount(1, $events);
        $this->assertEquals('position_drop', $events[0]['event']);
    }

    /** @test */
    public function uses_default_threshold_when_custom_null(): void
    {
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['custom_threshold' => null]);
        
        // Default threshold is 3, so 2-position change shouldn't trigger
        $events = $this->evaluator->evaluate($this->keyword, 5, 7);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function threshold_equals_change_no_alert(): void
    {
        // Default threshold is 3, exactly 3-position change shouldn't trigger
        $events = $this->evaluator->evaluate($this->keyword, 5, 8);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function threshold_one_triggers_any_change(): void
    {
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['custom_threshold' => 1]);
        
        $events = $this->evaluator->evaluate($this->keyword, 5, 6);
        
        $this->assertCount(1, $events);
    }

    // ==================== Alert Settings Integration ====================

    /** @test */
    public function keyword_alert_disabled_returns_empty(): void
    {
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['enabled' => false]);
        
        $events = $this->evaluator->evaluate($this->keyword, 5, 15);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function user_global_disabled_returns_empty(): void
    {
        AlertSetting::where('user_id', $this->user->id)
            ->update(['global_enabled' => false]);
        
        $events = $this->evaluator->evaluate($this->keyword, 5, 15);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function partial_event_types_filters_events(): void
    {
        KeywordAlertSetting::where('keyword_id', $this->keyword->id)
            ->update(['alert_events' => ['position_drop']]);
        
        // Drop from 5 to 15: should only get position_drop, not left_top_10
        $events = $this->evaluator->evaluate($this->keyword, 5, 15);
        
        $this->assertCount(1, $events);
        $this->assertEquals('position_drop', $events[0]['event']);
    }

    /** @test */
    public function all_event_types_returns_all_triggered(): void
    {
        // Already set to all 4 event types in setUp
        $events = $this->evaluator->evaluate($this->keyword, 5, 15);
        
        $this->assertCount(2, $events);
        $eventTypes = collect($events)->pluck('event')->toArray();
        $this->assertContains('position_drop', $eventTypes);
        $this->assertContains('left_top_10', $eventTypes);
    }

    // ==================== Event Metadata ====================

    /** @test */
    public function position_drop_metadata_complete(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 12);
        
        $this->assertEquals('position_drop', $events[0]['event']);
        $this->assertEquals(7, $events[0]['change']);
        $this->assertEquals(3, $events[0]['threshold']);
        $this->assertEquals(5, $events[0]['old_position']);
        $this->assertEquals(12, $events[0]['new_position']);
    }

    /** @test */
    public function position_gain_metadata_complete(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 20, 10);
        
        $gainEvent = collect($events)->firstWhere('event', 'position_gain');
        $this->assertEquals(10, $gainEvent['change']);
        $this->assertEquals(3, $gainEvent['threshold']);
        $this->assertEquals(20, $gainEvent['old_position']);
        $this->assertEquals(10, $gainEvent['new_position']);
    }

    /** @test */
    public function entered_top_10_metadata_complete(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 15, 8);
        
        $enteredEvent = collect($events)->firstWhere('event', 'entered_top_10');
        $this->assertEquals(15, $enteredEvent['old_position']);
        $this->assertEquals(8, $enteredEvent['new_position']);
        $this->assertArrayNotHasKey('threshold', $enteredEvent);
    }

    /** @test */
    public function left_top_10_metadata_complete(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 8, 15);
        
        $leftEvent = collect($events)->firstWhere('event', 'left_top_10');
        $this->assertEquals(8, $leftEvent['old_position']);
        $this->assertEquals(15, $leftEvent['new_position']);
        $this->assertArrayNotHasKey('threshold', $leftEvent);
    }

    // ==================== Edge Cases ====================

    /** @test */
    public function first_position_no_previous_returns_empty(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, null, 10);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function position_zero_handled_correctly(): void
    {
        // Position 0 means not ranked
        $events = $this->evaluator->evaluate($this->keyword, 0, 10);
        
        // Gaining a ranking (from 0 to 10) should trigger position_gain
        $gainEvent = collect($events)->firstWhere('event', 'position_gain');
        $this->assertNotNull($gainEvent);
    }

    /** @test */
    public function position_100_plus_handled_correctly(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 50, 150);
        
        // 100-position drop should trigger
        $this->assertNotEmpty($events);
    }

    /** @test */
    public function same_position_no_events(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 5);
        
        $this->assertEmpty($events);
    }

    /** @test */
    public function position_10_boundary_correct(): void
    {
        // Position 10 is still in top 10
        $events = $this->evaluator->evaluate($this->keyword, 11, 10);
        
        $enteredEvent = collect($events)->firstWhere('event', 'entered_top_10');
        $this->assertNotNull($enteredEvent);
    }

    /** @test */
    public function position_11_is_not_top_10(): void
    {
        // Position 11 is NOT in top 10
        $events = $this->evaluator->evaluate($this->keyword, 10, 11);
        
        $leftEvent = collect($events)->firstWhere('event', 'left_top_10');
        $this->assertNotNull($leftEvent);
    }

    /** @test */
    public function large_drop_from_position_1_to_100(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 1, 100);
        
        $dropEvent = collect($events)->firstWhere('event', 'position_drop');
        $this->assertNotNull($dropEvent);
        $this->assertEquals(99, $dropEvent['change']);
        
        $leftEvent = collect($events)->firstWhere('event', 'left_top_10');
        $this->assertNotNull($leftEvent);
    }

    // ==================== Security ====================

    /** @test */
    public function invalid_position_values_handled_gracefully(): void
    {
        // Should not throw exception
        $events = $this->evaluator->evaluate($this->keyword, -5, 10);
        
        // Implementation should handle this gracefully
        $this->assertNotNull($events);
    }

    /** @test */
    public function negative_position_treated_as_zero(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, -1, 10);
        
        // Should be treated as 0 to 10
        $gainEvent = collect($events)->firstWhere('event', 'position_gain');
        $this->assertNotNull($gainEvent);
    }
}
