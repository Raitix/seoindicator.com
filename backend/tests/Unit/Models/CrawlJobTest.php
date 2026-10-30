<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Unit tests for the CrawlJob model.
 *
 * @see TASK-22-2
 * @see SPEC-022
 */
class CrawlJobTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Creation and Mass Assignment Tests
    // ==========================================

    /**
     * Test creating a crawl job with valid data.
     */
    public function test_create_crawl_job(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $crawlJob = CrawlJob::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'job_id' => 'crawl-test-123',
            'url' => 'https://example.com',
            'status' => CrawlJob::STATUS_QUEUED,
            'depth' => 1,
            'options' => ['waitTime' => 2000],
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertDatabaseHas('crawl_jobs', [
            'id' => $crawlJob->id,
            'job_id' => 'crawl-test-123',
            'url' => 'https://example.com',
        ]);
    }

    /**
     * Test mass assignment fillable fields.
     */
    public function test_mass_assignment_fillable(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $data = [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'job_id' => 'crawl-fillable-test',
            'url' => 'https://test.com',
            'status' => CrawlJob::STATUS_QUEUED,
            'depth' => 2,
            'options' => ['waitTime' => 3000],
            'pages_crawled' => 5,
            'total_size' => 1024000,
            'error' => null,
            'started_at' => now(),
            'completed_at' => null,
            'expires_at' => now()->addDays(7),
        ];

        $crawlJob = CrawlJob::create($data);

        $this->assertEquals($data['job_id'], $crawlJob->job_id);
        $this->assertEquals($data['url'], $crawlJob->url);
        $this->assertEquals($data['status'], $crawlJob->status);
        $this->assertEquals($data['depth'], $crawlJob->depth);
        $this->assertEquals($data['pages_crawled'], $crawlJob->pages_crawled);
        $this->assertEquals($data['total_size'], $crawlJob->total_size);
    }

    /**
     * Test options cast to array.
     */
    public function test_options_cast_to_array(): void
    {
        $crawlJob = CrawlJob::factory()->create([
            'options' => [
                'waitForSelector' => '.content',
                'waitTime' => 2000,
                'userAgent' => 'TestBot/1.0',
            ],
        ]);

        $this->assertIsArray($crawlJob->options);
        $this->assertEquals('.content', $crawlJob->options['waitForSelector']);
        $this->assertEquals(2000, $crawlJob->options['waitTime']);
        $this->assertEquals('TestBot/1.0', $crawlJob->options['userAgent']);
    }

    /**
     * Test null options.
     */
    public function test_null_options(): void
    {
        $crawlJob = CrawlJob::factory()->create(['options' => null]);

        $this->assertNull($crawlJob->options);
    }

    /**
     * Test timestamp casts to Carbon instances.
     */
    public function test_timestamp_casts(): void
    {
        $now = now();
        $crawlJob = CrawlJob::factory()->create([
            'started_at' => $now,
            'completed_at' => $now->copy()->addMinutes(5),
            'expires_at' => $now->copy()->addDays(7),
        ]);

        $this->assertInstanceOf(Carbon::class, $crawlJob->started_at);
        $this->assertInstanceOf(Carbon::class, $crawlJob->completed_at);
        $this->assertInstanceOf(Carbon::class, $crawlJob->expires_at);
        $this->assertInstanceOf(Carbon::class, $crawlJob->created_at);
        $this->assertInstanceOf(Carbon::class, $crawlJob->updated_at);
    }

    /**
     * Test depth cast to integer.
     */
    public function test_depth_cast_to_integer(): void
    {
        $crawlJob = CrawlJob::factory()->create(['depth' => 2]);

        $this->assertIsInt($crawlJob->depth);
        $this->assertEquals(2, $crawlJob->depth);
    }

    /**
     * Test pages_crawled cast to integer.
     */
    public function test_pages_crawled_cast_to_integer(): void
    {
        $crawlJob = CrawlJob::factory()->create(['pages_crawled' => 10]);

        $this->assertIsInt($crawlJob->pages_crawled);
        $this->assertEquals(10, $crawlJob->pages_crawled);
    }

    /**
     * Test total_size cast to integer.
     */
    public function test_total_size_cast_to_integer(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 1048576]);

        $this->assertIsInt($crawlJob->total_size);
        $this->assertEquals(1048576, $crawlJob->total_size);
    }

    // ==========================================
    // Status Constants Tests
    // ==========================================

    /**
     * Test status constants are defined.
     */
    public function test_status_constants(): void
    {
        $this->assertEquals('queued', CrawlJob::STATUS_QUEUED);
        $this->assertEquals('running', CrawlJob::STATUS_RUNNING);
        $this->assertEquals('completed', CrawlJob::STATUS_COMPLETED);
        $this->assertEquals('failed', CrawlJob::STATUS_FAILED);
        $this->assertEquals('cancelled', CrawlJob::STATUS_CANCELLED);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    /**
     * Test crawl job belongs to a user.
     */
    public function test_crawl_job_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $crawlJob->user);
        $this->assertEquals($user->id, $crawlJob->user->id);
    }

    /**
     * Test user relationship is BelongsTo.
     */
    public function test_user_relationship_type(): void
    {
        $crawlJob = CrawlJob::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $crawlJob->user());
    }

    /**
     * Test crawl job belongs to a project (nullable).
     */
    public function test_crawl_job_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $crawlJob = CrawlJob::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $crawlJob->project);
        $this->assertEquals($project->id, $crawlJob->project->id);
    }

    /**
     * Test crawl job can have null project.
     */
    public function test_crawl_job_nullable_project(): void
    {
        $crawlJob = CrawlJob::factory()->withoutProject()->create();

        $this->assertNull($crawlJob->project_id);
        $this->assertNull($crawlJob->project);
    }

    /**
     * Test project relationship is BelongsTo.
     */
    public function test_project_relationship_type(): void
    {
        $crawlJob = CrawlJob::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $crawlJob->project());
    }

    /**
     * Test crawl job has many pages.
     */
    public function test_crawl_job_has_many_pages(): void
    {
        $crawlJob = CrawlJob::factory()->create();
        CrawlPage::factory()->count(3)->forJob($crawlJob)->create();

        $this->assertCount(3, $crawlJob->pages);
    }

    /**
     * Test pages relationship is HasMany.
     */
    public function test_pages_relationship_type(): void
    {
        $crawlJob = CrawlJob::factory()->create();

        $this->assertInstanceOf(HasMany::class, $crawlJob->pages());
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    /**
     * Test scopeQueued returns only queued jobs.
     */
    public function test_scope_queued(): void
    {
        CrawlJob::factory()->queued()->create();
        CrawlJob::factory()->running()->create();
        CrawlJob::factory()->completed()->create();

        $queuedJobs = CrawlJob::queued()->get();

        $this->assertEquals(1, $queuedJobs->count());
        foreach ($queuedJobs as $job) {
            $this->assertEquals(CrawlJob::STATUS_QUEUED, $job->status);
        }
    }

    /**
     * Test scopeRunning returns only running jobs.
     */
    public function test_scope_running(): void
    {
        CrawlJob::factory()->queued()->create();
        CrawlJob::factory()->running()->create();
        CrawlJob::factory()->completed()->create();

        $runningJobs = CrawlJob::running()->get();

        $this->assertEquals(1, $runningJobs->count());
        foreach ($runningJobs as $job) {
            $this->assertEquals(CrawlJob::STATUS_RUNNING, $job->status);
        }
    }

    /**
     * Test scopeCompleted returns only completed jobs.
     */
    public function test_scope_completed(): void
    {
        CrawlJob::factory()->queued()->create();
        CrawlJob::factory()->running()->create();
        CrawlJob::factory()->completed()->create();
        CrawlJob::factory()->failed()->create();

        $completedJobs = CrawlJob::completed()->get();

        $this->assertEquals(1, $completedJobs->count());
        foreach ($completedJobs as $job) {
            $this->assertEquals(CrawlJob::STATUS_COMPLETED, $job->status);
        }
    }

    /**
     * Test scopeFailed returns only failed jobs.
     */
    public function test_scope_failed(): void
    {
        CrawlJob::factory()->queued()->create();
        CrawlJob::factory()->failed()->create();
        CrawlJob::factory()->completed()->create();

        $failedJobs = CrawlJob::failed()->get();

        $this->assertEquals(1, $failedJobs->count());
        foreach ($failedJobs as $job) {
            $this->assertEquals(CrawlJob::STATUS_FAILED, $job->status);
        }
    }

    /**
     * Test scopeCancelled returns only cancelled jobs.
     */
    public function test_scope_cancelled(): void
    {
        CrawlJob::factory()->queued()->create();
        CrawlJob::factory()->cancelled()->create();

        $cancelledJobs = CrawlJob::cancelled()->get();

        $this->assertEquals(1, $cancelledJobs->count());
        foreach ($cancelledJobs as $job) {
            $this->assertEquals(CrawlJob::STATUS_CANCELLED, $job->status);
        }
    }

    /**
     * Test scopeForUser returns jobs for specific user.
     */
    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CrawlJob::factory()->forUser($user1)->create();
        CrawlJob::factory()->forUser($user1)->create();
        CrawlJob::factory()->forUser($user2)->create();

        $user1Jobs = CrawlJob::forUser($user1->id)->get();

        $this->assertEquals(2, $user1Jobs->count());
        foreach ($user1Jobs as $job) {
            $this->assertEquals($user1->id, $job->user_id);
        }
    }

    /**
     * Test scopeForProject returns jobs for specific project.
     */
    public function test_scope_for_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        CrawlJob::factory()->forProject($project1)->create();
        CrawlJob::factory()->forProject($project1)->create();
        CrawlJob::factory()->forProject($project2)->create();

        $project1Jobs = CrawlJob::forProject($project1->id)->get();

        $this->assertEquals(2, $project1Jobs->count());
        foreach ($project1Jobs as $job) {
            $this->assertEquals($project1->id, $job->project_id);
        }
    }

    /**
     * Test scopeExpired returns jobs with expires_at in the past.
     */
    public function test_scope_expired(): void
    {
        CrawlJob::factory()->expired()->create();
        CrawlJob::factory()->create(['expires_at' => now()->addDays(7)]);

        $expiredJobs = CrawlJob::expired()->get();

        $this->assertEquals(1, $expiredJobs->count());
        foreach ($expiredJobs as $job) {
            $this->assertTrue($job->expires_at->isPast());
        }
    }

    /**
     * Test chaining multiple scopes.
     */
    public function test_scope_chaining(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        CrawlJob::factory()->forUser($user)->forProject($project)->completed()->create();
        CrawlJob::factory()->forUser($user)->queued()->create();
        CrawlJob::factory()->forUser($user)->completed()->create();

        $jobs = CrawlJob::forUser($user->id)
            ->forProject($project->id)
            ->completed()
            ->get();

        $this->assertEquals(1, $jobs->count());
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    /**
     * Test isComplete accessor for completed status.
     */
    public function test_is_complete_accessor_completed(): void
    {
        $crawlJob = CrawlJob::factory()->completed()->create();

        $this->assertTrue($crawlJob->is_complete);
    }

    /**
     * Test isComplete accessor for failed status.
     */
    public function test_is_complete_accessor_failed(): void
    {
        $crawlJob = CrawlJob::factory()->failed()->create();

        $this->assertTrue($crawlJob->is_complete);
    }

    /**
     * Test isComplete accessor for cancelled status.
     */
    public function test_is_complete_accessor_cancelled(): void
    {
        $crawlJob = CrawlJob::factory()->cancelled()->create();

        $this->assertTrue($crawlJob->is_complete);
    }

    /**
     * Test isComplete accessor for queued status.
     */
    public function test_is_complete_accessor_queued(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create();

        $this->assertFalse($crawlJob->is_complete);
    }

    /**
     * Test isComplete accessor for running status.
     */
    public function test_is_complete_accessor_running(): void
    {
        $crawlJob = CrawlJob::factory()->running()->create();

        $this->assertFalse($crawlJob->is_complete);
    }

    /**
     * Test duration accessor calculates correctly.
     */
    public function test_duration_accessor_calculates_correctly(): void
    {
        $started = now()->subMinutes(5);
        $completed = now();

        $crawlJob = CrawlJob::factory()->create([
            'started_at' => $started,
            'completed_at' => $completed,
        ]);

        // Duration should be approximately 300 seconds (5 minutes)
        $this->assertGreaterThanOrEqual(299, $crawlJob->duration);
        $this->assertLessThanOrEqual(301, $crawlJob->duration);
    }

    /**
     * Test duration accessor returns null when started_at is null.
     */
    public function test_duration_accessor_null_when_no_started_at(): void
    {
        $crawlJob = CrawlJob::factory()->create([
            'started_at' => null,
            'completed_at' => now(),
        ]);

        $this->assertNull($crawlJob->duration);
    }

    /**
     * Test duration accessor returns null when completed_at is null.
     */
    public function test_duration_accessor_null_when_no_completed_at(): void
    {
        $crawlJob = CrawlJob::factory()->create([
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->assertNull($crawlJob->duration);
    }

    /**
     * Test sizeFormatted accessor formats bytes correctly.
     */
    public function test_size_formatted_accessor_bytes(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 512]);

        $this->assertEquals('512.0 B', $crawlJob->size_formatted);
    }

    /**
     * Test sizeFormatted accessor formats kilobytes correctly.
     */
    public function test_size_formatted_accessor_kilobytes(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 1024]);

        $this->assertEquals('1.0 KB', $crawlJob->size_formatted);
    }

    /**
     * Test sizeFormatted accessor formats megabytes correctly.
     */
    public function test_size_formatted_accessor_megabytes(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 1048576]);

        $this->assertEquals('1.0 MB', $crawlJob->size_formatted);
    }

    /**
     * Test sizeFormatted accessor formats large values correctly.
     */
    public function test_size_formatted_accessor_large(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 1572864]); // 1.5 MB

        $formatted = $crawlJob->size_formatted;
        $this->assertStringContainsString('MB', $formatted);
    }

    // ==========================================
    // Helper Method Tests
    // ==========================================

    /**
     * Test canBeCancelled returns true for queued job.
     */
    public function test_can_be_cancelled_queued(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create();

        $this->assertTrue($crawlJob->canBeCancelled());
    }

    /**
     * Test canBeCancelled returns true for running job.
     */
    public function test_can_be_cancelled_running(): void
    {
        $crawlJob = CrawlJob::factory()->running()->create();

        $this->assertTrue($crawlJob->canBeCancelled());
    }

    /**
     * Test canBeCancelled returns false for completed job.
     */
    public function test_can_be_cancelled_completed(): void
    {
        $crawlJob = CrawlJob::factory()->completed()->create();

        $this->assertFalse($crawlJob->canBeCancelled());
    }

    /**
     * Test canBeCancelled returns false for failed job.
     */
    public function test_can_be_cancelled_failed(): void
    {
        $crawlJob = CrawlJob::factory()->failed()->create();

        $this->assertFalse($crawlJob->canBeCancelled());
    }

    /**
     * Test getContentUrlAttribute returns correct URL.
     */
    public function test_get_content_url_attribute(): void
    {
        $crawlJob = CrawlJob::factory()->create(['job_id' => 'crawl-abc123']);

        $this->assertEquals('/api/v1/crawler/jobs/crawl-abc123/content', $crawlJob->content_url);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    /**
     * Test job_id is unique.
     */
    public function test_job_id_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CrawlJob::factory()->create(['job_id' => 'crawl-unique-test']);
        CrawlJob::factory()->create(['job_id' => 'crawl-unique-test']);
    }

    /**
     * Test zero depth is allowed.
     */
    public function test_zero_depth_allowed(): void
    {
        $crawlJob = CrawlJob::factory()->create(['depth' => 0]);

        $this->assertEquals(0, $crawlJob->depth);
    }

    /**
     * Test maximum depth (2 per spec).
     */
    public function test_maximum_depth(): void
    {
        $crawlJob = CrawlJob::factory()->create(['depth' => 2]);

        $this->assertEquals(2, $crawlJob->depth);
    }

    /**
     * Test empty options array.
     */
    public function test_empty_options_array(): void
    {
        $crawlJob = CrawlJob::factory()->create(['options' => []]);

        $this->assertEquals([], $crawlJob->options);
    }

    /**
     * Test zero pages_crawled.
     */
    public function test_zero_pages_crawled(): void
    {
        $crawlJob = CrawlJob::factory()->create(['pages_crawled' => 0]);

        $this->assertEquals(0, $crawlJob->pages_crawled);
    }

    /**
     * Test zero total_size.
     */
    public function test_zero_total_size(): void
    {
        $crawlJob = CrawlJob::factory()->create(['total_size' => 0]);

        $this->assertEquals(0, $crawlJob->total_size);
        $this->assertEquals('0.0 B', $crawlJob->size_formatted);
    }

    /**
     * Test long error message.
     */
    public function test_long_error_message(): void
    {
        $longError = str_repeat('Error message. ', 100);
        $crawlJob = CrawlJob::factory()->failed()->create(['error' => $longError]);

        $this->assertEquals($longError, $crawlJob->error);
    }

    /**
     * Test URL max length (2083 chars per RFC).
     */
    public function test_url_max_length(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2060);

        $crawlJob = CrawlJob::factory()->create(['url' => $longUrl]);

        $this->assertEquals($longUrl, $crawlJob->url);
        $this->assertLessThanOrEqual(2083, strlen($crawlJob->url));
    }

    // ==========================================
    // Cascade Delete Tests
    // ==========================================

    /**
     * Test crawl job deleted when user is deleted.
     */
    public function test_crawl_job_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('crawl_jobs', ['id' => $crawlJob->id]);
    }

    /**
     * Test project_id set to null when project is deleted.
     */
    public function test_project_id_null_when_project_deleted(): void
    {
        $project = Project::factory()->create();
        $crawlJob = CrawlJob::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $crawlJob->refresh();
        $this->assertNull($crawlJob->project_id);
    }

    /**
     * Test pages deleted when crawl job is deleted.
     */
    public function test_pages_deleted_when_crawl_job_deleted(): void
    {
        $crawlJob = CrawlJob::factory()->create();
        $page = CrawlPage::factory()->forJob($crawlJob)->create();

        $crawlJob->delete();

        $this->assertDatabaseMissing('crawl_pages', ['id' => $page->id]);
    }

    // ==========================================
    // Factory State Tests
    // ==========================================

    /**
     * Test factory state queued().
     */
    public function test_factory_state_queued(): void
    {
        $crawlJob = CrawlJob::factory()->queued()->create();

        $this->assertEquals(CrawlJob::STATUS_QUEUED, $crawlJob->status);
    }

    /**
     * Test factory state running().
     */
    public function test_factory_state_running(): void
    {
        $crawlJob = CrawlJob::factory()->running()->create();

        $this->assertEquals(CrawlJob::STATUS_RUNNING, $crawlJob->status);
        $this->assertNotNull($crawlJob->started_at);
    }

    /**
     * Test factory state completed().
     */
    public function test_factory_state_completed(): void
    {
        $crawlJob = CrawlJob::factory()->completed()->create();

        $this->assertEquals(CrawlJob::STATUS_COMPLETED, $crawlJob->status);
        $this->assertNotNull($crawlJob->started_at);
        $this->assertNotNull($crawlJob->completed_at);
        $this->assertGreaterThan(0, $crawlJob->pages_crawled);
    }

    /**
     * Test factory state failed().
     */
    public function test_factory_state_failed(): void
    {
        $crawlJob = CrawlJob::factory()->failed()->create();

        $this->assertEquals(CrawlJob::STATUS_FAILED, $crawlJob->status);
        $this->assertNotNull($crawlJob->error);
    }

    /**
     * Test factory state cancelled().
     */
    public function test_factory_state_cancelled(): void
    {
        $crawlJob = CrawlJob::factory()->cancelled()->create();

        $this->assertEquals(CrawlJob::STATUS_CANCELLED, $crawlJob->status);
    }

    /**
     * Test factory state expired().
     */
    public function test_factory_state_expired(): void
    {
        $crawlJob = CrawlJob::factory()->expired()->create();

        $this->assertTrue($crawlJob->expires_at->isPast());
    }

    /**
     * Test factory state withoutProject().
     */
    public function test_factory_state_without_project(): void
    {
        $crawlJob = CrawlJob::factory()->withoutProject()->create();

        $this->assertNull($crawlJob->project_id);
    }

    /**
     * Test factory state forUser().
     */
    public function test_factory_state_for_user(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $crawlJob->user_id);
    }

    /**
     * Test factory state forProject().
     */
    public function test_factory_state_for_project(): void
    {
        $project = Project::factory()->create();
        $crawlJob = CrawlJob::factory()->forProject($project)->create();

        $this->assertEquals($project->id, $crawlJob->project_id);
    }

    /**
     * Test factory state withDepth().
     */
    public function test_factory_state_with_depth(): void
    {
        $crawlJob = CrawlJob::factory()->withDepth(2)->create();

        $this->assertEquals(2, $crawlJob->depth);
    }

    /**
     * Test factory state withUrl().
     */
    public function test_factory_state_with_url(): void
    {
        $url = 'https://custom-url.com/page';
        $crawlJob = CrawlJob::factory()->withUrl($url)->create();

        $this->assertEquals($url, $crawlJob->url);
    }
}
