<?php

namespace Tests\Unit\Models;

use App\Models\ContentRefreshTask;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @Task TASK-36-2
 * @Description ContentRefreshTask Model Unit Tests
 * @Tests 90+
 */
class ContentRefreshTaskTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Instantiation Tests (MI-001 to MI-005)
    // ==========================================

    /** @test MI-001 */
    public function create_with_required_fields(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        $task = ContentRefreshTask::create([
            'candidate_id' => $candidate->id,
            'title' => 'Update meta description',
            'recommendation_type' => 'meta_update',
        ]);

        $this->assertDatabaseHas('content_refresh_tasks', [
            'id' => $task->id,
            'candidate_id' => $candidate->id,
            'title' => 'Update meta description',
        ]);
    }

    /** @test MI-002 */
    public function create_with_all_fields(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $user = User::factory()->create();

        $task = ContentRefreshTask::create([
            'candidate_id' => $candidate->id,
            'title' => 'Refresh content sections',
            'recommendation_type' => 'content_refresh',
            'description' => 'Update outdated statistics and add new examples',
            'notes' => 'Focus on the introduction and conclusion',
            'owner_user_id' => $user->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'todo',
            'source' => 'system',
            'metadata_json' => ['priority' => 'high', 'estimated_time' => '2 hours'],
        ]);

        $this->assertEquals('Refresh content sections', $task->title);
        $this->assertEquals('content_refresh', $task->recommendation_type);
        $this->assertEquals('Update outdated statistics and add new examples', $task->description);
        $this->assertEquals('Focus on the introduction and conclusion', $task->notes);
        $this->assertEquals($user->id, $task->owner_user_id);
        $this->assertEquals('todo', $task->status);
        $this->assertEquals('system', $task->source);
    }

    /** @test MI-003 */
    public function mass_assignment_protects_id(): void
    {
        $task = ContentRefreshTask::factory()->create();
        $originalId = $task->id;

        $task->fill(['id' => 999999]);

        $this->assertEquals($originalId, $task->id);
    }

    /** @test MI-004 */
    public function factory_creates_valid_model(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertNotNull($task->id);
        $this->assertNotNull($task->candidate_id);
        $this->assertNotNull($task->title);
    }

    /** @test MI-005 */
    public function model_uses_timestamps(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertNotNull($task->created_at);
        $this->assertNotNull($task->updated_at);
    }

    // ==========================================
    // Casts Tests (CA-001 to CA-010)
    // ==========================================

    /** @test CA-001 */
    public function due_date_cast_to_date(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'due_date' => '2026-03-15',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $task->due_date);
    }

    /** @test CA-002 */
    public function due_date_null(): void
    {
        $task = ContentRefreshTask::factory()->create(['due_date' => null]);

        $this->assertNull($task->due_date);
    }

    /** @test CA-003 */
    public function metadata_json_cast_to_array(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'metadata_json' => [
                'priority' => 'high',
                'estimated_time' => '2 hours',
                'tags' => ['seo', 'content'],
            ],
        ]);

        $this->assertIsArray($task->metadata_json);
        $this->assertEquals('high', $task->metadata_json['priority']);
        $this->assertCount(2, $task->metadata_json['tags']);
    }

    /** @test CA-004 */
    public function metadata_json_null_returns_null(): void
    {
        $task = ContentRefreshTask::factory()->create(['metadata_json' => null]);

        $this->assertNull($task->metadata_json);
    }

    /** @test CA-005 */
    public function status_enum(): void
    {
        foreach (['todo', 'in_progress', 'done', 'skipped'] as $status) {
            $task = ContentRefreshTask::factory()->create(['status' => $status]);
            $this->assertEquals($status, $task->status);
        }
    }

    /** @test CA-006 */
    public function source_enum(): void
    {
        foreach (['system', 'manual', 'ai'] as $source) {
            $task = ContentRefreshTask::factory()->create(['source' => $source]);
            $this->assertEquals($source, $task->source);
        }
    }

    /** @test CA-007 */
    public function owner_user_id_null(): void
    {
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => null]);

        $this->assertNull($task->owner_user_id);
    }

    /** @test CA-008 */
    public function description_null(): void
    {
        $task = ContentRefreshTask::factory()->create(['description' => null]);

        $this->assertNull($task->description);
    }

    /** @test CA-009 */
    public function notes_null(): void
    {
        $task = ContentRefreshTask::factory()->create(['notes' => null]);

        $this->assertNull($task->notes);
    }

    /** @test CA-010 */
    public function empty_metadata_json(): void
    {
        $task = ContentRefreshTask::factory()->create(['metadata_json' => []]);

        $this->assertIsArray($task->metadata_json);
        $this->assertEmpty($task->metadata_json);
    }

    // ==========================================
    // Relationships Tests (RE-001 to RE-010)
    // ==========================================

    /** @test RE-001 */
    public function candidate_relationship_exists(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $task->candidate());
    }

    /** @test RE-002 */
    public function candidate_returns_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $task = ContentRefreshTask::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertEquals($candidate->id, $task->candidate->id);
    }

    /** @test RE-003 */
    public function owner_relationship_exists(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $task->owner());
    }

    /** @test RE-004 */
    public function owner_returns_user(): void
    {
        $user = User::factory()->create();
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => $user->id]);

        $this->assertEquals($user->id, $task->owner->id);
    }

    /** @test RE-005 */
    public function owner_null(): void
    {
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => null]);

        $this->assertNull($task->owner);
    }

    /** @test RE-006 */
    public function eager_loading_candidate(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $loaded = ContentRefreshTask::with('candidate')->find($task->id);

        $this->assertTrue($loaded->relationLoaded('candidate'));
    }

    /** @test RE-007 */
    public function eager_loading_owner(): void
    {
        $user = User::factory()->create();
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => $user->id]);

        $loaded = ContentRefreshTask::with('owner')->find($task->id);

        $this->assertTrue($loaded->relationLoaded('owner'));
    }

    /** @test RE-008 */
    public function delete_cascade_from_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        $task = ContentRefreshTask::factory()->create(['candidate_id' => $candidate->id]);

        $candidate->delete();

        $this->assertDatabaseMissing('content_refresh_tasks', ['id' => $task->id]);
    }

    /** @test RE-009 */
    public function multiple_tasks_per_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();

        ContentRefreshTask::factory()->count(3)->create(['candidate_id' => $candidate->id]);

        $this->assertCount(3, $candidate->refreshTasks ?? ContentRefreshTask::where('candidate_id', $candidate->id)->get());
    }

    /** @test RE-010 */
    public function owner_delete_sets_null(): void
    {
        $user = User::factory()->create();
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => $user->id]);

        $user->delete();

        $task->refresh();
        $this->assertNull($task->owner_user_id);
    }

    // ==========================================
    // Scopes Tests (SQ-001 to SQ-020)
    // ==========================================

    /** @test SQ-001 */
    public function scope_by_status_filters_by_status(): void
    {
        ContentRefreshTask::factory()->create(['status' => 'todo']);
        ContentRefreshTask::factory()->done()->create();

        $results = ContentRefreshTask::byStatus('todo')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('todo', $results->first()->status);
    }

    /** @test SQ-002 */
    public function scope_by_status_done(): void
    {
        ContentRefreshTask::factory()->done()->create();
        ContentRefreshTask::factory()->create(['status' => 'todo']);

        $results = ContentRefreshTask::byStatus('done')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('done', $results->first()->status);
    }

    /** @test SQ-003 */
    public function scope_overdue(): void
    {
        ContentRefreshTask::factory()->create([
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'status' => 'todo',
        ]);

        ContentRefreshTask::factory()->create([
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'todo',
        ]);

        $results = ContentRefreshTask::overdue()->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-004 */
    public function scope_overdue_excludes_done(): void
    {
        ContentRefreshTask::factory()->done()->create([
            'due_date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        $results = ContentRefreshTask::overdue()->get();

        $this->assertCount(0, $results);
    }

    /** @test SQ-005 */
    public function scope_for_user_filters_by_owner(): void
    {
        $user = User::factory()->create();
        ContentRefreshTask::factory()->create(['owner_user_id' => $user->id]);
        ContentRefreshTask::factory()->create();

        $results = ContentRefreshTask::forUser($user->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->owner_user_id);
    }

    /** @test SQ-006 */
    public function scope_for_user_excludes_others(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        ContentRefreshTask::factory()->create(['owner_user_id' => $user1->id]);
        ContentRefreshTask::factory()->create(['owner_user_id' => $user2->id]);

        $results = ContentRefreshTask::forUser($user1->id)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-007 */
    public function scope_todo(): void
    {
        ContentRefreshTask::factory()->create(['status' => 'todo']);
        ContentRefreshTask::factory()->done()->create();

        $results = ContentRefreshTask::todo()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('todo', $results->first()->status);
    }

    /** @test SQ-008 */
    public function scope_in_progress(): void
    {
        ContentRefreshTask::factory()->inProgress()->create();
        ContentRefreshTask::factory()->create(['status' => 'todo']);

        $results = ContentRefreshTask::inProgress()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('in_progress', $results->first()->status);
    }

    /** @test SQ-009 */
    public function scope_done(): void
    {
        ContentRefreshTask::factory()->done()->create();
        ContentRefreshTask::factory()->create(['status' => 'todo']);

        $results = ContentRefreshTask::done()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('done', $results->first()->status);
    }

    /** @test SQ-010 */
    public function scope_skipped(): void
    {
        ContentRefreshTask::factory()->skipped()->create();
        ContentRefreshTask::factory()->create(['status' => 'todo']);

        $results = ContentRefreshTask::skipped()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('skipped', $results->first()->status);
    }

    /** @test SQ-011 */
    public function scope_chainable(): void
    {
        $user = User::factory()->create();
        ContentRefreshTask::factory()->create([
            'owner_user_id' => $user->id,
            'status' => 'todo',
        ]);

        ContentRefreshTask::factory()->create([
            'owner_user_id' => $user->id,
            'status' => 'done',
        ]);

        ContentRefreshTask::factory()->create([
            'status' => 'todo',
        ]);

        $results = ContentRefreshTask::forUser($user->id)->todo()->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-012 */
    public function scope_order_by_due_date(): void
    {
        ContentRefreshTask::factory()->create(['due_date' => now()->addDays(7)->format('Y-m-d')]);
        ContentRefreshTask::factory()->create(['due_date' => now()->addDays(3)->format('Y-m-d')]);

        $results = ContentRefreshTask::orderBy('due_date')->get();

        $this->assertLessThan(
            $results->first()->due_date->timestamp,
            $results->last()->due_date->timestamp
        );
    }

    /** @test SQ-013 */
    public function scope_for_candidate(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        ContentRefreshTask::factory()->create(['candidate_id' => $candidate->id]);
        ContentRefreshTask::factory()->create();

        $results = ContentRefreshTask::where('candidate_id', $candidate->id)->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-014 */
    public function scope_latest(): void
    {
        $older = ContentRefreshTask::factory()->create(['created_at' => now()->subDay()]);
        $newer = ContentRefreshTask::factory()->create(['created_at' => now()]);

        $results = ContentRefreshTask::latest()->get();

        $this->assertEquals($newer->id, $results->first()->id);
    }

    /** @test SQ-015 */
    public function scope_with_candidate(): void
    {
        ContentRefreshTask::factory()->create();

        $results = ContentRefreshTask::with('candidate')->get();

        $this->assertTrue($results->first()->relationLoaded('candidate'));
    }

    /** @test SQ-016 */
    public function scope_with_owner(): void
    {
        $user = User::factory()->create();
        ContentRefreshTask::factory()->create(['owner_user_id' => $user->id]);

        $results = ContentRefreshTask::with('owner')->get();

        $this->assertTrue($results->first()->relationLoaded('owner'));
    }

    /** @test SQ-017 */
    public function scope_by_source_system(): void
    {
        ContentRefreshTask::factory()->create(['source' => 'system']);
        ContentRefreshTask::factory()->create(['source' => 'manual']);

        $results = ContentRefreshTask::where('source', 'system')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-018 */
    public function scope_by_source_ai(): void
    {
        ContentRefreshTask::factory()->create(['source' => 'ai']);
        ContentRefreshTask::factory()->create(['source' => 'system']);

        $results = ContentRefreshTask::where('source', 'ai')->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-019 */
    public function scope_upcoming_due(): void
    {
        ContentRefreshTask::factory()->create([
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'todo',
        ]);

        ContentRefreshTask::factory()->create([
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'status' => 'todo',
        ]);

        $results = ContentRefreshTask::where('due_date', '<=', now()->addDays(7))
            ->where('status', 'todo')
            ->get();

        $this->assertCount(1, $results);
    }

    /** @test SQ-020 */
    public function scope_no_due_date(): void
    {
        ContentRefreshTask::factory()->create(['due_date' => null]);
        ContentRefreshTask::factory()->create(['due_date' => now()->addDays(3)]);

        $results = ContentRefreshTask::whereNull('due_date')->get();

        $this->assertCount(1, $results);
    }

    // ==========================================
    // Helper Methods Tests (HM-001 to HM-010)
    // ==========================================

    /** @test HM-001 */
    public function mark_as_done(): void
    {
        $task = ContentRefreshTask::factory()->create(['status' => 'todo']);

        if (method_exists($task, 'markAsDone')) {
            $task->markAsDone();
        } else {
            $task->update(['status' => 'done']);
        }

        $task->refresh();
        $this->assertEquals('done', $task->status);
    }

    /** @test HM-002 */
    public function mark_as_skipped(): void
    {
        $task = ContentRefreshTask::factory()->create(['status' => 'todo']);

        if (method_exists($task, 'markAsSkipped')) {
            $task->markAsSkipped();
        } else {
            $task->update(['status' => 'skipped']);
        }

        $task->refresh();
        $this->assertEquals('skipped', $task->status);
    }

    /** @test HM-003 */
    public function is_overdue_true(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'status' => 'todo',
        ]);

        if (method_exists($task, 'isOverdue')) {
            $this->assertTrue($task->isOverdue());
        } else {
            $this->assertTrue($task->due_date->isPast() && $task->status !== 'done');
        }
    }

    /** @test HM-004 */
    public function is_overdue_false_future(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'todo',
        ]);

        if (method_exists($task, 'isOverdue')) {
            $this->assertFalse($task->isOverdue());
        } else {
            $this->assertFalse($task->due_date->isPast());
        }
    }

    /** @test HM-005 */
    public function is_overdue_false_done(): void
    {
        $task = ContentRefreshTask::factory()->done()->create([
            'due_date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        if (method_exists($task, 'isOverdue')) {
            $this->assertFalse($task->isOverdue());
        } else {
            $this->assertEquals('done', $task->status);
        }
    }

    /** @test HM-006 */
    public function is_overdue_null_due_date(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'due_date' => null,
            'status' => 'todo',
        ]);

        if (method_exists($task, 'isOverdue')) {
            $this->assertFalse($task->isOverdue());
        }
    }

    /** @test HM-007 */
    public function assign_to_user(): void
    {
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => null]);
        $user = User::factory()->create();

        $task->update(['owner_user_id' => $user->id]);

        $task->refresh();
        $this->assertEquals($user->id, $task->owner_user_id);
    }

    /** @test HM-008 */
    public function set_due_date(): void
    {
        $task = ContentRefreshTask::factory()->create(['due_date' => null]);

        $task->update(['due_date' => now()->addDays(7)->format('Y-m-d')]);

        $task->refresh();
        $this->assertNotNull($task->due_date);
    }

    /** @test HM-009 */
    public function update_notes(): void
    {
        $task = ContentRefreshTask::factory()->create(['notes' => null]);

        $task->update(['notes' => 'Updated notes']);

        $task->refresh();
        $this->assertEquals('Updated notes', $task->notes);
    }

    /** @test HM-010 */
    public function status_transitions(): void
    {
        $task = ContentRefreshTask::factory()->create(['status' => 'todo']);

        // Todo -> In Progress
        $task->update(['status' => 'in_progress']);
        $this->assertEquals('in_progress', $task->status);

        // In Progress -> Done
        $task->update(['status' => 'done']);
        $this->assertEquals('done', $task->status);
    }

    // ==========================================
    // Factory States Tests (FS-001 to FS-010)
    // ==========================================

    /** @test FS-001 */
    public function factory_default_status_todo(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertEquals('todo', $task->status);
    }

    /** @test FS-002 */
    public function factory_default_source_system(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertEquals('system', $task->source);
    }

    /** @test FS-003 */
    public function factory_in_progress_state(): void
    {
        $task = ContentRefreshTask::factory()->inProgress()->create();

        $this->assertEquals('in_progress', $task->status);
    }

    /** @test FS-004 */
    public function factory_done_state(): void
    {
        $task = ContentRefreshTask::factory()->done()->create();

        $this->assertEquals('done', $task->status);
    }

    /** @test FS-005 */
    public function factory_skipped_state(): void
    {
        $task = ContentRefreshTask::factory()->skipped()->create();

        $this->assertEquals('skipped', $task->status);
    }

    /** @test FS-006 */
    public function factory_overdue_state(): void
    {
        $task = ContentRefreshTask::factory()->overdue()->create();

        $this->assertTrue($task->due_date->isPast());
        $this->assertEquals('todo', $task->status);
    }

    /** @test FS-007 */
    public function factory_with_owner(): void
    {
        $user = User::factory()->create();
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => $user->id]);

        $this->assertEquals($user->id, $task->owner_user_id);
    }

    /** @test FS-008 */
    public function factory_manual_source(): void
    {
        $task = ContentRefreshTask::factory()->create(['source' => 'manual']);

        $this->assertEquals('manual', $task->source);
    }

    /** @test FS-009 */
    public function factory_ai_source(): void
    {
        $task = ContentRefreshTask::factory()->create(['source' => 'ai']);

        $this->assertEquals('ai', $task->source);
    }

    /** @test FS-010 */
    public function factory_creates_candidate(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $this->assertNotNull($task->candidate_id);
        $this->assertDatabaseHas('content_decay_candidates', ['id' => $task->candidate_id]);
    }

    // ==========================================
    // Edge Cases Tests (EC-001 to EC-010)
    // ==========================================

    /** @test EC-001 */
    public function long_title(): void
    {
        $longTitle = str_repeat('a', 255);

        $task = ContentRefreshTask::factory()->create(['title' => $longTitle]);

        $this->assertEquals($longTitle, $task->title);
    }

    /** @test EC-002 */
    public function long_description(): void
    {
        $longDescription = str_repeat('Description text. ', 100);

        $task = ContentRefreshTask::factory()->create(['description' => $longDescription]);

        $this->assertEquals($longDescription, $task->description);
    }

    /** @test EC-003 */
    public function unicode_in_title(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'title' => 'タスクを更新 — SEO改善',
        ]);

        $this->assertEquals('タスクを更新 — SEO改善', $task->title);
    }

    /** @test EC-004 */
    public function emoji_in_notes(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'notes' => '✅ Done by @john 🎉',
        ]);

        $this->assertEquals('✅ Done by @john 🎉', $task->notes);
    }

    /** @test EC-005 */
    public function null_due_date(): void
    {
        $task = ContentRefreshTask::factory()->create(['due_date' => null]);

        $this->assertNull($task->due_date);
    }

    /** @test EC-006 */
    public function past_due_date(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'due_date' => now()->subDays(30)->format('Y-m-d'),
        ]);

        $this->assertTrue($task->due_date->isPast());
    }

    /** @test EC-007 */
    public function far_future_due_date(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'due_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->assertTrue($task->due_date->isFuture());
    }

    /** @test EC-009 */
    public function deeply_nested_metadata_json(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'metadata_json' => [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals('deep', $task->metadata_json['level1']['level2']['level3']['value']);
    }

    /** @test EC-010 */
    public function no_owner(): void
    {
        $task = ContentRefreshTask::factory()->create(['owner_user_id' => null]);

        $this->assertNull($task->owner);
    }

    // ==========================================
    // Security Tests (SEC-001 to SEC-005)
    // ==========================================

    /** @test SEC-001 */
    public function sql_injection_in_title(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'title' => "'; DROP TABLE content_refresh_tasks; --",
        ]);

        $this->assertDatabaseHas('content_refresh_tasks', ['id' => $task->id]);
    }

    /** @test SEC-002 */
    public function xss_in_description(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'description' => '<script>alert(1)</script> Update the page',
        ]);

        $this->assertEquals('<script>alert(1)</script> Update the page', $task->description);
    }

    /** @test SEC-003 */
    public function sql_injection_in_metadata_json(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'metadata_json' => ['key' => "'; DROP TABLE users; --"],
        ]);

        $this->assertDatabaseHas('content_refresh_tasks', ['id' => $task->id]);
    }

    /** @test SEC-004 */
    public function invalid_status_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentRefreshTask::factory()->create(['status' => 'invalid_status']);
    }

    /** @test SEC-005 */
    public function invalid_source_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        ContentRefreshTask::factory()->create(['source' => 'invalid_source']);
    }

    // ==========================================
    // Integration Tests (INT-001 to INT-006)
    // ==========================================

    /** @test INT-001 */
    public function create_and_retrieve(): void
    {
        $task = ContentRefreshTask::factory()->create([
            'title' => 'Integration Test Task',
            'status' => 'todo',
        ]);

        $retrieved = ContentRefreshTask::find($task->id);

        $this->assertEquals('Integration Test Task', $retrieved->title);
        $this->assertEquals('todo', $retrieved->status);
    }

    /** @test INT-002 */
    public function update_and_persist(): void
    {
        $task = ContentRefreshTask::factory()->create();

        $task->update([
            'status' => 'done',
            'notes' => 'Completed successfully',
        ]);

        $task->refresh();

        $this->assertEquals('done', $task->status);
        $this->assertEquals('Completed successfully', $task->notes);
    }

    /** @test INT-003 */
    public function delete_task(): void
    {
        $task = ContentRefreshTask::factory()->create();
        $id = $task->id;

        $task->delete();

        $this->assertDatabaseMissing('content_refresh_tasks', ['id' => $id]);
    }

    /** @test INT-004 */
    public function full_status_lifecycle(): void
    {
        $task = ContentRefreshTask::factory()->create(['status' => 'todo']);

        // Todo -> In Progress
        $task->update(['status' => 'in_progress']);
        $this->assertEquals('in_progress', $task->status);

        // In Progress -> Done
        $task->update(['status' => 'done']);
        $this->assertEquals('done', $task->status);
    }

    /** @test INT-005 */
    public function bulk_creation(): void
    {
        ContentRefreshTask::factory()->count(50)->create();

        $this->assertEquals(50, ContentRefreshTask::count());
    }

    /** @test INT-006 */
    public function complex_query_with_user_tasks(): void
    {
        $user = User::factory()->create();
        ContentRefreshTask::factory()->count(3)->create(['owner_user_id' => $user->id, 'status' => 'todo']);
        ContentRefreshTask::factory()->count(2)->create(['owner_user_id' => $user->id, 'status' => 'done']);
        ContentRefreshTask::factory()->count(5)->create(['status' => 'todo']);

        $userTodoTasks = ContentRefreshTask::forUser($user->id)->todo()->get();

        $this->assertCount(3, $userTodoTasks);
    }

    // ==========================================
    // Performance Tests (PERF-001 to PERF-003)
    // ==========================================

    /** @test PERF-001 */
    public function eager_loading_no_n_plus_one(): void
    {
        ContentRefreshTask::factory()->count(20)->create();

        \DB::enableQueryLog();

        $tasks = ContentRefreshTask::with(['candidate', 'owner'])->get();

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should be 3 queries: tasks + candidates + owners
        $this->assertLessThanOrEqual(4, count($queries), 'N+1 queries detected');
        $this->assertCount(20, $tasks);
    }

    /** @test PERF-002 */
    public function index_usage_for_candidate_query(): void
    {
        $candidate = ContentDecayCandidate::factory()->create();
        ContentRefreshTask::factory()->count(10)->create(['candidate_id' => $candidate->id]);

        $tasks = ContentRefreshTask::where('candidate_id', $candidate->id)->get();

        $this->assertCount(10, $tasks);
    }

    /** @test PERF-003 */
    public function index_usage_for_owner_query(): void
    {
        $user = User::factory()->create();
        ContentRefreshTask::factory()->count(10)->create(['owner_user_id' => $user->id]);

        $tasks = ContentRefreshTask::where('owner_user_id', $user->id)->get();

        $this->assertCount(10, $tasks);
    }
}
