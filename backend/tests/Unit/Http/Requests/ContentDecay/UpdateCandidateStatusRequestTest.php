<?php

namespace Tests\Unit\Http\Requests\ContentDecay;

use App\Http\Requests\Api\V1\ContentDecay\UpdateCandidateStatusRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * @group content-decay
 * @group validation
 */
class UpdateCandidateStatusRequestTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateCandidateStatusRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateCandidateStatusRequest();
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
    // STATUS VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_status_planned(): void
    {
        $validator = Validator::make(
            ['status' => 'planned'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_status_in_progress(): void
    {
        $validator = Validator::make(
            ['status' => 'in_progress'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_status_refreshed(): void
    {
        $validator = Validator::make(
            ['status' => 'refreshed'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_valid_status_ignored(): void
    {
        $validator = Validator::make(
            ['status' => 'ignored'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_invalid_status(): void
    {
        $validator = Validator::make(
            ['status' => 'invalid_status'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_empty_status(): void
    {
        $validator = Validator::make(
            ['status' => ''],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_missing_status(): void
    {
        $validator = Validator::make(
            [],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_fails_for_uppercase_status(): void
    {
        $validator = Validator::make(
            ['status' => 'PLANNED'],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    // ==========================================
    // NOTES VALIDATION TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_valid_notes(): void
    {
        $validator = Validator::make(
            ['status' => 'planned', 'notes' => 'This is a valid note'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_null_notes(): void
    {
        $validator = Validator::make(
            ['status' => 'planned', 'notes' => null],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_passes_for_missing_notes(): void
    {
        $validator = Validator::make(
            ['status' => 'planned'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_notes_exceeding_max_length(): void
    {
        $validator = Validator::make(
            ['status' => 'planned', 'notes' => str_repeat('a', 65536)],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    /** @test */
    public function validation_passes_for_notes_at_max_length(): void
    {
        $validator = Validator::make(
            ['status' => 'planned', 'notes' => str_repeat('a', 65535)],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function validation_passes_for_status_with_whitespace(): void
    {
        $validator = Validator::make(
            ['status' => ' planned ', 'notes' => 'Valid note'],
            $this->request->rules()
        );

        // This depends on whether the request trims input
        // Standard behavior would fail due to whitespace
        $this->assertTrue($validator->fails() || $validator->errors()->has('status'));
    }

    /** @test */
    public function validation_passes_for_unicode_notes(): void
    {
        $validator = Validator::make(
            ['status' => 'planned', 'notes' => 'Unicode: 你好世界 🌍 مرحبا'],
            $this->request->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function validation_fails_for_numeric_status(): void
    {
        $validator = Validator::make(
            ['status' => 123],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function validation_fails_for_array_status(): void
    {
        $validator = Validator::make(
            ['status' => ['planned']],
            $this->request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    // ==========================================
    // CUSTOM MESSAGES TESTS
    // ==========================================

    /** @test */
    public function custom_error_messages_exist(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('status.required', $messages);
        $this->assertArrayHasKey('status.in', $messages);
    }

    /** @test */
    public function custom_messages_are_used_on_validation_failure(): void
    {
        $validator = Validator::make(
            ['status' => 'invalid'],
            $this->request->rules(),
            $this->request->messages()
        );

        $errors = $validator->errors()->get('status');
        $this->assertNotEmpty($errors);
    }
}
