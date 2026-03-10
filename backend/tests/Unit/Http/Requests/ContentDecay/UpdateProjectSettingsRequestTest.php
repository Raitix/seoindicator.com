<?php

namespace Tests\Unit\Http\Requests\ContentDecay;

use App\Http\Requests\Api\V1\ContentDecay\UpdateProjectSettingsRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * @group content-decay
 * @group validation
 */
class UpdateProjectSettingsRequestTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateProjectSettingsRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateProjectSettingsRequest();
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function authorize_returns_true_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertTrue($this->request->authorize());
    }

    /** @test */
    public function authorize_returns_false_for_unauthenticated_user(): void
    {
        $this->assertFalse($this->request->authorize());
    }

    // ==========================================
    // DETECTION ENABLED VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_detection_enabled_true(): void
    {
        $validator = Validator::make(
            ['detection_enabled' => true],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_detection_enabled_false(): void
    {
        $validator = Validator::make(
            ['detection_enabled' => false],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_missing_detection_enabled(): void
    {
        $validator = Validator::make(
            [],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // WINDOW DAYS VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_window_days_7(): void
    {
        $validator = Validator::make(
            ['window_days' => 7],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_window_days_14(): void
    {
        $validator = Validator::make(
            ['window_days' => 14],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_window_days_28(): void
    {
        $validator = Validator::make(
            ['window_days' => 28],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_window_days_56(): void
    {
        $validator = Validator::make(
            ['window_days' => 56],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_window_days_below_minimum(): void
    {
        $validator = Validator::make(
            ['window_days' => 0],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('window_days', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_window_days_above_maximum(): void
    {
        $validator = Validator::make(
            ['window_days' => 365],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('window_days', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_negative_window_days(): void
    {
        $validator = Validator::make(
            ['window_days' => -1],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('window_days', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_non_integer_window_days(): void
    {
        $validator = Validator::make(
            ['window_days' => 14.5],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('window_days', $validator->errors()->toArray());
    }

    // ==========================================
    // MIN CLICKS THRESHOLD VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_min_clicks_threshold(): void
    {
        $validator = Validator::make(
            ['min_clicks_threshold' => 10],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_zero_min_clicks_threshold(): void
    {
        $validator = Validator::make(
            ['min_clicks_threshold' => 0],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_negative_min_clicks_threshold(): void
    {
        $validator = Validator::make(
            ['min_clicks_threshold' => -1],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('min_clicks_threshold', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_large_min_clicks_threshold(): void
    {
        $validator = Validator::make(
            ['min_clicks_threshold' => 10000],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // MIN IMPRESSIONS THRESHOLD VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_min_impressions_threshold(): void
    {
        $validator = Validator::make(
            ['min_impressions_threshold' => 100],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_zero_min_impressions_threshold(): void
    {
        $validator = Validator::make(
            ['min_impressions_threshold' => 0],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_negative_min_impressions_threshold(): void
    {
        $validator = Validator::make(
            ['min_impressions_threshold' => -1],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('min_impressions_threshold', $validator->errors()->toArray());
    }

    // ==========================================
    // MIN DECAY SCORE VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_min_decay_score(): void
    {
        $validator = Validator::make(
            ['min_decay_score' => 20.00],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_min_decay_score_zero(): void
    {
        $validator = Validator::make(
            ['min_decay_score' => 0],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_min_decay_score_above_100(): void
    {
        $validator = Validator::make(
            ['min_decay_score' => 101],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('min_decay_score', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_negative_min_decay_score(): void
    {
        $validator = Validator::make(
            ['min_decay_score' => -1],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('min_decay_score', $validator->errors()->toArray());
    }

    // ==========================================
    // DIGEST SETTINGS VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_digest_enabled_true(): void
    {
        $validator = Validator::make(
            ['digest_enabled' => true],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_digest_enabled_false(): void
    {
        $validator = Validator::make(
            ['digest_enabled' => false],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_digest_frequency_weekly(): void
    {
        $validator = Validator::make(
            ['digest_frequency' => 'weekly'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_digest_frequency_monthly(): void
    {
        $validator = Validator::make(
            ['digest_frequency' => 'monthly'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_invalid_digest_frequency(): void
    {
        $validator = Validator::make(
            ['digest_frequency' => 'daily'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('digest_frequency', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_uppercase_digest_frequency(): void
    {
        $validator = Validator::make(
            ['digest_frequency' => 'WEEKLY'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('digest_frequency', $validator->errors()->toArray());
    }

    // ==========================================
    // FULL VALID DATA TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_full_valid_data(): void
    {
        $validator = Validator::make(
            [
                'detection_enabled' => true,
                'window_days' => 28,
                'min_clicks_threshold' => 10,
                'min_impressions_threshold' => 100,
                'min_decay_score' => 20.00,
                'digest_enabled' => true,
                'digest_frequency' => 'weekly',
            ],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_minimal_data(): void
    {
        $validator = Validator::make(
            [],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_boundary_window_days(): void
    {
        // Test minimum valid
        $validator1 = Validator::make(['window_days' => 1], $this->request->rules());
        $this->assertFalse($validator1->fails());

        // Test maximum valid
        $validator2 = Validator::make(['window_days' => 56], $this->request->rules());
        $this->assertFalse($validator2->fails());
    }

    /** @test */
    public function validation_passes_for_boundary_min_decay_score(): void
    {
        // Test 0
        $validator1 = Validator::make(['min_decay_score' => 0], $this->request->rules());
        $this->assertFalse($validator1->fails());

        // Test 100
        $validator2 = Validator::make(['min_decay_score' => 100], $this->request->rules());
        $this->assertFalse($validator2->fails());
    }

    /** @test */
    public function validation_ignores_unknown_fields(): void
    {
        $validator = Validator::make(
            ['unknown_field' => 'value', 'window_days' => 28],
            $this->request->rules()
        );

        // Unknown fields should be ignored, not cause validation failure
        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // CUSTOM MESSAGES TESTS
    // ==========================================

    /** @test */
    public function custom_error_messages_exist(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('window_days.min', $messages);
        $this->assertArrayHasKey('window_days.max', $messages);
    }

    /** @test */
    public function custom_messages_are_used_on_validation_failure(): void
    {
        $validator = Validator::make(
            ['window_days' => 0],
            $this->request->rules(),
            $this->request->messages()
        );

        $errors = $validator->errors()->get('window_days');
        $this->assertNotEmpty($errors);
    }
}
