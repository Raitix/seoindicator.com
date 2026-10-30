<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\BaseIndicatorChecker;
use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\Suggestion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Unit tests for MobileFriendlyChecker.
 *
 * Tests the SEO indicator checker for mobile-friendliness analysis.
 * Covers viewport, responsive design, touch targets, and mobile readiness.
 *
 * @see TASK-18-19
 * @see TEST-18-19
 */
#[Group('seo')]
#[Group('indicators')]
class MobileFriendlyCheckerTest extends TestCase
{
    /**
     * The checker class being tested.
     */
    protected string $checkerClass = 'App\Services\SEO\Checkers\MobileFriendlyChecker';

    /**
     * Create a Crawler from HTML string.
     */
    protected function createCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    /**
     * Create the checker instance.
     */
    protected function createChecker(): object
    {
        $this->assertClassExists($this->checkerClass, 'MobileFriendlyChecker class should exist');
        return new $this->checkerClass();
    }

    /**
     * Assert that a class exists, with a helpful message for TDD.
     */
    protected function assertClassExists(string $className, string $message = ''): void
    {
        $this->assertTrue(
            class_exists($className),
            $message ?: "Class {$className} should exist. Developer needs to implement this class."
        );
    }

    // =========================================================================
    // IC: Interface Compliance Tests
    // =========================================================================

    #[Test]
    public function ic_01_id_returns_correct_identifier(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals('mobile_friendly', $checker->id());
    }

    #[Test]
    public function ic_02_name_returns_correct_name(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals('Mobile Friendly', $checker->name());
    }

    #[Test]
    public function ic_03_category_returns_technical(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals('technical', $checker->category());
    }

    #[Test]
    public function ic_04_weight_returns_0_08(): void
    {
        $checker = $this->createChecker();
        $this->assertEquals(0.08, $checker->weight());
    }

    #[Test]
    public function ic_05_implements_indicator_checker_interface(): void
    {
        $checker = $this->createChecker();
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function ic_06_extends_base_indicator_checker(): void
    {
        $checker = $this->createChecker();
        $this->assertInstanceOf(BaseIndicatorChecker::class, $checker);
    }

    // =========================================================================
    // VM: Viewport Meta Tests
    // =========================================================================

    #[Test]
    public function vm_01_proper_viewport_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue($result->details['hasViewport'] ?? false);
        $this->assertEquals('width=device-width, initial-scale=1', $result->details['viewportContent'] ?? '');
    }

