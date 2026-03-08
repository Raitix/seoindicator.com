<?php

namespace Tests\Feature\Api\V1\AIVisibility;

use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests for AI Visibility Alert Controller.
 *
 * @see TASK-34-20
 * @see SPEC-034
 */
class AIVisibilityAlertControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AIVisibilityProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = AIVisibilityProject::factory()->for($this->user, 'user')->create();
    }

    // ==========================================
    // Index Endpoint Tests (IDX)
    // ==========================================

    /** @test */
    public function it_returns_paginated_list_of_user_alerts(): void
    {
        Sanctum::actingAs($this->user);

        AIVisibilityAlert::factory()->count(5)->for($this->project, 'project')->create();

        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'project_id',
                        'project_name',
                        'alert_type',
                        'threshold',
                        'previous_value',
                        'current_value',
                        'change_percent',
                        'is_read',
                        'is_resolved',
                        'triggered_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'unread_count',
                ],
            ]);
    }

    /** @test */
    public function it_only_returns_alerts_for_authenticated_user(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->for($otherUser, 'user')->create();

        // Create alerts for both users
        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->create();
        AIVisibilityAlert::factory()->count(5)->for($otherProject, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function it_filters_by_project_id(): void
    {
        $project2 = AIVisibilityProject::factory()->for($this->user, 'user')->create();

        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->create();
        AIVisibilityAlert::factory()->count(2)->for($project2, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/alerts?project_id={$this->project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function it_filters_by_is_read(): void
    {
        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->create(['is_read' => false]);
        AIVisibilityAlert::factory()->count(2)->for($this->project, 'project')->create(['is_read' => true]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts?is_read=false');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function it_filters_by_alert_type(): void
    {
        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->mentionSpike()->create();
        AIVisibilityAlert::factory()->count(2)->for($this->project, 'project')->mentionDrop()->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts?alert_type=mention_spike');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function it_sorts_by_triggered_at_desc(): void
    {
        $alert1 = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['triggered_at' => now()->subDays(2)]);
        $alert2 = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['triggered_at' => now()->subDay()]);
        $alert3 = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['triggered_at' => now()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $alert3->id)
            ->assertJsonPath('data.1.id', $alert2->id)
            ->assertJsonPath('data.2.id', $alert1->id);
    }

    /** @test */
    public function it_returns_unread_count_in_meta(): void
    {
        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->create(['is_read' => false]);
        AIVisibilityAlert::factory()->count(2)->for($this->project, 'project')->create(['is_read' => true]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(200)
            ->assertJsonPath('meta.unread_count', 3);
    }

    /** @test */
    public function it_paginates_results(): void
    {
        AIVisibilityAlert::factory()->count(25)->for($this->project, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(10, 'data');
    }

    // ==========================================
    // Show Endpoint Tests (SH)
    // ==========================================

    /** @test */
    public function it_returns_single_alert(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $alert->id)
            ->assertJsonPath('data.project_name', $this->project->brand_name);
    }

    /** @test */
    public function it_returns_404_for_non_existent_alert(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /** @test */
    public function it_returns_403_for_other_users_alert(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->for($otherUser, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($otherProject, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    // ==========================================
    // Mark as Read Tests (RD)
    // ==========================================

    /** @test */
    public function it_marks_alert_as_read(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['is_read' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('ai_visibility_alerts', [
            'id' => $alert->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function it_returns_403_when_marking_other_users_alert_as_read(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->for($otherUser, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($otherProject, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/read");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_returns_404_when_marking_non_existent_alert_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai-visibility/alerts/99999/read');

        $response->assertStatus(404);
    }

    // ==========================================
    // Mark as Resolved Tests (RS)
    // ==========================================

    /** @test */
    public function it_marks_alert_as_resolved(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['is_resolved' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_resolved', true);

        $this->assertDatabaseHas('ai_visibility_alerts', [
            'id' => $alert->id,
            'is_resolved' => true,
        ]);

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    /** @test */
    public function it_sets_resolved_at_timestamp(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['is_resolved' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve");

        $response->assertStatus(200);

        $alert->refresh();
        $this->assertNotNull($alert->resolved_at);
        $response->assertJsonStructure(['data' => ['resolved_at']]);
    }

    /** @test */
    public function it_returns_403_when_resolving_other_users_alert(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->for($otherUser, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($otherProject, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve");

        $response->assertStatus(403);
    }

    // ==========================================
    // Mark All as Read Tests (MAR)
    // ==========================================

    /** @test */
    public function it_marks_all_unread_alerts_as_read(): void
    {
        AIVisibilityAlert::factory()->count(5)->for($this->project, 'project')->create(['is_read' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai-visibility/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.updated_count', 5);

        $this->assertEquals(0, AIVisibilityAlert::whereHas('project', fn($q) => $q->where('user_id', $this->user->id))
            ->where('is_read', false)->count());
    }

    /** @test */
    public function it_scopes_mark_all_read_by_project(): void
    {
        $project2 = AIVisibilityProject::factory()->for($this->user, 'user')->create();

        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->create(['is_read' => false]);
        AIVisibilityAlert::factory()->count(2)->for($project2, 'project')->create(['is_read' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/mark-all-read?project_id={$this->project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.updated_count', 3);

        // Project 2 should still have unread alerts
        $this->assertEquals(2, AIVisibilityAlert::where('project_id', $project2->id)
            ->where('is_read', false)->count());
    }

    /** @test */
    public function it_does_not_affect_other_users_alerts(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->for($otherUser, 'user')->create();

        AIVisibilityAlert::factory()->count(3)->for($this->project, 'project')->create(['is_read' => false]);
        AIVisibilityAlert::factory()->count(2)->for($otherProject, 'project')->create(['is_read' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai-visibility/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonPath('data.updated_count', 3);

        // Other user's alerts should still be unread
        $this->assertEquals(2, AIVisibilityAlert::where('project_id', $otherProject->id)
            ->where('is_read', false)->count());
    }

    // ==========================================
    // Delete Endpoint Tests (DEL)
    // ==========================================

    /** @test */
    public function it_deletes_alert(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('ai_visibility_alerts', [
            'id' => $alert->id,
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_alert(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/v1/ai-visibility/alerts/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_403_when_deleting_other_users_alert(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = AIVisibilityProject::factory()->for($otherUser, 'user')->create();
        $alert = AIVisibilityAlert::factory()->for($otherProject, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/ai-visibility/alerts/{$alert->id}");

        $response->assertStatus(403);
    }

    // ==========================================
    // Alert Preferences Tests (PREF)
    // ==========================================

    /** @test */
    public function it_returns_user_preferences(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alert-preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'email_notifications_enabled',
                    'webhook_url',
                    'alert_types',
                ],
            ]);
    }

    /** @test */
    public function it_returns_default_preferences_for_new_user(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alert-preferences');

        $response->assertStatus(200)
            ->assertJsonPath('data.email_notifications_enabled', true)
            ->assertJsonPath('data.alert_types', ['mention_spike', 'mention_drop', 'competitor_gain']);
    }

    /** @test */
    public function it_updates_user_preferences(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'email_notifications_enabled' => false,
            'webhook_url' => 'https://example.com/webhook',
            'alert_types' => ['mention_spike'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email_notifications_enabled', false)
            ->assertJsonPath('data.webhook_url', 'https://example.com/webhook')
            ->assertJsonPath('data.alert_types', ['mention_spike']);
    }

    /** @test */
    public function it_validates_preferences_update(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'webhook_url' => 'not-a-url',
            'alert_types' => ['invalid_type'],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_alert_types(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'alert_types' => ['mention_spike', 'invalid_type'],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_accepts_valid_alert_types(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'alert_types' => ['mention_spike', 'mention_drop', 'competitor_gain'],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_webhook_url_format(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'webhook_url' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_accepts_null_webhook_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/ai-visibility/alert-preferences', [
            'webhook_url' => null,
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // Edge Cases and Security Tests
    // ==========================================

    /** @test */
    public function it_handles_empty_alerts_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_handles_already_read_alert(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['is_read' => true]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/read");

        // Should still succeed (idempotent)
        $response->assertStatus(200)
            ->assertJsonPath('data.is_read', true);
    }

    /** @test */
    public function it_handles_already_resolved_alert(): void
    {
        $alert = AIVisibilityAlert::factory()->for($this->project, 'project')->create(['is_resolved' => true]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai-visibility/alerts/{$alert->id}/resolve");

        // Should still succeed (idempotent)
        $response->assertStatus(200)
            ->assertJsonPath('data.is_resolved', true);
    }

    /** @test */
    public function it_ignores_invalid_project_id_filter(): void
    {
        Sanctum::actingAs($this->user);

        // Try to filter by a project that doesn't belong to the user
        $response = $this->getJson('/api/v1/ai-visibility/alerts?project_id=99999');

        // Should return empty results, not error
        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
    }

    /** @test */
    public function it_limits_per_page_to_100(): void
    {
        AIVisibilityAlert::factory()->count(150)->for($this->project, 'project')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai-visibility/alerts?per_page=200');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 100);
    }
}
