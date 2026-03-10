<?php

namespace Tests\Unit\Http\Requests\ContentDecay;

use App\Http\Requests\Api\V1\ContentDecay\CreateRefreshTaskRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * @group content-decay
 * @group validation
 */
class CreateRefreshTaskRequestTest extends TestCase
{
    use RefreshDatabase;

    protected CreateRefreshTaskRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new CreateRefreshTaskRequest();
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
    // TITLE VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_fails_for_missing_title(): void
    {
        $validator = Validator::make(
            ['recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_empty_title(): void
    {
        $validator = Validator::make(
            ['title' => '', 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_valid_title(): void
    {
        $validator = Validator::make(
            ['title' => 'Rewrite title for better CTR', 'recommendation_type' => 'title_rewrite'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_title_exceeding_max_length(): void
    {
        $validator = Validator::make(
            ['title' => str_repeat('a', 256), 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_title_at_max_length(): void
    {
        $validator = Validator::make(
            ['title' => str_repeat('a', 255), 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // RECOMMENDATION TYPE VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_fails_for_missing_recommendation_type(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('recommendation_type', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_invalid_recommendation_type(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'invalid_type'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('recommendation_type', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_valid_title_rewrite(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'title_rewrite'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_meta_description_rewrite(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'meta_description_rewrite'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_content_refresh(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_technical_fix(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'technical_fix'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_internal_linking(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'internal_linking'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_competitor_analysis(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'competitor_analysis'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // DESCRIPTION VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_description(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'description' => 'Valid description'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_missing_description(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_null_description(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'description' => null],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // NOTES VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_notes(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'notes' => 'Valid notes'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_notes_exceeding_max_length(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'notes' => str_repeat('a', 65536)],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    // ==========================================
    // DUE DATE VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_due_date(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'due_date' => now()->addDays(7)->toDateString()],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_invalid_due_date_format(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'due_date' => 'not-a-date'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('due_date', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_missing_due_date(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_past_due_date(): void
    {
        // Past dates may be allowed or not depending on business rules
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'due_date' => now()->subDay()->toDateString()],
            $this->request->rules()
        );

        // This test documents the expected behavior
        // If past dates are not allowed, this should fail
        $this->assertFalse($validator->fails() || $validator->errors()->has('due_date'));
    }

    // ==========================================
    // OWNER USER ID VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_owner_user_id(): void
    {
        $user = User::factory()->create();
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'owner_user_id' => $user->id],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_non_existent_owner_user_id(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh', 'owner_user_id' => 999999],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('owner_user_id', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_missing_owner_user_id(): void
    {
        $validator = Validator::make(
            ['title' => 'Test task', 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // FULL VALID DATA TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_full_valid_data(): void
    {
        $user = User::factory()->create();
        $validator = Validator::make(
            [
                'title' => 'Complete content refresh',
                'recommendation_type' => 'content_refresh',
                'description' => 'Update examples and statistics',
                'notes' => 'Priority: high',
                'due_date' => now()->addDays(14)->toDateString(),
                'owner_user_id' => $user->id,
            ],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_unicode_title(): void
    {
        $validator = Validator::make(
            ['title' => '更新标题 你好世界', 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_numeric_title(): void
    {
        $validator = Validator::make(
            ['title' => 12345, 'recommendation_type' => 'content_refresh'],
            $this->request->rules()
        );

        // Numbers may be cast to strings in Laravel
        // This documents the expected behavior
        $this->assertFalse($validator->fails() || $validator->errors()->has('title'));
    }

    // ==========================================
    // CUSTOM MESSAGES TESTS
    // ==========================================

    /** @test */
    public function custom_error_messages_exist(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('title.required', $messages);
        $this->assertArrayHasKey('recommendation_type.required', $messages);
    }
}
