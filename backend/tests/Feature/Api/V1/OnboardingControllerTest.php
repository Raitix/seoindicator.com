<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\UserOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/onboarding';

    // ==================== GET PROGRESS ====================

    /**
     * Test GET /onboarding returns progress for authenticated user.
     */
    public function test_get_progress_authenticated(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
            'website_url' => 'https://example.com',
        ]);

        $response = $this->actingAs($user)->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $onboarding->id,
                    'current_step' => 2,
                    'completed_steps' => [1],
                    'website_url' => 'https://example.com',
                    'progress_percentage' => 40,
                ],
            ]);
    }

    /**
     * Test GET /onboarding returns null for user not started.
     */
    public function test_get_progress_not_started(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);
    }

    /**
     * Test GET /onboarding returns 401 for unauthenticated user.
     */
    public function test_get_progress_unauthenticated(): void
    {
        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(401);
    }

    /**
     * Test GET /onboarding includes progress percentage.
     */
    public function test_get_progress_includes_percentage(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 3,
        ]);

        $response = $this->actingAs($user)->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonPath('data.progress_percentage', 60);
    }

    // ==================== POST /onboarding (START/UPDATE) ====================

    /**
     * Test POST /onboarding starts new onboarding.
     */
    public function test_post_onboarding_start(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.current_step', 1);
        
        $this->assertDatabaseHas('user_onboarding', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test POST /onboarding step 1 with valid URL.
     */
    public function test_post_onboarding_step_1_valid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 1,
            'data' => [
                'website_url' => 'https://example.com',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.website_url', 'https://example.com')
            ->assertJsonPath('data.completed_steps', [1]);
    }

    /**
     * Test POST /onboarding step 1 with invalid URL.
     */
    public function test_post_onboarding_step_1_invalid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 1,
            'data' => [
                'website_url' => 'not-a-url',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test POST /onboarding step 1 with URL too long.
     */
    public function test_post_onboarding_step_1_url_too_long(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 1,
            'data' => [
                'website_url' => 'https://example.com/' . str_repeat('a', 500),
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test POST /onboarding step 1 with SSRF URL.
     */
    public function test_post_onboarding_step_1_ssrf(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 1,
            'data' => [
                'website_url' => 'http://localhost/test',
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test POST /onboarding step 2 with valid keyword.
     */
    public function test_post_onboarding_step_2_valid(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
        ]);

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 2,
            'data' => [
                'first_keyword' => 'seo tools',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_keyword', 'seo tools')
            ->assertJsonPath('data.current_step', 3);
    }

    /**
     * Test POST /onboarding step 2 with empty keyword.
     */
    public function test_post_onboarding_step_2_empty(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 2,
            'data' => [
                'first_keyword' => '',
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test POST /onboarding step 2 with keyword too long.
     */
    public function test_post_onboarding_step_2_too_long(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 2,
            'data' => [
                'first_keyword' => str_repeat('a', 256),
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test POST /onboarding step 4 with valid preferences.
     */
    public function test_post_onboarding_step_4_valid(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 4,
            'completed_steps' => [1, 2, 3],
        ]);

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 4,
            'data' => [
                'alert_preferences' => [
                    'email' => true,
                    'weekly' => false,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.alert_preferences.email', true)
            ->assertJsonPath('data.alert_preferences.weekly', false);
    }

    /**
     * Test POST /onboarding step 4 with invalid preferences.
     */
    public function test_post_onboarding_step_4_invalid(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 4,
        ]);

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 4,
            'data' => [
                'alert_preferences' => [
                    'email' => 'yes', // Should be boolean
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test POST /onboarding requires authentication.
     */
    public function test_post_onboarding_unauthenticated(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'step' => 1,
            'data' => [
                'website_url' => 'https://example.com',
            ],
        ]);

        $response->assertStatus(401);
    }

    // ==================== POST /onboarding/complete ====================

    /**
     * Test POST /onboarding/complete successfully.
     */
    public function test_post_onboarding_complete(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 5,
            'completed_steps' => [1, 2, 3, 4],
            'website_url' => 'https://example.com',
            'first_keyword' => 'seo tools',
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => ['completed_at', 'keyword_id'],
            ]);
        
        // Verify keyword was created
        $this->assertDatabaseHas('keywords', [
            'keyword' => 'seo tools',
        ]);
    }

    /**
     * Test POST /onboarding/complete already completed.
     */
    public function test_post_onboarding_complete_already_done(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/complete");

        $response->assertStatus(200);
    }

    /**
     * Test POST /onboarding/complete requires authentication.
     */
    public function test_post_onboarding_complete_unauthenticated(): void
    {
        $response = $this->postJson("{$this->baseUrl}/complete");

        $response->assertStatus(401);
    }

    // ==================== POST /onboarding/skip ====================

    /**
     * Test POST /onboarding/skip successfully.
     */
    public function test_post_onboarding_skip(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/skip");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => ['skipped_at'],
            ]);
    }

    /**
     * Test POST /onboarding/skip creates record if not exists.
     */
    public function test_post_onboarding_skip_creates_record(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/skip");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['skipped_at'],
            ]);
        
        $this->assertDatabaseHas('user_onboarding', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test POST /onboarding/skip already completed.
     */
    public function test_post_onboarding_skip_already_completed(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/skip");

        $response->assertStatus(200);
    }

    /**
     * Test POST /onboarding/skip requires authentication.
     */
    public function test_post_onboarding_skip_unauthenticated(): void
    {
        $response = $this->postJson("{$this->baseUrl}/skip");

        $response->assertStatus(401);
    }

    // ==================== ERROR RESPONSES ====================

    /**
     * Test error response structure for validation errors.
     */
    public function test_error_response_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 1,
            'data' => [
                'website_url' => 'invalid',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    // ==================== RESPONSE STRUCTURE ====================

    /**
     * Test response structure includes all expected fields.
     */
    public function test_response_structure(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
            'website_url' => 'https://example.com',
        ]);

        $response = $this->actingAs($user)->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'current_step',
                    'completed_steps',
                    'website_url',
                    'first_keyword',
                    'keyword_id',
                    'alert_preferences',
                    'completed_at',
                    'progress_percentage',
                ],
            ]);
    }

    // ==================== EDGE CASES ====================

    /**
     * Test keyword normalization (lowercase, trim).
     */
    public function test_keyword_normalization(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
        ]);

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 2,
            'data' => [
                'first_keyword' => '  SEO Tools  ',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_keyword', 'seo tools');
    }

    /**
     * Test resume after skip.
     */
    public function test_resume_after_skip(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
            'skipped_at' => now()->subMinutes(5),
        ]);

        // Start again should clear skipped_at and resume
        $response = $this->actingAs($user)->postJson($this->baseUrl, []);

        $response->assertStatus(200)
            ->assertJsonPath('data.current_step', 2);
    }

    /**
     * Test step validation - negative step.
     */
    public function test_negative_step(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => -1,
            'data' => [],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test step validation - step > 5.
     */
    public function test_step_greater_than_5(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->baseUrl, [
            'step' => 6,
            'data' => [],
        ]);

        $response->assertStatus(422);
    }
}
