<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\UserOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserOnboardingTest extends TestCase
{
    use RefreshDatabase;

    // ==================== MODEL CREATION ====================

    /**
     * Test model can be created with valid data.
     */
    public function test_model_can_be_created(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);
        
        $this->assertInstanceOf(UserOnboarding::class, $onboarding);
        $this->assertEquals($user->id, $onboarding->user_id);
        $this->assertEquals(1, $onboarding->current_step);
    }

    /**
     * Test model has all fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'current_step' => 2,
            'completed_steps' => [1],
            'website_url' => 'https://example.com',
            'first_keyword' => 'seo tools',
            'keyword_id' => $keyword->id,
            'alert_preferences' => ['email' => true, 'weekly' => false],
        ]);
        
        $this->assertEquals($user->id, $onboarding->user_id);
        $this->assertEquals($project->id, $onboarding->project_id);
        $this->assertEquals(2, $onboarding->current_step);
        $this->assertEquals([1], $onboarding->completed_steps);
        $this->assertEquals('https://example.com', $onboarding->website_url);
        $this->assertEquals('seo tools', $onboarding->first_keyword);
        $this->assertEquals($keyword->id, $onboarding->keyword_id);
        $this->assertEquals(['email' => true, 'weekly' => false], $onboarding->alert_preferences);
    }

    // ==================== CASTS ====================

    /**
     * Test completed_steps is cast to array.
     */
    public function test_completed_steps_cast_to_array(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'completed_steps' => [1, 2, 3],
        ]);
        
        $this->assertIsArray($onboarding->completed_steps);
        $this->assertEquals([1, 2, 3], $onboarding->completed_steps);
    }

    /**
     * Test completed_steps handles null.
     */
    public function test_completed_steps_handles_null(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'completed_steps' => null,
        ]);
        
        $this->assertNull($onboarding->completed_steps);
    }

    /**
     * Test alert_preferences is cast to array.
     */
    public function test_alert_preferences_cast_to_array(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'alert_preferences' => ['email' => true, 'weekly' => true],
        ]);
        
        $this->assertIsArray($onboarding->alert_preferences);
        $this->assertEquals(['email' => true, 'weekly' => true], $onboarding->alert_preferences);
    }

    /**
     * Test alert_preferences handles null.
     */
    public function test_alert_preferences_handles_null(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'alert_preferences' => null,
        ]);
        
        $this->assertNull($onboarding->alert_preferences);
    }

    /**
     * Test completed_at is cast to datetime.
     */
    public function test_completed_at_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $onboarding->completed_at);
    }

    /**
     * Test skipped_at is cast to datetime.
     */
    public function test_skipped_at_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'skipped_at' => now(),
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $onboarding->skipped_at);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Test belongsTo relationship with User.
     */
    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
        ]);
        
        $this->assertInstanceOf(User::class, $onboarding->user);
        $this->assertEquals($user->id, $onboarding->user->id);
    }

    /**
     * Test belongsTo relationship with Project.
     */
    public function test_belongs_to_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
        
        $this->assertInstanceOf(Project::class, $onboarding->project);
        $this->assertEquals($project->id, $onboarding->project->id);
    }

    /**
     * Test project relationship returns null when not set.
     */
    public function test_project_relationship_returns_null(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'project_id' => null,
        ]);
        
        $this->assertNull($onboarding->project);
    }

    /**
     * Test belongsTo relationship with Keyword.
     */
    public function test_belongs_to_keyword(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'keyword_id' => $keyword->id,
        ]);
        
        $this->assertInstanceOf(Keyword::class, $onboarding->keyword);
        $this->assertEquals($keyword->id, $onboarding->keyword->id);
    }

    /**
     * Test keyword relationship returns null when not set.
     */
    public function test_keyword_relationship_returns_null(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'keyword_id' => null,
        ]);
        
        $this->assertNull($onboarding->keyword);
    }

    // ==================== DEFAULT VALUES ====================

    /**
     * Test default current_step is 1.
     */
    public function test_default_current_step(): void
    {
        $user = User::factory()->create();
        
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
        ]);
        
        // Refresh to get database defaults
        $onboarding = UserOnboarding::find($onboarding->id);
        
        $this->assertEquals(1, $onboarding->current_step);
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Test isCompleted method.
     */
    public function test_is_completed(): void
    {
        $user = User::factory()->create();
        
        $completedOnboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);
        
        $incompleteOnboarding = UserOnboarding::create([
            'user_id' => User::factory()->create()->id,
            'completed_at' => null,
        ]);
        
        $this->assertTrue($completedOnboarding->isCompleted());
        $this->assertFalse($incompleteOnboarding->isCompleted());
    }

    /**
     * Test isSkipped method.
     */
    public function test_is_skipped(): void
    {
        $user = User::factory()->create();
        
        $skippedOnboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'skipped_at' => now(),
        ]);
        
        $activeOnboarding = UserOnboarding::create([
            'user_id' => User::factory()->create()->id,
            'skipped_at' => null,
        ]);
        
        $this->assertTrue($skippedOnboarding->isSkipped());
        $this->assertFalse($activeOnboarding->isSkipped());
    }

    /**
     * Test progressPercentage accessor.
     */
    public function test_progress_percentage_accessor(): void
    {
        $user = User::factory()->create();
        
        // Step 1 = 20%
        $onboarding1 = UserOnboarding::create([
            'user_id' => $user->id,
            'current_step' => 1,
        ]);
        $this->assertEquals(20, $onboarding1->progress_percentage);
        
        // Step 3 = 60%
        $onboarding3 = UserOnboarding::create([
            'user_id' => User::factory()->create()->id,
            'current_step' => 3,
        ]);
        $this->assertEquals(60, $onboarding3->progress_percentage);
        
        // Completed = 100%
        $onboardingComplete = UserOnboarding::create([
            'user_id' => User::factory()->create()->id,
            'current_step' => 5,
            'completed_at' => now(),
        ]);
        $this->assertEquals(100, $onboardingComplete->progress_percentage);
    }
}
