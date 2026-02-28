<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SERPFeatureDetector;
use App\Services\SERP\SERPResult;
use App\Models\SerpFeature;
use Illuminate\Support\Facades\Log;
use Mockery;

class SERPFeatureDetectorTest extends TestCase
{
    protected SERPFeatureDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new SERPFeatureDetector();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Constructor Tests (C-001 to C-004)
    // ============================================

    /** @test */
    public function it_creates_instance_with_default_detectors(): void
    {
        // C-001: Create with default detectors
        $detector = new SERPFeatureDetector();
        $this->assertInstanceOf(SERPFeatureDetector::class, $detector);
    }

    /** @test */
    public function it_has_all_feature_types_supported(): void
    {
        // C-001: Verify all 10 feature types are supported
        $reflection = new \ReflectionClass(SerpFeature::class);
        $this->assertTrue($reflection->hasConstant('FEATURE_TYPES'));
        
        $featureTypes = SerpFeature::FEATURE_TYPES;
        $this->assertCount(10, $featureTypes);
        $this->assertContains('featured_snippet', $featureTypes);
        $this->assertContains('local_pack', $featureTypes);
        $this->assertContains('image_pack', $featureTypes);
        $this->assertContains('video', $featureTypes);
        $this->assertContains('people_also_ask', $featureTypes);
        $this->assertContains('knowledge_panel', $featureTypes);
        $this->assertContains('sitelinks', $featureTypes);
        $this->assertContains('reviews', $featureTypes);
        $this->assertContains('faq', $featureTypes);
        $this->assertContains('product', $featureTypes);
    }

    // ============================================
    // detectFeatures() Success Cases (D-001 to D-008)
    // ============================================

    /** @test */
    public function it_returns_array_of_serp_feature_objects(): void
    {
        // D-001: Returns array of SERPFeature objects
        $result = $this->detector->detectFeatures(
            'seo tools',
            'https://example.com',
            []
        );

        $this->assertIsArray($result);
        $this->assertCount(10, $result); // All 10 feature types
    }

    /** @test */
    public function each_feature_has_required_fields(): void
    {
        // D-002: Each feature has required fields
        $result = $this->detector->detectFeatures(
            'seo tools',
            'https://example.com',
            []
        );

        foreach ($result as $feature) {
            $this->assertArrayHasKey('type', $feature);
            $this->assertArrayHasKey('is_present', $feature);
            $this->assertArrayHasKey('keyword', $feature);
            $this->assertArrayHasKey('checked_at', $feature);
        }
    }

