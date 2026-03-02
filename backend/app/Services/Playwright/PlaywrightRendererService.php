<?php

namespace App\Services\Playwright;

use App\Services\Playwright\Contracts\BrowserInterface;
use App\Services\Playwright\Contracts\BrowserContextInterface;
use App\Services\Playwright\Contracts\PageInterface;
use App\Services\Playwright\Data\RenderResult;
use App\Services\Playwright\Data\ExtractedContent;
use App\Services\Playwright\Exceptions\TimeoutException;
use App\Services\Playwright\Exceptions\NavigationException;
use App\Services\Playwright\Exceptions\RenderException;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Service for rendering JavaScript-heavy pages using Playwright.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class PlaywrightRendererService
{
    protected ?BrowserInterface $sharedBrowser = null;
    protected bool $returnPartialOnError = false;

    public function __construct(
        protected BrowserLauncher $browserLauncher,
        protected ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? app(LoggerInterface::class);
    }

    /**
     * Render a URL and extract content.
     *
     * @param string $url URL to render
     * @param array $options Render options
     * @return RenderResult
     * @throws TimeoutException
     * @throws NavigationException
     * @throws RenderException
     */
    public function render(string $url, array $options = []): RenderResult
    {
        // Validate URL
        $this->validateUrl($url);

        $startTime = microtime(true);
        $originalUrl = $url;

        try {
            $browser = $this->getSharedBrowser();
            $context = $this->createContext($browser, $options);
            $page = $context->newPage();

            // Set timeout
            $timeout = $options['timeout'] ?? config('playwright.timeout', 30000);
            $page->setDefaultNavigationTimeout($timeout);

            // Navigate to URL
            $page->goto($url, ['timeout' => $timeout, 'waitUntil' => 'networkidle']);

            // Wait for selector if specified
            if (!empty($options['waitForSelector'])) {
                $page->waitForSelector($options['waitForSelector'], ['timeout' => $timeout]);
            }

            // Additional wait time
            if (!empty($options['waitTime'])) {
                // Use waitForFunction with setTimeout internally
                $page->waitForFunction('() => new Promise(r => setTimeout(r, ' . $options['waitTime'] . '))');
            }

            // Extract content
            $content = $this->extractContent($page);
            $links = $this->extractLinks($page, $url);
            $meta = $this->extractMetadata($page);

            $loadTime = (int) ((microtime(true) - $startTime) * 1000);

            $page->close();
            $context->close();

            return new RenderResult(
                url: $page->url(),
                originalUrl: $originalUrl,
                title: $content->title,
                renderedHtml: $content->html,
                structuredText: $content->structuredText,
                meta: $meta,
                links: $links,
                size: $content->size,
                loadTime: $loadTime,
                statusCode: 200,
                error: null
            );
        } catch (TimeoutException $e) {
            throw $e;
        } catch (NavigationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Playwright render failed', [
                'url' => $url,
                'options' => $options,
                'error' => $e->getMessage(),
            ]);

            if ($this->returnPartialOnError) {
                return new RenderResult(
                    url: $url,
                    originalUrl: $originalUrl,
                    title: null,
                    renderedHtml: null,
                    structuredText: null,
                    meta: [],
                    links: [],
                    size: 0,
                    loadTime: 0,
                    statusCode: 0,
                    error: $e->getMessage()
                );
            }

            throw new RenderException($url, 'render', $e->getMessage());
        }
    }

    /**
     * Extract content from a page.
     */
    public function extractContent(PageInterface $page): ExtractedContent
    {
        $html = $page->content();
        $title = $page->title();

        // Extract structured text (remove scripts, styles, hidden elements)
        $structuredText = $page->evaluate('
            () => {
                const clone = document.body.cloneNode(true);
                
                // Remove script and style tags
                clone.querySelectorAll("script, style").forEach(el => el.remove());
                
                // Remove hidden elements
                clone.querySelectorAll("[style*=\"display: none\"], [style*=\"visibility: hidden\"]").forEach(el => el.remove());
                
                return clone.textContent.trim();
            }
        ');

        return new ExtractedContent(
            html: $html,
            title: $title,
            structuredText: is_string($structuredText) ? $structuredText : '',
            size: strlen($html)
        );
    }

    /**
     * Extract links from a page.
     */
    public function extractLinks(PageInterface $page, string $baseUrl): array
    {
        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);

        $links = $page->evaluate('
            () => {
                return Array.from(document.querySelectorAll("a[href]")).map(a => ({
                    href: a.href,
                    text: a.textContent.trim(),
                    rel: a.getAttribute("rel"),
                    target: a.getAttribute("target")
                }));
            }
        ');

        // Handle null or non-array results
        if (!is_array($links)) {
            return [];
        }

        return array_filter(array_map(function ($link) use ($baseDomain) {
            if (empty($link['href'])) {
                return null;
            }

            $url = $link['href'];
            $linkDomain = parse_url($url, PHP_URL_HOST);

            return [
                'url' => $url,
                'text' => $link['text'] ?? '',
                'rel' => $link['rel'] ?? null,
                'target' => $link['target'] ?? null,
                'isInternal' => $linkDomain === $baseDomain,
            ];
        }, $links), fn($l) => $l !== null);
    }

    /**
     * Extract metadata from a page.
     */
    public function extractMetadata(PageInterface $page): array
    {
        return [
            'title' => $page->title(),
            'description' => $page->evaluate('
                () => document.querySelector(\'meta[name="description"]\')?.content
            '),
            'robots' => $page->evaluate('
                () => document.querySelector(\'meta[name="robots"]\')?.content
            '),
            'canonical' => $page->evaluate('
                () => document.querySelector(\'link[rel="canonical"]\')?.href
            '),
            'ogTitle' => $page->evaluate('
                () => document.querySelector(\'meta[property="og:title"]\')?.content
            '),
            'ogDescription' => $page->evaluate('
                () => document.querySelector(\'meta[property="og:description"]\')?.content
            '),
            'ogImage' => $page->evaluate('
                () => document.querySelector(\'meta[property="og:image"]\')?.content
            '),
        ];
    }

    /**
     * Close browser instances.
     */
    public function close(): void
    {
        $this->browserLauncher->closeAll();
        $this->sharedBrowser = null;
    }

    /**
     * Get the browser launcher instance.
     */
    public function getBrowserLauncher(): BrowserLauncher
    {
        return $this->browserLauncher;
    }

    /**
     * Get the logger instance.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the shared browser instance.
     */
    public function getSharedBrowser(): BrowserInterface
    {
        if (!$this->sharedBrowser) {
            $this->sharedBrowser = $this->browserLauncher->getSharedInstance();
        }
        return $this->sharedBrowser;
    }

    /**
     * Set whether to return partial results on error.
     */
    public function setReturnPartialOnError(bool $value): self
    {
        $this->returnPartialOnError = $value;
        return $this;
    }

    /**
     * Create a browser context with options.
     */
    protected function createContext(BrowserInterface $browser, array $options): BrowserContextInterface
    {
        $contextOptions = [];

        if (!empty($options['userAgent'])) {
            $contextOptions['userAgent'] = $options['userAgent'];
        }

        if (!empty($options['viewport'])) {
            $contextOptions['viewport'] = $options['viewport'];
        }

        $context = $browser->newContext($contextOptions);

        // Set cookies
        if (!empty($options['cookies'])) {
            $context->addCookies($options['cookies']);
        }

        // Set extra headers
        if (!empty($options['headers'])) {
            $context->setExtraHTTPHeaders($options['headers']);
        }

        return $context;
    }

    /**
     * Validate URL for SSRF protection.
     */
    protected function validateUrl(string $url): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException("Only HTTP and HTTPS protocols are allowed");
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (empty($host)) {
            throw new \InvalidArgumentException('URL must have a valid host');
        }

        // Block localhost variants
        $lowerHost = strtolower($host);
        if (in_array($lowerHost, ['localhost', '127.0.0.1', '::1', '0.0.0.0', '[::1]'])) {
            throw new \InvalidArgumentException('Access to localhost is not allowed');
        }

        // Block localhost subdomains (e.g., localhost.localdomain)
        if (str_ends_with($lowerHost, '.localhost') || str_ends_with($lowerHost, '.local')) {
            throw new \InvalidArgumentException('Access to localhost is not allowed');
        }

        // Block cloud metadata IP
        if ($host === '169.254.169.254') {
            throw new \InvalidArgumentException('Access to cloud metadata service is not allowed');
        }

        // Only check IP restrictions if host is an IP address (not a domain name)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Block private/reserved IP ranges (10.x, 172.16-31.x, 192.168.x, etc.)
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \InvalidArgumentException('Access to private IP addresses is not allowed');
            }

            // Block link-local addresses (169.254.x.x)
            $ipParts = explode('.', $host);
            if (count($ipParts) === 4 && $ipParts[0] === '169' && $ipParts[1] === '254' && $host !== '169.254.169.254') {
                throw new \InvalidArgumentException('Access to link-local addresses is not allowed');
            }
        }
    }
}
