<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\CompetitorRepository;
use App\Models\User;
use App\Models\Competitor;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for CompetitorRepository
 *
 * Tests the database-level operations for competitors.
 */
class CompetitorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CompetitorRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->repository = app(CompetitorRepository::class);
    }

    /**
     * Test: Create a competitor
     */
    public function test_create_competitor(): void
    {
        $user = User::factory()->create();

        $competitor = $this->repository->create($user->id, [
            'domain' => 'competitor.com',
            'displayName' => 'Test Competitor',
            'notes' => 'Some notes',
        ]);

        $this->assertDatabaseHas('competitors', [
            'id' => $competitor->id,
            'domain' => 'competitor.com',
            'display_name' => 'Test Competitor',
            'notes' => 'Some notes',
        ]);
    }

    /**
     * Test: List competitors by user
     */
    public function test_list_competitors_by_user(): void
    {
        $user = User::factory()->create();
        $competitor1 = Competitor::factory()->create(['user_id' => $user->id]);
        $competitor2 = Competitor::factory()->create(['user_id' => $user->id]);

        $competitors = $this->repository->listByUser($user->id);

        $this->assertCount(2, $competitors);
        $this->assertEqualsCanonicalizing([
            $competitor1->id,
            $competitor2->id,
        ], $competitors->pluck('id')->toArray());
    }

    /**
     * Test: Find competitor by ID
     */
    public function test_find_competitor_by_id(): void
    {
        $competitor = Competitor::factory()->create([
            'domain' => 'example.com',
        ]);

        $found = $this->repository->find($competitor->id);

        $this->assertNotNull($found);
        $this->assertEquals('example.com', $found->domain);
    }

    /**
     * Test: Update competitor
     */
    public function test_update_competitor(): void
    {
        $competitor = Competitor::factory()->create([
            'domain' => 'example.com',
            'display_name' => 'Old Name',
        ]);

        $updated = $this->repository->update($competitor->id, [
            'display_name' => 'New Name',
        ]);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('competitors', [
            'id' => $competitor->id,
            'display_name' => 'New Name',
        ]);
    }

    /**
     * Test: Delete competitor
     */
    public function test_delete_competitor(): void
    {
        $competitor = Competitor::factory()->create();

        $deleted = $this->repository->delete($competitor->id);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('competitors', [
            'id' => $competitor->id,
        ]);
    }
}
