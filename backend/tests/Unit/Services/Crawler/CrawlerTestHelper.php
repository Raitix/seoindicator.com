<?php

namespace Tests\Unit\Services\Crawler;

/**
 * Helper trait for crawler service tests.
 *
 * Provides factory methods, mock helpers, and sample fixtures
 * for testing the Headless Crawler Service.
 *
 * @see TASK-22-18
 */
trait CrawlerTestHelper
{
    /**
     * Create a mock crawl job data array.
     */
    protected function createMockJobData(array $overrides = []): array
    {
        return array_merge([
            'jobId' => 'crawl-' . \Illuminate\Support\Str::uuid()->toString(),
            'status' => 'queued',
            'url' => 'https://example.com',
            'depth' => 0,
            'pagesCrawled' => 0,
            'totalSize' => 0,
            'error' => null,
            'startedAt' => null,
            'completedAt' => null,
        ], $overrides);
    }

    /**
     * Create a mock crawl page data array.
     */
    protected function createMockPageData(array $overrides = []): array
    {
        return array_merge([
            'url' => 'https://example.com/page',
            'depth' => 0,
            'title' => 'Test Page',
            'rendered_html' => '<html><body>Test content</body></html>',
            'structured_text' => 'Test content',
            'meta' => [
                'description' => 'Test description',
                'robots' => 'index, follow',
            ],
            'links' => [
                ['url' => 'https://example.com/link1', 'text' => 'Link 1', 'internal' => true],
                ['url' => 'https://external.com/link2', 'text' => 'Link 2', 'internal' => false],
            ],
            'size' => 1024,
            'crawledAt' => now()->toIso8601String(),
            'error' => null,
        ], $overrides);
    }

    /**
     * Get sample HTML for testing.
     */
    protected function getSampleHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sample page description">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/sample/">
    <meta property="og:title" content="Sample OG Title">
    <meta property="og:description" content="Sample OG Description">
    <meta property="og:image" content="https://example.com/image.jpg">
    <title>Sample Page Title</title>
</head>
<body>
    <header>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="https://external.com">External Link</a>
        </nav>
    </header>
    <main>
        <h1>Main Heading</h1>
        <p>This is sample content for testing purposes.</p>
        <img src="image.jpg" alt="Sample image">
        <a href="/contact" rel="nofollow">Contact</a>
    </main>
    <footer>
        <p>&copy; 2024 Example</p>
    </footer>
</body>
</html>
HTML;
    }

    /**
     * Get malformed HTML for testing.
     */
    protected function getMalformedHtml(): string
    {
        return <<<'HTML'
<html>
<head>
<title>Malformed Page
</head>
<body>
<h1>Missing closing tags
<p>Unclosed paragraph
<div>Mixed content
</body>
</html>
HTML;
    }

    /**
     * Get HTML with Unicode content.
     */
    protected function getUnicodeHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>テストページ - 日本語</title>
    <meta name="description" content="这是一个中文描述 🚀">
</head>
<body>
    <h1>Привет мир!</h1>
    <p>Ελληνικά κείμενο για δοκιμή.</p>
    <p>🎉 Unicode emoji test: Hello 世界</p>
