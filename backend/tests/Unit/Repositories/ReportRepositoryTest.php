<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\ReportRepository;
use App\Models\Report;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ReportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ReportRepository();
    }

    /**
     * Test create report.
     */
    public function test_create_report(): void
    {
        $site = Site::factory()->create();
        
        $data = [
            'site_id' => $site->id,
            'name' => 'Test Report',
            'type' => 'summary',
            'format' => 'pdf',
            'status' => 'pending',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['overview', 'keywords'],
        ];
        
        $result = $this->repository->create($data);
        
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals('Test Report', $result->name);
        $this->assertDatabaseHas('reports', ['name' => 'Test Report']);
    }

    /**
     * Test find report by ID.
     */
    public function test_find_by_id_returns_report(): void
    {
        $report = Report::factory()->create();
        
        $result = $this->repository->find($report->id);
        
        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals($report->id, $result->id);
    }

    /**
     * Test find returns null for invalid ID.
     */
    public function test_find_returns_null_for_invalid_id(): void
    {
        $result = $this->repository->find(9999);
        
        $this->assertNull($result);
    }

    /**
     * Test findBySite returns reports for site.
     */
    public function test_find_by_site_returns_reports(): void
    {
        $site = Site::factory()->create();
        Report::factory()->count(5)->create(['site_id' => $site->id]);
        
        $result = $this->repository->findBySite($site->id);
        
        $this->assertCount(5, $result);
    }

    /**
     * Test findBySite returns empty collection for site with no reports.
     */
    public function test_find_by_site_returns_empty_for_no_reports(): void
    {
        $site = Site::factory()->create();
        
        $result = $this->repository->findBySite($site->id);
        
        $this->assertCount(0, $result);
    }

    /**
     * Test findBySite with pagination.
     */
    public function test_find_by_site_with_pagination(): void
    {
        $site = Site::factory()->create();
        Report::factory()->count(25)->create(['site_id' => $site->id]);
        
        $result = $this->repository->findBySite($site->id, perPage: 10, page: 1);
        
        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    /**
     * Test findBySite orders by created_at desc.
     */
    public function test_find_by_site_orders_by_created_at_desc(): void
    {
        $site = Site::factory()->create();
        $old = Report::factory()->create(['site_id' => $site->id, 'created_at' => now()->subDay()]);
        $new = Report::factory()->create(['site_id' => $site->id, 'created_at' => now()]);
        
        $result = $this->repository->findBySite($site->id);
        
        $this->assertEquals($new->id, $result->first()->id);
        $this->assertEquals($old->id, $result->last()->id);
    }

    /**
     * Test findByStatus returns reports with status.
     */
    public function test_find_by_status_returns_reports(): void
    {
        Report::factory()->count(3)->completed()->create();
        Report::factory()->count(2)->pending()->create();
        
        $result = $this->repository->findByStatus('completed');
        
        $this->assertCount(3, $result);
    }

    /**
     * Test findByStatus with pagination.
     */
    public function test_find_by_status_with_pagination(): void
    {
        Report::factory()->count(25)->completed()->create();
        
        $result = $this->repository->findByStatus('completed', perPage: 10, page: 1);
        
        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
    }

    /**
     * Test findByStatus orders by created_at desc.
     */
    public function test_find_by_status_orders_by_created_at_desc(): void
    {
        $old = Report::factory()->completed()->create(['created_at' => now()->subDay()]);
        $new = Report::factory()->completed()->create(['created_at' => now()]);
        
        $result = $this->repository->findByStatus('completed');
        
        $this->assertEquals($new->id, $result->first()->id);
        $this->assertEquals($old->id, $result->last()->id);
    }

    /**
     * Test update report.
     */
    public function test_update_report(): void
    {
        $report = Report::factory()->pending()->create();
        
        $result = $this->repository->update($report->id, [
            'status' => 'completed',
            'generated_at' => now(),
        ]);
        
        $this->assertTrue($result);
        $this->assertEquals('completed', $report->fresh()->status);
    }

    /**
     * Test update returns false for invalid ID.
     */
    public function test_update_returns_false_for_invalid_id(): void
    {
        $result = $this->repository->update(9999, ['status' => 'completed']);
        
        $this->assertFalse($result);
    }

    /**
     * Test delete report.
     */
    public function test_delete_report(): void
    {
        $report = Report::factory()->create();
        
        $result = $this->repository->delete($report->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /**
     * Test delete returns false for invalid ID.
     */
    public function test_delete_returns_false_for_invalid_id(): void
    {
        $result = $this->repository->delete(9999);
        
        $this->assertFalse($result);
    }

    /**
     * Test findByDateRange filters by start date.
     */
    public function test_find_by_date_range_filters_start_date(): void
    {
        $site = Site::factory()->create();
        
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-10 10:00:00',
        ]);
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-20 10:00:00',
        ]);
        
        $result = $this->repository->findByDateRange($site->id, '2026-02-15', null);
        
        $this->assertCount(1, $result);
    }

    /**
     * Test findByDateRange filters by end date.
     */
    public function test_find_by_date_range_filters_end_date(): void
    {
        $site = Site::factory()->create();
        
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-10 10:00:00',
        ]);
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-20 10:00:00',
        ]);
        
        $result = $this->repository->findByDateRange($site->id, null, '2026-02-15');
        
        $this->assertCount(1, $result);
    }

    /**
     * Test findByDateRange filters by both dates.
     */
    public function test_find_by_date_range_filters_both_dates(): void
    {
        $site = Site::factory()->create();
        
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-05 10:00:00',
        ]);
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-15 10:00:00',
        ]);
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-25 10:00:00',
        ]);
        
        $result = $this->repository->findByDateRange($site->id, '2026-02-10', '2026-02-20');
        
        $this->assertCount(1, $result);
    }

    /**
     * Test findByDateRange returns all when no dates provided.
     */
    public function test_find_by_date_range_no_dates(): void
    {
        $site = Site::factory()->create();
        Report::factory()->count(5)->create(['site_id' => $site->id]);
        
        $result = $this->repository->findByDateRange($site->id, null, null);
        
        $this->assertCount(5, $result);
    }

    /**
     * Test findByDateRange with pagination.
     */
    public function test_find_by_date_range_with_pagination(): void
    {
        $site = Site::factory()->create();
        Report::factory()->count(25)->create(['site_id' => $site->id]);
        
        $result = $this->repository->findByDateRange($site->id, null, null, perPage: 10, page: 1);
        
        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
    }

    /**
     * Test findByDateRange orders by generated_at desc.
     */
    public function test_find_by_date_range_orders_by_generated_at_desc(): void
    {
        $site = Site::factory()->create();
        
        $old = Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-10 10:00:00',
        ]);
        $new = Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-20 10:00:00',
        ]);
        
        $result = $this->repository->findByDateRange($site->id, null, null);
        
        $this->assertEquals($new->id, $result->first()->id);
        $this->assertEquals($old->id, $result->last()->id);
    }

    /**
     * Test findByDateRange only returns reports for specified site.
     */
    public function test_find_by_date_range_filters_by_site(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();
        
        Report::factory()->count(3)->create(['site_id' => $site1->id]);
        Report::factory()->count(2)->create(['site_id' => $site2->id]);
        
        $result = $this->repository->findByDateRange($site1->id, null, null);
        
        $this->assertCount(3, $result);
    }

    /**
     * Test findByDateRange includes null generated_at when start date provided.
     */
    public function test_find_by_date_range_excludes_null_generated_at_with_start(): void
    {
        $site = Site::factory()->create();
        
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => '2026-02-15 10:00:00',
        ]);
        Report::factory()->create([
            'site_id' => $site->id,
            'generated_at' => null,
        ]);
        
        $result = $this->repository->findByDateRange($site->id, '2026-02-01', null);
        
        $this->assertCount(1, $result);
    }
}
