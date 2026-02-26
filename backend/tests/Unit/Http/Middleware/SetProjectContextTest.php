<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\SetProjectContext;
use App\Models\Project;
use App\Models\User;
use Mockery;

class SetProjectContextTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // Header Extraction Tests (HE)
    // ========================================

    /** @test - HE-001: Valid X-Project-ID header */
    public function it_binds_project_from_valid_header(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        // Mock auth
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        // Mock Project query
        Project::shouldReceive('where')
            ->with('id', $project->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('where')
            ->with('user_id', $user->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('first')
            ->once()
            ->andReturn($project);

        $response = $middleware->handle($request, function ($req) use ($project) {
            $this->assertEquals($project, app('current_project'));
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    /** @test - HE-002: Header takes precedence over session */
    public function header_takes_precedence_over_session(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project2->id);
        $request->session()->put('current_project_id', $project1->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        // Should query for project2 (from header), not project1 (from session)
        Project::shouldReceive('where')
            ->with('id', $project2->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('where')
            ->with('user_id', $user->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('first')
            ->once()
            ->andReturn($project2);

        $middleware->handle($request, function ($req) use ($project2) {
            $this->assertEquals($project2, app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-003: Header with numeric string */
    public function it_handles_numeric_string_in_header(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        $middleware->handle($request, function ($req) {
            return new Response();
        });
    }

    /** @test - HE-004: Header with whitespace */
    public function it_trims_whitespace_from_header(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '  ' . $project->id . '  ');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')
            ->with('id', $project->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        $middleware->handle($request, function ($req) {
            return new Response();
        });
    }

    /** @test - HE-005: Empty header value */
    public function it_handles_empty_header_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-006: Header with zero value */
    public function it_handles_zero_value_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '0');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-007: Header with negative value */
    public function it_handles_negative_value_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '-1');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-008: Header with non-numeric value */
    public function it_handles_non_numeric_header_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', 'abc');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-010: Header with very large number */
    public function it_handles_very_large_number_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '999999999999999999');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        // Should handle gracefully without overflow error
        $middleware->handle($request, function ($req) {
            return new Response();
        });
    }

    // ========================================
    // Session Extraction Tests (SE)
    // ========================================

    /** @test - SE-001: Valid session project ID */
    public function it_binds_project_from_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->session()->put('current_project_id', $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertEquals($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - SE-003: Session with null value */
    public function it_handles_null_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->session()->put('current_project_id', null);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - SE-008: Session not set */
    public function it_handles_missing_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        // No session value set

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    // ========================================
    // Authorization Tests (AU)
    // ========================================

    /** @test - AU-001: User owns the project */
    public function it_binds_project_when_user_owns_it(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')
            ->with('id', $project->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('where')
            ->with('user_id', $user->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('first')
            ->once()
            ->andReturn($project);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertEquals($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - AU-002: User does not own project */
    public function it_does_not_bind_project_when_user_does_not_own_it(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user1->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        // Authenticated as user2, trying to access user1's project
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user2->id);

        // Query will return null because user_id doesn't match
        Project::shouldReceive('where')
            ->with('id', $project->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('where')
            ->with('user_id', $user2->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('first')
            ->once()
            ->andReturn(null);

        $middleware->handle($request, function ($req) {
            // Project should NOT be bound
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - AU-005: Anonymous user (unauthenticated) */
    public function it_skips_binding_for_unauthenticated_user(): void
    {
        $project = Project::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(false);

        // Should not query database at all
        Project::shouldNotReceive('where');

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    // ========================================
    // Archived Project Handling Tests (AR)
    // ========================================

    /** @test - AR-001: Active project */
    public function it_binds_active_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'archived_at' => null,
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertEquals($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - AR-002: Archived project */
    public function it_binds_archived_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'archived_at' => now()->subDay(),
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        // Archived projects ARE accessible (business decision)
        $middleware->handle($request, function ($req) use ($project) {
            $this->assertEquals($project, app('current_project'));
            return new Response();
        });
    }

    // ========================================
    // Edge Case Tests (EC)
    // ========================================

    /** @test - EC-001: Both header and session empty */
    public function it_handles_no_project_context(): void
    {
        $request = Request::create('/api/test', 'GET');
        // No header, no session

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - EC-002: Header with special characters (SQL injection attempt) */
    public function it_sanitizes_sql_injection_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', "1'; DROP TABLE projects; --");

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        // Should not perform any query (invalid ID)
        Project::shouldNotReceive('where');

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - EC-008: Header with leading zeros */
    public function it_handles_leading_zeros_in_header(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '000' . $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')
            ->with('id', $project->id)
            ->once()
            ->andReturnSelf();
        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertEquals($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - EC-012: Middleware applied to non-project route */
    public function it_passes_through_without_error_on_any_route(): void
    {
        $request = Request::create('/api/health', 'GET');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    // ========================================
    // Error Handling Tests (EH)
    // ========================================

    /** @test - EH-002: Project model not found */
    public function it_handles_non_existent_project_id(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '99999');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn(null);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    /** @test - EH-003: Invalid project ID type */
    public function it_handles_invalid_project_id_type(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', 'not-a-number');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }

    // ========================================
    // Project Binding Tests (PB)
    // ========================================

    /** @test - PB-001: Valid project bound to app */
    public function it_binds_valid_project_to_app_container(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'domain' => 'https://example.com',
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', (string) $project->id);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn($user->id);

        Project::shouldReceive('where')->andReturnSelf();
        Project::shouldReceive('first')->andReturn($project);

        $middleware->handle($request, function ($req) use ($project) {
            $boundProject = app('current_project');
            $this->assertNotNull($boundProject);
            $this->assertEquals($project->id, $boundProject->id);
            $this->assertEquals($project->name, $boundProject->name);
            return new Response();
        });
    }

    /** @test - PB-006: No project bound when ID invalid */
    public function it_does_not_bind_project_for_invalid_id(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '-5');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertNull(app('current_project'));
            return new Response();
        });
    }
}