    #[Test]
    public function vm_02_missing_viewport_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['hasViewport'] ?? true);
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function vm_03_fixed_width_viewport_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=1024"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasViewport'] ?? false);
        $this->assertFalse($result->details['isResponsive'] ?? true);
    }

    #[Test]
    public function vm_04_user_scalable_no_warning(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, user-scalable=no"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should detect but warn about accessibility issue
        $this->assertTrue($result->details['hasViewport'] ?? false);
        $this->assertTrue($result->details['userScalableDisabled'] ?? false);
    }

    #[Test]
    public function vm_05_minimum_scale_accepted(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, minimum-scale=1.0"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasViewport'] ?? false);
    }

    #[Test]
    public function vm_06_case_insensitive_viewport(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><META NAME="VIEWPORT" CONTENT="width=device-width"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasViewport'] ?? false);
    }

    #[Test]
    public function vm_07_empty_viewport_content(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content=""></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Has viewport but empty content - should flag as issue
        $this->assertTrue($result->details['hasViewport'] ?? false);
        $this->assertLessThanOrEqual(2, $result->score);
    }

    // =========================================================================
    // RD: Responsive Design Tests
    // =========================================================================

    #[Test]
    public function rd_01_css_media_queries_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>@media (max-width: 768px) { .sidebar { display: none; } }</style>
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasMediaQueries'] ?? false);
    }

    #[Test]
    public function rd_02_responsive_framework_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css">
        </head><body><div class="container-fluid"></div></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Bootstrap detected - should be responsive
        $this->assertTrue($result->details['isResponsive'] ?? false);
    }

    #[Test]
    public function rd_03_no_responsive_css_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { width: 1200px; }</style>
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Fixed width without media queries
        $this->assertFalse($result->details['hasMediaQueries'] ?? true);
    }

    #[Test]
    public function rd_04_tailwind_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <script src="https://cdn.tailwindcss.com"></script>
        </head><body class="md:flex lg:grid"></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['isResponsive'] ?? false);
    }

    #[Test]
    public function rd_05_multiple_media_queries(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>
                @media (max-width: 576px) { .mobile { display: block; } }
                @media (min-width: 768px) { .tablet { display: block; } }
                @media (min-width: 992px) { .desktop { display: block; } }
            </style>
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasMediaQueries'] ?? false);
        $this->assertGreaterThanOrEqual(3, $result->details['mediaQueryCount'] ?? 0);
    }

    #[Test]
    public function rd_06_viewport_container_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
        </head><body><div class="container-fluid"><div class="row"></div></div></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasResponsiveContainer'] ?? false);
    }

    // =========================================================================
    // TT: Touch Target Tests
    // =========================================================================

    #[Test]
    public function tt_01_proper_touch_targets(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>button { min-height: 48px; min-width: 48px; }</style>
        </head><body><button>Click Me</button></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['touchTargetsOk'] ?? false);
    }

    #[Test]
    public function tt_02_small_touch_targets_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>button { width: 20px; height: 20px; }</style>
        </head><body><button>X</button></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['touchTargetsOk'] ?? true);
    }

    #[Test]
    public function tt_03_adequate_link_spacing(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>a { display: inline-block; padding: 16px; margin: 8px; }</style>
        </head><body>
            <a href="/1">Link 1</a>
            <a href="/2">Link 2</a>
        </body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['linkSpacingOk'] ?? false);
    }

    #[Test]
    public function tt_04_close_touch_targets_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>a { margin: 2px; font-size: 10px; }</style>
        </head><body>
            <a href="/1">1</a><a href="/2">2</a><a href="/3">3</a>
        </body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['linkSpacingOk'] ?? true);
    }

    #[Test]
    public function tt_05_large_clickable_area(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
        </head><body>
            <button style="padding: 20px 40px;">Large Button</button>
        </body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['touchTargetsOk'] ?? false);
    }

    // =========================================================================
    // FS: Font Size Tests
    // =========================================================================

    #[Test]
    public function fs_01_readable_font_size(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { font-size: 16px; }</style>
        </head><body><p>Readable text</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['fontSizeOk'] ?? false);
    }

    #[Test]
    public function fs_02_too_small_font_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { font-size: 10px; } p { font-size: 8px; }</style>
        </head><body><p>Tiny text</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['fontSizeOk'] ?? true);
    }

    #[Test]
    public function fs_03_relative_units_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { font-size: 1rem; } h1 { font-size: 2em; }</style>
        </head><body><h1>Title</h1></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['usesRelativeUnits'] ?? false);
    }

    #[Test]
    public function fs_04_minimum_font_size(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { font-size: 12px; }</style>
        </head><body><p>Text</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // 12px is borderline acceptable
        $this->assertNotNull($result->details['fontSizeOk'] ?? null);
    }

    // =========================================================================
    // HS: Horizontal Scroll Tests
    // =========================================================================

    #[Test]
    public function hs_01_no_horizontal_scroll(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { max-width: 100%; overflow-x: hidden; }</style>
        </head><body><p>Content fits</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['noHorizontalScroll'] ?? false);
    }

    #[Test]
    public function hs_02_fixed_width_overflow_flagged(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { width: 1200px; }</style>
        </head><body><p>Wide content</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['noHorizontalScroll'] ?? true);
    }

    #[Test]
    public function hs_03_wide_table_warning(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
        </head><body>
            <table style="width: 1500px;"><tr><td>Wide table</td></tr></table>
        </body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertFalse($result->details['noHorizontalScroll'] ?? true);
        $this->assertTrue($result->details['hasWideTables'] ?? false);
    }

    #[Test]
    public function hs_04_responsive_images(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>img { max-width: 100%; height: auto; }</style>
        </head><body><img src="test.jpg" alt="Test"></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['imagesResponsive'] ?? false);
    }

    // =========================================================================
    // SC: Scoring Tests
    // =========================================================================

    #[Test]
    public function sc_01_perfect_mobile_returns_3(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                @media (max-width: 768px) { .sidebar { display: none; } }
                body { font-size: 16px; }
                button { min-height: 48px; min-width: 48px; }
            </style>
        </head><body><button>Click Me</button></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->passed);
    }

    #[Test]
    public function sc_02_good_mobile_returns_2(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>body { font-size: 14px; }</style>
        </head><body><p>Content</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function sc_03_poor_mobile_returns_1(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <style>body { width: 1200px; font-size: 10px; }</style>
        </head><body><p>Not mobile friendly</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
        $this->assertFalse($result->passed);
    }

    #[Test]
    public function sc_04_viewport_only_returns_2(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
        </head><body><p>Just viewport, no responsive CSS</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function sc_05_responsive_no_viewport_returns_1(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <style>@media (max-width: 768px) { body { color: red; } }</style>
        </head><body><p>Missing viewport meta</p></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals(1, $result->score);
    }

    // =========================================================================
    // EC: Edge Case Tests
    // =========================================================================

    #[Test]
    public function ec_01_amp_page_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html amp><head>
            <meta name="viewport" content="width=device-width">
        </head><body>AMP content</body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['isAmp'] ?? false);
    }

    #[Test]
    public function ec_02_pwa_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <link rel="manifest" href="/manifest.json">
            <meta name="theme-color" content="#000">
        </head><body>PWA content</body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['isPwa'] ?? false);
    }

    #[Test]
    public function ec_03_empty_html(): void
    {
        $checker = $this->createChecker();
        $html = '<html></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function ec_04_null_url_error(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, '');

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('error', $result->details);
    }

    #[Test]
    public function ec_05_malformed_html_handled(): void
    {
        $checker = $this->createChecker();
        $html = '<html><head><meta name="viewport" content="width=device-width"';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function ec_06_multiple_viewport_tags(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <meta name="viewport" content="width=1024">
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        // Should use first viewport and warn about duplicates
        $this->assertTrue($result->details['hasViewport'] ?? false);
        $this->assertGreaterThanOrEqual(1, $result->details['viewportCount'] ?? 1);
    }

    #[Test]
    public function ec_07_app_deep_links_detected(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <meta name="apple-itunes-app" content="app-id=123456">
            <link rel="android-app" href="android-app://com.example">
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertTrue($result->details['hasAppLinks'] ?? false);
    }

    // =========================================================================
    // SG: Suggestions Generation Tests
    // =========================================================================

    #[Test]
    public function sg_01_missing_viewport_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $this->assertNotEmpty($suggestions);
        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'viewport') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest adding viewport meta tag');
    }

    #[Test]
    public function sg_02_fixed_width_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=1024">
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'device-width') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest using device-width');
    }

    #[Test]
    public function sg_03_small_touch_target_suggestion(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width">
            <style>button { width: 20px; height: 20px; }</style>
        </head><body><button>X</button></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'touch') !== false || stripos($suggestion->message, 'target') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should suggest larger touch targets');
    }

    #[Test]
    public function sg_04_user_scalable_warning(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, user-scalable=no">
        </head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion->message, 'scalable') !== false || stripos($suggestion->message, 'zoom') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should warn about disabled user scaling');
    }

    #[Test]
    public function sg_05_suggestions_have_correct_types(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        foreach ($suggestions as $suggestion) {
            $this->assertInstanceOf(Suggestion::class, $suggestion);
            $this->assertContains($suggestion->type, ['warning', 'error', 'info']);
        }
    }

    #[Test]
    public function sg_06_perfect_mobile_no_error_suggestions(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                @media (max-width: 768px) { .sidebar { display: none; } }
                body { font-size: 16px; }
                button { min-height: 48px; }
            </style>
        </head><body><button>Click</button></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);

        // Perfect mobile should have no error/warning suggestions
        $errorOrWarning = array_filter($suggestions, fn($s) => in_array($s->type, ['error', 'warning']));
        $this->assertEmpty($errorOrWarning);
    }

    // =========================================================================
    // RS: Result Structure Tests
    // =========================================================================

    #[Test]
    public function rs_01_result_has_required_fields(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertEquals('mobile_friendly', $result->id);
        $this->assertEquals('Mobile Friendly', $result->name);
        $this->assertEquals('technical', $result->category);
        $this->assertEquals(0.08, $result->weight);
    }

    #[Test]
    public function rs_02_details_populated(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertArrayHasKey('hasViewport', $result->details);
        $this->assertArrayHasKey('viewportContent', $result->details);
        $this->assertArrayHasKey('isResponsive', $result->details);
    }

    #[Test]
    public function rs_03_boolean_flags_are_boolean(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertIsBool($result->details['hasViewport']);
        $this->assertIsBool($result->passed);
    }

    #[Test]
    public function rs_04_score_in_valid_range(): void
    {
        $checker = $this->createChecker();
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $crawler = $this->createCrawler($html);

        $result = $checker->check($crawler, 'https://example.com');

        $this->assertGreaterThanOrEqual(0, $result->score);
        $this->assertLessThanOrEqual(3, $result->score);
    }
}
