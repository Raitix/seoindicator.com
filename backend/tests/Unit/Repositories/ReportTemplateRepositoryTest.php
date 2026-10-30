<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Repositories\ReportTemplateRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTemplateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReportTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ReportTemplateRepository();
    }

    /**
     * Test create method.
     */
    public function test_repository_can_create_template(): void
    {
        $site = Site::factory()->create();
        $data = [
            'site_id' => $site->id,
            'name' => 'New Template',
            'sections' => ['all'],
        ];
        
        $template = $this->repository->create($data);
        
        $this->assertInstanceOf(ReportTemplate::class, $template);
        $this->assertDatabaseHas('report_templates', ['name' => 'New Template']);
    }

    /**
     * Test find method.
     */
    public function test_repository_can_find_template(): void
    {
        $template = ReportTemplate::factory()->create();
        
        $found = $this->repository->find($template->id);
        
        $this->assertNotNull($found);
        $this->assertEquals($template->id, $found->id);
    }

    /**
     * Test findBySite method.
     */
    public function test_repository_can_find_by_site(): void
    {
        $site = Site::factory()->create();
        ReportTemplate::factory()->count(3)->create(['site_id' => $site->id]);
        
        $templates = $this->repository->findBySite($site->id);
        
        $this->assertCount(3, $templates);
    }

    /**
     * Test findDefault method.
     */
    public function test_repository_can_find_default(): void
    {
        $site = Site::factory()->create();
        $default = ReportTemplate::factory()->create([
            'site_id' => $site->id,
            'is_default' => true
        ]);
        ReportTemplate::factory()->create([
            'site_id' => $site->id,
            'is_default' => false
        ]);
        
        $found = $this->repository->findDefault($site->id);
        
        $this->assertNotNull($found);
        $this->assertEquals($default->id, $found->id);
    }

    /**
     * Test update method.
     */
    public function test_repository_can_update_template(): void
    {
        $template = ReportTemplate::factory()->create(['name' => 'Old Name']);
        
        $result = $this->repository->update($template->id, ['name' => 'Updated Name']);
        
        $this->assertTrue($result);
        $this->assertEquals('Updated Name', $template->fresh()->name);
    }

    /**
     * Test delete method.
     */
    public function test_repository_can_delete_template(): void
    {
        $template = ReportTemplate::factory()->create();
        
        $result = $this->repository->delete($template->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('report_templates', ['id' => $template->id]);
    }
}