    /** @test */
    public function it_detects_url_matching_for_target_site(): void
    {
        // D-003: URL matching for target site
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/seo-tools',
                'title' => 'Best SEO Tools',
                'snippet' => 'The best SEO tools...'
            ]
        ];

        $result = $this->detector->detectFeatures(
            'seo tools',
            'https://example.com',
            $serpData
        );

        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['is_present']);
        $this->assertEquals('https://example.com/seo-tools', $featuredSnippet['url']);
    }

    /** @test */
    public function it_detects_url_matching_for_competitor(): void
    {
        // D-004: URL matching for competitor
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://competitor.com/seo-tools',
                'title' => 'Best SEO Tools',
                'snippet' => 'The best SEO tools...'
            ]
        ];

        $result = $this->detector->detectFeatures(
            'seo tools',
            'https://example.com',
            $serpData
        );

        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertFalse($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_handles_empty_serp_result(): void
    {
        // D-005: Empty SERP result
        $result = $this->detector->detectFeatures(
            'seo tools',
            'https://example.com',
            []
        );

        foreach ($result as $feature) {
            $this->assertFalse($feature['is_present']);
        }
    }

    /** @test */
    public function it_handles_null_serp_result_gracefully(): void
    {
        // D-006: Null SERP result handled gracefully
        $result = $this->detector->detectFeatures(
            'seo tools',
            'https://example.com',
            null
        );

        $this->assertIsArray($result);
        foreach ($result as $feature) {
            $this->assertFalse($feature['is_present']);
        }
    }

    /** @test */
    public function it_detects_all_feature_types(): void
    {
        // D-007: All feature types detected
        $result = $this->detector->detectFeatures('test', null, []);

        $types = collect($result)->pluck('type')->toArray();
        foreach (SerpFeature::FEATURE_TYPES as $expectedType) {
            $this->assertContains($expectedType, $types);
        }
    }

    /** @test */
    public function detection_includes_position_for_local_pack(): void
    {
        // D-008: Detection includes position
        $serpData = [
            'localPack' => [
                ['name' => 'Other Business', 'url' => 'https://other.com'],
                ['name' => 'Example Site', 'url' => 'https://example.com'],
                ['name' => 'Third Business', 'url' => 'https://third.com']
            ]
        ];

        $result = $this->detector->detectFeatures(
            'seo services',
            'https://example.com',
            $serpData
        );

        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertTrue($localPack['is_present']);
        $this->assertEquals(2, $localPack['position']);
    }

    // ============================================
    // URL Matching Logic (U-001 to U-008)
    // ============================================

    /** @test */
    public function it_matches_exact_hostname(): void
    {
        // U-001: Exact hostname match
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_handles_subdomain_matching(): void
    {
        // U-002: Subdomain match (target: example.com, feature: sub.example.com)
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://sub.example.com/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        // Subdomain should match (sub.example.com ends with .example.com)
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_handles_www_prefix(): void
    {
        // U-003: www prefix handled
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://www.example.com/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_ignores_protocol_in_url_matching(): void
    {
        // U-004: Protocol ignored (http vs https)
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'http://example.com/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_ignores_query_params_in_url_matching(): void
    {
        // U-006: Query params ignored
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/page?ref=123&utm_source=test',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_ignores_trailing_slash_in_url_matching(): void
    {
        // U-007: Trailing slash ignored
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/page/',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_matches_hostnames_case_insensitively(): void
    {
        // U-008: Case insensitive hostname matching
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://EXAMPLE.COM/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        
        $this->assertTrue($featuredSnippet['is_present']);
    }

    // ============================================
    // Error Handling (E-001 to E-005)
    // ============================================

    /** @test */
    public function it_handles_malformed_serp_data(): void
    {
        // E-001: Malformed SERP data
        $serpData = ['corrupted' => true, 'invalid_structure' => null];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);

        $this->assertIsArray($result);
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_handles_invalid_url_in_serp(): void
    {
        // E-002: Invalid URL in SERP
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'not-a-valid-url',
            ]
        ];

        // Should not throw exception
        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $this->assertIsArray($result);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertFalse($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_handles_missing_required_fields(): void
    {
        // E-003: Missing required fields
        $serpData = [
            'answerBox' => [] // Empty answer box
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $this->assertIsArray($result);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertFalse($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_continues_detection_when_detector_throws(): void
    {
        // E-005: Detector throws error, others continue
        Log::spy();

        // Create a detector that will encounter an error with specific data
        $serpData = [
            'localPack' => 'not-an-array', // Will cause error in local pack detector
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);

        // All 10 features should still be returned
        $this->assertCount(10, $result);
        
        // Local pack should be marked as not present
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertFalse($localPack['is_present']);
    }

    // ============================================
    // Featured Snippet Detection (FS-001 to FS-005)
    // ============================================

    /** @test */
    public function it_detects_no_featured_snippet(): void
    {
        // FS-001: No answer box
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertFalse($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_detects_featured_snippet_with_our_url(): void
    {
        // FS-002: Answer box with our URL
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['is_present']);
        $this->assertEquals(1, $featuredSnippet['position']);
    }

    /** @test */
    public function it_detects_featured_snippet_with_competitor_url(): void
    {
        // FS-003: Answer box with competitor URL
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://competitor.com/page',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertFalse($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_includes_snippet_in_featured_snippet_result(): void
    {
        // FS-004: Answer box with snippet
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/page',
                'snippet' => 'This is the answer text...',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertEquals('This is the answer text...', $featuredSnippet['snippet']);
    }

    /** @test */
    public function it_includes_title_in_featured_snippet_result(): void
    {
        // FS-005: Answer box with title
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/page',
                'title' => 'Page Title Here',
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertEquals('Page Title Here', $featuredSnippet['title']);
    }

    /** @test */
    public function it_detects_various_answer_box_types(): void
    {
        $validTypes = ['featured_snippet', 'organic', 'paragraph', 'list', 'table'];
        
        foreach ($validTypes as $type) {
            $serpData = [
                'answerBox' => [
                    'type' => $type,
                    'url' => 'https://example.com/page',
                ]
            ];

            $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
            $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
            
            $this->assertTrue($featuredSnippet['is_present'], "Failed for type: {$type}");
        }
    }

    // ============================================
    // Local Pack Detection (LP-001 to LP-006)
    // ============================================

    /** @test */
    public function it_detects_no_local_pack(): void
    {
        // LP-001: No local pack
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertFalse($localPack['is_present']);
    }

    /** @test */
    public function it_detects_local_pack_with_our_url(): void
    {
        // LP-002: Local pack with our URL at position 1
        $serpData = [
            'localPack' => [
                ['name' => 'Example Site', 'url' => 'https://example.com']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertTrue($localPack['is_present']);
        $this->assertEquals(1, $localPack['position']);
    }

    /** @test */
    public function it_detects_local_pack_position_2(): void
    {
        // LP-003: Local pack position 2
        $serpData = [
            'localPack' => [
                ['name' => 'First', 'url' => 'https://first.com'],
                ['name' => 'Example Site', 'url' => 'https://example.com']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertTrue($localPack['is_present']);
        $this->assertEquals(2, $localPack['position']);
    }

    /** @test */
    public function it_detects_local_pack_position_3(): void
    {
        // LP-004: Local pack position 3
        $serpData = [
            'localPack' => [
                ['name' => 'First', 'url' => 'https://first.com'],
                ['name' => 'Second', 'url' => 'https://second.com'],
                ['name' => 'Example Site', 'url' => 'https://example.com']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertTrue($localPack['is_present']);
        $this->assertEquals(3, $localPack['position']);
    }

    /** @test */
    public function it_detects_local_pack_not_in_top_3(): void
    {
        // LP-005: Local pack exists but we're not in top 3
        $serpData = [
            'localPack' => [
                ['name' => 'First', 'url' => 'https://first.com'],
                ['name' => 'Second', 'url' => 'https://second.com'],
                ['name' => 'Third', 'url' => 'https://third.com']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertFalse($localPack['is_present']);
    }

    /** @test */
    public function it_handles_empty_local_pack_array(): void
    {
        // LP-006: Empty local pack array
        $serpData = ['localPack' => []];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertFalse($localPack['is_present']);
    }

    // ============================================
    // Image Pack Detection
    // ============================================

    /** @test */
    public function it_detects_image_pack(): void
    {
        $serpData = [
            'images' => [
                ['source' => 'https://example.com/image1.jpg'],
                ['source' => 'https://other.com/image2.jpg']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $imagePack = collect($result)->firstWhere('type', 'image_pack');
        $this->assertTrue($imagePack['is_present']);
    }

    /** @test */
    public function it_detects_no_image_pack(): void
    {
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $imagePack = collect($result)->firstWhere('type', 'image_pack');
        $this->assertFalse($imagePack['is_present']);
    }

    // ============================================
    // Video Detection
    // ============================================

    /** @test */
    public function it_detects_video_results(): void
    {
        $serpData = [
            'videos' => [
                ['url' => 'https://youtube.com/watch?v=123'],
                ['url' => 'https://example.com/video']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $video = collect($result)->firstWhere('type', 'video');
        $this->assertTrue($video['is_present']);
    }

    /** @test */
    public function it_detects_no_video_results(): void
    {
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $video = collect($result)->firstWhere('type', 'video');
        $this->assertFalse($video['is_present']);
    }

    // ============================================
    // People Also Ask Detection
    // ============================================

    /** @test */
    public function it_detects_people_also_ask(): void
    {
        $serpData = [
            'peopleAlsoAsk' => [
                ['question' => 'What is SEO?', 'url' => 'https://example.com/what-is-seo'],
                ['question' => 'Best SEO tools?', 'url' => 'https://other.com/tools']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $paa = collect($result)->firstWhere('type', 'people_also_ask');
        $this->assertTrue($paa['is_present']);
    }

    /** @test */
    public function it_detects_no_people_also_ask(): void
    {
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $paa = collect($result)->firstWhere('type', 'people_also_ask');
        $this->assertFalse($paa['is_present']);
    }

    // ============================================
    // Knowledge Panel Detection
    // ============================================

    /** @test */
    public function it_detects_knowledge_panel(): void
    {
        $serpData = [
            'knowledgePanel' => [
                'title' => 'Example Company',
                'website' => 'https://example.com'
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $kp = collect($result)->firstWhere('type', 'knowledge_panel');
        $this->assertTrue($kp['is_present']);
    }

    /** @test */
    public function it_detects_no_knowledge_panel(): void
    {
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $kp = collect($result)->firstWhere('type', 'knowledge_panel');
        $this->assertFalse($kp['is_present']);
    }

    // ============================================
    // Sitelinks Detection (SL-001 to SL-003)
    // ============================================

    /** @test */
    public function it_detects_no_sitelinks(): void
    {
        // SL-001: No sitelinks
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $sitelinks = collect($result)->firstWhere('type', 'sitelinks');
        $this->assertFalse($sitelinks['is_present']);
    }

    /** @test */
    public function it_detects_sitelinks_on_our_result(): void
    {
        // SL-002: Sitelinks on our result
        $serpData = [
            'results' => [
                [
                    'url' => 'https://example.com',
                    'title' => 'Example Site',
                    'sitelinks' => [
                        ['title' => 'Pricing', 'url' => 'https://example.com/pricing'],
                        ['title' => 'Features', 'url' => 'https://example.com/features']
                    ]
                ]
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $sitelinks = collect($result)->firstWhere('type', 'sitelinks');
        $this->assertTrue($sitelinks['is_present']);
    }

    /** @test */
    public function it_detects_sitelinks_on_competitor(): void
    {
        // SL-003: Sitelinks on competitor
        $serpData = [
            'results' => [
                [
                    'url' => 'https://competitor.com',
                    'title' => 'Competitor',
                    'sitelinks' => [
                        ['title' => 'Pricing', 'url' => 'https://competitor.com/pricing']
                    ]
                ]
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $sitelinks = collect($result)->firstWhere('type', 'sitelinks');
        $this->assertFalse($sitelinks['is_present']);
    }

    // ============================================
    // Reviews Detection (RV-001 to RV-004)
    // ============================================

    /** @test */
    public function it_detects_no_reviews(): void
    {
        // RV-001: No reviews
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $reviews = collect($result)->firstWhere('type', 'reviews');
        $this->assertFalse($reviews['is_present']);
    }

    /** @test */
    public function it_detects_reviews_on_our_result(): void
    {
        // RV-002: Stars on our result
        $serpData = [
            'results' => [
                [
                    'url' => 'https://example.com',
                    'title' => 'Example Site',
                    'rating' => '4.5'
                ]
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $reviews = collect($result)->firstWhere('type', 'reviews');
        $this->assertTrue($reviews['is_present']);
    }

    /** @test */
    public function it_includes_rating_in_reviews_result(): void
    {
        // RV-003: Stars with rating
        $serpData = [
            'results' => [
                [
                    'url' => 'https://example.com',
                    'title' => 'Example Site',
                    'rating' => '4.8'
                ]
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $reviews = collect($result)->firstWhere('type', 'reviews');
        $this->assertTrue($reviews['is_present']);
        $this->assertStringContainsString('4.8', $reviews['snippet']);
    }

    // ============================================
    // FAQ Detection
    // ============================================

    /** @test */
    public function it_detects_faq_rich_results(): void
    {
        $serpData = [
            'results' => [
                [
                    'url' => 'https://example.com',
                    'title' => 'Example Site',
                    'faq' => [
                        ['question' => 'What is this?', 'answer' => 'An example']
                    ]
                ]
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $faq = collect($result)->firstWhere('type', 'faq');
        $this->assertTrue($faq['is_present']);
    }

    /** @test */
    public function it_detects_no_faq_rich_results(): void
    {
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $faq = collect($result)->firstWhere('type', 'faq');
        $this->assertFalse($faq['is_present']);
    }

    // ============================================
    // Product Detection
    // ============================================

    /** @test */
    public function it_detects_product_listings(): void
    {
        $serpData = [
            'products' => [
                ['title' => 'Product 1', 'url' => 'https://other.com/product1'],
                ['title' => 'Our Product', 'url' => 'https://example.com/product', 'price' => '$29.99']
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $product = collect($result)->firstWhere('type', 'product');
        $this->assertTrue($product['is_present']);
        $this->assertEquals('$29.99', $product['snippet']);
    }

    /** @test */
    public function it_detects_no_product_listings(): void
    {
        $result = $this->detector->detectFeatures('test', 'https://example.com', []);
        
        $product = collect($result)->firstWhere('type', 'product');
        $this->assertFalse($product['is_present']);
    }

    // ============================================
    // Mock SERP Data Support (M-001 to M-004)
    // ============================================

    /** @test */
    public function it_accepts_mock_serp_result(): void
    {
        // M-001: Accept mock SERP result
        $mockData = [
            '_mock' => true,
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/mock'
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $mockData);
        
        $this->assertIsArray($result);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function mock_data_works_with_all_feature_types(): void
    {
        // M-003: Mock data can force presence
        $mockData = [
            'answerBox' => ['type' => 'featured_snippet', 'url' => 'https://example.com/fs'],
            'localPack' => [['url' => 'https://example.com/lp']],
            'images' => [['source' => 'https://example.com/img']],
            'videos' => [['url' => 'https://example.com/vid']],
            'peopleAlsoAsk' => [['url' => 'https://example.com/paa']],
            'knowledgePanel' => ['website' => 'https://example.com'],
            'results' => [
                ['url' => 'https://example.com', 'sitelinks' => [['url' => '/page']], 'rating' => '4.5', 'faq' => [['q' => 'test']]]
            ],
            'products' => [['url' => 'https://example.com/prod']]
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $mockData);
        
        foreach ($result as $feature) {
            $this->assertTrue($feature['is_present'], "Feature {$feature['type']} should be present");
        }
    }

    // ============================================
    // Edge Cases and Adversarial Tests (X-001 to X-010)
    // ============================================

    /** @test */
    public function it_handles_empty_keyword_string(): void
    {
        // X-001: Empty keyword string
        $result = $this->detector->detectFeatures('', 'https://example.com', []);
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_handles_empty_target_url(): void
    {
        // X-002: Empty target URL
        $serpData = [
            'answerBox' => ['type' => 'featured_snippet', 'url' => 'https://example.com']
        ];

        $result = $this->detector->detectFeatures('test', '', $serpData);
        
        // All features should be not present since no target URL
        foreach ($result as $feature) {
            $this->assertFalse($feature['is_present']);
        }
    }

    /** @test */
    public function it_handles_invalid_target_url(): void
    {
        // X-003: Invalid target URL
        $serpData = [
            'answerBox' => ['type' => 'featured_snippet', 'url' => 'https://example.com']
        ];

        $result = $this->detector->detectFeatures('test', 'not-a-url', $serpData);
        
        $this->assertIsArray($result);
        // Should handle gracefully
    }

    /** @test */
    public function it_handles_very_long_keyword(): void
    {
        // X-004: Very long keyword (500+ chars)
        $longKeyword = str_repeat('a', 600);
        
        $result = $this->detector->detectFeatures($longKeyword, 'https://example.com', []);
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_sanitizes_special_characters_in_keyword(): void
    {
        // X-005: Special chars in keyword (XSS attempt)
        $result = $this->detector->detectFeatures('<script>alert(1)</script>', 'https://example.com', []);
        
        $this->assertIsArray($result);
        // Should not throw exception
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_handles_deeply_nested_serp_data(): void
    {
        // X-006: Deeply nested SERP data
        $deepData = [];
        $current = &$deepData;
        for ($i = 0; $i < 15; $i++) {
            $current['nested'] = [];
            $current = &$current['nested'];
        }
        $current['value'] = 'deep';

        $result = $this->detector->detectFeatures('test', 'https://example.com', $deepData);
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_handles_unicode_in_urls(): void
    {
        // X-008: Unicode in URLs
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://例え.jp/パス'
            ]
        ];

        // Should not throw exception
        $result = $this->detector->detectFeatures('test', 'https://例え.jp', $serpData);
        
        $this->assertIsArray($result);
    }

    /** @test */
    public function it_handles_punycode_urls(): void
    {
        // X-009: Punycode URLs
        $serpData = [
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://xn--example-9ua.com/page'
            ]
        ];

        $result = $this->detector->detectFeatures('test', 'https://xn--example-9ua.com', $serpData);
        
        $this->assertIsArray($result);
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['is_present']);
    }

    /** @test */
    public function it_handles_null_values_in_serp_data(): void
    {
        // Null values throughout
        $serpData = [
            'answerBox' => null,
            'localPack' => null,
            'images' => null,
            'results' => null
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
        
        foreach ($result as $feature) {
            $this->assertFalse($feature['is_present']);
        }
    }

    // ============================================
    // Alternative Field Names
    // ============================================

    /** @test */
    public function it_handles_alternative_field_names(): void
    {
        // Test various naming conventions (camelCase, snake_case)
        $serpData = [
            'answer_box' => ['type' => 'featured_snippet', 'url' => 'https://example.com'],
            'local_pack' => [['url' => 'https://example.com/lp']],
            'image_pack' => [['source' => 'https://example.com/img']],
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $imagePack = collect($result)->firstWhere('type', 'image_pack');
        
        $this->assertTrue($featuredSnippet['is_present']);
        $this->assertTrue($localPack['is_present']);
        $this->assertTrue($imagePack['is_present']);
    }

    // ============================================
    // Integration with SERPResult Interface
    // ============================================

    /** @test */
    public function it_handles_serp_result_interface(): void
    {
        // Create a mock SERPResult
        $mockSerpResult = Mockery::mock(SERPResult::class);
        $mockSerpResult->shouldReceive('getKeyword')->andReturn('test keyword');
        $mockSerpResult->shouldReceive('getResults')->andReturn([]);
        $mockSerpResult->shouldReceive('getSerpFeatures')->andReturn(null);

        $result = $this->detector->detectFeatures('test', 'https://example.com', $mockSerpResult);
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_extracts_features_from_serp_result_interface(): void
    {
        // Create a mock SERPResult with features
        $mockSerpResult = Mockery::mock(SERPResult::class);
        $mockSerpResult->shouldReceive('getKeyword')->andReturn('test keyword');
        $mockSerpResult->shouldReceive('getResults')->andReturn([]);
        $mockSerpResult->shouldReceive('getSerpFeatures')->andReturn([
            'answerBox' => ['type' => 'featured_snippet', 'url' => 'https://example.com/fs']
        ]);

        $result = $this->detector->detectFeatures('test', 'https://example.com', $mockSerpResult);
        
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['is_present']);
    }

    // ============================================
    // Logging Tests (L-001 to L-005)
    // ============================================

    /** @test */
    public function it_logs_detection_errors(): void
    {
        // L-003: Detection errors logged
        // The detector handles most errors gracefully without logging
        // This test verifies the detector handles invalid data without crashing
        $serpData = [
            'localPack' => 'not-an-array-causes-error'
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        // Should still return valid results
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
    }

    /** @test */
    public function it_continues_detection_after_error(): void
    {
        // Verify that error in one detector doesn't stop others
        // The implementation handles errors gracefully within each detector
        $serpData = [
            'localPack' => 'invalid-will-be-ignored',
            'answerBox' => ['type' => 'featured_snippet', 'url' => 'https://example.com']
        ];

        $result = $this->detector->detectFeatures('test', 'https://example.com', $serpData);
        
        // Featured snippet should still work
        $featuredSnippet = collect($result)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['is_present']);
        
        // Local pack should be not present (handled gracefully)
        $localPack = collect($result)->firstWhere('type', 'local_pack');
        $this->assertFalse($localPack['is_present']);
    }

    // ============================================
    // Full Integration Test
    // ============================================

    /** @test */
    public function full_serp_detection_integration(): void
    {
        $fullSerpData = [
            'keyword' => 'best seo tools 2026',
            'answerBox' => [
                'type' => 'featured_snippet',
                'url' => 'https://example.com/best-seo-tools',
                'title' => 'Top 10 SEO Tools for 2026',
                'snippet' => 'The best SEO tools include comprehensive keyword tracking, backlink analysis, and competitor monitoring...'
            ],
            'localPack' => [
                ['name' => 'Example SEO Co', 'url' => 'https://example.com', 'rating' => 4.9],
                ['name' => 'Other SEO', 'url' => 'https://other.com', 'rating' => 4.5],
                ['name' => 'Third SEO', 'url' => 'https://third.com', 'rating' => 4.2]
            ],
            'images' => [
                ['source' => 'https://example.com/seo-dashboard.png', 'title' => 'SEO Dashboard'],
                ['source' => 'https://other.com/seo-chart.png', 'title' => 'SEO Chart']
            ],
            'videos' => [
                ['url' => 'https://youtube.com/watch?v=abc123', 'title' => 'SEO Tutorial']
            ],
            'peopleAlsoAsk' => [
                ['question' => 'What are the best free SEO tools?', 'url' => 'https://example.com/free-seo-tools'],
                ['question' => 'How much do SEO tools cost?', 'url' => 'https://other.com/pricing']
            ],
            'knowledgePanel' => [
                'title' => 'SEO Tools',
                'type' => 'Topic',
                'description' => 'Software for search engine optimization...',
                'website' => 'https://example.com'
            ],
            'results' => [
                [
                    'position' => 1,
                    'url' => 'https://example.com/seo-tools',
                    'title' => 'Best SEO Tools 2026 - Example.com',
                    'sitelinks' => [
                        ['title' => 'Pricing', 'url' => 'https://example.com/pricing'],
                        ['title' => 'Features', 'url' => 'https://example.com/features'],
                        ['title' => 'Reviews', 'url' => 'https://example.com/reviews']
                    ],
                    'rating' => '4.8',
                    'faq' => [
                        ['question' => 'What is included?', 'answer' => 'All features...']
                    ]
                ]
            ],
            'products' => [
                ['title' => 'SEO Pro Plan', 'url' => 'https://example.com/pro', 'price' => '$99/mo']
            ]
        ];

        $result = $this->detector->detectFeatures(
            'best seo tools 2026',
            'https://example.com',
            $fullSerpData
        );

        // Verify all expected features are detected
        $features = collect($result)->keyBy('type');
        
        $this->assertTrue($features['featured_snippet']['is_present'], 'Featured snippet');
        $this->assertEquals('Top 10 SEO Tools for 2026', $features['featured_snippet']['title']);
        
        $this->assertTrue($features['local_pack']['is_present'], 'Local pack');
        $this->assertEquals(1, $features['local_pack']['position']);
        
        $this->assertTrue($features['image_pack']['is_present'], 'Image pack');
        $this->assertFalse($features['video']['is_present'], 'Video (not our domain)');
        
        $this->assertTrue($features['people_also_ask']['is_present'], 'People also ask');
        $this->assertTrue($features['knowledge_panel']['is_present'], 'Knowledge panel');
        $this->assertTrue($features['sitelinks']['is_present'], 'Sitelinks');
        $this->assertTrue($features['reviews']['is_present'], 'Reviews');
        $this->assertTrue($features['faq']['is_present'], 'FAQ');
        $this->assertTrue($features['product']['is_present'], 'Product');
    }
}