</body>
</html>
HTML;
    }

    /**
     * Get HTML with large content (for size testing).
     */
    protected function getLargeHtml(int $targetSize = 1024 * 1024): string
    {
        $base = '<!DOCTYPE html><html><body>';
        $content = str_repeat('x', $targetSize - strlen($base) - 20);
        return $base . '<p>' . $content . '</p></body></html>';
    }

    /**
     * Get HTML with multiple links for link extraction testing.
     */
    protected function getHtmlWithLinks(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Links Test</title></head>
<body>
    <a href="/">Home</a>
    <a href="/about">About</a>
    <a href="/contact">Contact</a>
    <a href="https://external.com">External</a>
    <a href="https://external.com/page" rel="nofollow">Nofollow</a>
    <a href="https://ads.com" rel="sponsored">Sponsored</a>
    <a href="https://wiki.com" rel="ugc">UGC</a>
    <a href="https://external.com/blank" target="_blank">Opens in new tab</a>
    <a href="javascript:void(0)">JavaScript Link</a>
    <a href="mailto:test@example.com">Email Link</a>
    <a href="#section">Anchor Link</a>
    <a href="/file.pdf">PDF File</a>
    <a href="/image.jpg">Image File</a>
    <img src="image.jpg" alt="Not a link">
</body>
</html>
HTML;
    }

    /**
     * Get sample render result.
     */
    protected function getSampleRenderResult(): array
    {
        return [
            'url' => 'https://example.com/',
            'originalUrl' => 'https://example.com/',
            'title' => 'Example Domain',
            'renderedHtml' => $this->getSampleHtml(),
            'structuredText' => 'Sample content for testing purposes.',
            'meta' => [
                'description' => 'Sample page description',
                'robots' => 'index, follow',
                'canonical' => 'https://example.com/sample/',
            ],
            'links' => [
                ['url' => 'https://example.com/', 'text' => 'Home', 'isInternal' => true],
                ['url' => 'https://example.com/about', 'text' => 'About', 'isInternal' => true],
                ['url' => 'https://external.com', 'text' => 'External Link', 'isInternal' => false],
            ],
            'size' => 1024,
            'loadTime' => 500,
            'statusCode' => 200,
            'error' => null,
        ];
    }

    /**
     * Create a mock browser for Playwright testing.
     */
    protected function createMockBrowser(): object
    {
        return new class {
            public function newContext(array $options = []): object
            {
                return new class {
                    public function newPage(): object
                    {
                        return new class {
                            public function goto(string $url, array $options = []): void {}
                            public function content(): string
                            {
                                return '<html><body>Mock content</body></html>';
                            }
                            public function title(): string
                            {
                                return 'Mock Title';
                            }
                            public function url(): string
                            {
                                return 'https://example.com/';
                            }
                            public function evaluate(string $script): mixed
                            {
                                if (str_contains($script, 'querySelectorAll("a[href]")')) {
                                    return [
                                        ['href' => 'https://example.com/', 'text' => 'Home', 'rel' => null, 'target' => null],
                                    ];
                                }
                                return null;
                            }
                            public function setDefaultNavigationTimeout(int $timeout): void {}
                            public function waitForSelector(string $selector, array $options = []): void {}
                            public function waitForFunction(string $fn): void {}
                            public function close(): void {}
                        };
                    }
                    public function addCookies(array $cookies): void {}
                    public function setExtraHTTPHeaders(array $headers): void {}
                    public function close(): void {}
                };
            }
            public function close(): void {}
        };
    }

    /**
     * Create a mock page for Playwright testing.
     */
    protected function createMockPage(string $html = null, string $title = 'Mock Title'): object
    {
        $content = $html ?? '<html><body>Mock content</body></html>';
        
        return new class($content, $title) {
            private string $content;
            private string $title;
            private string $url = 'https://example.com/';

            public function __construct(string $content, string $title)
            {
                $this->content = $content;
                $this->title = $title;
            }

            public function content(): string
            {
                return $this->content;
            }

            public function title(): string
            {
                return $this->title;
            }

            public function url(): string
            {
                return $this->url;
            }

            public function goto(string $url, array $options = []): void
            {
                $this->url = $url;
            }

            public function evaluate(string $script): mixed
            {
                if (str_contains($script, 'querySelectorAll("a[href]")')) {
                                    return [
                    ['href' => 'https://example.com/', 'text' => 'Home', 'rel' => null, 'target' => null],
                ];
                }
                if (str_contains($script, 'document.body.cloneNode')) {
                    return 'Mock structured text';
                }
                if (str_contains($script, 'meta[name="description"]')) {
                    return 'Mock description';
                }
                return null;
            }

            public function setDefaultNavigationTimeout(int $timeout): void {}
            public function waitForSelector(string $selector, array $options = []): void {}
            public function waitForFunction(string $fn): void {}
            public function close(): void {}
        };
    }

    /**
     * Assert that a URL validation result is valid.
     */
    protected function assertUrlValid(array $result, string $expectedUrl = null): void
    {
        $this->assertTrue($result['valid'], "URL should be valid but got error: " . ($result['error'] ?? 'unknown'));
        if ($expectedUrl !== null) {
            $this->assertEquals($expectedUrl, $result['url']);
        }
    }

    /**
     * Assert that a URL validation result is blocked.
     */
    protected function assertUrlBlocked(array $result, string $expectedReason = null): void
    {
        $this->assertFalse($result['valid'], "URL should be blocked");
        $this->assertTrue($result['blocked'] ?? false, "URL should have blocked flag");
        if ($expectedReason !== null) {
            $this->assertStringContainsString($expectedReason, $result['blockReason'] ?? $result['error']);
        }
    }

    /**
     * Generate a random URL for testing.
     */
    protected function randomUrl(string $domain = 'example.com'): string
    {
        return 'https://' . $domain . '/' . \Illuminate\Support\Str::random(8);
    }

    /**
     * Get blocked IP addresses for SSRF testing.
     */
    protected function getBlockedIps(): array
    {
        return [
            // Localhost
            '127.0.0.1',
            '127.0.0.2',
            '127.255.255.255',
            
            // Private networks
            '10.0.0.1',
            '10.255.255.255',
            '172.16.0.1',
            '172.31.255.255',
            '192.168.0.1',
            '192.168.255.255',
            
            // Link-local / Metadata
            '169.254.0.1',
            '169.254.169.254', // Cloud metadata
            
            // Unspecified
            '0.0.0.0',
            '0.0.0.1',
            
            // Multicast
            '224.0.0.1',
            '239.255.255.255',
            
            // Reserved
            '240.0.0.1',
            '255.255.255.255',
            
            // IPv6
            '::1',
            '::',
            'fe80::1',
            'fc00::1',
            'ff00::1',
        ];
    }

    /**
     * Get valid public IP addresses for testing.
     */
    protected function getValidPublicIps(): array
    {
        return [
            '8.8.8.8',         // Google DNS
            '1.1.1.1',         // Cloudflare DNS
            '208.67.222.222',  // OpenDNS
            '93.184.216.34',   // example.com
        ];
    }

    /**
     * Get blocked domain patterns for testing.
     */
    protected function getBlockedDomains(): array
    {
        return [
            'localhost',
            'test.internal',
            'app.local',
            'service.localhost',
            'internal.company.com',
        ];
    }
}
