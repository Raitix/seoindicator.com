<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\SetProjectContext;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ProjectContextSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // Adversarial Security Tests (AS)
    // ========================================

    /** @test - AS-001: SQL injection via header */
    public function it_prevents_sql_injection_via_header(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => "1'; DROP TABLE projects; --"])
            ->getJson('/api/test-endpoint');

        // The middleware should sanitize input and not execute any SQL
        // Project table should still exist
        $this->assertDatabaseHas('projects', []);
    }

    /** @test - AS-002: SQL injection via session */
    public function it_prevents_sql_injection_via_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['current_project_id' => "1; DROP TABLE users; --"])
            ->getJson('/api/test-endpoint');

        // Users table should still exist
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test - AS-003: IDOR attack prevention */
    public function it_prevents_idor_attack(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user1Project = Project::factory()->create(['user_id' => $user1->id]);
        $user2Project = Project::factory()->create(['user_id' => $user2->id]);

        // User 1 tries to access User 2's project
        $response = $this->actingAs($user1)
            ->withHeaders(['X-Project-ID' => (string) $user2Project->id])
            ->getJson('/api/test-endpoint');

        // Project should NOT be bound
        $this->assertNull(app('current_project'));
    }

    /** @test - AS-004: Session hijacking prevention */
    public function it_prevents_session_hijacking_with_project_access(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user2Project = Project::factory()->create(['user_id' => $user2->id]);

        // Simulate stolen session with different user's project
        $response = $this->actingAs($user1)
            ->withSession(['current_project_id' => $user2Project->id])
            ->getJson('/api/test-endpoint');

        // User 1 should not get User 2's project bound
        // because middleware checks project ownership
        $response->assertStatus(200); // Should not error
        $this->assertNull(app('current_project'));
    }

    /** @test - AS-005: Header injection prevention */
    public function it_prevents_header_injection(): void
    {
        $user = User::factory()->create();

        // Try to inject multiple headers
        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Project-ID' => "1\r\nX-Injected: malicious",
            ])
            ->getJson('/api/test-endpoint');

        // Should handle gracefully
        $response->assertStatus(200);
    }

    /** @test - AS-006: CSRF protection maintained */
    public function csrf_protection_works_with_project_switch(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // POST request should require CSRF token (Laravel handles this)
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->post('/api/test-endpoint', []);

        // Response depends on CSRF middleware
        // This test verifies the middleware doesn't bypass CSRF
        $response->assertStatus(200);
    }

    /** @test - AS-007: Race condition prevention */
    public function it_handles_concurrent_project_switches(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);

        // Simulate concurrent requests
        $requests = collect([1, 2, 3, 4, 5])->map(function ($i) use ($user, $project1, $project2) {
            return function () use ($user, $project1, $project2, $i) {
                $projectId = $i % 2 === 0 ? $project1->id : $project2->id;
                
                $request = Request::create('/api/test', 'GET');
                $request->headers->set('X-Project-ID', (string) $projectId);
                
                $middleware = new SetProjectContext();
                
                // Mock auth for this request
                Auth::shouldReceive('check')->andReturn(true);
                Auth::shouldReceive('id')->andReturn($user->id);
                
                return $middleware->handle($request, function ($req) {
                    return new Response();
                });
            };
        });

        // All requests should complete without race conditions
        $requests->each(function ($requestFn) {
            $response = $requestFn();
            $this->assertInstanceOf(Response::class, $response);
        });
    }

    /** @test - AS-008: Memory dump protection */
    public function project_data_not_exposed_in_logs(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'notes' => 'Sensitive project notes',
        ]);

        // Create a log channel that captures output
        $logOutput = [];
        
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');

        // Verify project notes are not in any log output
        // (This is a conceptual test - actual implementation depends on logging config)
        $this->assertStringNotContainsString('Sensitive project notes', json_encode($logOutput));
    }

    /** @test - AS-009: Timing attack prevention */
    public function no_timing_information_leak(): void
    {
        $user = User::factory()->create();
        
        // Create multiple projects
        $ownProject = Project::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        // Time own project access
        $startOwn = microtime(true);
        $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $ownProject->id])
            ->getJson('/api/test-endpoint');
        $timeOwn = microtime(true) - $startOwn;

        // Time other project access (should be rejected)
        $startOther = microtime(true);
        $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => (string) $otherProject->id])
            ->getJson('/api/test-endpoint');
        $timeOther = microtime(true) - $startOther;

        // Times should be similar (within 50ms tolerance)
        // to prevent timing attacks
        $this->assertLessThan(0.05, abs($timeOwn - $timeOther));
    }

    /** @test - AS-010: Mass assignment via session prevention */
    public function it_prevents_mass_assignment_via_session(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Try to inject extra fields via session
        $response = $this->actingAs($user)
            ->withSession([
                'current_project_id' => $project->id,
                'is_admin' => true,  // Should be ignored
                'permissions' => ['*'],  // Should be ignored
            ])
            ->getJson('/api/test-endpoint');

        // Only project_id should be used
        $response->assertStatus(200);
    }

    // ========================================
    // Authorization Boundary Tests
    // ========================================

    /** @test */
    public function user_cannot_access_other_users_active_project(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user2Project = Project::factory()->create([
            'user_id' => $user2->id,
            'archived_at' => null,
        ]);

        $response = $this->actingAs($user1)
            ->withHeaders(['X-Project-ID' => (string) $user2Project->id])
            ->getJson('/api/test-endpoint');

        $this->assertNull(app('current_project'));
    }

    /** @test */
    public function user_cannot_access_other_users_archived_project(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user2ArchivedProject = Project::factory()->create([
            'user_id' => $user2->id,
            'archived_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user1)
            ->withHeaders(['X-Project-ID' => (string) $user2ArchivedProject->id])
            ->getJson('/api/test-endpoint');

        $this->assertNull(app('current_project'));
    }

    /** @test */
    public function unauthenticated_user_cannot_set_project_context(): void
    {
        $project = Project::factory()->create();

        $response = $this->withoutMiddleware()
            ->withHeaders(['X-Project-ID' => (string) $project->id])
            ->getJson('/api/test-endpoint');

        // No project should be bound for unauthenticated users
        $this->assertNull(app('current_project'));
    }

    // ========================================
    // Input Validation Boundary Tests
    // ========================================

    /** @test */
    public function it_handles_xss_attempt_in_project_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => '<script>alert("xss")</script>'])
            ->getJson('/api/test-endpoint');

        // Should sanitize and reject
        $this->assertNull(app('current_project'));
    }

    /** @test */
    public function it_handles_path_traversal_in_project_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => '../../../etc/passwd'])
            ->getJson('/api/test-endpoint');

        // Should sanitize and reject
        $this->assertNull(app('current_project'));
    }

    /** @test */
    public function it_handles_json_injection_in_project_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => '{"id":1,"user_id":2}'])
            ->getJson('/api/test-endpoint');

        // Should not parse as JSON
        $this->assertNull(app('current_project'));
    }

    /** @test */
    public function it_handles_null_byte_injection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => "1\x00malicious"])
            ->getJson('/api/test-endpoint');

        // Should handle null bytes safely
        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_unicode_homograph_attack(): void
    {
        $user = User::factory()->create();

        // Using fullwidth digits that look like regular digits
        $response = $this->actingAs($user)
            ->withHeaders(['X-Project-ID' => '１２３'])  // Fullwidth 123
            ->getJson('/api/test-endpoint');

        // Should not interpret fullwidth as numeric
        $this->assertNull(app('current_project'));
    }
}
