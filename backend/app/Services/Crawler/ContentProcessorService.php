<?php

namespace App\Services\Crawler;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Content Processor Service for advanced HTML-to-text conversion and metadata extraction.
 *
 * Processes HTML content to extract structured text suitable for SEO analysis,
 * along with metadata including OpenGraph, Twitter Cards, and standard meta tags.
 *
 * @see TASK-22-6
 * @see SPEC-022
 */
class ContentProcessorService
{
    /**
     * Memory limit for HTML processing (512MB)
     */
    private const MEMORY_LIMIT = '512M';

    /**
     * Maximum output size for structured text (500KB)
     */
    private const MAX_OUTPUT_SIZE = 500000;

    /**
     * Tags to remove from content extraction
     */
    private const REMOVE_TAGS = ['script', 'style', 'noscript', 'svg'];

    /**
     * Elements that represent navigation/boilerplate to remove
     */
    private const BOILERPLATE_TAGS = ['nav', 'footer', 'aside', 'header'];

    /**
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;

    /**
     * Create a new ContentProcessorService instance.
     *
     * @param LoggerInterface|null $logger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? Log::getFacadeRoot();
    }

    /**
     * Maximum input HTML size for processing (2MB)
     */
    private const MAX_INPUT_SIZE = 2000000;

    /**
     * Maximum recursion depth for text extraction
     */
    private const MAX_RECURSION_DEPTH = 100;

