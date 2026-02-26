<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\SetProjectContext;
use App\Models\Project;
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
        $user = Mockery::mock();
        $user->id = 1;
        
        $project = new Project();
        $project->id = 1;
        $project->user_id = 1;
        $project->name = 'Test Project';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project, $user) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $response = $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    /** @test - HE-002: Header takes precedence over session */
    public function header_takes_precedence_over_session(): void
    {
        $project2 = new Project();
        $project2->id = 2;
        $project2->user_id = 1;
        $project2->name = 'Project 2';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '2');
        
        // Create a mock session
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn(1);
        $request->setLaravelSession($session);

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project2) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(2, 1)
                ->once()
                ->andReturn($project2);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project2) {
            $this->assertSame($project2, app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-003: Header with numeric string */
    public function it_handles_numeric_string_in_header(): void
    {
        $project = new Project();
        $project->id = 123;
        $project->user_id = 1;

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '123');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(123, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - HE-004: Header with whitespace */
    public function it_trims_whitespace_from_header(): void
    {
        $project = new Project();
        $project->id = 5;
        $project->user_id = 1;

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '  5  ');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(5, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
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

        $response = $middleware->handle($request, function ($req) {
            // No project should be bound
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    /** @test - HE-006: Header with zero value */
    public function it_handles_zero_value_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '0');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
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
            $this->assertFalse(app()->has('current_project'));
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
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - HE-009: Header with float value */
    public function it_handles_float_value_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1.5');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        // Filter_var with FILTER_VALIDATE_INT will reject floats
        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - HE-010: Header with very large number */
    public function it_handles_very_large_number_in_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '999999999999999999');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) {
            // Even though the number is very large, filter_var handles it
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->once()
                ->andReturn(null); // Project not found
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        // Should handle gracefully without overflow error
        $response = $middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    // ========================================
    // Session Extraction Tests (SE)
    // ========================================

    /** @test - SE-001: Valid session project ID */
    public function it_binds_project_from_session(): void
    {
        $project = new Project();
        $project->id = 1;
        $project->user_id = 1;

        $request = Request::create('/api/test', 'GET');
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn(1);
        $request->setLaravelSession($session);

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - SE-002: Session without header */
    public function it_uses_session_when_no_header(): void
    {
        $project = new Project();
        $project->id = 1;
        $project->user_id = 1;

        $request = Request::create('/api/test', 'GET');
        // No header set
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn(1);
        $request->setLaravelSession($session);

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - SE-003: Session with null value */
    public function it_handles_null_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn(null);
        $request->setLaravelSession($session);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - SE-004: Session with zero value */
    public function it_handles_zero_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn(0);
        $request->setLaravelSession($session);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - SE-005: Session with negative value */
    public function it_handles_negative_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn(-1);
        $request->setLaravelSession($session);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - SE-006: Session with non-numeric value */
    public function it_handles_non_numeric_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn('abc');
        $request->setLaravelSession($session);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - SE-007: Session with empty string */
    public function it_handles_empty_string_session(): void
    {
        $request = Request::create('/api/test', 'GET');
        
        $session = Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $session->shouldReceive('get')->with('current_project_id')->andReturn('');
        $request->setLaravelSession($session);

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - SE-008: Session not set */
    public function it_handles_missing_session_value(): void
    {
        $request = Request::create('/api/test', 'GET');
        // No session set

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(true);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    // ========================================
    // Authorization Tests (AU)
    // ========================================

    /** @test - AU-001: User owns the project */
    public function it_binds_project_when_user_owns_it(): void
    {
        $project = new Project();
        $project->id = 1;
        $project->user_id = 1;
        $project->name = 'Test Project';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - AU-002: User does not own project */
    public function it_does_not_bind_project_when_user_does_not_own_it(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 2)
                ->once()
                ->andReturn(null); // Project not found for this user
        });

        // Authenticated as user 2, trying to access user 1's project
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(2);

        $middleware->handle($request, function ($req) {
            // Project should NOT be bound
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - AU-003: Cross-user project access attempt */
    public function it_prevents_cross_user_project_access(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '3'); // User 2's project

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(3, 1)
                ->once()
                ->andReturn(null); // Project not found for user 1
        });

        // Authenticated as user 1
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - AU-005: Anonymous user (unauthenticated) */
    public function it_skips_binding_for_unauthenticated_user(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1');

        $middleware = new SetProjectContext();

        Auth::shouldReceive('check')->once()->andReturn(false);

        // Should not query database at all
        $response = $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    // ========================================
    // Archived Project Handling Tests (AR)
    // ========================================

    /** @test - AR-001: Active project */
    public function it_binds_active_project(): void
    {
        $project = new Project();
        $project->id = 1;
        $project->user_id = 1;
        $project->archived_at = null;
        $project->name = 'Active Project';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
            return new Response();
        });
    }

    /** @test - AR-002: Archived project */
    public function it_binds_archived_project(): void
    {
        $project = new Project();
        $project->id = 4;
        $project->user_id = 1;
        $project->archived_at = now();
        $project->name = 'Archived Project';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '4');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(4, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        // Archived projects ARE accessible (business decision)
        $middleware->handle($request, function ($req) use ($project) {
            $this->assertSame($project, app('current_project'));
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
            $this->assertFalse(app()->has('current_project'));
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
        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - EC-008: Header with leading zeros */
    public function it_handles_leading_zeros_in_header(): void
    {
        // On 64-bit systems, leading zeros are parsed correctly by filter_var
        // e.g., "0001" becomes integer 1
        $project = new Project();
        $project->id = 1;
        $project->user_id = 1;
        $project->name = 'Test Project';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '0001');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            // Verify project is bound - use try/catch since app()->has() may not work correctly in test
            try {
                $boundProject = app('current_project');
                $this->assertSame($project, $boundProject);
            } catch (\Exception $e) {
                $this->fail('Project should have been bound but was not: ' . $e->getMessage());
            }
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
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '99999');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(99999, 1)
                ->once()
                ->andReturn(null);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
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
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    // ========================================
    // Project Binding Tests (PB)
    // ========================================

    /** @test - PB-001: Valid project bound to app */
    public function it_binds_valid_project_to_app_container(): void
    {
        $project = new Project();
        $project->id = 1;
        $project->name = 'Test Project';
        $project->domain = 'https://example.com';
        $project->user_id = 1;

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '1');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) use ($project) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(1, 1)
                ->once()
                ->andReturn($project);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) use ($project) {
            $boundProject = app('current_project');
            $this->assertNotNull($boundProject);
            $this->assertSame($project, $boundProject);
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
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }

    /** @test - PB-007: No project bound when not found */
    public function it_does_not_bind_project_when_not_found(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Project-ID', '999');

        $middleware = $this->partialMock(SetProjectContext::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('findProjectForUser')
                ->with(999, 1)
                ->once()
                ->andReturn(null);
        });

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn(1);

        $middleware->handle($request, function ($req) {
            $this->assertFalse(app()->has('current_project'));
            return new Response();
        });
    }
}
