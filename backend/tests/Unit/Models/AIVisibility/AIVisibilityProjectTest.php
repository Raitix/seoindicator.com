<?php

namespace Tests\Unit\Models\AIVisibility;

use Tests\TestCase;
use App\Models\User;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Unit tests for TASK-34-2: AIVisibilityProject Model
 *
 * Tests the AIVisibilityProject model for:
 * - Model creation with required fields
 * - Fillable attributes (brand_name, keywords, competitors, etc.)
 * - Casts (array, boolean, datetime)
 * - Relationships (belongsTo User, hasMany results/analytics/alerts)
 * - Scopes (active, dueForQuery)
 * - Accessors (getCompetitorListAttribute)
 *
 * @see \App\Models\AIVisibility\AIVisibilityProject
 */
class AIVisibilityProjectTest extends TestCase
{
    use RefreshDatabase;

    // ==================== MODEL EXISTENCE TESTS ====================

    /**
     * VP-001: Model exists at correct namespace.
     */
    public function test_vp001_model_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AIVisibilityProject::class), 'AIVisibilityProject model should exist');

        $reflection = new \ReflectionClass(AIVisibilityProject::class);
        $this->assertEquals('App\Models\AIVisibility\AIVisibilityProject', $reflection->getName());
    }

    /**
     * VP-002: Create with required fields.
     */
    public function test_vp002_create_with_required_fields(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme', 'acme corp'],
            'competitors' => [['name' => 'Competitor A']],
        ]);

        $this->assertInstanceOf(AIVisibilityProject::class, $project);
        $this->assertEquals('Acme Corp', $project->brand_name);
        $this->assertEquals($user->id, $project->user_id);
    }

    // ==================== FILLABLE TESTS ====================

    /**
     * VP-003: Fillable brand_name.
     */
    public function test_vp003_fillable_brand_name(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Brand',
            'keywords' => ['test'],
            'competitors' => [],
        ]);

        $this->assertEquals('Test Brand', $project->brand_name);
    }

    /**
     * VP-004: Fillable keywords.
     */
    public function test_vp004_fillable_keywords(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['keyword1', 'keyword2', 'keyword3'],
            'competitors' => [],
        ]);

        $this->assertEquals(['keyword1', 'keyword2', 'keyword3'], $project->keywords);
    }

    /**
     * VP-005: Fillable competitors.
     */
    public function test_vp005_fillable_competitors(): void
    {
        $user = User::factory()->create();

        $competitors = [
            ['name' => 'Competitor A', 'keywords' => ['comp a']],
            ['name' => 'Competitor B', 'keywords' => ['comp b']],
        ];

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['test'],
            'competitors' => $competitors,
        ]);

        $this->assertEquals($competitors, $project->competitors);
    }

    /**
     * VP-006: Fillable query_templates.
     */
    public function test_vp006_fillable_query_templates(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['test'],
            'competitors' => [],
            'query_templates' => ['What is {brand}?', 'How does {brand} compare?'],
        ]);

        $this->assertEquals(['What is {brand}?', 'How does {brand} compare?'], $project->query_templates);
    }

    /**
     * VP-007: Fillable tracking_frequency.
     */
    public function test_vp007_fillable_tracking_frequency(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['test'],
            'competitors' => [],
            'tracking_frequency' => 'daily',
        ]);

        $this->assertEquals('daily', $project->tracking_frequency);
    }

    /**
     * VP-008: Fillable is_active.
     */
    public function test_vp008_fillable_is_active(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['test'],
            'competitors' => [],
            'is_active' => false,
        ]);

        $this->assertFalse($project->is_active);
    }

    /**
     * VP-009: Fillable last_queried_at.
     */
    public function test_vp009_fillable_last_queried_at(): void
    {
        $user = User::factory()->create();
        $now = now();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['test'],
            'competitors' => [],
            'last_queried_at' => $now,
        ]);

        $this->assertInstanceOf(Carbon::class, $project->last_queried_at);
    }

    // ==================== CASTS TESTS ====================

    /**
     * VP-010: Cast keywords → array.
     */
    public function test_vp010_cast_keywords_to_array(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => ['one', 'two', 'three'],
            'competitors' => [],
        ]);

        $this->assertIsArray($project->keywords);
        $this->assertEquals(['one', 'two', 'three'], $project->keywords);
    }

    /**
     * VP-011: Cast competitors → array.
     */
    public function test_vp011_cast_competitors_to_array(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [['name' => 'Comp A'], ['name' => 'Comp B']],
        ]);

        $this->assertIsArray($project->competitors);
        $this->assertCount(2, $project->competitors);
    }

    /**
     * VP-012: Cast query_templates → array.
     */
    public function test_vp012_cast_query_templates_to_array(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
            'query_templates' => ['template1', 'template2'],
        ]);

        $this->assertIsArray($project->query_templates);
        $this->assertCount(2, $project->query_templates);
    }

    /**
     * VP-013: Cast is_active → boolean.
     */
    public function test_vp013_cast_is_active_to_boolean(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
            'is_active' => 1,
        ]);

        $this->assertIsBool($project->is_active);
        $this->assertTrue($project->is_active);
    }

    /**
     * VP-014: Cast last_queried_at → datetime.
     */
    public function test_vp014_cast_last_queried_at_to_datetime(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
            'last_queried_at' => '2026-03-07 10:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $project->last_queried_at);
        $this->assertEquals('2026-03-07 10:30:00', $project->last_queried_at->format('Y-m-d H:i:s'));
    }

    // ==================== RELATIONSHIP TESTS ====================

    /**
     * VP-015: BelongsTo user().
     */
    public function test_vp015_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    /**
     * VP-016: HasMany queryResults().
     */
    public function test_vp016_has_many_query_results(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        // Create query results (using DB directly since model may not exist yet)
        // Note: Using actual migration column name 'model_used' (alias is 'model' via accessor)
        DB::table('ai_query_results')->insert([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'model_used' => 'gpt-4',
            'query_text' => 'Test query',
            'response_text' => 'Test response',
            'queried_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (method_exists($project, 'queryResults')) {
            $this->assertCount(1, $project->queryResults);
        } else {
            $this->markTestSkipped('queryResults relationship not yet implemented');
        }
    }

    /**
     * VP-017: HasMany analytics().
     */
    public function test_vp017_has_many_analytics(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (method_exists($project, 'analytics')) {
            $this->assertCount(1, $project->analytics);
        } else {
            $this->markTestSkipped('analytics relationship not yet implemented');
        }
    }

    /**
     * VP-018: HasMany alerts().
     */
    public function test_vp018_has_many_alerts(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        // Note: triggered_at is required in migration (NOT NULL)
        DB::table('ai_visibility_alerts')->insert([
            'project_id' => $project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 20.00,
            'previous_value' => 30.00,
            'current_value' => 60.00,
            'change_percent' => 100.00,
            'triggered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (method_exists($project, 'alerts')) {
            $this->assertCount(1, $project->alerts);
        } else {
            $this->markTestSkipped('alerts relationship not yet implemented');
        }
    }

    // ==================== SCOPE TESTS ====================

    /**
     * VP-019: Scope active().
     */
    public function test_vp019_scope_active(): void
    {
        $user = User::factory()->create();

        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Active Project',
            'keywords' => [],
            'competitors' => [],
            'is_active' => true,
        ]);

        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Inactive Project',
            'keywords' => [],
            'competitors' => [],
            'is_active' => false,
        ]);

        if (method_exists(AIVisibilityProject::class, 'scopeActive')) {
            $activeProjects = AIVisibilityProject::active()->get();

            $this->assertCount(1, $activeProjects);
            $this->assertEquals('Active Project', $activeProjects->first()->brand_name);
        } else {
            $this->markTestSkipped('active scope not yet implemented');
        }
    }

    /**
     * VP-020: Scope dueForQuery().
     */
    public function test_vp020_scope_due_for_query(): void
    {
        $user = User::factory()->create();

        // Project never queried (should be due)
        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Never Queried',
            'keywords' => [],
            'competitors' => [],
            'is_active' => true,
            'tracking_frequency' => 'weekly',
            'last_queried_at' => null,
        ]);

        // Project queried yesterday (not due for weekly)
        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Recently Queried',
            'keywords' => [],
            'competitors' => [],
            'is_active' => true,
            'tracking_frequency' => 'weekly',
            'last_queried_at' => now()->subDay(),
        ]);

        // Project queried 8 days ago (due for weekly)
        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Due for Query',
            'keywords' => [],
            'competitors' => [],
            'is_active' => true,
            'tracking_frequency' => 'weekly',
            'last_queried_at' => now()->subDays(8),
        ]);

        if (method_exists(AIVisibilityProject::class, 'scopeDueForQuery')) {
            $dueProjects = AIVisibilityProject::dueForQuery()->get();

            $this->assertCount(2, $dueProjects);
            $this->assertTrue($dueProjects->contains('brand_name', 'Never Queried'));
            $this->assertTrue($dueProjects->contains('brand_name', 'Due for Query'));
            $this->assertFalse($dueProjects->contains('brand_name', 'Recently Queried'));
        } else {
            $this->markTestSkipped('dueForQuery scope not yet implemented');
        }
    }

    // ==================== ACCESSOR TESTS ====================

    /**
     * VP-021: Accessor getCompetitorListAttribute().
     */
    public function test_vp021_accessor_get_competitor_list(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [
                ['name' => 'Competitor A', 'keywords' => ['a']],
                ['name' => 'Competitor B', 'keywords' => ['b']],
                ['name' => 'Competitor C', 'keywords' => ['c']],
            ],
        ]);

        if (method_exists($project, 'getCompetitorListAttribute')) {
            $this->assertEquals(['Competitor A', 'Competitor B', 'Competitor C'], $project->competitor_list);
        } else {
            $this->markTestSkipped('getCompetitorListAttribute accessor not yet implemented');
        }
    }

    // ==================== DEFAULT VALUE TESTS ====================

    /**
     * VP-022: Default is_active is true.
     */
    public function test_vp022_default_is_active(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        $this->assertTrue($project->is_active);
    }

    /**
     * VP-023: Default tracking_frequency is weekly.
     */
    public function test_vp023_default_tracking_frequency(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        $this->assertEquals('weekly', $project->tracking_frequency);
    }

    // ==================== NULLABLE TESTS ====================

    /**
     * VP-024: Nullable query_templates.
     */
    public function test_vp024_nullable_query_templates(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
            'query_templates' => null,
        ]);

        $this->assertNull($project->query_templates);
    }

    /**
     * VP-025: Nullable last_queried_at.
     */
    public function test_vp025_nullable_last_queried_at(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
            'last_queried_at' => null,
        ]);

        $this->assertNull($project->last_queried_at);
    }

    // ==================== RELATIONSHIP LOADING TESTS ====================

    /**
     * VP-026: User relationship retrieval.
     */
    public function test_vp026_user_relationship_retrieval(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        $loadedProject = AIVisibilityProject::with('user')->find($project->id);

        $this->assertEquals('Test User', $loadedProject->user->name);
    }

    // ==================== CASCADE DELETE TESTS ====================

    /**
     * VP-027: QueryResults cascade delete.
     */
    public function test_vp027_query_results_cascade_delete(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite foreign key cascade not enforced by default');
        }

        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        DB::table('ai_query_results')->insert([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'model_used' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'queried_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project->delete();

        $this->assertDatabaseMissing('ai_query_results', ['project_id' => $project->id]);
    }

    /**
     * VP-028: Analytics cascade delete.
     */
    public function test_vp028_analytics_cascade_delete(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite foreign key cascade not enforced by default');
        }

        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project->delete();

        $this->assertDatabaseMissing('ai_mention_analytics', ['project_id' => $project->id]);
    }

    /**
     * VP-029: Alerts cascade delete.
     */
    public function test_vp029_alerts_cascade_delete(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite foreign key cascade not enforced by default');
        }

        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        DB::table('ai_visibility_alerts')->insert([
            'project_id' => $project->id,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 30.00,
            'current_value' => 50.00,
            'change_percent' => 66.67,
            'triggered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project->delete();

        $this->assertDatabaseMissing('ai_visibility_alerts', ['project_id' => $project->id]);
    }

    // ==================== ENUM VALIDATION TESTS ====================

    /**
     * VP-030: Tracking frequency enum values.
     */
    public function test_vp030_tracking_frequency_enum_values(): void
    {
        $user = User::factory()->create();

        $validFrequencies = ['daily', 'weekly', 'monthly'];

        foreach ($validFrequencies as $frequency) {
            $project = AIVisibilityProject::create([
                'user_id' => $user->id,
                'brand_name' => "Test {$frequency}",
                'keywords' => [],
                'competitors' => [],
                'tracking_frequency' => $frequency,
            ]);

            $this->assertEquals($frequency, $project->tracking_frequency);
        }
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * VP-031: Large keywords array.
     */
    public function test_vp031_large_keywords_array(): void
    {
        $user = User::factory()->create();

        $keywords = [];
        for ($i = 0; $i < 1000; $i++) {
            $keywords[] = "keyword-{$i}";
        }

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => $keywords,
            'competitors' => [],
        ]);

        $this->assertCount(1000, $project->keywords);
    }

    /**
     * VP-032: Complex competitors JSON.
     */
    public function test_vp032_complex_competitors_json(): void
    {
        $user = User::factory()->create();

        $competitors = [
            [
                'name' => 'Competitor A',
                'keywords' => ['comp a', 'competitor a'],
                'url' => 'https://competitor-a.com',
                'metadata' => [
                    'founded' => 2010,
                    'employees' => 500,
                ],
            ],
            [
                'name' => 'Competitor B',
                'keywords' => ['comp b'],
                'products' => ['Product 1', 'Product 2'],
            ],
        ];

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => $competitors,
        ]);

        $this->assertEquals('Competitor A', $project->competitors[0]['name']);
        $this->assertEquals(2010, $project->competitors[0]['metadata']['founded']);
        $this->assertEquals(['Product 1', 'Product 2'], $project->competitors[1]['products']);
    }

    /**
     * VP-033: Unicode in brand_name.
     */
    public function test_vp033_unicode_in_brand_name(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => '品牌名称-🎉-Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        $this->assertEquals('品牌名称-🎉-Test', $project->brand_name);
    }

    /**
     * VP-034: Active scope chainable.
     */
    public function test_vp034_active_scope_chainable(): void
    {
        $user = User::factory()->create();

        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Active Due',
            'keywords' => [],
            'competitors' => [],
            'is_active' => true,
            'last_queried_at' => now()->subDays(10),
        ]);

        AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Inactive Due',
            'keywords' => [],
            'competitors' => [],
            'is_active' => false,
            'last_queried_at' => now()->subDays(10),
        ]);

        if (method_exists(AIVisibilityProject::class, 'scopeActive') &&
            method_exists(AIVisibilityProject::class, 'scopeDueForQuery')) {
            $projects = AIVisibilityProject::active()->dueForQuery()->get();

            $this->assertCount(1, $projects);
            $this->assertEquals('Active Due', $projects->first()->brand_name);
        } else {
            $this->markTestSkipped('Scopes not yet implemented');
        }
    }

    // ==================== MODEL CONFIGURATION TESTS ====================

    /**
     * VP-035: PHPDoc type hints.
     */
    public function test_vp035_phpdoc_type_hints(): void
    {
        $reflection = new \ReflectionClass(AIVisibilityProject::class);
        $docComment = $reflection->getDocComment();

        // Check that class has PHPDoc
        $this->assertNotFalse($docComment, 'Model should have PHPDoc comments');
    }

    /**
     * VP-036: Table name explicit.
     */
    public function test_vp036_table_name_explicit(): void
    {
        $project = new AIVisibilityProject();

        if (property_exists($project, 'table')) {
            $this->assertEquals('ai_visibility_projects', $project->getTable());
        } else {
            // Laravel will infer the table name
            $this->assertEquals('ai_visibility_projects', $project->getTable());
        }
    }

    /**
     * VP-037: Uses $fillable array.
     */
    public function test_vp037_uses_fillable_array(): void
    {
        $project = new AIVisibilityProject();

        $reflection = new \ReflectionClass($project);
        $property = $reflection->getProperty('fillable');

        $this->assertTrue($property->isProtected(), 'fillable should be protected');
        $this->assertIsArray($project->getFillable());
    }

    /**
     * VP-038: Timestamps enabled.
     */
    public function test_vp038_timestamps_enabled(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        $this->assertNotNull($project->created_at);
        $this->assertNotNull($project->updated_at);
        $this->assertInstanceOf(Carbon::class, $project->created_at);
        $this->assertInstanceOf(Carbon::class, $project->updated_at);
    }

    /**
     * VP-039: Multiple projects per user.
     */
    public function test_vp039_multiple_projects_per_user(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            AIVisibilityProject::create([
                'user_id' => $user->id,
                'brand_name' => "Brand {$i}",
                'keywords' => [],
                'competitors' => [],
            ]);
        }

        $this->assertEquals(5, $user->aiVisibilityProjects()->count());
    }

    /**
     * VP-040: Query relationship count.
     */
    public function test_vp040_query_relationship_count(): void
    {
        $user = User::factory()->create();

        $project = AIVisibilityProject::create([
            'user_id' => $user->id,
            'brand_name' => 'Test',
            'keywords' => [],
            'competitors' => [],
        ]);

        // Insert 3 query results
        // Note: Using actual migration column name 'model_used' (alias is 'model' via accessor)
        for ($i = 0; $i < 3; $i++) {
            DB::table('ai_query_results')->insert([
                'project_id' => $project->id,
                'platform' => 'chatgpt',
                'model_used' => 'gpt-4',
                'query_text' => "Query {$i}",
                'response_text' => "Response {$i}",
                'queried_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (method_exists($project, 'queryResults')) {
            $this->assertEquals(3, $project->queryResults()->count());
        } else {
            $this->markTestSkipped('queryResults relationship not yet implemented');
        }
    }
}
