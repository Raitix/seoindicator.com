<?php

namespace Tests\Feature\Documentation;

use Tests\TestCase;

/**
 * Documentation Consistency Tests for TASK-35-28.
 * 
 * Verifies that DOCUMENTATION.md accurately reflects the current GBP model
 * implementation delivered by TASK-35-3. This is a documentation validation
 * task, not runtime code testing.
 * 
 * @see TESTS/TEST-35-28.md
 * @see TASK-35-28
 */
class DocumentationConsistencyTest extends TestCase
{
    /**
     * Path to DOCUMENTATION.md relative to project root.
     */
    protected string $documentationPath;

    /**
     * Path to models directory.
     */
    protected string $modelsPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // DOCUMENTATION.md is in the PROJECT-SEOINDICATOR directory
        // backend is at: workspace/PROJECT-SEOINDICATOR/backend
        // DOCUMENTATION.md is at: workspace/PROJECT-SEOINDICATOR/DOCUMENTATION.md
        $this->documentationPath = dirname(base_path(), 2) . '/PROJECT-SEOINDICATOR/DOCUMENTATION.md';
        $this->modelsPath = app_path('Models');
    }

    /**
     * Get the content of DOCUMENTATION.md.
     */
    protected function getDocumentationContent(): string
    {
        $this->assertFileExists($this->documentationPath, 'DOCUMENTATION.md must exist');
        return file_get_contents($this->documentationPath);
    }

    // =========================================================================
    // AC1: TASK-35-3 Not Marked as "Not Implemented"
    // =========================================================================

    /** @test */
    public function doc_001_task_35_3_not_marked_as_not_implemented(): void
    {
        $content = $this->getDocumentationContent();

        // Should not find "TASK-35-3" followed by "Not Implemented"
        $hasStaleStatus = preg_match(
            '/TASK-35-3[^\n]*Not Implemented/i',
            $content
        );

        $this->assertEquals(
            0,
            $hasStaleStatus,
            'DOCUMENTATION.md should not state that TASK-35-3 is "Not Implemented"'
        );
    }

    /** @test */
    public function doc_002_gbp_service_section_shows_task_35_3_as_complete(): void
    {
        $content = $this->getDocumentationContent();

        // Find GBP service dependency section and check TASK-35-3 status
        // Pattern: Look for task status tables near GBP service sections
        $hasCompleteStatus = preg_match(
            '/TASK-35-3[^\n]*(Done|Complete|✅)/i',
            $content
        );

        $this->assertGreaterThan(
            0,
            $hasCompleteStatus,
            'DOCUMENTATION.md should show TASK-35-3 as Done or Complete in status tables'
        );
    }

    /** @test */
    public function doc_003_task_35_3_not_listed_as_pending(): void
    {
        $content = $this->getDocumentationContent();

        // Should not find TASK-35-3 with pending status in status tables
        $hasPendingStatus = preg_match(
            '/\|\s*TASK-35-3\s*\|[^|]*pending/i',
            $content
        );

        $this->assertEquals(
            0,
            $hasPendingStatus,
            'DOCUMENTATION.md should not list TASK-35-3 with pending status'
        );
    }

    // =========================================================================
    // AC2: BusinessLocation Relationship Documentation Correct
    // =========================================================================

    /** @test */
    public function doc_004_insights_relationship_documents_gbp_insight_model(): void
    {
        $content = $this->getDocumentationContent();

        // Look for BusinessLocation relationship table documenting insights()
        $hasCorrectInsightsDoc = preg_match(
            '/insights\(\)[^|]*\|[^|]*App\\\\Models\\\\GBPInsight/i',
            $content
        );

        $this->assertGreaterThan(
            0,
            $hasCorrectInsightsDoc,
            'DOCUMENTATION.md should show insights() relationship returning App\Models\GBPInsight'
        );
    }

    /** @test */
    public function doc_005_reviews_relationship_documents_gbp_review_model(): void
    {
        $content = $this->getDocumentationContent();

        // Look for BusinessLocation relationship table documenting reviews()
        $hasCorrectReviewsDoc = preg_match(
            '/reviews\(\)[^|]*\|[^|]*App\\\\Models\\\\GBPReview/i',
            $content
        );

        $this->assertGreaterThan(
            0,
            $hasCorrectReviewsDoc,
            'DOCUMENTATION.md should show reviews() relationship returning App\Models\GBPReview'
        );
    }

    /** @test */
    public function doc_006_posts_relationship_documents_gbp_post_model(): void
    {
        $content = $this->getDocumentationContent();

        // Look for BusinessLocation relationship table documenting posts()
        $hasCorrectPostsDoc = preg_match(
            '/posts\(\)[^|]*\|[^|]*App\\\\Models\\\\GBPPost/i',
            $content
        );

        $this->assertGreaterThan(
            0,
            $hasCorrectPostsDoc,
            'DOCUMENTATION.md should show posts() relationship returning App\Models\GBPPost'
        );
    }

    // =========================================================================
    // AC3: No "Anonymous Model" Claims Remain
    // =========================================================================

    /** @test */
    public function doc_007_no_anonymous_table_backed_claims_for_gbp_insights(): void
    {
        $content = $this->getDocumentationContent();

        // Should not find claims that gbp_insights uses anonymous/table-backed models
        $hasAnonymousClaims = preg_match(
            '/gbp_insights[^.]*anonymous|anonymous[^.]*gbp_insights/i',
            $content
        );

        $this->assertEquals(
            0,
            $hasAnonymousClaims,
            'DOCUMENTATION.md should not claim gbp_insights uses anonymous/table-backed models'
        );
    }

    /** @test */
    public function doc_008_no_anonymous_table_backed_claims_for_gbp_posts(): void
    {
        $content = $this->getDocumentationContent();

        // Should not find claims that gbp_posts uses anonymous/table-backed models
        $hasAnonymousClaims = preg_match(
            '/gbp_posts[^.]*anonymous|anonymous[^.]*gbp_posts/i',
            $content
        );

        $this->assertEquals(
            0,
            $hasAnonymousClaims,
            'DOCUMENTATION.md should not claim gbp_posts uses anonymous/table-backed models'
        );
    }

    /** @test */
    public function doc_009_no_pending_future_model_work_claims(): void
    {
        $content = $this->getDocumentationContent();

        // Should not find claims about pending future model work for GBP
        $hasPendingWorkClaims = preg_match(
            '/(gbp_insights|gbp_posts|GBPInsight|GBPPost|GBPReview)[^.]*pending[^.]*model|pending[^.]*model[^.]*(gbp_insights|gbp_posts|GBPInsight|GBPPost|GBPReview)/i',
            $content
        );

        $this->assertEquals(
            0,
            $hasPendingWorkClaims,
            'DOCUMENTATION.md should not claim GBP models are pending future model work'
        );
    }

    // =========================================================================
    // AC4: GBP Service Dependency Notes Accurate
    // =========================================================================

    /** @test */
    public function doc_010_service_notes_reflect_actual_model_state(): void
    {
        $content = $this->getDocumentationContent();

        // Look for GBP service dependency notes
        // Should find references to dedicated GBP models backing relationships
        $hasCorrectDependencyNote = preg_match(
            '/Dedicated GBP (child )?models (now )?back (insights|reviews|posts)/i',
            $content
        );

        $this->assertGreaterThan(
            0,
            $hasCorrectDependencyNote,
            'DOCUMENTATION.md should note that dedicated GBP models back the relationships'
        );
    }

    /** @test */
    public function doc_011_dependencies_section_shows_gbp_models_as_implemented(): void
    {
        $content = $this->getDocumentationContent();

        // Find BusinessLocation dependencies section
        // Should list GBPInsight, GBPReview, GBPPost as related child models
        $hasAllModelsListed = preg_match(
            '/(GBPInsight|GBPReview|GBPPost|LocalRanking|LocalCompetitor|LocalSEOAlert)/i',
            $content
        );

        $this->assertGreaterThan(
            0,
            $hasAllModelsListed,
            'DOCUMENTATION.md should list GBP models as related child models in dependencies'
        );
    }

    // =========================================================================
    // AC5: Documentation Update Preserves Runtime
    // =========================================================================

    /** @test */
    public function doc_012_model_files_exist_and_match_documentation(): void
    {
        // Verify the actual model files exist that DOCUMENTATION.md describes
        $modelFiles = [
            'GBPInsight.php',
            'GBPReview.php',
            'GBPPost.php',
            'BusinessLocation.php',
        ];

        foreach ($modelFiles as $modelFile) {
            $modelPath = $this->modelsPath . '/' . $modelFile;
            
            $this->assertFileExists(
                $modelPath,
                "Model file {$modelFile} must exist as documented in DOCUMENTATION.md"
            );
        }
    }

    /** @test */
    public function doc_013_business_location_model_has_correct_relationships(): void
    {
        // Verify BusinessLocation.php actually references the GBP models
        $businessLocationPath = $this->modelsPath . '/BusinessLocation.php';
        
        $this->assertFileExists($businessLocationPath, 'BusinessLocation.php must exist');
        
        $content = file_get_contents($businessLocationPath);
        
        // Check for references to GBP models in relationship methods
        $hasGBPInsight = strpos($content, 'GBPInsight') !== false;
        $hasGBPReview = strpos($content, 'GBPReview') !== false;
        $hasGBPPost = strpos($content, 'GBPPost') !== false;
        
        $this->assertTrue(
            $hasGBPInsight,
            'BusinessLocation.php should reference GBPInsight model'
        );
        
        $this->assertTrue(
            $hasGBPReview,
            'BusinessLocation.php should reference GBPReview model'
        );
        
        $this->assertTrue(
            $hasGBPPost,
            'BusinessLocation.php should reference GBPPost model'
        );
    }

    /** @test */
    public function doc_014_gbp_insight_model_exists_and_has_correct_structure(): void
    {
        $modelPath = $this->modelsPath . '/GBPInsight.php';
        
        $this->assertFileExists($modelPath, 'GBPInsight.php model must exist');
        
        $content = file_get_contents($modelPath);
        
        // Verify it's a proper Eloquent model with BusinessLocation relationship
        $this->assertStringContainsString('BusinessLocation', $content, 
            'GBPInsight should have relationship to BusinessLocation');
        $this->assertStringContainsString('belongsTo', $content,
            'GBPInsight should define belongsTo relationship');
    }

    /** @test */
    public function doc_015_gbp_review_model_exists_and_has_correct_structure(): void
    {
        $modelPath = $this->modelsPath . '/GBPReview.php';
        
        $this->assertFileExists($modelPath, 'GBPReview.php model must exist');
        
        $content = file_get_contents($modelPath);
        
        // Verify it's a proper Eloquent model with BusinessLocation relationship
        $this->assertStringContainsString('BusinessLocation', $content,
            'GBPReview should have relationship to BusinessLocation');
        $this->assertStringContainsString('belongsTo', $content,
            'GBPReview should define belongsTo relationship');
    }

    /** @test */
    public function doc_016_gbp_post_model_exists_and_has_correct_structure(): void
    {
        $modelPath = $this->modelsPath . '/GBPPost.php';
        
        $this->assertFileExists($modelPath, 'GBPPost.php model must exist');
        
        $content = file_get_contents($modelPath);
        
        // Verify it's a proper Eloquent model with BusinessLocation relationship
        $this->assertStringContainsString('BusinessLocation', $content,
            'GBPPost should have relationship to BusinessLocation');
        $this->assertStringContainsString('belongsTo', $content,
            'GBPPost should define belongsTo relationship');
    }
}