    /**
     * Extract structured text from HTML content.
     *
     * Converts HTML to readable text while preserving:
     * - Heading hierarchy (with markdown-style prefixes)
     * - List structures
     * - Link text
     * - Image alt text
     *
     * Removes:
     * - Script, style, noscript tags
     * - Hidden elements (aria-hidden, display:none)
     * - Navigation, footer, sidebar content
     *
     * @param string|null $html The HTML content to process
     * @return string The extracted structured text
     */
    public function extractStructuredText(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Truncate very large input to prevent memory exhaustion
        if (strlen($html) > self::MAX_INPUT_SIZE) {
            $html = substr($html, 0, self::MAX_INPUT_SIZE);
        }

        try {
            $dom = $this->createDomDocument($html);
            if (!$dom) {
                return '';
            }

            // Get the body or the whole document
            $body = $dom->getElementsByTagName('body')->item(0);
            $contentNode = $body ?? $dom->documentElement;

            if (!$contentNode) {
                return '';
            }

            // Clone the node so we don't modify the original
            $clonedNode = $contentNode->cloneNode(true);

            // Remove unwanted elements
            $this->removeUnwantedElements($dom, $clonedNode);

            // Extract text with structure
            $text = $this->extractTextFromNode($dom, $clonedNode, 0, 0);

            // Normalize whitespace
            $text = $this->normalizeWhitespace($text);

            // Limit output size
            $text = $this->limitOutputSize($text);

            // Clean up DOM to free memory
            unset($clonedNode, $contentNode, $dom);

            return $text;
        } catch (\Throwable $e) {
            $this->logWarning('Failed to extract structured text', [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Extract metadata from HTML content.
     *
     * Extracts:
     * - Title (from <title> and og:title)
     * - Meta description
     * - Meta robots
     * - Canonical URL
     * - Open Graph tags
     * - Twitter Card tags
     * - Language attribute
     * - Charset
     *
     * @param string|null $html The HTML content to process
     * @return array The extracted metadata
     */
    public function extractMetadata(?string $html): array
    {
        $result = $this->getEmptyMetadata();

        if (empty($html)) {
            return $result;
        }

        try {
            $dom = $this->createDomDocument($html);
            if (!$dom) {
                return $result;
            }

            $xpath = new DOMXPath($dom);

            // Extract title
            $result['title'] = $this->extractTitle($xpath);

            // Extract meta description
            $result['description'] = $this->extractMetaContent($xpath, 'name', 'description');

            // Extract robots meta
            $result['robots'] = $this->extractMetaContent($xpath, 'name', 'robots');

            // Extract canonical URL
            $result['canonical'] = $this->extractCanonical($xpath);

            // Extract Open Graph data
            $result['og'] = $this->extractOpenGraph($xpath);

            // Extract Twitter Card data
            $result['twitter'] = $this->extractTwitterCards($xpath);

            // Extract language
            $result['language'] = $this->extractLanguage($dom);

            // Extract charset
            $result['charset'] = $this->extractCharset($xpath, $dom);

            // Clean up DOM to free memory
            unset($xpath, $dom);

            return $result;
        } catch (\Throwable $e) {
            $this->logWarning('Failed to extract metadata', [
                'error' => $e->getMessage(),
            ]);
            return $result;
        }
    }

    /**
     * Calculate the byte size of content.
     *
     * Correctly handles UTF-8 multibyte characters.
     *
     * @param string|null $content The content to measure
     * @return int The size in bytes
     */
    public function calculateSize(?string $content): int
    {
        if ($content === null || $content === '') {
            return 0;
        }

        return strlen($content);
    }

    /**
     * Process HTML content through the full pipeline.
     *
     * Returns both structured text and metadata in a single call.
     *
     * @param string|null $html The HTML content to process
     * @return array The processed content with text, metadata, and size
     */
    public function processContent(?string $html): array
    {
        $text = $this->extractStructuredText($html);
        $metadata = $this->extractMetadata($html);
        $size = $this->calculateSize($text);

        return [
            'text' => $text,
            'metadata' => $metadata,
            'size' => $size,
        ];
    }

    /**
     * Create a DOMDocument from HTML string.
     *
     * @param string $html
     * @return DOMDocument|null
     */
    private function createDomDocument(string $html): ?DOMDocument
    {
        // Suppress errors for malformed HTML
        $previousErrorReporting = error_reporting(0);
        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();

            // Add UTF-8 encoding wrapper if not present
            if (stripos($html, '<?xml') === false && stripos($html, 'charset') === false) {
                $html = '<?xml encoding="UTF-8">' . $html;
            }

            // Load HTML with UTF-8 support
            $loaded = $dom->loadHTML(
                $html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
            );

            if (!$loaded) {
                return null;
            }

            // Clear libxml errors
            libxml_clear_errors();

            return $dom;
        } catch (\Throwable $e) {
            $this->logWarning('Failed to parse HTML', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } finally {
            error_reporting($previousErrorReporting);
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    /**
     * Remove unwanted elements from the DOM.
     *
     * @param DOMDocument $dom
     * @param DOMNode $node
     */
    private function removeUnwantedElements(DOMDocument $dom, DOMNode $node): void
    {
        // Remove script, style, noscript, and svg tags
        foreach (self::REMOVE_TAGS as $tagName) {
            $elements = $node->getElementsByTagName($tagName);
            $toRemove = [];
            foreach ($elements as $element) {
                $toRemove[] = $element;
            }
            foreach ($toRemove as $element) {
                $element->parentNode?->removeChild($element);
            }
        }

        // Remove boilerplate elements (nav, footer, aside, header)
        foreach (self::BOILERPLATE_TAGS as $tagName) {
            $elements = $node->getElementsByTagName($tagName);
            $toRemove = [];
            foreach ($elements as $element) {
                $toRemove[] = $element;
            }
            foreach ($toRemove as $element) {
                $element->parentNode?->removeChild($element);
            }
        }

        // Remove elements with aria-hidden="true"
        $xpath = new DOMXPath($dom);
        $hiddenElements = $xpath->query('.//*[@aria-hidden="true"]', $node);
        if ($hiddenElements) {
            $toRemove = [];
            foreach ($hiddenElements as $element) {
                $toRemove[] = $element;
            }
            foreach ($toRemove as $element) {
                $element->parentNode?->removeChild($element);
            }
        }

        // Remove elements with display:none in style attribute
        $elements = $xpath->query('.//*[@style]', $node);
        if ($elements) {
            $toRemove = [];
            foreach ($elements as $element) {
                $style = $element->getAttribute('style');
                if (preg_match('/display\s*:\s*none/i', $style)) {
                    $toRemove[] = $element;
                }
            }
            foreach ($toRemove as $element) {
                $element->parentNode?->removeChild($element);
            }
        }
    }

    /**
     * Extract text from a DOM node with structure preservation.
     *
     * @param DOMDocument $dom
     * @param DOMNode $node
     * @param int $listIndex Counter for ordered lists
     * @param int $depth Current recursion depth
     * @return string
     */
    private function extractTextFromNode(DOMDocument $dom, DOMNode $node, int $listIndex = 0, int $depth = 0): string
    {
        // Prevent infinite recursion on malformed HTML
        if ($depth > self::MAX_RECURSION_DEPTH) {
            return '';
        }

        $text = '';

        if ($node->nodeType === XML_TEXT_NODE) {
            return trim($node->nodeValue);
        }

        if (!$node instanceof DOMElement) {
            // For non-element nodes, process children
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    $text .= $this->extractTextFromNode($dom, $child, $listIndex, $depth + 1) . ' ';
                }
            }
            return trim($text);
        }

        $tagName = strtolower($node->tagName);

        // Handle headings
        if (preg_match('/^h([1-6])$/', $tagName, $matches)) {
            $level = (int) $matches[1];
            $prefix = str_repeat('#', $level) . ' ';
            // Get text content directly from heading to avoid recursion
            $content = trim($node->textContent);
            return "\n" . $prefix . $content . "\n";
        }

        // Handle unordered lists
        if ($tagName === 'ul') {
            $text = "\n";
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                    $content = $this->extractTextFromNode($dom, $child, $listIndex, $depth + 1);
                    $text .= "- " . $content . "\n";
                }
            }
            return $text;
        }

        // Handle ordered lists
        if ($tagName === 'ol') {
            $text = "\n";
            $index = 1;
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                    $content = $this->extractTextFromNode($dom, $child, $listIndex, $depth + 1);
                    $text .= $index . ". " . $content . "\n";
                    $index++;
                }
            }
            return $text;
        }

        // Handle links
        if ($tagName === 'a') {
            $linkText = trim($node->textContent);
            if (!empty($linkText)) {
                return '[' . $linkText . ']';
            }
            return '';
        }

        // Handle images
        if ($tagName === 'img') {
            $alt = $node->getAttribute('alt');
            if (!empty($alt)) {
                return '[alt: ' . $alt . ']';
            }
            return '';
        }

        // Handle paragraphs and divs
        if ($tagName === 'p' || $tagName === 'div') {
            // Process children, not the node itself
            $content = '';
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    $content .= $this->extractTextFromNode($dom, $child, $listIndex, $depth + 1) . ' ';
                }
            }
            $content = trim($content);
            if (!empty($content)) {
                return "\n" . $content . "\n";
            }
            return '';
        }

        // Handle line breaks
        if ($tagName === 'br') {
            return "\n";
        }

        // Handle pre-formatted text
        if ($tagName === 'pre') {
            return "\n" . $node->textContent . "\n";
        }

        // Handle blockquote
        if ($tagName === 'blockquote') {
            // Process children, not the node itself
            $content = '';
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    $content .= $this->extractTextFromNode($dom, $child, $listIndex, $depth + 1) . ' ';
                }
            }
            $content = trim($content);
            return "\n> " . $content . "\n";
        }

        // Handle other elements - process children
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $text .= $this->extractTextFromNode($dom, $child, $listIndex, $depth + 1) . ' ';
            }
        }

        return trim($text);
    }

    /**
     * Normalize whitespace in text.
     *
     * @param string $text
     * @return string
     */
    private function normalizeWhitespace(string $text): string
    {
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize multiple spaces to single space
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Normalize multiple newlines to double newline
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Remove spaces at the beginning of lines
        $text = preg_replace('/^[ \t]+/m', '', $text);

        // Remove spaces at the end of lines
        $text = preg_replace('/[ \t]+$/m', '', $text);

        // Trim the whole text
        $text = trim($text);

        return $text;
    }

    /**
     * Limit output size to maximum allowed.
     *
     * @param string $text
     * @return string
     */
    private function limitOutputSize(string $text): string
    {
        if (strlen($text) > self::MAX_OUTPUT_SIZE) {
            return substr($text, 0, self::MAX_OUTPUT_SIZE);
        }
        return $text;
    }

    /**
     * Get empty metadata structure.
     *
     * @return array
     */
    private function getEmptyMetadata(): array
    {
        return [
            'title' => null,
            'description' => null,
            'robots' => null,
            'canonical' => null,
            'og' => [
                'title' => null,
                'description' => null,
                'image' => null,
                'type' => null,
                'url' => null,
            ],
            'twitter' => [
                'card' => null,
                'title' => null,
                'description' => null,
            ],
            'language' => null,
            'charset' => null,
        ];
    }

    /**
     * Extract title from the document.
     *
     * @param DOMXPath $xpath
     * @return string|null
     */
    private function extractTitle(DOMXPath $xpath): ?string
    {
        $titleNodes = $xpath->query('//title');
        if ($titleNodes && $titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
            return $this->decodeEntities($title);
        }
        return null;
    }

    /**
     * Extract meta content by attribute name.
     *
     * @param DOMXPath $xpath
     * @param string $attributeName The attribute name (name or property)
     * @param string $value The meta name value to find
     * @return string|null
     */
    private function extractMetaContent(DOMXPath $xpath, string $attributeName, string $value): ?string
    {
        $nodes = $xpath->query("//meta[@{$attributeName}='{$value}']/@content");
        if ($nodes && $nodes->length > 0) {
            $content = trim($nodes->item(0)->nodeValue);
            return $this->decodeEntities($content);
        }
        return null;
    }

    /**
     * Extract canonical URL.
     *
     * @param DOMXPath $xpath
     * @return string|null
     */
    private function extractCanonical(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//link[@rel="canonical"]/@href');
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }
        return null;
    }

    /**
     * Extract Open Graph metadata.
     *
     * @param DOMXPath $xpath
     * @return array
     */
    private function extractOpenGraph(DOMXPath $xpath): array
    {
        return [
            'title' => $this->extractMetaContent($xpath, 'property', 'og:title'),
            'description' => $this->extractMetaContent($xpath, 'property', 'og:description'),
            'image' => $this->extractMetaContent($xpath, 'property', 'og:image'),
            'type' => $this->extractMetaContent($xpath, 'property', 'og:type'),
            'url' => $this->extractMetaContent($xpath, 'property', 'og:url'),
        ];
    }

    /**
     * Extract Twitter Card metadata.
     *
     * @param DOMXPath $xpath
     * @return array
     */
    private function extractTwitterCards(DOMXPath $xpath): array
    {
        return [
            'card' => $this->extractMetaContent($xpath, 'name', 'twitter:card'),
            'title' => $this->extractMetaContent($xpath, 'name', 'twitter:title'),
            'description' => $this->extractMetaContent($xpath, 'name', 'twitter:description'),
        ];
    }

    /**
     * Extract language from html element.
     *
     * @param DOMDocument $dom
     * @return string|null
     */
    private function extractLanguage(DOMDocument $dom): ?string
    {
        $htmlNodes = $dom->getElementsByTagName('html');
        if ($htmlNodes && $htmlNodes->length > 0) {
            $lang = $htmlNodes->item(0)->getAttribute('lang');
            if (!empty($lang)) {
                return $lang;
            }
        }
        return null;
    }

    /**
     * Extract charset from document.
     *
     * @param DOMXPath $xpath
     * @param DOMDocument $dom
     * @return string|null
     */
    private function extractCharset(DOMXPath $xpath, DOMDocument $dom): ?string
    {
        // Try meta charset first
        $nodes = $xpath->query('//meta[@charset]/@charset');
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        // Try Content-Type meta
        $nodes = $xpath->query('//meta[@http-equiv="Content-Type"]/@content');
        if ($nodes && $nodes->length > 0) {
            $content = $nodes->item(0)->nodeValue;
            if (preg_match('/charset=([^;\\s]+)/i', $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Decode HTML entities in text.
     *
     * @param string|null $text
     * @return string|null
     */
    private function decodeEntities(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array $context
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning($message, $context);
        }
    }
}
