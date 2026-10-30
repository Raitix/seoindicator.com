<?php

namespace Tests\Feature\Api\V1\AIVisibility;

use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests for AI Visibility Alert Routes.
 *
 * @see TASK-34-20
 * @see SPEC-034
 */
class AIVisibilityAlertRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ==========================================
    // Route Registration Tests (RR)
    // ==========================================

    /** @test */
    public function it_registers_get_alerts_index_route(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_registers_get_alert_show_route(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_registers_post_alert_read_route(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/read");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_registers_post_alert_resolve_route(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_registers_post_mark_all_read_route(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ai-visibility/alerts/mark-all-read');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_registers_delete_alert_route(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(204);
    }

    /** @test */
    public function it_registers_get_alert_preferences_route(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai-visibility/alert-preferences');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_registers_put_alert_preferences_route(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'email_notifications_enabled' => true,
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // Authentication Tests (AUTH)
    // ==========================================

    /** @test */
    public function unauthenticated_user_cannot_access_alerts_index(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_alert_show(): void
    {
        $alert = AIVisibilityAlert::factory()->create();

        $response = $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_mark_alert_as_read(): void
    {
        $alert = AIVisibilityAlert::factory()->create();

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/read");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_mark_alert_as_resolved(): void
    {
        $alert = AIVisibilityAlert::factory()->create();

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_mark_all_as_read(): void
    {
        $response = $this->postJson('/api/v1/ai-visibility/alerts/mark-all-read');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_alert(): void
    {
        $alert = AIVisibilityAlert::factory()->create();

        $response = $this->deleteJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_preferences(): void
    {
        $response = $this->getJson('/api/v1/ai-visibility/alert-preferences');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_preferences(): void
    {
        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', []);

        $response->assertStatus(401);
    }

    // ==========================================
    // HTTP Method Restriction Tests (HMR)
    // ==========================================

    /** @test */
    public function alerts_index_only_accepts_get(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/ai-visibility/alerts')->assertStatus(405);
        $this->putJson('/api/v1/ai-visibility/alerts')->assertStatus(405);
        $this->deleteJson('/api/v1/ai-visibility/alerts')->assertStatus(405);
    }

    /** @test */
    public function alert_show_only_accepts_get(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}")->assertStatus(405);
        $this->putJson("/api/v1/ai-visibility/alerts/{$alert->id}")->assertStatus(405);
    }

    /** @test */
    public function alert_read_only_accepts_post(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}/read")->assertStatus(405);
        $this->putJson("/api/v1/ai-visibility/alerts/{$alert->id}/read")->assertStatus(405);
        $this->deleteJson("/api/v1/ai-visibility/alerts/{$alert->id}/read")->assertStatus(405);
    }

    /** @test */
    public function alert_resolve_only_accepts_post(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve")->assertStatus(405);
        $this->putJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve")->assertStatus(405);
        $this->deleteJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve")->assertStatus(405);
    }

    /** @test */
    public function mark_all_read_only_accepts_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/ai-visibility/alerts/mark-all-read')->assertStatus(405);
        $this->putJson('/api/v1/ai-visibility/alerts/mark-all-read')->assertStatus(405);
        $this->deleteJson('/api/v1/ai-visibility/alerts/mark-all-read')->assertStatus(405);
    }

    /** @test */
    public function alert_delete_only_accepts_delete(): void
    {
        $user = User::factory()->create();
        $project = AIVisibilityProject::factory()->for($user, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($project, 'project')->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}")->assertStatus(200);
        $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}")->assertStatus(405);
        $this->putJson("/api/v1/ai-visibility/alerts/{$alert->id}")->assertStatus(405);
    }

    /** @test */
    public function alert_preferences_get_only_accepts_get(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/ai-visibility/alert-preferences')->assertStatus(405);
        $this->deleteJson('/api/v1/ai-visibility/alert-preferences')->assertStatus(405);
    }

    /** @test */
    public function alert_preferences_update_only_accepts_put(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/ai-visibility/alert-preferences')->assertStatus(200);
        $this->postJson('/api/v1/ai-visibility/alert-preferences')->assertStatus(405);
        $this->deleteJson('/api/v1/ai-visibility/alert-preferences')->assertStatus(405);
    }
}
