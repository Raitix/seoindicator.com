<?php

namespace Tests\Unit\Services\AIVisibility;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use App\Services\AIVisibility\AIVisibilityService;
use App\Services\AIVisibility\Contracts\AIVisibilityServiceInterface;
use App\Services\AIVisibility\Exceptions\QueryLimitExceededException;
use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\MentionParser;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Integration tests for AIVisibilityService
 * 
 * @covers \App\Services\AIVisibility\AIVisibilityService
 * @task TASK-34-5
 * @spec SPEC-034
 */
class AIVisibilityServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected AIVisibilityService $service;
    protected $mockQueryEngine;
    protected $mockMentionParser;
    protected $mockLogger;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockQueryEngine = Mockery::mock(AIQueryEngine::class);
        $this->mockMentionParser = Mockery::mock(MentionParser::class);
        $this->mockLogger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $this->service = new AIVisibilityService(
            $this->mockQueryEngine,
            $this->mockMentionParser,
            $this->mockLogger
        );

        // Create a test user
        $this->user = User::factory()->create(['plan' => 'free']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // Category 1: Service Instantiation (VS-001 to VS-010)
    // ============================================================

    /** @test VS-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AIVisibilityService::class));
        $this->assertInstanceOf(AIVisibilityService::class, $this->service);
    }

    /** @test VS-002: Implements AIVisibilityServiceInterface */
    public function test_implements_service_interface(): void
    {
        $this->assertInstanceOf(AIVisibilityServiceInterface::class, $this->service);
    }

    /** @test VS-003: Constructor accepts dependencies */
    public function test_constructor_accepts_dependencies(): void
    {
        $service = new AIVisibilityService(
            $this->mockQueryEngine,
            $this->mockMentionParser,
            $this->mockLogger
        );
        
        $this->assertInstanceOf(AIVisibilityService::class, $service);
    }

    /** @test VS-004: Dependency injection support */
    public function test_dependency_injection_support(): void
    {
        $this->app->instance(AIQueryEngine::class, $this->mockQueryEngine);
        $this->app->instance(MentionParser::class, $this->mockMentionParser);
        
        $service = $this->app->make(AIVisibilityService::class);
        
        $this->assertInstanceOf(AIVisibilityService::class, $service);
    }

    /** @test VS-005: AIQueryEngine injected correctly */
    public function test_query_engine_injected_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('queryEngine');
        $property->setAccessible(true);
        
        $this->assertSame($this->mockQueryEngine, $property->getValue($this->service));
    }

    /** @test VS-006: MentionParser injected correctly */
    public function test_mention_parser_injected_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('mentionParser');
        $property->setAccessible(true);
        
        $this->assertSame($this->mockMentionParser, $property->getValue($this->service));
    }

    /** @test VS-007: Logger injected correctly */
    public function test_logger_injected_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);
        
        $this->assertSame($this->mockLogger, $property->getValue($this->service));
    }

    /** @test VS-008: Config loaded for query limits */
    public function test_config_loaded_for_query_limits(): void
    {
        config(['ai-visibility.query_limits' => [
            'free' => 5,
            'pro' => 50,
            'business' => 200,
        ]]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getQueryLimitConfig');
        $method->setAccessible(true);
        
        $config = $method->invoke($this->service);
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('free', $config);
        $this->assertEquals(5, $config['free']);
    }

    /** @test VS-009: Cache TTL configurable */
    public function test_cache_ttl_configurable(): void
    {
        config(['ai-visibility.cache_ttl' => 3600]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCacheTtl');
        $method->setAccessible(true);
        
        $ttl = $method->invoke($this->service);
        
        $this->assertEquals(3600, $ttl);
    }

    /** @test VS-010: Service is singleton */
    public function test_service_is_singleton(): void
    {
        $this->app->singleton(AIVisibilityService::class, function ($app) {
            return new AIVisibilityService(
                $app->make(AIQueryEngine::class),
                $app->make(MentionParser::class),
                $app->make(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $instance1 = $this->app->make(AIVisibilityService::class);
        $instance2 = $this->app->make(AIVisibilityService::class);
        
        $this->assertSame($instance1, $instance2);
    }

    // ============================================================
    // Category 2: createProject() Method (VS-011 to VS-035)
    // ============================================================

    /** @test VS-011: Method exists */
    public function test_create_project_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'createProject'));
    }

    /** @test VS-012: Accepts userId parameter */
    public function test_create_project_accepts_user_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'createProject');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    /** @test VS-013: Accepts data array */
    public function test_create_project_accepts_data_array(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'createProject');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('data', $parameters[1]->getName());
        $this->assertEquals('array', $parameters[1]->getType()->getName());
    }

    /** @test VS-014: Returns AIVisibilityProject */
    public function test_create_project_returns_model(): void
    {
        $data = [
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme'],
            'competitors' => [],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertInstanceOf(AIVisibilityProject::class, $project);
        $this->assertEquals('Acme Corp', $project->brand_name);
    }

    /** @test VS-015: Creates project with brand_name */
    public function test_create_project_stores_brand_name(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals('Test Brand', $project->brand_name);
        $this->assertDatabaseHas('ai_visibility_projects', [
            'brand_name' => 'Test Brand',
        ]);
    }

    /** @test VS-016: Creates project with keywords */
    public function test_create_project_stores_keywords(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'keywords' => ['keyword1', 'keyword2'],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals(['keyword1', 'keyword2'], $project->keywords);
    }

    /** @test VS-017: Creates project with competitors */
    public function test_create_project_stores_competitors(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'competitors' => [
                ['name' => 'Competitor A', 'keywords' => ['comp-a']],
            ],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertCount(1, $project->competitors);
    }

    /** @test VS-018: Creates project with query_templates */
    public function test_create_project_stores_query_templates(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'query_templates' => ['Template 1', 'Template 2'],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals(['Template 1', 'Template 2'], $project->query_templates);
    }

    /** @test VS-019: Sets default tracking_frequency */
    public function test_create_project_default_tracking_frequency(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals('weekly', $project->tracking_frequency);
    }

    /** @test VS-020: Sets default is_active */
    public function test_create_project_default_is_active(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertTrue($project->is_active);
    }

    /** @test VS-021: Sets user_id from parameter */
    public function test_create_project_sets_user_id(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals($this->user->id, $project->user_id);
    }

    /** @test VS-022: Validates competitor count: 0 */
    public function test_create_project_validates_zero_competitors(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'competitors' => [],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals([], $project->competitors);
    }

    /** @test VS-023: Validates competitor count: 1 */
    public function test_create_project_validates_one_competitor(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'competitors' => [['name' => 'A', 'keywords' => ['a']]],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertCount(1, $project->competitors);
    }

    /** @test VS-024: Validates competitor count: 2 */
    public function test_create_project_validates_two_competitors(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'competitors' => [
                ['name' => 'A', 'keywords' => ['a']],
                ['name' => 'B', 'keywords' => ['b']],
            ],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertCount(2, $project->competitors);
    }

    /** @test VS-025: Validates competitor count: 3 */
    public function test_create_project_validates_three_competitors(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'competitors' => [
                ['name' => 'A', 'keywords' => ['a']],
                ['name' => 'B', 'keywords' => ['b']],
                ['name' => 'C', 'keywords' => ['c']],
            ],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertCount(3, $project->competitors);
    }

    /** @test VS-026: Validates competitor count: 4 throws exception */
    public function test_create_project_rejects_four_competitors(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = [
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme'],
            'competitors' => [
                ['name' => 'A', 'keywords' => ['a']],
                ['name' => 'B', 'keywords' => ['b']],
                ['name' => 'C', 'keywords' => ['c']],
                ['name' => 'D', 'keywords' => ['d']],
            ],
        ];
        
        $this->service->createProject($this->user->id, $data);
    }

    /** @test VS-027: Validates competitor count: 10 throws exception */
    public function test_create_project_rejects_ten_competitors(): void
    {
        $this->expectException(ValidationException::class);
        
        $competitors = [];
        for ($i = 1; $i <= 10; $i++) {
            $competitors[] = ['name' => "Competitor $i", 'keywords' => ["comp$i"]];
        }
        
        $data = [
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme'],
            'competitors' => $competitors,
        ];
        
        $this->service->createProject($this->user->id, $data);
    }

    /** @test VS-028: Validates keywords format: array */
    public function test_create_project_validates_keywords_array(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'keywords' => ['a', 'b', 'c'],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals(['a', 'b', 'c'], $project->keywords);
    }

    /** @test VS-029: Validates keywords format: string throws exception */
    public function test_create_project_rejects_keywords_string(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = [
            'brand_name' => 'Acme Corp',
            'keywords' => 'not an array',
            'competitors' => [],
        ];
        
        $this->service->createProject($this->user->id, $data);
    }

    /** @test VS-030: Validates keywords format: empty array valid */
    public function test_create_project_accepts_empty_keywords(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'keywords' => [],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals([], $project->keywords);
    }

    /** @test VS-031: Validates brand_name required */
    public function test_create_project_requires_brand_name(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = [
            'keywords' => ['acme'],
            'competitors' => [],
        ];
        
        $this->service->createProject($this->user->id, $data);
    }

    /** @test VS-032: Logs project creation */
    public function test_create_project_logs_creation(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'project created') &&
                    isset($context['user_id']);
            });
        
        $project = $this->service->createProject($this->user->id, $data);
        
        // Assert the project was created (mock expectation is verified)
        $this->assertNotNull($project->id);
    }

    /** @test VS-033: Returns model with id */
    public function test_create_project_returns_model_with_id(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertNotNull($project->id);
    }

    /** @test VS-035: Dispatches project created event */
    public function test_create_project_dispatches_event(): void
    {
        $data = ['brand_name' => 'Test Brand'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        // Use Laravel's event faking to verify event dispatch
        \Illuminate\Support\Facades\Event::fake();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        // Assert the project was created and has an ID
        $this->assertNotNull($project->id);
        
        // Verify the event was dispatched with the correct name
        \Illuminate\Support\Facades\Event::assertDispatched('ai-visibility.project.created', function ($eventName, $payload) use ($project) {
            return isset($payload[0]) && $payload[0]->id === $project->id;
        });
    }

    // ============================================================
    // Category 3: updateProject() Method (VS-036 to VS-055)
    // ============================================================

    /** @test VS-036: Method exists */
    public function test_update_project_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'updateProject'));
    }

    /** @test VS-037: Accepts projectId parameter */
    public function test_update_project_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'updateProject');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test VS-038: Accepts data array */
    public function test_update_project_accepts_data(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'updateProject');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('data', $parameters[1]->getName());
    }

    /** @test VS-039: Returns updated model */
    public function test_update_project_returns_model(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['brand_name' => 'Updated']);
        
        $this->assertInstanceOf(AIVisibilityProject::class, $updated);
    }

    /** @test VS-040: Updates brand_name */
    public function test_update_project_updates_brand_name(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'Old Name',
        ]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['brand_name' => 'New Name']);
        
        $this->assertEquals('New Name', $updated->brand_name);
    }

    /** @test VS-041: Updates keywords */
    public function test_update_project_updates_keywords(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['keywords' => ['new', 'keywords']]);
        
        $this->assertEquals(['new', 'keywords'], $updated->keywords);
    }

    /** @test VS-042: Updates competitors */
    public function test_update_project_updates_competitors(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, [
            'competitors' => [['name' => 'New Comp', 'keywords' => ['nc']]],
        ]);
        
        $this->assertCount(1, $updated->competitors);
    }

    /** @test VS-043: Updates tracking_frequency */
    public function test_update_project_updates_frequency(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'tracking_frequency' => 'weekly',
        ]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['tracking_frequency' => 'daily']);
        
        $this->assertEquals('daily', $updated->tracking_frequency);
    }

    /** @test VS-046: Validates project exists */
    public function test_update_project_not_found_throws(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->updateProject(99999, ['brand_name' => 'Updated']);
    }

    /** @test VS-047: Validates competitor count on update */
    public function test_update_project_validates_competitor_count(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->expectException(ValidationException::class);
        
        $this->service->updateProject($project->id, [
            'competitors' => [
                ['name' => 'A', 'keywords' => ['a']],
                ['name' => 'B', 'keywords' => ['b']],
                ['name' => 'C', 'keywords' => ['c']],
                ['name' => 'D', 'keywords' => ['d']],
            ],
        ]);
    }

    /** @test VS-048: Logs project update */
    public function test_update_project_logs_update(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'project updated');
            });
        
        $updated = $this->service->updateProject($project->id, ['brand_name' => 'Updated']);
        
        // Assert the project was updated (mock expectation is verified)
        $this->assertEquals('Updated', $updated->brand_name);
    }

    /** @test VS-050: Partial update: only brand_name */
    public function test_update_project_partial_brand_name(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'Old Name',
            'tracking_frequency' => 'weekly',
        ]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['brand_name' => 'New Name']);
        
        $this->assertEquals('New Name', $updated->brand_name);
        $this->assertEquals('weekly', $updated->tracking_frequency);
    }

    /** @test VS-051: Partial update: only keywords */
    public function test_update_project_partial_keywords(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'Brand',
        ]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['keywords' => ['new']]);
        
        $this->assertEquals('Brand', $updated->brand_name);
        $this->assertEquals(['new'], $updated->keywords);
    }

    /** @test VS-052: Does not change user_id */
    public function test_update_project_preserves_user_id(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        $originalUserId = $project->user_id;
        
        $this->mockLogger->shouldReceive('info')->once();
        
        // Try to update with user_id in data (should be ignored)
        $updated = $this->service->updateProject($project->id, [
            'brand_name' => 'Updated',
            'user_id' => 999,
        ]);
        
        $this->assertEquals($originalUserId, $updated->user_id);
    }

    /** @test VS-053: Does not change id */
    public function test_update_project_preserves_id(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        $originalId = $project->id;
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $updated = $this->service->updateProject($project->id, ['brand_name' => 'Updated']);
        
        $this->assertEquals($originalId, $updated->id);
    }

    // ============================================================
    // Category 4: deleteProject() Method (VS-056 to VS-075)
    // ============================================================

    /** @test VS-056: Method exists */
    public function test_delete_project_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteProject'));
    }

    /** @test VS-057: Accepts projectId parameter */
    public function test_delete_project_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'deleteProject');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test VS-058: Accepts userId parameter */
    public function test_delete_project_accepts_user_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'deleteProject');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('userId', $parameters[1]->getName());
    }

    /** @test VS-059: Returns true on success */
    public function test_delete_project_returns_true(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $result = $this->service->deleteProject($project->id, $this->user->id);
        
        $this->assertTrue($result);
    }

    /** @test VS-061: Validates user ownership: not owner throws exception */
    public function test_delete_project_non_owner_throws(): void
    {
        $otherUser = User::factory()->create();
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->expectException(AuthorizationException::class);
        
        $this->service->deleteProject($project->id, $otherUser->id);
    }

    /** @test VS-062: Validates project exists */
    public function test_delete_project_not_found_throws(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->deleteProject(99999, $this->user->id);
    }

    /** @test VS-063: Soft delete when configured */
    public function test_delete_project_soft_delete(): void
    {
        config(['ai-visibility.delete_strategy' => 'soft']);
        
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $this->service->deleteProject($project->id, $this->user->id);
        
        // Should still exist in database (soft deleted)
        $this->assertDatabaseHas('ai_visibility_projects', [
            'id' => $project->id,
        ]);
        
        // But should be trashed
        $this->assertNotNull(AIVisibilityProject::withTrashed()->find($project->id)->deleted_at);
    }

    /** @test VS-068: Logs project deletion */
    public function test_delete_project_logs_deletion(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'project deleted');
            });
        
        $result = $this->service->deleteProject($project->id, $this->user->id);
        
        // Assert the deletion was logged (mock expectation is verified)
        $this->assertTrue($result);
    }

    /** @test VS-069: Clears related cache */
    public function test_delete_project_clears_cache(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        // Set some cache
        Cache::put("ai_visibility_analytics_{$project->id}_week", ['data'], 3600);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $this->service->deleteProject($project->id, $this->user->id);
        
        // Cache should be cleared
        $this->assertNull(Cache::get("ai_visibility_analytics_{$project->id}_week"));
    }

    /** @test VS-072: Returns false if already deleted */
    public function test_delete_project_already_deleted(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        // First delete
        $this->service->deleteProject($project->id, $this->user->id);
        
        // Second delete should return false
        $result = $this->service->deleteProject($project->id, $this->user->id);
        
        $this->assertFalse($result);
    }

    // ============================================================
    // Category 5: runQueries() Method (VS-076 to VS-110)
    // ============================================================

    /** @test VS-076: Method exists */
    public function test_run_queries_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'runQueries'));
    }

    /** @test VS-077: Accepts projectId parameter */
    public function test_run_queries_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'runQueries');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test VS-078: Returns array of job IDs */
    public function test_run_queries_returns_job_ids(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        Queue::fake();
        
        $jobIds = $this->service->runQueries($project->id);
        
        $this->assertIsArray($jobIds);
        $this->assertCount(2, $jobIds); // chatgpt and perplexity
    }

    /** @test VS-085: Updates last_queried_at */
    public function test_run_queries_updates_timestamp(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'last_queried_at' => null,
        ]);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        Queue::fake();
        
        $this->service->runQueries($project->id);
        
        $project->refresh();
        
        $this->assertNotNull($project->last_queried_at);
    }

    /** @test VS-086: Validates project exists */
    public function test_run_queries_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->runQueries(99999);
    }

    /** @test VS-087: Validates project is active */
    public function test_run_queries_inactive_project_throws(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);
        
        $this->expectException(\RuntimeException::class);
        
        $this->service->runQueries($project->id);
    }

    /** @test VS-089: Query limit: free tier at limit throws */
    public function test_run_queries_free_at_limit_throws(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        // Create 5 query results to reach limit
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $project->id,
            'queried_at' => now(),
        ]);
        
        $this->expectException(QueryLimitExceededException::class);
        
        $this->service->runQueries($project->id);
    }

    // ============================================================
    // Category 6: getAnalytics() Method (VS-111 to VS-145)
    // ============================================================

    /** @test VS-111: Method exists */
    public function test_get_analytics_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAnalytics'));
    }

    /** @test VS-112: Accepts projectId parameter */
    public function test_get_analytics_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getAnalytics');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test VS-113: Accepts period parameter */
    public function test_get_analytics_accepts_period(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getAnalytics');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('period', $parameters[1]->getName());
    }

    /** @test VS-114: Returns array */
    public function test_get_analytics_returns_array(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id);
        
        $this->assertIsArray($analytics);
    }

    /** @test VS-115: Returns mention_rate_by_platform */
    public function test_get_analytics_returns_mention_rate(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id);
        
        $this->assertArrayHasKey('mention_rate_by_platform', $analytics);
    }

    /** @test VS-116: Returns share_of_voice */
    public function test_get_analytics_returns_share_of_voice(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id);
        
        $this->assertArrayHasKey('share_of_voice', $analytics);
    }

    /** @test VS-117: Returns sentiment_breakdown */
    public function test_get_analytics_returns_sentiment(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id);
        
        $this->assertArrayHasKey('sentiment_breakdown', $analytics);
        $this->assertArrayHasKey('positive', $analytics['sentiment_breakdown']);
        $this->assertArrayHasKey('neutral', $analytics['sentiment_breakdown']);
        $this->assertArrayHasKey('negative', $analytics['sentiment_breakdown']);
    }

    /** @test VS-118: Returns trend_direction */
    public function test_get_analytics_returns_trend(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id);
        
        $this->assertArrayHasKey('trend_direction', $analytics);
        $this->assertContains($analytics['trend_direction'], ['up', 'down', 'stable']);
    }

    /** @test VS-119: Period: day */
    public function test_get_analytics_period_day(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id, 'day');
        
        $this->assertIsArray($analytics);
    }

    /** @test VS-120: Period: week */
    public function test_get_analytics_period_week(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id, 'week');
        
        $this->assertIsArray($analytics);
    }

    /** @test VS-121: Period: month */
    public function test_get_analytics_period_month(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id, 'month');
        
        $this->assertIsArray($analytics);
    }

    /** @test VS-122: Caches results for 1 hour */
    public function test_get_analytics_caches_results(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $cacheKey = "ai_visibility_analytics_{$project->id}_week";
        
        // First call
        $analytics1 = $this->service->getAnalytics($project->id);
        
        // Should be cached
        $cached = Cache::get($cacheKey);
        
        $this->assertNotNull($cached);
    }

    /** @test VS-125: Validates project exists */
    public function test_get_analytics_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getAnalytics(99999);
    }

    /** @test VS-126: Validates period value */
    public function test_get_analytics_invalid_period(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $this->expectException(ValidationException::class);
        
        $this->service->getAnalytics($project->id, 'invalid');
    }

    /** @test VS-127: Returns zeros for no data */
    public function test_get_analytics_zeros_no_data(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $analytics = $this->service->getAnalytics($project->id);
        
        $this->assertEquals(0, $analytics['total_queries']);
        $this->assertEquals(0, $analytics['brand_mentions']);
    }

    // ============================================================
    // Category 7: getCompetitorComparison() Method (VS-146 to VS-175)
    // ============================================================

    /** @test VS-146: Method exists */
    public function test_get_competitor_comparison_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCompetitorComparison'));
    }

    /** @test VS-147: Accepts projectId parameter */
    public function test_get_competitor_comparison_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getCompetitorComparison');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test VS-148: Returns array */
    public function test_get_competitor_comparison_returns_array(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        $this->assertIsArray($comparison);
    }

    /** @test VS-149: Returns brand data */
    public function test_get_competitor_comparison_returns_brand(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        $this->assertArrayHasKey('brand', $comparison);
        $this->assertArrayHasKey('name', $comparison['brand']);
        $this->assertArrayHasKey('mention_count', $comparison['brand']);
        $this->assertArrayHasKey('mention_rate', $comparison['brand']);
    }

    /** @test VS-150: Returns competitors array */
    public function test_get_competitor_comparison_returns_competitors(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        $this->assertArrayHasKey('competitors', $comparison);
        $this->assertIsArray($comparison['competitors']);
    }

    /** @test VS-157: Caches results for 1 hour */
    public function test_get_competitor_comparison_caches(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $cacheKey = "ai_visibility_comparison_{$project->id}";
        
        // First call
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        // Should be cached
        $cached = Cache::get($cacheKey);
        
        $this->assertNotNull($cached);
    }

    /** @test VS-159: Validates project exists */
    public function test_get_competitor_comparison_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getCompetitorComparison(99999);
    }

    /** @test VS-160: Returns empty competitors if none */
    public function test_get_competitor_comparison_empty_competitors(): void
    {
        $project = AIVisibilityProject::factory()->withoutCompetitors()->create(['user_id' => $this->user->id]);
        
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        $this->assertEquals([], $comparison['competitors']);
    }

    /** @test VS-172: Returns winner */
    public function test_get_competitor_comparison_returns_winner(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        $this->assertArrayHasKey('winner', $comparison);
    }

    /** @test VS-173: Returns performance gap */
    public function test_get_competitor_comparison_returns_gap(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $comparison = $this->service->getCompetitorComparison($project->id);
        
        $this->assertArrayHasKey('gap_percentage', $comparison);
    }

    // ============================================================
    // Category 8: checkAlerts() Method (VS-176 to VS-210)
    // ============================================================

    /** @test VS-176: Method exists */
    public function test_check_alerts_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'checkAlerts'));
    }

    /** @test VS-177: Accepts projectId parameter */
    public function test_check_alerts_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'checkAlerts');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test VS-178: Returns array of alerts */
    public function test_check_alerts_returns_array(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $alerts = $this->service->checkAlerts($project->id);
        
        $this->assertIsArray($alerts);
    }

    /** @test VS-194: Validates project exists */
    public function test_check_alerts_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->checkAlerts(99999);
    }

    /** @test VS-195: Returns empty array if no alerts */
    public function test_check_alerts_empty_no_breaches(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $alerts = $this->service->checkAlerts($project->id);
        
        // No previous data, so no alerts
        $this->assertEmpty($alerts);
    }

    /** @test VS-200: Handles no previous data */
    public function test_check_alerts_no_previous_data(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        $alerts = $this->service->checkAlerts($project->id);
        
        // No previous period data should return empty alerts
        $this->assertEmpty($alerts);
    }

    // ============================================================
    // Category 9: Query Limit Enforcement (VS-211 to VS-235)
    // ============================================================

    /** @test VS-211: Free tier limit: 5 */
    public function test_query_limit_free_tier(): void
    {
        $this->user->update(['plan' => 'free']);
        
        $limit = $this->service->getQueryLimit($this->user->id);
        
        $this->assertEquals(5, $limit);
    }

    /** @test VS-212: Pro tier limit: 50 */
    public function test_query_limit_pro_tier(): void
    {
        $this->user->update(['plan' => 'pro']);
        
        $limit = $this->service->getQueryLimit($this->user->id);
        
        $this->assertEquals(50, $limit);
    }

    /** @test VS-213: Business tier limit: 200 */
    public function test_query_limit_business_tier(): void
    {
        $this->user->update(['plan' => 'business']);
        
        $limit = $this->service->getQueryLimit($this->user->id);
        
        $this->assertEquals(200, $limit);
    }

    /** @test VS-214: getQueryLimit() method */
    public function test_get_query_limit_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getQueryLimit'));
    }

    /** @test VS-215: getQueryLimit() returns int */
    public function test_get_query_limit_returns_int(): void
    {
        $limit = $this->service->getQueryLimit($this->user->id);
        
        $this->assertIsInt($limit);
    }

    /** @test VS-216: getQueryUsage() method */
    public function test_get_query_usage_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getQueryUsage'));
    }

    /** @test VS-217: getQueryUsage() returns int */
    public function test_get_query_usage_returns_int(): void
    {
        $usage = $this->service->getQueryUsage($this->user->id);
        
        $this->assertIsInt($usage);
    }

    /** @test VS-218: getRemainingQueries() method */
    public function test_get_remaining_queries_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getRemainingQueries'));
    }

    /** @test VS-219: getRemainingQueries() returns int */
    public function test_get_remaining_queries_returns_int(): void
    {
        $remaining = $this->service->getRemainingQueries($this->user->id);
        
        $this->assertIsInt($remaining);
    }

    /** @test VS-220: Remaining = limit - usage */
    public function test_remaining_calculation(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        // Create some query results
        AIQueryResult::factory()->count(2)->create([
            'project_id' => $project->id,
            'queried_at' => now(),
        ]);
        
        $limit = $this->service->getQueryLimit($this->user->id);
        $usage = $this->service->getQueryUsage($this->user->id);
        $remaining = $this->service->getRemainingQueries($this->user->id);
        
        $this->assertEquals($limit - $usage, $remaining);
    }

    /** @test VS-229: canRunQueries() method */
    public function test_can_run_queries_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'canRunQueries'));
    }

    /** @test VS-230: canRunQueries() returns bool */
    public function test_can_run_queries_returns_bool(): void
    {
        $result = $this->service->canRunQueries($this->user->id);
        
        $this->assertIsBool($result);
    }

    /** @test VS-231: canRunQueries() returns false at limit */
    public function test_can_run_queries_false_at_limit(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        // Create 5 query results to reach limit
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $project->id,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->canRunQueries($this->user->id);
        
        $this->assertFalse($result);
    }

    /** @test VS-232: Zero queries requested */
    public function test_can_run_queries_zero(): void
    {
        $result = $this->service->canRunQueries($this->user->id, 0);
        
        $this->assertTrue($result);
    }

    /** @test VS-233: Negative queries throws */
    public function test_can_run_queries_negative_throws(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->canRunQueries($this->user->id, -1);
    }

    /** @test VS-235: Unlimited tier (null limit) */
    public function test_unlimited_tier(): void
    {
        $this->user->update(['plan' => 'unlimited']);
        
        $limit = $this->service->getQueryLimit($this->user->id);
        
        $this->assertEquals(PHP_INT_MAX, $limit);
    }

    // ============================================================
    // Category 10: Integration & Edge Cases (VS-236 to VS-260)
    // ============================================================

    /** @test VS-241: Unicode in brand_name */
    public function test_unicode_brand_name(): void
    {
        $data = ['brand_name' => '東京株式会社'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals('東京株式会社', $project->brand_name);
    }

    /** @test VS-242: Unicode in keywords */
    public function test_unicode_keywords(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'keywords' => ['日本語', '中文'],
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals(['日本語', '中文'], $project->keywords);
    }

    /** @test VS-243: Emoji in brand_name */
    public function test_emoji_brand_name(): void
    {
        $data = ['brand_name' => 'Acme 🚀'];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals('Acme 🚀', $project->brand_name);
    }

    /** @test VS-247: Empty brand_name */
    public function test_empty_brand_name(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = ['brand_name' => ''];
        
        $this->service->createProject($this->user->id, $data);
    }

    /** @test VS-248: Null values in optional fields */
    public function test_null_optional_fields(): void
    {
        $data = [
            'brand_name' => 'Test Brand',
            'keywords' => null,
            'competitors' => null,
        ];
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $project = $this->service->createProject($this->user->id, $data);
        
        $this->assertEquals([], $project->keywords);
        $this->assertEquals([], $project->competitors);
    }

    /** @test VS-249: Cache invalidation on update */
    public function test_cache_invalidation_update(): void
    {
        $project = AIVisibilityProject::factory()->create(['user_id' => $this->user->id]);
        
        // Set cache
        Cache::put("ai_visibility_analytics_{$project->id}_week", ['data'], 3600);
        
        $this->mockLogger->shouldReceive('info')->once();
        
        $this->service->updateProject($project->id, ['brand_name' => 'Updated']);
        
        // Cache should be cleared
        $this->assertNull(Cache::get("ai_visibility_analytics_{$project->id}_week"));
    }

    /** @test VS-259: Dependency injection throughout */
    public function test_dependency_injection_throughout(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $queryEngineProp = $reflection->getProperty('queryEngine');
        $queryEngineProp->setAccessible(true);
        
        $mentionParserProp = $reflection->getProperty('mentionParser');
        $mentionParserProp->setAccessible(true);
        
        $loggerProp = $reflection->getProperty('logger');
        $loggerProp->setAccessible(true);
        
        $this->assertInstanceOf(AIQueryEngine::class, $queryEngineProp->getValue($this->service));
        $this->assertInstanceOf(MentionParser::class, $mentionParserProp->getValue($this->service));
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $loggerProp->getValue($this->service));
    }
}
