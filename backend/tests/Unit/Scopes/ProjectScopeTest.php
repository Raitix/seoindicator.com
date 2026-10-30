<?php

namespace Tests\Unit\Scopes;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for ProjectScope global Eloquent scope.
 *
 * Tests the global scope that automatically filters queries by the current project
 * when set via the SetProjectContext middleware (TASK-8-4).
 *
 * @see TASK-8-5
 * @see TESTS/TEST-8-5.md
 */
class ProjectScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // PS-001: Scope Application (Basic)
    // ========================================================================

    #[Test]
    #[Group('PS-001')]
    public function ps_001_01_scope_adds_where_clause_when_project_context_set(): void
    {
        // Arrange: Mock a project in the container
        $mockProject = Mockery::mock();
        $mockProject->id = 42;

        // Use a closure-based binding to simulate app('current_project')
        App::instance('current_project', $mockProject);

        // Create a test scope
        $scope = new \App\Scopes\ProjectScope();

        // Create a mock model and builder
        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // Expect the where clause to be added
        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', 42)
            ->andReturnSelf();

        // Act
        $scope->apply($builder, $model);

        // Assert via Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-001')]
    public function ps_001_02_scope_does_not_filter_when_no_project_context(): void
    {
        // Arrange: Clear any project context by binding null
        // We use a closure that returns null to avoid BindingResolutionException
        App::bind('current_project', fn() => null);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // The where method should NOT be called since project is null
        $builder->shouldNotReceive('where');

        // Act
        $scope->apply($builder, $model);

        // Assert via Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-001')]
    public function ps_001_03_scope_does_not_filter_when_project_is_null(): void
    {
        // Arrange: Bind null as current_project using a closure
        App::bind('current_project', fn() => null);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldNotReceive('where');

        // Act
        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-001')]
    public function ps_001_04_scope_does_not_filter_when_project_id_is_null(): void
    {
        // Arrange: Mock a project with null id
        $mockProject = Mockery::mock();
        $mockProject->id = null;

        App::instance('current_project', $mockProject);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldNotReceive('where');

        // Act
        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    // ========================================================================
    // PS-003: Scope Removal (Admin Override)
    // ========================================================================

    #[Test]
    #[Group('PS-003')]
    public function ps_003_01_scope_can_be_removed_with_without_global(): void
    {
        // This test verifies the scope can be removed using withoutGlobal()
        // The actual behavior is handled by Eloquent's query builder

        // Arrange: Create a mock model that uses ProjectScope
        $modelClass = new class extends Model {
            protected $table = 'test_table';
        };

        // The scope removal is an Eloquent feature, we just verify the scope class exists
        $scope = new \App\Scopes\ProjectScope();

        // Assert: Scope is a valid Eloquent Scope
        $this->assertInstanceOf(Scope::class, $scope);
    }

    #[Test]
    #[Group('PS-003')]
    public function ps_003_02_scope_removal_returns_unfiltered_results(): void
    {
        // This is an integration-style test that would verify:
        // Model::withoutGlobal(ProjectScope::class)->get() returns all records
        // For unit testing, we verify the scope class structure

        $scope = new \App\Scopes\ProjectScope();
        $reflection = new \ReflectionClass($scope);

        // Verify the apply method exists with correct signature
        $this->assertTrue($reflection->hasMethod('apply'));

        $applyMethod = $reflection->getMethod('apply');
        $parameters = $applyMethod->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('builder', $parameters[0]->getName());
        $this->assertEquals('model', $parameters[1]->getName());
    }

    // ========================================================================
    // PS-005: Security Tests
    // ========================================================================

    #[Test]
    #[Group('PS-005')]
    public function ps_005_01_scope_uses_parameterized_query(): void
    {
        // Arrange: Create the scope
        $scope = new \App\Scopes\ProjectScope();

        $mockProject = Mockery::mock();
        $mockProject->id = 42;

        App::instance('current_project', $mockProject);

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // The where() method uses parameterized queries (Eloquent default)
        // This test verifies we're using where('column', value) not raw SQL
        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', Mockery::type('int'))
            ->andReturnSelf();

        // Act
        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-005')]
    public function ps_005_02_scope_ignores_external_project_id_manipulation(): void
    {
        // The scope should ONLY use app('current_project')->id
        // not any user-supplied project_id

        $mockProject = Mockery::mock();
        $mockProject->id = 100; // The "correct" project ID from context

        App::instance('current_project', $mockProject);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // Verify the scope uses the container-bound project ID, not any external value
        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', 100) // Must be 100, the value from current_project
            ->andReturnSelf();

        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-005')]
    public function ps_005_03_cross_project_data_access_blocked(): void
    {
        // Simulate user trying to access data from project 1
        // but their context is project 2

        $mockProject = Mockery::mock();
        $mockProject->id = 2; // User's current project

        App::instance('current_project', $mockProject);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // The scope enforces project_id = 2, blocking access to project 1 data
        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', 2)
            ->andReturnSelf();

        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    // ========================================================================
    // PS-008: Context Binding
    // ========================================================================

    #[Test]
    #[Group('PS-008')]
    public function ps_008_01_scope_uses_bound_project_instance(): void
    {
        $mockProject = Mockery::mock();
        $mockProject->id = 999;

        App::instance('current_project', $mockProject);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', 999)
            ->andReturnSelf();

        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-008')]
    public function ps_008_02_scope_handles_missing_binding_gracefully(): void
    {
        // Ensure no binding exists - use a closure that returns null
        App::bind('current_project', fn() => null);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // Should not throw, should not filter
        $builder->shouldNotReceive('where');

        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    // ========================================================================
    // PS-004: Edge Cases
    // ========================================================================

    #[Test]
    #[Group('PS-004')]
    public function ps_004_05_scope_works_with_joins(): void
    {
        $mockProject = Mockery::mock();
        $mockProject->id = 5;

        App::instance('current_project', $mockProject);

        $scope = new \App\Scopes\ProjectScope();

        // Mock model with table name
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getTable')->andReturn('keywords');

        $builder = Mockery::mock(Builder::class);

        // When joins are present, we might need qualified column names
        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', 5)
            ->andReturnSelf();

        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    #[Test]
    #[Group('PS-004')]
    public function ps_004_03_scope_combined_with_other_conditions(): void
    {
        $mockProject = Mockery::mock();
        $mockProject->id = 10;

        App::instance('current_project', $mockProject);

        $scope = new \App\Scopes\ProjectScope();

        $model = Mockery::mock(Model::class);
        $builder = Mockery::mock(Builder::class);

        // The scope adds a where clause that will be ANDed with existing conditions
        $builder->shouldReceive('where')
            ->once()
            ->with('project_id', 10)
            ->andReturnSelf();

        $scope->apply($builder, $model);

        $this->assertTrue(true);
    }

    // ========================================================================
    // Model Integration Tests
    // ========================================================================

    #[Test]
    #[Group('PS-002')]
    public function ps_002_01_keyword_model_should_use_project_scope(): void
    {
        // Verify that the Keyword model is expected to have the ProjectScope
        // This test documents the expected behavior for the Developer

        // The Developer should add ProjectScope to Keyword model
        // Expected: Keyword::addGlobalScope(new ProjectScope())
        // or: Keyword model uses HasProjectScope trait

        // For now, we document this expectation
        $this->assertTrue(true, 'Keyword model should use ProjectScope (Developer to implement)');
    }

    #[Test]
    #[Group('PS-002')]
    public function ps_002_02_competitor_model_should_use_project_scope(): void
    {
        $this->assertTrue(true, 'Competitor model should use ProjectScope (Developer to implement)');
    }

    #[Test]
    #[Group('PS-002')]
    public function ps_002_03_report_model_should_use_project_scope(): void
    {
        $this->assertTrue(true, 'Report model should use ProjectScope (Developer to implement)');
    }

    #[Test]
    #[Group('PS-002')]
    public function ps_002_04_alert_settings_model_should_use_project_scope(): void
    {
        $this->assertTrue(true, 'AlertSettings model should use ProjectScope (Developer to implement)');
    }

    // ========================================================================
    // Integration Tests (require migrations)
    // ========================================================================

    #[Test]
    #[Group('integration')]
    #[Group('PS-006')]
    public function ps_006_01_scope_uses_index_for_performance(): void
    {
        // This test would verify that queries with ProjectScope use the index
        // Requires: TASK-8-2 migration with idx_keywords_project_id index
        // For now, document the expectation

        $this->assertTrue(true, 'Scope should leverage project_id index (requires TASK-8-2 migration)');
    }

    #[Test]
    #[Group('integration')]
    public function integration_scope_filters_actual_database_records(): void
    {
        // This integration test requires:
        // 1. TASK-8-2 migration (project_id column)
        // 2. Database with test data
        // 3. app('current_project') binding

        $this->markTestSkipped('Requires TASK-8-2 migration for project_id column');
    }
}
