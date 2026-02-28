<?php

namespace Tests\Unit\Models;

use App\Models\SEOAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SEOAnalysis model
 * 
 * @see TASK-18-2
 * @see TEST-18-2
 */
class SEOAnalysisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test model creation with minimal data.
     * 
     * @test
     */
    public function create_with_minimal_data(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        $this->assertNotNull($analysis->id);
        $this->assertEquals('example.com', $analysis->domain);
        $this->assertEquals('https://example.com', $analysis->normalized_url);
        $this->assertEquals(50, $analysis->overall_score);
    }

    /**
     * Test model creation with user_id.
     * 
     * @test
     */
    public function create_with_user_id(): void
    {
        $user = User::factory()->create();

        $analysis = SEOAnalysis::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        $this->assertEquals($user->id, $analysis->user_id);
    }

    /**
     * Test model creation with session_id.
     * 
     * @test
     */
    public function create_with_session_id(): void
    {
        $analysis = SEOAnalysis::create([
            'session_id' => 'abc123def456',
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        $this->assertEquals('abc123def456', $analysis->session_id);
    }

    /**
     * Test model creation without user_id or session_id (anonymous).
     * 
     * @test
     */
    public function create_anonymous(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        $this->assertNull($analysis->user_id);
        $this->assertNull($analysis->session_id);
    }

    /**
     * Test model creation with all fields.
     * 
     * @test
     */
    public function create_with_all_fields(): void
    {
        $user = User::factory()->create();
        $now = now();
        $expires = $now->copy()->addDay();

        $analysis = SEOAnalysis::create([
            'user_id' => $user->id,
            'session_id' => 'session123',
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 78,
            'status' => 'completed',
            'error_message' => null,
            'analysis_data' => ['score' => 78],
            'checked_at' => $now,
            'expires_at' => $expires,
        ]);

        $this->assertEquals($user->id, $analysis->user_id);
        $this->assertEquals('session123', $analysis->session_id);
        $this->assertEquals('example.com', $analysis->domain);
        $this->assertEquals('https://example.com', $analysis->normalized_url);
        $this->assertEquals(78, $analysis->overall_score);
        $this->assertEquals('completed', $analysis->status);
        $this->assertEquals(['score' => 78], $analysis->analysis_data);
    }

    /**
     * Test factory creates valid model.
     * 
     * @test
     */
    public function factory_creates_valid_model(): void
    {
        $analysis = SEOAnalysis::factory()->create();

        $this->assertNotNull($analysis->id);
        $this->assertNotNull($analysis->domain);
        $this->assertNotNull($analysis->normalized_url);
        $this->assertNotNull($analysis->overall_score);
    }

    /**
     * Test JSON casting - store as array.
     * 
     * @test
     */
    public function json_casting_store_as_array(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => ['score' => 78, 'indicators' => []],
        ]);

        $this->assertIsArray($analysis->analysis_data);
        $this->assertEquals(78, $analysis->analysis_data['score']);
    }

    /**
     * Test JSON casting - retrieve as array.
     * 
     * @test
     */
    public function json_casting_retrieve_as_array(): void
    {
        $id = SEOAnalysis::insertGetId([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => json_encode(['nested' => ['key' => 'value']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analysis = SEOAnalysis::find($id);

        $this->assertIsArray($analysis->analysis_data);
        $this->assertEquals('value', $analysis->analysis_data['nested']['key']);
    }

    /**
     * Test JSON casting - nested JSON.
     * 
     * @test
     */
    public function json_casting_nested(): void
    {
        $data = [
            'overallScore' => 78,
            'categoryScores' => [
                'content' => 82,
                'technical' => 75,
            ],
            'indicators' => [
                ['id' => 'url', 'score' => 3],
                ['id' => 'title', 'score' => 2],
            ],
        ];

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => $data,
        ]);

        $this->assertEquals(82, $analysis->analysis_data['categoryScores']['content']);
        $this->assertCount(2, $analysis->analysis_data['indicators']);
    }

    /**
     * Test JSON casting - empty object.
     * 
     * @test
     */
    public function json_casting_empty_object(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        $this->assertIsArray($analysis->analysis_data);
        $this->assertEmpty($analysis->analysis_data);
    }

    /**
     * Test belongsTo relationship to User.
     * 
     * @test
     */
    public function belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $analysis = SEOAnalysis::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $analysis->user);
        $this->assertEquals($user->id, $analysis->user->id);
    }

    /**
     * Test belongsTo relationship returns null when no user.
     * 
     * @test
     */
    public function belongs_to_user_returns_null(): void
    {
        $analysis = SEOAnalysis::factory()->create(['user_id' => null]);

        $this->assertNull($analysis->user);
    }

    /**
     * Test User hasMany analyses relationship.
     * 
     * @test
     */
    public function user_has_many_analyses(): void
    {
        $user = User::factory()->create();
        SEOAnalysis::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->seoAnalyses);
    }

    /**
     * Test scope byStatus.
     * 
     * @test
     */
    public function scope_by_status(): void
    {
        SEOAnalysis::factory()->create(['status' => 'pending']);
        SEOAnalysis::factory()->create(['status' => 'completed']);
        SEOAnalysis::factory()->create(['status' => 'failed']);

        $completed = SEOAnalysis::byStatus('completed')->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status);
    }

    /**
     * Test scope byUser.
     * 
     * @test
     */
    public function scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        SEOAnalysis::factory()->count(2)->create(['user_id' => $user1->id]);
        SEOAnalysis::factory()->count(3)->create(['user_id' => $user2->id]);

        $user1Analyses = SEOAnalysis::byUser($user1->id)->get();

        $this->assertCount(2, $user1Analyses);
    }

    /**
     * Test scope bySession.
     * 
     * @test
     */
    public function scope_by_session(): void
    {
        SEOAnalysis::factory()->create(['session_id' => 'session1']);
        SEOAnalysis::factory()->create(['session_id' => 'session2']);
        SEOAnalysis::factory()->create(['session_id' => 'session1']);

        $session1Analyses = SEOAnalysis::bySession('session1')->get();

        $this->assertCount(2, $session1Analyses);
    }

    /**
     * Test scope expired.
     * 
     * @test
     */
    public function scope_expired(): void
    {
        SEOAnalysis::factory()->create(['expires_at' => now()->subHour()]);
        SEOAnalysis::factory()->create(['expires_at' => now()->addHour()]);
        SEOAnalysis::factory()->create(['expires_at' => null]);

        $expired = SEOAnalysis::expired()->get();

        $this->assertCount(1, $expired);
    }

    /**
     * Test scope notExpired.
     * 
     * @test
     */
    public function scope_not_expired(): void
    {
        SEOAnalysis::factory()->create(['expires_at' => now()->subHour()]);
        SEOAnalysis::factory()->create(['expires_at' => now()->addHour()]);
        SEOAnalysis::factory()->create(['expires_at' => null]);

        $notExpired = SEOAnalysis::notExpired()->get();

        $this->assertCount(2, $notExpired); // future + null
    }

    /**
     * Test scope forDomain.
     * 
     * @test
     */
    public function scope_for_domain(): void
    {
        SEOAnalysis::factory()->create(['domain' => 'example.com']);
        SEOAnalysis::factory()->create(['domain' => 'other.com']);
        SEOAnalysis::factory()->create(['domain' => 'example.com']);

        $domainAnalyses = SEOAnalysis::forDomain('example.com')->get();

        $this->assertCount(2, $domainAnalyses);
    }

    /**
     * Test scope pending.
     * 
     * @test
     */
    public function scope_pending(): void
    {
        SEOAnalysis::factory()->create(['status' => 'pending']);
        SEOAnalysis::factory()->create(['status' => 'completed']);

        $pending = SEOAnalysis::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    /**
     * Test scope completed.
     * 
     * @test
     */
    public function scope_completed(): void
    {
        SEOAnalysis::factory()->create(['status' => 'completed']);
        SEOAnalysis::factory()->create(['status' => 'failed']);

        $completed = SEOAnalysis::completed()->get();

        $this->assertCount(1, $completed);
    }

    /**
     * Test scope failed.
     * 
     * @test
     */
    public function scope_failed(): void
    {
        SEOAnalysis::factory()->create(['status' => 'failed']);
        SEOAnalysis::factory()->create(['status' => 'completed']);

        $failed = SEOAnalysis::failed()->get();

        $this->assertCount(1, $failed);
    }

    /**
     * Test chained scopes.
     * 
     * @test
     */
    public function chained_scopes(): void
    {
        $user = User::factory()->create();

        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);
        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
            'created_at' => now(),
        ]);
        SEOAnalysis::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'created_at' => now()->subDays(10),
        ]);

        $results = SEOAnalysis::byUser($user->id)
            ->completed()
            ->recent(7)
            ->get();

        $this->assertCount(1, $results);
    }

    /**
     * Test markAsRunning method.
     * 
     * @test
     */
    public function mark_as_running(): void
    {
        $analysis = SEOAnalysis::factory()->create(['status' => 'pending']);

        $analysis->markAsRunning();

        $this->assertEquals('running', $analysis->fresh()->status);
    }

    /**
     * Test markAsCompleted method.
     * 
     * @test
     */
    public function mark_as_completed(): void
    {
        $analysis = SEOAnalysis::factory()->create(['status' => 'running']);
        $data = ['indicators' => []];

        $analysis->markAsCompleted(85, $data);

        $fresh = $analysis->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals(85, $fresh->overall_score);
        $this->assertEquals($data, $fresh->analysis_data);
        $this->assertNotNull($fresh->checked_at);
    }

    /**
     * Test markAsFailed method.
     * 
     * @test
     */
    public function mark_as_failed(): void
    {
        $analysis = SEOAnalysis::factory()->create(['status' => 'running']);

        $analysis->markAsFailed('Connection timeout');

        $fresh = $analysis->fresh();
        $this->assertEquals('failed', $fresh->status);
        $this->assertEquals('Connection timeout', $fresh->error_message);
    }

    /**
     * Test isExpired method - expired.
     * 
     * @test
     */
    public function is_expired_true(): void
    {
        $analysis = SEOAnalysis::factory()->make(['expires_at' => now()->subHour()]);

        $this->assertTrue($analysis->isExpired());
    }

    /**
     * Test isExpired method - not expired.
     * 
     * @test
     */
    public function is_expired_false(): void
    {
        $analysis = SEOAnalysis::factory()->make(['expires_at' => now()->addHour()]);

        $this->assertFalse($analysis->isExpired());
    }

    /**
     * Test isExpired method - null expires_at.
     * 
     * @test
     */
    public function is_expired_null(): void
    {
        $analysis = SEOAnalysis::factory()->make(['expires_at' => null]);

        $this->assertFalse($analysis->isExpired());
    }

    /**
     * Test status helper methods.
     * 
     * @test
     */
    public function status_helper_methods(): void
    {
        $pending = SEOAnalysis::factory()->make(['status' => 'pending']);
        $running = SEOAnalysis::factory()->make(['status' => 'running']);
        $completed = SEOAnalysis::factory()->make(['status' => 'completed']);
        $failed = SEOAnalysis::factory()->make(['status' => 'failed']);

        $this->assertTrue($pending->isPending());
        $this->assertTrue($running->isRunning());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($failed->isFailed());
    }

    /**
     * Test status label accessor.
     * 
     * @test
     */
    public function status_label_accessor(): void
    {
        $analysis = SEOAnalysis::factory()->make(['status' => 'completed']);

        $this->assertEquals('Completed', $analysis->status_label);
    }

    /**
     * Test SQL injection protection.
     * 
     * @test
     */
    public function sql_injection_protection(): void
    {
        $maliciousDomain = "example.com'; DROP TABLE seo_analyses;--";

        $analysis = SEOAnalysis::create([
            'domain' => $maliciousDomain,
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        // If SQL injection succeeded, table would be dropped
        $this->assertDatabaseHas('seo_analyses', ['id' => $analysis->id]);
    }

    /**
     * Test XSS protection - error_message field.
     * 
     * @test
     */
    public function xss_protection_error_message(): void
    {
        $xss = '<script>alert(1)</script>';

        $analysis = SEOAnalysis::create([
            'domain' => 'example.com',
            'normalized_url' => 'https://example.com',
            'overall_score' => 50,
            'analysis_data' => [],
            'error_message' => $xss,
        ]);

        $this->assertEquals($xss, $analysis->error_message);
        // Output escaping happens at view layer
    }

    /**
     * Test Unicode domain handling.
     * 
     * @test
     */
    public function unicode_domain_handling(): void
    {
        $analysis = SEOAnalysis::create([
            'domain' => '例え.jp',
            'normalized_url' => 'https://xn--r8jz45g.jp',
            'overall_score' => 50,
            'analysis_data' => [],
        ]);

        $this->assertEquals('例え.jp', $analysis->domain);
    }
}
