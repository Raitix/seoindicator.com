<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Unit tests for the CrawlPage model.
 *
 * @see TASK-22-2
 * @see SPEC-022
 */
class CrawlPageTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Creation and Mass Assignment Tests
    // ==========================================

    /**
     * Test creating a crawl page with valid data.
     */
    public function test_create_crawl_page(): void
    {
        $crawlJob = CrawlJob::factory()->create();

        $crawlPage = CrawlPage::create([
            'crawl_job_id' => $crawlJob->id,
            'url' => 'https://example.com/page',
            'depth' => 0,
            'title' => 'Test Page',
            'rendered_html' => '<html><body>Test</body></html>',
            'structured_text' => 'Test content',
            'meta' => ['description' => 'Test description'],
            'links' => [['href' => '/about', 'text' => 'About', 'internal' => true]],
            'size' => 1024,
            'crawled_at' => now(),
        ]);

        $this->assertDatabaseHas('crawl_pages', [
            'id' => $crawlPage->id,
            'url' => 'https://example.com/page',
            'title' => 'Test Page',
        ]);
    }

    /**
     * Test mass assignment fillable fields.
     */
    public function test_mass_assignment_fillable(): void
    {
        $crawlJob = CrawlJob::factory()->create();

        $data = [
            'crawl_job_id' => $crawlJob->id,
            'url' => 'https://test.com/page',
            'depth' => 1,
            'title' => 'Fillable Test',
            'rendered_html' => '<html><body>Content</body></html>',
            'structured_text' => 'Structured content',
            'meta' => ['description' => 'Meta description', 'robots' => 'index, follow'],
            'links' => [
                ['href' => '/contact', 'text' => 'Contact', 'internal' => true],
                ['href' => 'https://external.com', 'text' => 'External', 'internal' => false],
            ],
            'size' => 2048,
            'crawled_at' => now(),
            'error' => null,
        ];

        $crawlPage = CrawlPage::create($data);

        $this->assertEquals($data['url'], $crawlPage->url);
        $this->assertEquals($data['depth'], $crawlPage->depth);
        $this->assertEquals($data['title'], $crawlPage->title);
        $this->assertEquals($data['size'], $crawlPage->size);
    }

    /**
     * Test meta cast to array.
     */
    public function test_meta_cast_to_array(): void
    {
        $crawlPage = CrawlPage::factory()->create([
            'meta' => [
                'description' => 'Test description',
                'robots' => 'index, follow',
                'canonical' => 'https://example.com/canonical',
            ],
        ]);

        $this->assertIsArray($crawlPage->meta);
        $this->assertEquals('Test description', $crawlPage->meta['description']);
        $this->assertEquals('index, follow', $crawlPage->meta['robots']);
        $this->assertEquals('https://example.com/canonical', $crawlPage->meta['canonical']);
    }

    /**
     * Test links cast to array.
     */
    public function test_links_cast_to_array(): void
    {
        $crawlPage = CrawlPage::factory()->create([
            'links' => [
                ['href' => '/about', 'text' => 'About', 'internal' => true],
                ['href' => 'https://external.com', 'text' => 'External', 'internal' => false],
            ],
        ]);

        $this->assertIsArray($crawlPage->links);
        $this->assertCount(2, $crawlPage->links);
        $this->assertEquals('/about', $crawlPage->links[0]['href']);
        $this->assertFalse($crawlPage->links[1]['internal']);
    }

    /**
     * Test null meta.
     */
    public function test_null_meta(): void
    {
        $crawlPage = CrawlPage::factory()->create(['meta' => null]);

        $this->assertNull($crawlPage->meta);
    }

    /**
     * Test null links.
     */
    public function test_null_links(): void
    {
        $crawlPage = CrawlPage::factory()->create(['links' => null]);

        $this->assertNull($crawlPage->links);
    }

    /**
     * Test crawled_at cast to datetime.
     */
    public function test_crawled_at_cast_to_datetime(): void
    {
        $now = now();
        $crawlPage = CrawlPage::factory()->create(['crawled_at' => $now]);

        $this->assertInstanceOf(Carbon::class, $crawlPage->crawled_at);
        $this->assertTrue($crawlPage->crawled_at->isToday());
    }

    /**
     * Test depth cast to integer.
     */
    public function test_depth_cast_to_integer(): void
    {
        $crawlPage = CrawlPage::factory()->create(['depth' => 2]);

        $this->assertIsInt($crawlPage->depth);
        $this->assertEquals(2, $crawlPage->depth);
    }

    /**
     * Test size cast to integer.
     */
    public function test_size_cast_to_integer(): void
    {
        $crawlPage = CrawlPage::factory()->create(['size' => 102400]);

        $this->assertIsInt($crawlPage->size);
        $this->assertEquals(102400, $crawlPage->size);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    /**
     * Test crawl page belongs to a crawl job.
     */
    public function test_crawl_page_belongs_to_crawl_job(): void
    {
        $crawlJob = CrawlJob::factory()->create();
        $crawlPage = CrawlPage::factory()->create(['crawl_job_id' => $crawlJob->id]);

        $this->assertInstanceOf(CrawlJob::class, $crawlPage->crawlJob);
        $this->assertEquals($crawlJob->id, $crawlPage->crawlJob->id);
    }

    /**
     * Test crawlJob relationship is BelongsTo.
     */
    public function test_crawl_job_relationship_type(): void
    {
        $crawlPage = CrawlPage::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $crawlPage->crawlJob());
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    /**
     * Test scopeForJob returns pages for specific job.
     */
    public function test_scope_for_job(): void
    {
        $job1 = CrawlJob::factory()->create();
        $job2 = CrawlJob::factory()->create();

        CrawlPage::factory()->count(3)->forJob($job1)->create();
        CrawlPage::factory()->count(2)->forJob($job2)->create();

        $job1Pages = CrawlPage::forJob($job1->id)->get();

        $this->assertEquals(3, $job1Pages->count());
        foreach ($job1Pages as $page) {
            $this->assertEquals($job1->id, $page->crawl_job_id);
        }
    }

    /**
     * Test scopeAtDepth returns pages at specific depth.
     */
    public function test_scope_at_depth(): void
    {
        CrawlPage::factory()->atDepth(0)->create();
        CrawlPage::factory()->atDepth(1)->create();
        CrawlPage::factory()->atDepth(2)->create();

        $depth0Pages = CrawlPage::atDepth(0)->get();
        $depth1Pages = CrawlPage::atDepth(1)->get();
        $depth2Pages = CrawlPage::atDepth(2)->get();

        $this->assertEquals(1, $depth0Pages->count());
        $this->assertEquals(1, $depth1Pages->count());
        $this->assertEquals(1, $depth2Pages->count());
    }

    /**
     * Test scopeSuccessful returns pages without errors.
     */
    public function test_scope_successful(): void
    {
        CrawlPage::factory()->successful()->create();
        CrawlPage::factory()->successful()->create();
        CrawlPage::factory()->withError()->create();

        $successfulPages = CrawlPage::successful()->get();

        $this->assertEquals(2, $successfulPages->count());
        foreach ($successfulPages as $page) {
            $this->assertNull($page->error);
        }
    }

    /**
     * Test scopeSuccessful excludes pages with errors.
     */
    public function test_scope_successful_excludes_errors(): void
    {
        CrawlPage::factory()->successful()->create();
        CrawlPage::factory()->withError('Connection timeout')->create();
        CrawlPage::factory()->withError('DNS failure')->create();

        $successfulPages = CrawlPage::successful()->get();

        $this->assertEquals(1, $successfulPages->count());
    }

    /**
     * Test chaining multiple scopes.
     */
    public function test_scope_chaining(): void
    {
        $job = CrawlJob::factory()->create();

        CrawlPage::factory()->forJob($job)->atDepth(0)->successful()->create();
        CrawlPage::factory()->forJob($job)->atDepth(0)->withError()->create();
        CrawlPage::factory()->forJob($job)->atDepth(1)->successful()->create();

        $pages = CrawlPage::forJob($job->id)
            ->atDepth(0)
            ->successful()
            ->get();

        $this->assertEquals(1, $pages->count());
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    /**
     * Test sizeFormatted accessor formats bytes correctly.
     */
    public function test_size_formatted_accessor_bytes(): void
    {
        $crawlPage = CrawlPage::factory()->create(['size' => 512]);

        $this->assertEquals('512.0 B', $crawlPage->size_formatted);
    }

    /**
     * Test sizeFormatted accessor formats kilobytes correctly.
     */
    public function test_size_formatted_accessor_kilobytes(): void
    {
        $crawlPage = CrawlPage::factory()->create(['size' => 1024]);

        $this->assertEquals('1.0 KB', $crawlPage->size_formatted);
    }

    /**
     * Test sizeFormatted accessor formats megabytes correctly.
     */
    public function test_size_formatted_accessor_megabytes(): void
    {
        $crawlPage = CrawlPage::factory()->create(['size' => 1048576]);

        $this->assertEquals('1.0 MB', $crawlPage->size_formatted);
    }

    /**
     * Test sizeFormatted accessor formats large values correctly.
     */
    public function test_size_formatted_accessor_large(): void
    {
        $crawlPage = CrawlPage::factory()->create(['size' => 1572864]); // 1.5 MB

        $formatted = $crawlPage->size_formatted;
        $this->assertStringContainsString('MB', $formatted);
    }

    /**
     * Test internalLinks accessor returns only internal links.
     */
    public function test_internal_links_accessor(): void
    {
        $crawlPage = CrawlPage::factory()->withMixedLinks()->create();

        $internalLinks = $crawlPage->internal_links;

        $this->assertCount(1, $internalLinks);
        foreach ($internalLinks as $link) {
            $this->assertTrue($link['internal']);
        }
    }

    /**
     * Test externalLinks accessor returns only external links.
     */
    public function test_external_links_accessor(): void
    {
        $crawlPage = CrawlPage::factory()->withMixedLinks()->create();

        $externalLinks = $crawlPage->external_links;

        $this->assertCount(1, $externalLinks);
        foreach ($externalLinks as $link) {
            $this->assertFalse($link['internal']);
        }
    }

    /**
     * Test internalLinks accessor with only internal links.
     */
    public function test_internal_links_accessor_only_internal(): void
    {
        $crawlPage = CrawlPage::factory()->withOnlyInternalLinks()->create();

        $internalLinks = $crawlPage->internal_links;

        $this->assertCount(2, $internalLinks);
    }

    /**
     * Test externalLinks accessor with only external links.
     */
    public function test_external_links_accessor_only_external(): void
    {
        $crawlPage = CrawlPage::factory()->withOnlyExternalLinks()->create();

        $externalLinks = $crawlPage->external_links;

        $this->assertCount(2, $externalLinks);
    }

    /**
     * Test internalLinks accessor with null links.
     */
    public function test_internal_links_accessor_null_links(): void
    {
        $crawlPage = CrawlPage::factory()->withoutLinks()->create();

        $this->assertEquals([], $crawlPage->internal_links);
    }

    /**
     * Test externalLinks accessor with null links.
     */
    public function test_external_links_accessor_null_links(): void
    {
        $crawlPage = CrawlPage::factory()->withoutLinks()->create();

        $this->assertEquals([], $crawlPage->external_links);
    }

    /**
     * Test internalLinks accessor with empty links array.
     */
    public function test_internal_links_accessor_empty_links(): void
    {
        $crawlPage = CrawlPage::factory()->create(['links' => []]);

        $this->assertEquals([], $crawlPage->internal_links);
    }

    /**
     * Test links with missing internal key.
     */
    public function test_links_with_missing_internal_key(): void
    {
        $crawlPage = CrawlPage::factory()->create([
            'links' => [
                ['href' => '/about', 'text' => 'About'], // missing 'internal' key
            ],
        ]);

        // Missing 'internal' key should be treated as false (external)
        $this->assertCount(0, $crawlPage->internal_links);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    /**
     * Test zero depth is allowed.
     */
    public function test_zero_depth_allowed(): void
    {
        $crawlPage = CrawlPage::factory()->create(['depth' => 0]);

        $this->assertEquals(0, $crawlPage->depth);
    }

    /**
     * Test maximum depth (2 per spec).
     */
    public function test_maximum_depth(): void
    {
        $crawlPage = CrawlPage::factory()->create(['depth' => 2]);

        $this->assertEquals(2, $crawlPage->depth);
    }

    /**
     * Test zero size.
     */
    public function test_zero_size(): void
    {
        $crawlPage = CrawlPage::factory()->create(['size' => 0]);

        $this->assertEquals(0, $crawlPage->size);
        $this->assertEquals('0.0 B', $crawlPage->size_formatted);
    }

    /**
     * Test null title.
     */
    public function test_null_title(): void
    {
        $crawlPage = CrawlPage::factory()->create(['title' => null]);

        $this->assertNull($crawlPage->title);
    }

    /**
     * Test null rendered_html.
     */
    public function test_null_rendered_html(): void
    {
        $crawlPage = CrawlPage::factory()->create(['rendered_html' => null]);

        $this->assertNull($crawlPage->rendered_html);
    }

    /**
     * Test null structured_text.
     */
    public function test_null_structured_text(): void
    {
        $crawlPage = CrawlPage::factory()->create(['structured_text' => null]);

        $this->assertNull($crawlPage->structured_text);
    }

    /**
     * Test null crawled_at.
     */
    public function test_null_crawled_at(): void
    {
        $crawlPage = CrawlPage::factory()->create(['crawled_at' => null]);

        $this->assertNull($crawlPage->crawled_at);
    }

    /**
     * Test long title (500 chars max per spec).
     */
    public function test_long_title(): void
    {
        $longTitle = str_repeat('a', 500);
        $crawlPage = CrawlPage::factory()->create(['title' => $longTitle]);

        $this->assertEquals($longTitle, $crawlPage->title);
    }

    /**
     * Test URL max length (2083 chars per RFC).
     */
    public function test_url_max_length(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2060);

        $crawlPage = CrawlPage::factory()->create(['url' => $longUrl]);

        $this->assertEquals($longUrl, $crawlPage->url);
        $this->assertLessThanOrEqual(2083, strlen($crawlPage->url));
    }

    /**
     * Test large rendered_html (MEDIUMTEXT supports up to 16MB).
     */
    public function test_large_rendered_html(): void
    {
        // Create a ~500KB HTML content (demonstrates MEDIUMTEXT capability)
        $largeHtml = '<html><body>' . str_repeat('<p>Content paragraph.</p>', 20000) . '</body></html>';

        $crawlPage = CrawlPage::factory()->create([
            'rendered_html' => $largeHtml,
            'size' => strlen($largeHtml),
        ]);

        $this->assertEquals($largeHtml, $crawlPage->rendered_html);
        $this->assertGreaterThan(400000, $crawlPage->size); // Verify it's large (400KB+)
    }

    /**
     * Test empty meta array.
     */
    public function test_empty_meta_array(): void
    {
        $crawlPage = CrawlPage::factory()->create(['meta' => []]);

        $this->assertEquals([], $crawlPage->meta);
    }

    /**
     * Test empty links array.
     */
    public function test_empty_links_array(): void
    {
        $crawlPage = CrawlPage::factory()->create(['links' => []]);

        $this->assertEquals([], $crawlPage->links);
    }

    /**
     * Test error message.
     */
    public function test_error_message(): void
    {
        $crawlPage = CrawlPage::factory()->withError('Connection timeout after 30 seconds')->create();

        $this->assertEquals('Connection timeout after 30 seconds', $crawlPage->error);
    }

    /**
     * Test page with error has no content.
     */
    public function test_page_with_error_no_content(): void
    {
        $crawlPage = CrawlPage::factory()->withError('DNS resolution failed')->create();

        $this->assertNotNull($crawlPage->error);
        $this->assertNull($crawlPage->rendered_html);
        $this->assertNull($crawlPage->structured_text);
    }

    // ==========================================
    // Cascade Delete Tests
    // ==========================================

    /**
     * Test crawl page deleted when crawl job is deleted.
     */
    public function test_crawl_page_deleted_when_crawl_job_deleted(): void
    {
        $crawlJob = CrawlJob::factory()->create();
        $crawlPage = CrawlPage::factory()->forJob($crawlJob)->create();

        $crawlJob->delete();

        $this->assertDatabaseMissing('crawl_pages', ['id' => $crawlPage->id]);
    }

    /**
     * Test multiple pages deleted when crawl job is deleted.
     */
    public function test_multiple_pages_deleted_with_job(): void
    {
        $crawlJob = CrawlJob::factory()->create();
        $pages = CrawlPage::factory()->count(5)->forJob($crawlJob)->create();

        $crawlJob->delete();

        foreach ($pages as $page) {
            $this->assertDatabaseMissing('crawl_pages', ['id' => $page->id]);
        }
    }

    // ==========================================
    // Factory State Tests
    // ==========================================

    /**
     * Test factory state successful().
     */
    public function test_factory_state_successful(): void
    {
        $crawlPage = CrawlPage::factory()->successful()->create();

        $this->assertNull($crawlPage->error);
        $this->assertNotNull($crawlPage->crawled_at);
    }

    /**
     * Test factory state withError().
     */
    public function test_factory_state_with_error(): void
    {
        $crawlPage = CrawlPage::factory()->withError()->create();

        $this->assertNotNull($crawlPage->error);
    }

    /**
     * Test factory state withError() with custom message.
     */
    public function test_factory_state_with_error_custom(): void
    {
        $errorMessage = 'Custom error message';
        $crawlPage = CrawlPage::factory()->withError($errorMessage)->create();

        $this->assertEquals($errorMessage, $crawlPage->error);
    }

    /**
     * Test factory state forJob().
     */
    public function test_factory_state_for_job(): void
    {
        $crawlJob = CrawlJob::factory()->create();
        $crawlPage = CrawlPage::factory()->forJob($crawlJob)->create();

        $this->assertEquals($crawlJob->id, $crawlPage->crawl_job_id);
    }

    /**
     * Test factory state atDepth().
     */
    public function test_factory_state_at_depth(): void
    {
        $crawlPage = CrawlPage::factory()->atDepth(2)->create();

        $this->assertEquals(2, $crawlPage->depth);
    }

    /**
     * Test factory state withUrl().
     */
    public function test_factory_state_with_url(): void
    {
        $url = 'https://custom-url.com/page';
        $crawlPage = CrawlPage::factory()->withUrl($url)->create();

        $this->assertEquals($url, $crawlPage->url);
    }

    /**
     * Test factory state withLinks().
     */
    public function test_factory_state_with_links(): void
    {
        $links = [
            ['href' => '/page1', 'text' => 'Page 1', 'internal' => true],
            ['href' => 'https://external.com', 'text' => 'External', 'internal' => false],
        ];
        $crawlPage = CrawlPage::factory()->withLinks($links)->create();

        $this->assertEquals($links, $crawlPage->links);
    }

    /**
     * Test factory state withMeta().
     */
    public function test_factory_state_with_meta(): void
    {
        $meta = [
            'description' => 'Custom description',
            'robots' => 'noindex, nofollow',
            'og:title' => 'Open Graph Title',
        ];
        $crawlPage = CrawlPage::factory()->withMeta($meta)->create();

        $this->assertEquals($meta, $crawlPage->meta);
    }

    /**
     * Test factory state withoutLinks().
     */
    public function test_factory_state_without_links(): void
    {
        $crawlPage = CrawlPage::factory()->withoutLinks()->create();

        $this->assertNull($crawlPage->links);
    }

    /**
     * Test factory state withOnlyInternalLinks().
     */
    public function test_factory_state_with_only_internal_links(): void
    {
        $crawlPage = CrawlPage::factory()->withOnlyInternalLinks()->create();

        foreach ($crawlPage->links as $link) {
            $this->assertTrue($link['internal']);
        }
    }

    /**
     * Test factory state withOnlyExternalLinks().
     */
    public function test_factory_state_with_only_external_links(): void
    {
        $crawlPage = CrawlPage::factory()->withOnlyExternalLinks()->create();

        foreach ($crawlPage->links as $link) {
            $this->assertFalse($link['internal']);
        }
    }

    /**
     * Test factory state withMixedLinks().
     */
    public function test_factory_state_with_mixed_links(): void
    {
        $crawlPage = CrawlPage::factory()->withMixedLinks()->create();

        $internalCount = 0;
        $externalCount = 0;
        foreach ($crawlPage->links as $link) {
            if ($link['internal']) {
                $internalCount++;
            } else {
                $externalCount++;
            }
        }

        $this->assertGreaterThan(0, $internalCount);
        $this->assertGreaterThan(0, $externalCount);
    }

    /**
     * Test factory state withSize().
     */
    public function test_factory_state_with_size(): void
    {
        $crawlPage = CrawlPage::factory()->withSize(512000)->create();

        $this->assertEquals(512000, $crawlPage->size);
    }

    // ==========================================
    // Complex Link Scenarios
    // ==========================================

    /**
     * Test links with various internal values.
     */
    public function test_links_with_various_internal_values(): void
    {
        $crawlPage = CrawlPage::factory()->create([
            'links' => [
                ['href' => '/about', 'text' => 'About', 'internal' => true],
                ['href' => 'https://external.com', 'text' => 'External 1', 'internal' => false],
                ['href' => '/contact', 'text' => 'Contact', 'internal' => true],
                ['href' => 'https://another.com', 'text' => 'External 2', 'internal' => false],
            ],
        ]);

        $this->assertCount(2, $crawlPage->internal_links);
        $this->assertCount(2, $crawlPage->external_links);
    }

    /**
     * Test links with unicode characters.
     */
    public function test_links_with_unicode_characters(): void
    {
        $crawlPage = CrawlPage::factory()->create([
            'links' => [
                ['href' => '/produkts', 'text' => 'Produkts (Latvian)', 'internal' => true],
                ['href' => 'https://example.com/продукт', 'text' => 'Product (Russian)', 'internal' => false],
            ],
        ]);

        $this->assertCount(1, $crawlPage->internal_links);
        $this->assertCount(1, $crawlPage->external_links);
        $this->assertEquals('Produkts (Latvian)', $crawlPage->internal_links[0]['text']);
    }

    /**
     * Test meta with various SEO fields.
     */
    public function test_meta_with_various_seo_fields(): void
    {
        $meta = [
            'description' => 'Page description',
            'robots' => 'index, follow',
            'canonical' => 'https://example.com/canonical',
            'og:title' => 'Open Graph Title',
            'og:description' => 'OG Description',
            'og:image' => 'https://example.com/image.jpg',
            'twitter:card' => 'summary_large_image',
        ];

        $crawlPage = CrawlPage::factory()->withMeta($meta)->create();

        $this->assertEquals($meta, $crawlPage->meta);
    }
}
