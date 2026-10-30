<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\UserOnboarding;
use App\Services\OnboardingService;
use App\Events\UserActivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OnboardingService();
    }

    // ==================== GET PROGRESS ====================

    /**
     * Test getProgress returns onboarding for user with existing record.
     */
    public function test_get_progress_returns_onboarding(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
        ]);

        $result = $this->service->getProgress($user);

        $this->assertInstanceOf(UserOnboarding::class, $result);
        $this->assertEquals($onboarding->id, $result->id);
    }

    /**
     * Test getProgress returns null for user without onboarding.
     */
    public function test_get_progress_returns_null(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getProgress($user);

        $this->assertNull($result);
    }

    // ==================== START ONBOARDING ====================

    /**
     * Test startOnboarding creates new record.
     */
    public function test_start_onboarding_creates_record(): void
    {
        $user = User::factory()->create();

        $result = $this->service->startOnboarding($user);

        $this->assertInstanceOf(UserOnboarding::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(1, $result->current_step);
        $this->assertDatabaseHas('user_onboarding', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test startOnboarding returns existing record if already exists.
     */
    public function test_start_onboarding_returns_existing(): void
    {
        $user = User::factory()->create();
        $existing = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 3,
        ]);

        $result = $this->service->startOnboarding($user);

        $this->assertEquals($existing->id, $result->id);
        $this->assertEquals(3, $result->current_step);
    }

    // ==================== UPDATE STEP ====================

    /**
     * Test updateStep with valid website URL.
     */
    public function test_update_step_website_url(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);

        $result = $this->service->updateStep($user, 1, [
            'website_url' => 'https://example.com',
        ]);

        $this->assertEquals('https://example.com', $result->website_url);
        $this->assertEquals(2, $result->current_step);
        $this->assertEquals([1], $result->completed_steps);
    }

    /**
     * Test updateStep with valid keyword.
     */
    public function test_update_step_keyword(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
            'website_url' => 'https://example.com',
        ]);

        $result = $this->service->updateStep($user, 2, [
            'first_keyword' => 'seo tools',
        ]);

        $this->assertEquals('seo tools', $result->first_keyword);
        $this->assertEquals(3, $result->current_step);
        $this->assertEquals([1, 2], $result->completed_steps);
    }

    /**
     * Test updateStep with alert preferences.
     */
    public function test_update_step_alert_preferences(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 4,
            'completed_steps' => [1, 2, 3],
        ]);

        $result = $this->service->updateStep($user, 4, [
            'alert_preferences' => ['email' => true, 'weekly' => false],
        ]);

        $this->assertEquals(['email' => true, 'weekly' => false], $result->alert_preferences);
        $this->assertEquals(5, $result->current_step);
        $this->assertContains(4, $result->completed_steps);
    }

    /**
     * Test updateStep throws validation error for invalid URL.
     */
    public function test_update_step_invalid_url(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->updateStep($user, 1, [
            'website_url' => 'not-a-valid-url',
        ]);
    }

    /**
     * Test updateStep throws validation error for SSRF URL.
     */
    public function test_update_step_ssrf_url(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->updateStep($user, 1, [
            'website_url' => 'http://localhost/test',
        ]);
    }

    /**
     * Test updateStep throws validation error for empty keyword.
     */
    public function test_update_step_empty_keyword(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
            'completed_steps' => [1],
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->updateStep($user, 2, [
            'first_keyword' => '',
        ]);
    }

    // ==================== COMPLETE STEP ====================

    /**
     * Test completeStep marks step as completed.
     */
    public function test_complete_step(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);

        $result = $this->service->completeStep($user, 1);

        $this->assertEquals([1], $result->completed_steps);
        $this->assertEquals(2, $result->current_step);
    }

    /**
     * Test completeStep handles multiple completions.
     */
    public function test_complete_step_multiple(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);

        $result = $this->service->completeStep($user, 1);
        $result = $this->service->completeStep($user, 2);

        $this->assertEquals([1, 2], $result->completed_steps);
        $this->assertEquals(3, $result->current_step);
    }

    // ==================== SKIP ONBOARDING ====================

    /**
     * Test skipOnboarding sets skipped_at.
     */
    public function test_skip_onboarding(): void
    {
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 2,
        ]);

        $result = $this->service->skipOnboarding($user);

        $this->assertNotNull($result->skipped_at);
    }

    /**
     * Test skipOnboarding creates record if not exists.
     */
    public function test_skip_onboarding_creates_record(): void
    {
        $user = User::factory()->create();

        $result = $this->service->skipOnboarding($user);

        $this->assertNotNull($result);
        $this->assertNotNull($result->skipped_at);
    }

    // ==================== COMPLETE ONBOARDING ====================

    /**
     * Test completeOnboarding sets completed_at.
     */
    public function test_complete_onboarding(): void
    {
        $user = User::factory()->create();
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 5,
            'completed_steps' => [1, 2, 3, 4],
        ]);

        $result = $this->service->completeOnboarding($user);

        $this->assertNotNull($result->completed_at);
    }

    /**
     * Test completeOnboarding creates keyword.
     */
    public function test_complete_onboarding_creates_keyword(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 5,
            'completed_steps' => [1, 2, 3, 4],
            'website_url' => 'https://example.com',
            'first_keyword' => 'seo tools',
        ]);

        $result = $this->service->completeOnboarding($user);

        $this->assertNotNull($result->keyword_id);
        $this->assertDatabaseHas('keywords', [
            'keyword' => 'seo tools',
        ]);
    }

    /**
     * Test completeOnboarding dispatches UserActivated event.
     */
    public function test_complete_onboarding_dispatches_event(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 5,
            'completed_steps' => [1, 2, 3, 4],
            'first_keyword' => 'seo tools',
        ]);

        $this->service->completeOnboarding($user);

        Event::assertDispatched(UserActivated::class);
    }

    /**
     * Test completeOnboarding without keyword.
     */
    public function test_complete_onboarding_without_keyword(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 5,
            'completed_steps' => [1, 2, 3, 4],
            'first_keyword' => null,
        ]);

        $result = $this->service->completeOnboarding($user);

        $this->assertNotNull($result->completed_at);
        $this->assertNull($result->keyword_id);
    }

    /**
     * Test completeOnboarding already completed.
     */
    public function test_complete_onboarding_already_completed(): void
    {
        $user = User::factory()->create();
        $existingTime = now()->subMinutes(5);
        UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => $existingTime,
        ]);

        $result = $this->service->completeOnboarding($user);

        // Should not change completed_at
        $this->assertEquals(
            $existingTime->timestamp,
            $result->completed_at->timestamp,
            '',
            1 // Allow 1 second difference
        );
    }

    // ==================== CALCULATE PROGRESS ====================

    /**
     * Test calculateProgressPercentage for step 1.
     */
    public function test_calculate_progress_step_1(): void
    {
        $onboarding = new UserOnboarding(['current_step' => 1]);

        $result = $this->service->calculateProgressPercentage($onboarding);

        $this->assertEquals(20, $result);
    }

    /**
     * Test calculateProgressPercentage for step 3.
     */
    public function test_calculate_progress_step_3(): void
    {
        $onboarding = new UserOnboarding(['current_step' => 3]);

        $result = $this->service->calculateProgressPercentage($onboarding);

        $this->assertEquals(60, $result);
    }

    /**
     * Test calculateProgressPercentage for step 5.
     */
    public function test_calculate_progress_step_5(): void
    {
        $onboarding = new UserOnboarding(['current_step' => 5]);

        $result = $this->service->calculateProgressPercentage($onboarding);

        $this->assertEquals(100, $result);
    }

    /**
     * Test calculateProgressPercentage for completed.
     */
    public function test_calculate_progress_completed(): void
    {
        $onboarding = new UserOnboarding([
            'current_step' => 5,
            'completed_at' => now(),
        ]);

        $result = $this->service->calculateProgressPercentage($onboarding);

        $this->assertEquals(100, $result);
    }

    // ==================== SSRF PROTECTION ====================

    /**
     * Test SSRF protection blocks localhost.
     */
    public function test_ssrf_blocks_localhost(): void
    {
        $this->assertFalse($this->service->isValidUrl('http://localhost/test'));
        $this->assertFalse($this->service->isValidUrl('http://localhost:8080/test'));
    }

    /**
     * Test SSRF protection blocks 127.0.0.1.
     */
    public function test_ssrf_blocks_127_0_0_1(): void
    {
        $this->assertFalse($this->service->isValidUrl('http://127.0.0.1/test'));
        $this->assertFalse($this->service->isValidUrl('http://127.0.0.1:8080/test'));
    }

    /**
     * Test SSRF protection blocks private IP 10.x.
     */
    public function test_ssrf_blocks_private_10(): void
    {
        $this->assertFalse($this->service->isValidUrl('http://10.0.0.1/test'));
        $this->assertFalse($this->service->isValidUrl('http://10.255.255.255/test'));
    }

    /**
     * Test SSRF protection blocks private IP 172.16-31.x.
     */
    public function test_ssrf_blocks_private_172(): void
    {
        $this->assertFalse($this->service->isValidUrl('http://172.16.0.1/test'));
        $this->assertFalse($this->service->isValidUrl('http://172.31.255.255/test'));
    }

    /**
     * Test SSRF protection blocks private IP 192.168.x.
     */
    public function test_ssrf_blocks_private_192(): void
    {
        $this->assertFalse($this->service->isValidUrl('http://192.168.1.1/test'));
        $this->assertFalse($this->service->isValidUrl('http://192.168.255.255/test'));
    }

    /**
     * Test SSRF protection blocks cloud metadata IP.
     */
    public function test_ssrf_blocks_cloud_metadata(): void
    {
        $this->assertFalse($this->service->isValidUrl('http://169.254.169.254/latest/meta-data'));
    }

    /**
     * Test SSRF protection blocks file protocol.
     */
    public function test_ssrf_blocks_file_protocol(): void
    {
        $this->assertFalse($this->service->isValidUrl('file:///etc/passwd'));
    }

    /**
     * Test SSRF protection blocks gopher protocol.
     */
    public function test_ssrf_blocks_gopher_protocol(): void
    {
        $this->assertFalse($this->service->isValidUrl('gopher://internal-host/test'));
    }

    /**
     * Test SSRF allows valid public URLs.
     */
    public function test_ssrf_allows_valid_public_urls(): void
    {
        $this->assertTrue($this->service->isValidUrl('https://example.com'));
        $this->assertTrue($this->service->isValidUrl('https://www.google.com/search?q=test'));
        $this->assertTrue($this->service->isValidUrl('https://subdomain.example.com/path?query=value'));
    }

    /**
     * Test SSRF rejects URL longer than 500 chars.
     */
    public function test_ssrf_rejects_long_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 500);
        
        $this->assertFalse($this->service->isValidUrl($longUrl));
    }
}
