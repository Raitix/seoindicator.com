<?php

namespace Tests\Unit\Services;

use App\Models\AlertSettings;
use App\Models\Keyword;
use App\Models\KeywordAlertSettings;
use App\Models\Site;
use App\Models\User;
use App\Services\AlertEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected AlertEvaluator $evaluator;
    protected User $user;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new AlertEvaluator();

        $this->user = User::factory()->create();

        $site = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
            'name' => 'Test Site',
        ]);

        $this->keyword = Keyword::create([
            'user_id' => $this->user->id,
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);

        // Create default alert settings
        AlertSettings::create([
            'user_id' => $this->user->id,
            'global_enabled' => true,
            'default_threshold' => 3,
        ]);
    }

    public function test_evaluate_no_previous_position(): void
    {
        $events = $this->evaluator->evaluate(
            $this->keyword,
            null, // no previous position
            10
        );

        $this->assertEmpty($events);
    }

    public function test_evaluate_no_new_position(): void
    {
        $events = $this->evaluator->evaluate(
            $this->keyword,
            5,
            null // not found
        );

        $this->assertEmpty($events);
    }

    public function test_evaluate_global_alerts_disabled(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'global_enabled' => false,
        ]);

        $events = $this->evaluator->evaluate($this->keyword, 5, 10);

        $this->assertEmpty($events);
    }

    public function test_evaluate_keyword_alerts_disabled(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'enabled' => false,
        ]);

        $events = $this->evaluator->evaluate($this->keyword, 5, 10);

        $this->assertEmpty($events);
    }

    public function test_evaluate_position_drop_below_threshold(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 7);

        // Change of 2, threshold is 3 - no alert
        $this->assertEmpty($events);
    }

    public function test_evaluate_position_drop_at_threshold(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 8);

        // Change of 3, threshold is 3 - alert
        $this->assertCount(1, $events);
        $this->assertEquals(AlertEvaluator::EVENT_POSITION_DROP, $events[0]['event']);
        $this->assertEquals(3, $events[0]['change']);
    }

    public function test_evaluate_position_drop_above_threshold(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 15);

        // Position dropped by 10 AND left top 10 (5 -> 15)
        $this->assertCount(2, $events);
        
        $dropEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_POSITION_DROP);
        $this->assertNotNull($dropEvent);
        $this->assertEquals(10, $dropEvent['change']);
        
        $leftTop10Event = collect($events)->firstWhere('event', AlertEvaluator::EVENT_LEFT_TOP_10);
        $this->assertNotNull($leftTop10Event);
    }

    public function test_evaluate_position_gain_below_threshold(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 10, 8);

        // Improvement of 2, threshold is 3 - no alert
        $this->assertEmpty($events);
    }

    public function test_evaluate_position_gain_at_threshold(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 10, 7);

        // Improvement of 3, threshold is 3 - alert
        $this->assertCount(1, $events);
        $this->assertEquals(AlertEvaluator::EVENT_POSITION_GAIN, $events[0]['event']);
        $this->assertEquals(3, $events[0]['change']);
    }

    public function test_evaluate_position_gain_above_threshold(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 20, 5);

        // Position improved by 15 AND entered top 10 (20 -> 5)
        $this->assertCount(2, $events);
        
        $gainEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_POSITION_GAIN);
        $this->assertNotNull($gainEvent);
        $this->assertEquals(15, $gainEvent['change']);
        
        $enteredTop10Event = collect($events)->firstWhere('event', AlertEvaluator::EVENT_ENTERED_TOP_10);
        $this->assertNotNull($enteredTop10Event);
    }

    public function test_evaluate_entered_top_10(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 15, 8);

        $this->assertCount(2, $events);

        $top10Event = collect($events)->firstWhere('event', AlertEvaluator::EVENT_ENTERED_TOP_10);
        $this->assertNotNull($top10Event);
        $this->assertEquals(15, $top10Event['old_position']);
        $this->assertEquals(8, $top10Event['new_position']);
    }

    public function test_evaluate_left_top_10(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 8, 15);

        $this->assertCount(2, $events);

        $leftEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_LEFT_TOP_10);
        $this->assertNotNull($leftEvent);
    }

    public function test_evaluate_entered_top_3(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 2);

        $this->assertCount(2, $events);

        $top3Event = collect($events)->firstWhere('event', AlertEvaluator::EVENT_ENTERED_TOP_3);
        $this->assertNotNull($top3Event);
    }

    public function test_evaluate_left_top_3(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 2, 5);

        $this->assertCount(2, $events);

        $leftEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_LEFT_TOP_3);
        $this->assertNotNull($leftEvent);
    }

    public function test_evaluate_custom_threshold(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'custom_threshold' => 1,
        ]);

        // Change of 2, global threshold is 3, but keyword threshold is 1
        $events = $this->evaluator->evaluate($this->keyword, 5, 7);

        $this->assertCount(1, $events);
        $this->assertEquals(AlertEvaluator::EVENT_POSITION_DROP, $events[0]['event']);
    }

    public function test_evaluate_filter_by_enabled_events(): void
    {
        KeywordAlertSettings::create([
            'keyword_id' => $this->keyword->id,
            'alert_events' => ['position_drop'],
        ]);

        // Position gain of 5 (exceeds threshold)
        $events = $this->evaluator->evaluate($this->keyword, 10, 5);

        // Should be filtered out because only position_drop is enabled
        $this->assertEmpty($events);
    }

    public function test_evaluate_serp_feature_gained(): void
    {
        $events = $this->evaluator->evaluate(
            $this->keyword,
            5,
            5,
            ['serp_features' => ['gained' => ['featured_snippet'], 'lost' => []]]
        );

        $serpEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_SERP_FEATURE_GAINED);
        $this->assertNotNull($serpEvent);
        $this->assertEquals('featured_snippet', $serpEvent['feature_type']);
    }

    public function test_evaluate_serp_feature_lost(): void
    {
        $events = $this->evaluator->evaluate(
            $this->keyword,
            5,
            5,
            ['serp_features' => ['gained' => [], 'lost' => ['local_pack']]]
        );

        $serpEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_SERP_FEATURE_LOST);
        $this->assertNotNull($serpEvent);
        $this->assertEquals('local_pack', $serpEvent['feature_type']);
    }

    public function test_evaluate_serp_features_disabled_globally(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'serp_feature_alerts_enabled' => false,
        ]);

        $events = $this->evaluator->evaluate(
            $this->keyword,
            5,
            5,
            ['serp_features' => ['gained' => ['featured_snippet'], 'lost' => []]]
        );

        $serpEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_SERP_FEATURE_GAINED);
        $this->assertNull($serpEvent);
    }

    public function test_evaluate_serp_feature_type_filter(): void
    {
        AlertSettings::where('user_id', $this->user->id)->update([
            'serp_feature_types' => ['featured_snippet'],
        ]);

        $events = $this->evaluator->evaluate(
            $this->keyword,
            5,
            5,
            ['serp_features' => ['gained' => ['local_pack'], 'lost' => []]]
        );

        // local_pack not in allowed types
        $serpEvent = collect($events)->firstWhere('event', AlertEvaluator::EVENT_SERP_FEATURE_GAINED);
        $this->assertNull($serpEvent);
    }

    public function test_evaluate_multiple_events(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 12);

        // Position drop of 7 + left top 10
        $this->assertCount(2, $events);

        $eventsList = collect($events)->pluck('event')->all();
        $this->assertContains(AlertEvaluator::EVENT_POSITION_DROP, $eventsList);
        $this->assertContains(AlertEvaluator::EVENT_LEFT_TOP_10, $eventsList);
    }

    public function test_evaluate_no_change(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 5);

        $this->assertEmpty($events);
    }

    public function test_evaluate_threshold_metadata_included(): void
    {
        $events = $this->evaluator->evaluate($this->keyword, 5, 10);

        $this->assertEquals(3, $events[0]['threshold']);
        $this->assertEquals(5, $events[0]['old_position']);
        $this->assertEquals(10, $events[0]['new_position']);
    }

    public function test_all_events_constant(): void
    {
        $this->assertEquals([
            'position_drop',
            'position_gain',
            'entered_top_10',
            'left_top_10',
            'entered_top_3',
            'left_top_3',
            'serp_feature_gained',
            'serp_feature_lost',
        ], AlertEvaluator::ALL_EVENTS);
    }

    public function test_mvp_events_constant(): void
    {
        $this->assertEquals([
            'position_drop',
            'position_gain',
            'entered_top_10',
            'left_top_10',
        ], AlertEvaluator::MVP_EVENTS);
    }
}
