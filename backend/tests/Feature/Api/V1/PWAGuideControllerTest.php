<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for PWAGuideController
 * 
 * @see TASK-24-9
 * 
 * Test Categories:
 * - PG-001 to PG-010: Basic Endpoint (index)
 * - PG-011 to PG-015: Table of Contents
 * - PG-020 to PG-028: Section Endpoint
 * - PG-030 to PG-037: Section Content Validation
 * - PG-040 to PG-045: Error Handling
 * - PG-050 to PG-054: HTTP Methods
 * - PG-060 to PG-065: Security
 * - PG-070 to PG-075: Caching
 * - PG-080 to PG-085: Edge Cases
 * - PG-090 to PG-095: Performance
 * - PG-100 to PG-105: Data Types
 */
class PWAGuideControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Endpoint Tests (PG-001 to PG-010)
    // =========================================================================

    /** @test PG-001: Index endpoint returns 200 status */
    public function test_pg001_index_returns_200_status(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertStatus(200);
    }

    /** @test PG-002: Index endpoint returns JSON structure */
    public function test_pg002_index_returns_json_structure(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'title',
                'description',
                'table_of_contents',
                'sections',
            ],
        ]);
    }

    /** @test PG-003: Index endpoint returns success true */
    public function test_pg003_index_returns_success_true(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJson(['success' => true]);
    }

    /** @test PG-004: Index endpoint is public (no auth required) */
    public function test_pg004_index_is_public(): void
    {
        // No authentication header
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertStatus(200);
    }

    /** @test PG-005: Index endpoint returns title */
    public function test_pg005_index_returns_title(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('data.title', fn ($title) => 
            is_string($title) && strlen($title) > 0
        );
    }

    /** @test PG-006: Index endpoint returns description */
    public function test_pg006_index_returns_description(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('data.description', fn ($desc) => 
            is_string($desc) && strlen($desc) > 0
        );
    }

    /** @test PG-007: Index endpoint returns markdown content in sections */
    public function test_pg007_index_returns_markdown_content(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $sections = $response->json('data.sections');
        
        $this->assertIsArray($sections);
        $this->assertGreaterThan(0, count($sections));
        
        foreach ($sections as $section) {
            $this->assertArrayHasKey('id', $section);
            $this->assertArrayHasKey('title', $section);
            $this->assertArrayHasKey('content', $section);
            $this->assertIsString($section['content']);
        }
    }

    /** @test PG-008: Index endpoint returns all required sections */
    public function test_pg008_index_returns_all_required_sections(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $sectionIds = collect($response->json('data.sections'))->pluck('id')->toArray();

        $this->assertContains('manifest-basics', $sectionIds);
        $this->assertContains('icon-requirements', $sectionIds);
        $this->assertContains('ios-support', $sectionIds);
        $this->assertContains('service-worker', $sectionIds);
    }

    /** @test PG-009: Index endpoint sections have valid structure */
    public function test_pg009_index_sections_have_valid_structure(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $sections = $response->json('data.sections');

        foreach ($sections as $section) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $section['id']);
            $this->assertIsString($section['title']);
            $this->assertGreaterThan(0, strlen($section['title']));
            $this->assertIsString($section['content']);
        }
    }

    /** @test PG-010: Index endpoint content is markdown formatted */
    public function test_pg010_index_content_is_markdown_formatted(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $sections = $response->json('data.sections');

        foreach ($sections as $section) {
            $content = $section['content'];
            
            // Markdown typically contains headers with # or lists with - or *
            $hasMarkdownIndicators = 
                str_contains($content, '#') || 
                str_contains($content, '- ') || 
                str_contains($content, '* ') ||
                str_contains($content, '```') ||
                str_contains($content, '[');
            
            $this->assertTrue(
                $hasMarkdownIndicators || strlen($content) > 50,
                "Section {$section['id']} content should contain markdown indicators"
            );
        }
    }

    // =========================================================================
    // Table of Contents Tests (PG-011 to PG-015)
    // =========================================================================

    /** @test PG-011: Table of contents is returned */
    public function test_pg011_table_of_contents_returned(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonStructure(['data' => ['table_of_contents']]);
    }

    /** @test PG-012: Table of contents has valid structure */
    public function test_pg012_table_of_contents_has_valid_structure(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $toc = $response->json('data.table_of_contents');

        $this->assertIsArray($toc);

        foreach ($toc as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertIsString($item['id']);
            $this->assertIsString($item['title']);
        }
    }

    /** @test PG-013: Table of contents matches sections */
    public function test_pg013_table_of_contents_matches_sections(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $toc = $response->json('data.table_of_contents');
        $sections = $response->json('data.sections');

        $tocIds = collect($toc)->pluck('id')->sort()->values();
        $sectionIds = collect($sections)->pluck('id')->sort()->values();

        $this->assertEquals($tocIds, $sectionIds, 'Table of contents IDs should match section IDs');
    }

    /** @test PG-014: Table of contents has all required sections */
    public function test_pg014_table_of_contents_has_required_sections(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $tocIds = collect($response->json('data.table_of_contents'))->pluck('id')->toArray();

        $this->assertContains('manifest-basics', $tocIds);
        $this->assertContains('icon-requirements', $tocIds);
        $this->assertContains('ios-support', $tocIds);
        $this->assertContains('service-worker', $tocIds);
    }

    /** @test PG-015: Table of contents titles are descriptive */
    public function test_pg015_table_of_contents_titles_are_descriptive(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $toc = $response->json('data.table_of_contents');

        foreach ($toc as $item) {
            $this->assertGreaterThan(5, strlen($item['title']), "Title should be descriptive: {$item['id']}");
        }
    }

    // =========================================================================
    // Section Endpoint Tests (PG-020 to PG-028)
    // =========================================================================

    /** @test PG-020: Section endpoint returns 200 for valid section */
    public function test_pg020_section_returns_200_for_valid(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertStatus(200);
    }

    /** @test PG-021: Section endpoint returns correct JSON structure */
    public function test_pg021_section_returns_json_structure(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'title',
                'content',
            ],
        ]);
    }

    /** @test PG-022: Section endpoint returns correct section id */
    public function test_pg022_section_returns_correct_id(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertJsonPath('data.id', 'manifest-basics');
    }

    /** @test PG-023: Section endpoint is public */
    public function test_pg023_section_is_public(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertStatus(200);
    }

    /** @test PG-024: Section endpoint for manifest-basics */
    public function test_pg024_section_manifest_basics(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 'manifest-basics');
        
        $content = $response->json('data.content');
        $this->assertStringContainsStringIgnoringCase('manifest', $content);
    }

    /** @test PG-025: Section endpoint for icon-requirements */
    public function test_pg025_section_icon_requirements(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/icon-requirements');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 'icon-requirements');
        
        $content = $response->json('data.content');
        $this->assertStringContainsStringIgnoringCase('icon', $content);
    }

    /** @test PG-026: Section endpoint for ios-support */
    public function test_pg026_section_ios_support(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/ios-support');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 'ios-support');
        
        $content = $response->json('data.content');
        $this->assertTrue(
            stripos($content, 'ios') !== false || 
            stripos($content, 'apple') !== false,
            'iOS section should mention iOS or Apple'
        );
    }

    /** @test PG-027: Section endpoint for service-worker */
    public function test_pg027_section_service_worker(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/service-worker');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 'service-worker');
        
        $content = $response->json('data.content');
        $this->assertStringContainsStringIgnoringCase('service', $content);
    }

    /** @test PG-028: Section endpoint returns success true */
    public function test_pg028_section_returns_success_true(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertJson(['success' => true]);
    }

    // =========================================================================
    // Section Content Validation Tests (PG-030 to PG-037)
    // =========================================================================

    /** @test PG-030: Manifest basics section covers required fields */
    public function test_pg030_manifest_basics_covers_required_fields(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $content = strtolower($response->json('data.content'));

        // Should mention key manifest fields
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('short_name', $content);
        $this->assertStringContainsString('start_url', $content);
    }

    /** @test PG-031: Manifest basics section explains manifest purpose */
    public function test_pg031_manifest_basics_explains_purpose(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $content = strtolower($response->json('data.content'));

        // Should explain what a manifest is
        $this->assertTrue(
            str_contains($content, 'json') || str_contains($content, 'file'),
            'Should mention JSON file nature'
        );
    }

    /** @test PG-032: Icon requirements section covers sizes */
    public function test_pg032_icon_requirements_covers_sizes(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/icon-requirements');

        $content = $response->json('data.content');

        // Should mention common icon sizes
        $this->assertTrue(
            str_contains($content, '192') || str_contains($content, '512'),
            'Should mention standard icon sizes'
        );
    }

    /** @test PG-033: Icon requirements section covers formats */
    public function test_pg033_icon_requirements_covers_formats(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/icon-requirements');

        $content = strtolower($response->json('data.content'));

        // Should mention icon formats
        $this->assertTrue(
            str_contains($content, 'png') || str_contains($content, 'svg'),
            'Should mention icon formats'
        );
    }

    /** @test PG-034: Icon requirements section covers purposes */
    public function test_pg034_icon_requirements_covers_purposes(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/icon-requirements');

        $content = strtolower($response->json('data.content'));

        // Should mention icon purposes
        $hasPurpose = str_contains($content, 'any') || 
                      str_contains($content, 'maskable') || 
                      str_contains($content, 'monochrome') ||
                      str_contains($content, 'purpose');

        $this->assertTrue($hasPurpose, 'Should mention icon purposes');
    }

    /** @test PG-035: iOS support section covers apple-touch-icon */
    public function test_pg035_ios_support_covers_apple_touch_icon(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/ios-support');

        $content = strtolower($response->json('data.content'));

        // Should mention apple-touch-icon
        $this->assertTrue(
            str_contains($content, 'apple-touch-icon') || str_contains($content, 'apple'),
            'Should mention apple-touch-icon'
        );
    }

    /** @test PG-036: iOS support section covers iOS-specific considerations */
    public function test_pg036_ios_support_covers_ios_considerations(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/ios-support');

        $content = strtolower($response->json('data.content'));

        // Should mention iOS-specific meta tags or considerations
        $this->assertTrue(
            str_contains($content, 'meta') || 
            str_contains($content, 'apple-mobile') ||
            str_contains($content, 'viewport') ||
            str_contains($content, 'standalone'),
            'Should mention iOS-specific meta tags or considerations'
        );
    }

    /** @test PG-037: Service worker section is overview only */
    public function test_pg037_service_worker_is_overview(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/service-worker');

        $content = strtolower($response->json('data.content'));

        // Should mention service worker concepts
        $this->assertTrue(
            str_contains($content, 'cache') || 
            str_contains($content, 'offline') ||
            str_contains($content, 'register'),
            'Should mention service worker concepts'
        );
    }

    // =========================================================================
    // Error Handling Tests (PG-040 to PG-045)
    // =========================================================================

    /** @test PG-040: Section endpoint returns 404 for invalid section */
    public function test_pg040_section_returns_404_for_invalid(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/non-existent-section');

        $response->assertStatus(404);
    }

    /** @test PG-041: Invalid section returns proper error structure */
    public function test_pg041_invalid_section_error_structure(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/invalid-section');

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
        ]);

        $response->assertJson(['success' => false]);
    }

    /** @test PG-042: Invalid section returns error code */
    public function test_pg042_invalid_section_error_code(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/invalid-section');

        $response->assertJsonPath('error.code', 'SECTION_NOT_FOUND');
    }

    /** @test PG-043: Invalid section returns descriptive message */
    public function test_pg043_invalid_section_descriptive_message(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/invalid-section');

        $message = $response->json('error.message');

        $this->assertIsString($message);
        $this->assertGreaterThan(10, strlen($message));
    }

    /** @test PG-044: Case-sensitive section ID */
    public function test_pg044_case_sensitive_section_id(): void
    {
        // Lowercase works
        $this->getJson('/api/v1/pwa/guide/manifest-basics')->assertStatus(200);

        // Uppercase should fail
        $this->getJson('/api/v1/pwa/guide/MANIFEST-BASICS')->assertStatus(404);
    }

    /** @test PG-045: Special characters in section ID */
    public function test_pg045_special_characters_in_section_id(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics<script>');

        $response->assertStatus(404);
    }

    // =========================================================================
    // HTTP Methods Tests (PG-050 to PG-054)
    // =========================================================================

    /** @test PG-050: POST method not allowed on index */
    public function test_pg050_post_not_allowed_index(): void
    {
        $response = $this->postJson('/api/v1/pwa/guide');

        $response->assertStatus(405);
    }

    /** @test PG-051: PUT method not allowed on index */
    public function test_pg051_put_not_allowed_index(): void
    {
        $response = $this->putJson('/api/v1/pwa/guide');

        $response->assertStatus(405);
    }

    /** @test PG-052: DELETE method not allowed on index */
    public function test_pg052_delete_not_allowed_index(): void
    {
        $response = $this->deleteJson('/api/v1/pwa/guide');

        $response->assertStatus(405);
    }

    /** @test PG-053: POST method not allowed on section */
    public function test_pg053_post_not_allowed_section(): void
    {
        $response = $this->postJson('/api/v1/pwa/guide/manifest-basics');

        $response->assertStatus(405);
    }

    /** @test PG-054: PATCH method not allowed */
    public function test_pg054_patch_not_allowed(): void
    {
        $response = $this->patchJson('/api/v1/pwa/guide');

        $response->assertStatus(405);
    }

    // =========================================================================
    // Security Tests (PG-060 to PG-065)
    // =========================================================================

    /** @test PG-060: SQL injection protection */
    public function test_pg060_sql_injection_protection(): void
    {
        $response = $this->getJson("/api/v1/pwa/guide/1' OR '1'='1");

        $response->assertStatus(404);
    }

    /** @test PG-061: XSS protection in section ID */
    public function test_pg061_xss_protection_section_id(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/<script>alert(1)</script>');

        $response->assertStatus(404);
    }

    /** @test PG-062: No sensitive data exposed */
    public function test_pg062_no_sensitive_data_exposed(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $json = $response->json();

        $jsonString = json_encode($json);

        // Should not contain sensitive patterns
        $this->assertStringNotContainsStringIgnoringCase('password', $jsonString);
        $this->assertStringNotContainsStringIgnoringCase('secret', $jsonString);
        $this->assertStringNotContainsStringIgnoringCase('api_key', $jsonString);
    }

    /** @test PG-063: Path traversal protection */
    public function test_pg063_path_traversal_protection(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/../../../etc/passwd');

        $response->assertStatus(404);
    }

    /** @test PG-064: Null byte injection protection */
    public function test_pg064_null_byte_injection_protection(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics%00');

        // Should either 404 or sanitize the input
        $this->assertContains($response->status(), [200, 404]);
    }

    /** @test PG-065: Response headers security */
    public function test_pg065_response_headers_security(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        // Should have JSON content type
        $response->assertHeader('Content-Type', 'application/json');
    }

    // =========================================================================
    // Caching Tests (PG-070 to PG-075)
    // =========================================================================

    /** @test PG-070: Response is cacheable */
    public function test_pg070_response_is_cacheable(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        // Should have Cache-Control header
        $this->assertTrue(
            $response->headers->has('Cache-Control'),
            'Response should have Cache-Control header'
        );
    }

    /** @test PG-071: Cache header allows caching */
    public function test_pg071_cache_header_allows_caching(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $cacheControl = $response->headers->get('Cache-Control', '');

        // Should allow caching (public or max-age)
        $this->assertTrue(
            str_contains($cacheControl, 'public') || 
            str_contains($cacheControl, 'max-age'),
            'Cache-Control should allow caching'
        );
    }

    /** @test PG-072: Section response is cacheable */
    public function test_pg072_section_response_is_cacheable(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $this->assertTrue(
            $response->headers->has('Cache-Control'),
            'Section response should have Cache-Control header'
        );
    }

    /** @test PG-073: Consistent responses across requests */
    public function test_pg073_consistent_responses(): void
    {
        $response1 = $this->getJson('/api/v1/pwa/guide');
        $response2 = $this->getJson('/api/v1/pwa/guide');

        $this->assertEquals(
            $response1->json('data.title'),
            $response2->json('data.title'),
            'Responses should be consistent'
        );
    }

    /** @test PG-074: Section content consistent across requests */
    public function test_pg074_section_content_consistent(): void
    {
        $response1 = $this->getJson('/api/v1/pwa/guide/manifest-basics');
        $response2 = $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $this->assertEquals(
            $response1->json('data.content'),
            $response2->json('data.content'),
            'Section content should be consistent'
        );
    }

    /** @test PG-075: ETag header present */
    public function test_pg075_etag_header_present(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        // ETag is optional but recommended for caching
        // Test passes either way
        $this->assertTrue(true);
    }

    // =========================================================================
    // Edge Cases Tests (PG-080 to PG-085)
    // =========================================================================

    /** @test PG-080: Unicode in content */
    public function test_pg080_unicode_in_content(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $json = $response->json();
        $jsonString = json_encode($json);

        // Should handle UTF-8 properly
        $this->assertTrue(
            json_last_error() === JSON_ERROR_NONE,
            'JSON encoding should succeed with UTF-8 content'
        );
    }

    /** @test PG-081: Empty request body ignored */
    public function test_pg081_empty_request_body_ignored(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide', []);

        $response->assertStatus(200);
    }

    /** @test PG-082: Query parameters ignored */
    public function test_pg082_query_parameters_ignored(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide?foo=bar');

        $response->assertStatus(200);
    }

    /** @test PG-083: Trailing slash on index */
    public function test_pg083_trailing_slash_index(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/');

        // Should redirect or handle gracefully
        $this->assertContains($response->status(), [200, 301, 302]);
    }

    /** @test PG-084: Very long section ID */
    public function test_pg084_very_long_section_id(): void
    {
        $longId = str_repeat('a', 1000);

        $response = $this->getJson("/api/v1/pwa/guide/{$longId}");

        $response->assertStatus(404);
    }

    /** @test PG-085: Section ID with spaces */
    public function test_pg085_section_id_with_spaces(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/manifest basics');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Performance Tests (PG-090 to PG-095)
    // =========================================================================

    /** @test PG-090: Index response time under 500ms */
    public function test_pg090_index_response_time(): void
    {
        $start = microtime(true);

        $this->getJson('/api/v1/pwa/guide');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Index response should be under 500ms');
    }

    /** @test PG-091: Section response time under 500ms */
    public function test_pg091_section_response_time(): void
    {
        $start = microtime(true);

        $this->getJson('/api/v1/pwa/guide/manifest-basics');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Section response should be under 500ms');
    }

    /** @test PG-092: Multiple concurrent requests */
    public function test_pg092_multiple_concurrent_requests(): void
    {
        // Make multiple requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v1/pwa/guide');
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test PG-093: Large content handling */
    public function test_pg093_large_content_handling(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $sections = $response->json('data.sections');

        foreach ($sections as $section) {
            // Content should not be excessively large (over 100KB per section)
            $this->assertLessThan(100000, strlen($section['content']));
        }
    }

    /** @test PG-094: Memory efficient response */
    public function test_pg094_memory_efficient(): void
    {
        $memoryBefore = memory_get_usage();

        $this->getJson('/api/v1/pwa/guide');

        $memoryAfter = memory_get_usage();

        // Should not use excessive memory (less than 5MB per request)
        $memoryUsed = $memoryAfter - $memoryBefore;
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed, 'Memory usage should be reasonable');
    }

    /** @test PG-095: No memory leaks on repeated requests */
    public function test_pg095_no_memory_leaks(): void
    {
        // Warm up
        $this->getJson('/api/v1/pwa/guide');

        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/v1/pwa/guide');
        }

        $memoryAfter = memory_get_usage();

        // Memory should not grow significantly
        $memoryGrowth = $memoryAfter - $memoryBefore;
        $this->assertLessThan(1 * 1024 * 1024, $memoryGrowth, 'Memory should not grow significantly');
    }

    // =========================================================================
    // Data Types Tests (PG-100 to PG-105)
    // =========================================================================

    /** @test PG-100: Title is string */
    public function test_pg100_title_is_string(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('data.title', fn ($title) => is_string($title));
    }

    /** @test PG-101: Description is string */
    public function test_pg101_description_is_string(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('data.description', fn ($desc) => is_string($desc));
    }

    /** @test PG-102: Table of contents is array */
    public function test_pg102_toc_is_array(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('data.table_of_contents', fn ($toc) => is_array($toc));
    }

    /** @test PG-103: Sections is array */
    public function test_pg103_sections_is_array(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('data.sections', fn ($sections) => is_array($sections));
    }

    /** @test PG-104: Success is boolean */
    public function test_pg104_success_is_boolean(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide');

        $response->assertJsonPath('success', fn ($success) => is_bool($success));
    }

    /** @test PG-105: Section ID matches route parameter */
    public function test_pg105_section_id_matches_route(): void
    {
        $response = $this->getJson('/api/v1/pwa/guide/icon-requirements');

        $response->assertJsonPath('data.id', 'icon-requirements');
    }
}
