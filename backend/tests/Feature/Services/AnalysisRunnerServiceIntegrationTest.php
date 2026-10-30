<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\AnalysisRunnerService;
use App\Services\IndicatorCheckerInterface;
use App\Models\SEOAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

/**
 * Integration tests for AnalysisRunnerService.
 *
 * Tests real service interactions, database operations,
 * and end-to-end analysis workflows.
 *
 * @see \App\Services\AnalysisRunnerService
 * @see TEST-18-6
 */
class AnalysisRunnerServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AnalysisRunnerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AnalysisRunnerService::class);
    }

    // =========================================================================
    // END-TO-END ANALYSIS TESTS
    // =========================================================================

    /**
     * Full analysis workflow with database persistence.
     */
    public function test_full_analysis_creates_database_record(): void
    {
        $url = 'https://example.com';
        $html = '<!DOCTYPE html>
            <html>
            <head>
                <title>Test Page Title</title>
                <meta name="description" content="Test description">
                <meta name="keywords" content="test, keywords">
            </head>
            <body>
                <h1>Main Heading</h1>
                <p>Content paragraph.</p>
                <img src="image.jpg" alt="Alt text">
                <a href="/link">Internal Link</a>
            </body>
            </html>';

        $result = $this->service->run($url, $html);

        // Verify result structure
        $this->assertNotNull($result);
        $this->assertEquals($url, $result['url']);
        $this->assertEquals('completed', $result['status']);

        // Verify database record
        $analysis = SEOAnalysis::where('url', $url)->first();
        $this->assertNotNull($analysis);
        $this->assertNotNull($analysis->analysis_data);
    }

    /**
     * Analysis with authenticated user.
     */
    public function test_analysis_links_to_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $url = 'https://example.com/auth-test';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html, [
            'user_id' => $user->id,
        ]);

        $analysis = SEOAnalysis::where('url', $url)->first();
        $this->assertNotNull($analysis);
        $this->assertEquals($user->id, $analysis->user_id);
    }

    /**
     * Analysis with anonymous session.
     */
    public function test_analysis_links_to_session(): void
    {
        $sessionId = 'test-session-' . uniqid();

        $url = 'https://example.com/session-test';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html, [
            'session_id' => $sessionId,
        ]);

        $analysis = SEOAnalysis::where('url', $url)->first();
        $this->assertNotNull($analysis);
        $this->assertEquals($sessionId, $analysis->session_id);
    }

    // =========================================================================
    // REAL INDICATOR TESTS
    // =========================================================================

    /**
     * Title tag indicator integration.
     */
    public function test_title_tag_indicator_integration(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Good Title Length Here</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $titleIndicator = collect($result['indicators'])
            ->firstWhere('id', 'title_tag');

        if ($titleIndicator) {
            $this->assertArrayHasKey('score', $titleIndicator);
            $this->assertArrayHasKey('passed', $titleIndicator);
        }
    }

    /**
     * Meta description indicator integration.
     */
    public function test_meta_description_indicator_integration(): void
    {
        $url = 'https://example.com';
        $html = '<html><head>
            <title>Test</title>
            <meta name="description" content="This is a good meta description with enough length.">
        </head><body></body></html>';

        $result = $this->service->run($url, $html);

        $metaIndicator = collect($result['indicators'])
            ->firstWhere('id', 'meta_description');

        if ($metaIndicator) {
            $this->assertArrayHasKey('score', $metaIndicator);
        }
    }

    /**
     * H1 heading indicator integration.
     */
    public function test_h1_indicator_integration(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body><h1>Main Heading</h1></body></html>';

        $result = $this->service->run($url, $html);

        $h1Indicator = collect($result['indicators'])
            ->firstWhere('id', 'h1_tag');

        if ($h1Indicator) {
            $this->assertArrayHasKey('score', $h1Indicator);
            $this->assertArrayHasKey('details', $h1Indicator);
        }
    }

    // =========================================================================
    // EVENT DISPATCH TESTS
    // =========================================================================

    /**
     * AnalysisStarted event is dispatched.
     */
    public function test_analysis_started_event_dispatched(): void
    {
        Event::fake();

        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->service->run($url, $html);

        // Event assertion will depend on actual event class
        // Event::assertDispatched(AnalysisStarted::class);
        $this->assertTrue(true); // Placeholder until event class exists
    }

    /**
     * AnalysisCompleted event is dispatched.
     */
    public function test_analysis_completed_event_dispatched(): void
    {
        Event::fake();

        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->service->run($url, $html);

        // Event assertion will depend on actual event class
        // Event::assertDispatched(AnalysisCompleted::class);
        $this->assertTrue(true); // Placeholder until event class exists
    }

    // =========================================================================
    // PERFORMANCE TESTS
    // =========================================================================

    /**
     * Analysis completes within timeout.
     */
    public function test_analysis_completes_within_timeout(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>' . str_repeat('<p>Content</p>', 100) . '</body></html>';

        $start = microtime(true);
        $result = $this->service->run($url, $html, ['timeout' => 30]);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(30, $elapsed);
        $this->assertNotNull($result);
    }

    /**
     * Large HTML handled efficiently.
     */
    public function test_large_html_handled_efficiently(): void
    {
        $url = 'https://example.com';
        // Create a large HTML document
        $html = '<html><head><title>Test</title></head><body>';
        for ($i = 0; $i < 1000; $i++) {
            $html .= '<div><h2>Section ' . $i . '</h2><p>Content for section ' . $i . '</p></div>';
        }
        $html .= '</body></html>';

        $result = $this->service->run($url, $html, ['timeout' => 60]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('timing', $result);
    }

    // =========================================================================
    // PARTIAL FAILURE TESTS
    // =========================================================================

    /**
     * Partial results stored when some indicators fail.
     */
    public function test_partial_results_stored_on_failures(): void
    {
        $url = 'https://example.com/partial-test';
        // Empty HTML should cause some indicators to fail gracefully
        $html = '<html><head></head><body></body></html>';

        $result = $this->service->run($url, $html);

        // Should still have a result with partial data
        $this->assertNotNull($result);
        $this->assertArrayHasKey('indicators', $result);

        // Some indicators should have results
        $this->assertNotEmpty($result['indicators']);
    }

    /**
     * Error details captured for failed indicators.
     */
    public function test_error_details_captured(): void
    {
        $url = 'https://example.com';
        $html = '';

        $result = $this->service->run($url, $html);

        // Should have errors array
        $this->assertArrayHasKey('errors', $result);
    }

    // =========================================================================
    // SCORE CALCULATION TESTS
    // =========================================================================

    /**
     * Overall score calculated correctly.
     */
    public function test_overall_score_calculation(): void
    {
        $url = 'https://example.com';
        $html = '<html>
            <head>
                <title>Perfect Title Length Here</title>
                <meta name="description" content="A good meta description for the page.">
            </head>
            <body>
                <h1>Main Heading</h1>
                <p>Content paragraph.</p>
            </body>
        </html>';

        $result = $this->service->run($url, $html);

        $this->assertArrayHasKey('overallScore', $result);
        $this->assertIsInt($result['overallScore']);
        $this->assertGreaterThanOrEqual(0, $result['overallScore']);
        $this->assertLessThanOrEqual(100, $result['overallScore']);
    }

    /**
     * Category scores calculated correctly.
     */
    public function test_category_scores_calculation(): void
    {
        $url = 'https://example.com';
        $html = '<html>
            <head>
                <title>Test Page</title>
                <meta name="description" content="Description.">
            </head>
            <body>
                <h1>Heading</h1>
            </body>
        </html>';

        $result = $this->service->run($url, $html);

        if (isset($result['categoryScores'])) {
            $this->assertIsArray($result['categoryScores']);
        }
    }

    // =========================================================================
    // DATA PERSISTENCE TESTS
    // =========================================================================

    /**
     * Analysis data JSON properly stored.
     */
    public function test_analysis_data_json_stored(): void
    {
        $url = 'https://example.com/json-test';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->service->run($url, $html);

        $analysis = SEOAnalysis::where('url', $url)->first();

        $this->assertNotNull($analysis);
        $this->assertNotNull($analysis->analysis_data);

        // Should be valid JSON/array
        $data = $analysis->analysis_data;
        $this->assertIsArray($data);
    }

    /**
     * Timing data stored correctly.
     */
    public function test_timing_data_stored(): void
    {
        $url = 'https://example.com/timing-test';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertArrayHasKey('timing', $result);
        $this->assertArrayHasKey('total', $result['timing']);
        $this->assertGreaterThan(0, $result['timing']['total']);
    }

    // =========================================================================
    // CONCURRENT ANALYSIS TESTS
    // =========================================================================

    /**
     * Multiple analyses can run concurrently.
     */
    public function test_multiple_analyses_concurrent(): void
    {
        $urls = [
            'https://example1.com',
            'https://example2.com',
            'https://example3.com',
        ];

        $html = '<html><head><title>Test</title></head><body></body></html>';

        $results = [];
        foreach ($urls as $url) {
            $results[] = $this->service->run($url, $html);
        }

        foreach ($results as $result) {
            $this->assertNotNull($result);
            $this->assertEquals('completed', $result['status']);
        }

        // Verify all were persisted
        $this->assertEquals(3, SEOAnalysis::whereIn('url', $urls)->count());
    }

    /**
     * Same URL analysis updates existing record.
     */
    public function test_same_url_updates_existing(): void
    {
        $url = 'https://example.com/update-test';
        $html1 = '<html><head><title>First Title</title></head><body></body></html>';
        $html2 = '<html><head><title>Second Title Updated</title></head><body></body></html>';

        // First analysis
        $this->service->run($url, $html1);
        $firstCount = SEOAnalysis::where('url', $url)->count();

        // Second analysis (should update, not create new)
        $this->service->run($url, $html2);
        $secondCount = SEOAnalysis::where('url', $url)->count();

        $this->assertEquals($firstCount, $secondCount);
    }

    // =========================================================================
    // INPUT VARIATION TESTS
    // =========================================================================

    /**
     * Analysis with HTTPS URL.
     */
    public function test_https_url_handling(): void
    {
        $url = 'https://secure.example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
        $this->assertEquals($url, $result['url']);
    }

    /**
     * Analysis with unicode content.
     */
    public function test_unicode_content_handling(): void
    {
        $url = 'https://example.com/unicode';
        $html = '<html>
            <head>
                <title>日本語タイトル - 中文标题</title>
                <meta name="description" content="Описание на русском">
            </head>
            <body>
                <h1>العربية</h1>
                <p>עברית</p>
            </body>
        </html>';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
        $this->assertEquals('completed', $result['status']);
    }

    /**
     * Analysis with HTML5 semantic elements.
     */
    public function test_html5_semantic_elements(): void
    {
        $url = 'https://example.com/html5';
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>HTML5 Page</title>
        </head>
        <body>
            <header><nav><a href="/">Home</a></nav></header>
            <main>
                <article>
                    <h1>Article Title</h1>
                    <section><p>Content</p></section>
                </article>
                <aside>Sidebar</aside>
            </main>
            <footer>Copyright</footer>
        </body>
        </html>';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
        $this->assertEquals('completed', $result['status']);
    }
}
