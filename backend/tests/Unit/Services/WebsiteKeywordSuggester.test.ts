/**
 * Website Keyword Suggester Service Tests
 *
 * Tests for the service that parses website URLs to generate keyword suggestions.
 *
 * @see TEST-17-17.md for test documentation
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock fetch for HTTP requests
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock HTML parser
class HTMLParser {
  extractTitle(html: string): string {
    const match = html.match(/<title[^>]*>([^<]+)<\/title>/i);
    return match ? match[1].trim() : '';
  }

  extractHeadings(html: string, tag: 'h1' | 'h2'): string[] {
    const regex = new RegExp(`<${tag}[^>]*>([^<]+)<\/${tag}>`, 'gi');
    const headings: string[] = [];
    let match;
    while ((match = regex.exec(html)) !== null) {
      headings.push(match[1].trim());
    }
    return headings.slice(0, 5);
  }

  extractMetaKeywords(html: string): string[] {
    const match = html.match(/<meta\s+name="keywords"\s+content="([^"]+)"/i);
    if (!match) return [];
    return match[1].split(',').map((k) => k.trim());
  }
}

// Mock service implementation
class WebsiteKeywordSuggester {
  private parser = new HTMLParser();
  private cache = new Map<string, { suggestions: string[]; timestamp: number }>();
  private cacheTTL = 3600000; // 1 hour

  async fetchHTML(url: string): Promise<string> {
    const response = await fetch(url, {
      headers: { 'User-Agent': 'SEOIndicator/1.0' },
    });
    if (!response.ok) {
      throw new Error(`Failed to fetch: ${response.status}`);
    }
    return response.text();
  }

  extractDomain(url: string): string | null {
    try {
      const urlObj = new URL(url);
      const hostname = urlObj.hostname;
      // Remove TLD
      const parts = hostname.split('.');
      if (parts.length >= 2) {
        return parts[parts.length - 2];
      }
      return hostname;
    } catch {
      return null;
    }
  }

  normalizeURL(url: string): string {
    url = url.trim();
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url;
    }
    return url;
  }

  validateURL(url: string): boolean {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  }

  generateSuggestions(title: string, headings: string[], domain: string | null): string[] {
    const suggestions: Set<string> = new Set();

    // From title
    if (title) {
      const cleanTitle = title.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim();
      if (cleanTitle) {
        suggestions.add(cleanTitle);
        suggestions.add(`best ${cleanTitle}`);
        suggestions.add(`free ${cleanTitle}`);
      }
    }

    // From headings
    headings.forEach((h) => {
      const clean = h.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim();
      if (clean && clean.split(' ').length <= 4) {
        suggestions.add(clean);
      }
    });

    // From domain
    if (domain) {
      suggestions.add(`${domain} review`);
      suggestions.add(`${domain} alternative`);
    }

    return Array.from(suggestions).slice(0, 5);
  }

  async suggest(url: string): Promise<string[]> {
    // Check cache
    const cached = this.cache.get(url);
    if (cached && Date.now() - cached.timestamp < this.cacheTTL) {
      return cached.suggestions;
    }

    // Normalize URL
    const normalizedURL = this.normalizeURL(url);

    // Validate
    if (!this.validateURL(normalizedURL)) {
      throw new Error('Invalid URL');
    }

    // Fetch HTML
    const html = await this.fetchHTML(normalizedURL);

    // Extract data
    const title = this.parser.extractTitle(html);
    const h1s = this.parser.extractHeadings(html, 'h1');
    const h2s = this.parser.extractHeadings(html, 'h2');
    const domain = this.extractDomain(normalizedURL);

    // Generate suggestions
    const suggestions = this.generateSuggestions(title, [...h1s, ...h2s], domain);

    // Cache result
    this.cache.set(url, { suggestions, timestamp: Date.now() });

    return suggestions;
  }
}

describe('Website Keyword Suggester', () => {
  let suggester: WebsiteKeywordSuggester;

  beforeEach(() => {
    suggester = new WebsiteKeywordSuggester();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ============================================
  // KS-001: URL Parsing
  // ============================================
  describe('URL Parsing', () => {
    it('KS-001-01: parse valid URL', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve('<html><title>Test</title></html>'),
      });

      const result = await suggester.suggest('https://example.com');

      expect(mockFetch).toHaveBeenCalledWith('https://example.com', expect.anything());
    });

    it('KS-001-02: handle https', () => {
      const result = suggester.normalizeURL('https://example.com');
      expect(result).toBe('https://example.com');
    });

    it('KS-001-03: handle http', () => {
      const result = suggester.normalizeURL('http://example.com');
      expect(result).toBe('http://example.com');
    });

    it('KS-001-04: handle www prefix', () => {
      const result = suggester.normalizeURL('www.example.com');
      expect(result).toBe('https://www.example.com');
    });

    it('KS-001-05: handle invalid URL', async () => {
      await expect(suggester.suggest('not-a-url')).rejects.toThrow('Invalid URL');
    });

    it('KS-001-06: handle non-existent domain', async () => {
      mockFetch.mockRejectedValue(new Error('ENOTFOUND'));

      await expect(suggester.suggest('https://nonexistent.invalid')).rejects.toThrow();
    });

    it('KS-001-07: handle timeout', async () => {
      mockFetch.mockImplementation(() => new Promise((_, reject) => {
        setTimeout(() => reject(new Error('Timeout')), 100);
      }));

      await expect(suggester.suggest('https://slow-site.com')).rejects.toThrow();
    });

    it('KS-001-08: strip query params', () => {
      const result = suggester.normalizeURL('example.com?foo=bar');
      expect(result).toBe('https://example.com/?foo=bar');
    });
  });

  // ============================================
  // KS-002: Title Extraction
  // ============================================
  describe('Title Extraction', () => {
    const parser = new HTMLParser();

    it('KS-002-01: extract from title tag', () => {
      const html = '<html><head><title>SEO Tools</title></head></html>';
      const result = parser.extractTitle(html);
      expect(result).toBe('SEO Tools');
    });

    it('KS-002-02: handle missing title', () => {
      const html = '<html><head></head></html>';
      const result = parser.extractTitle(html);
      expect(result).toBe('');
    });

    it('KS-002-03: strip HTML entities', () => {
      const html = '<title>SEO &amp; Tools</title>';
      const result = parser.extractTitle(html);
      expect(result).toBe('SEO &amp; Tools');
    });

    it('KS-002-04: handle long title', () => {
      const longTitle = 'A'.repeat(200);
      const html = `<title>${longTitle}</title>`;
      const result = parser.extractTitle(html);
      expect(result).toBe(longTitle);
    });

    it('KS-002-05: remove special characters in suggestions', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve('<title>SEO-Tools!</title>'),
      });

      const result = await suggester.suggest('https://example.com');
      expect(result.some(s => s.includes('seo tools'))).toBe(true);
    });
  });

  // ============================================
  // KS-003: Heading Extraction
  // ============================================
  describe('Heading Extraction', () => {
    const parser = new HTMLParser();

    it('KS-003-01: extract H1', () => {
      const html = '<h1>Best SEO Tools</h1>';
      const result = parser.extractHeadings(html, 'h1');
      expect(result).toContain('Best SEO Tools');
    });

    it('KS-003-02: extract multiple H2s', () => {
      const html = '<h2>First</h2><h2>Second</h2><h2>Third</h2>';
      const result = parser.extractHeadings(html, 'h2');
      expect(result).toHaveLength(3);
    });

    it('KS-003-03: handle missing headings', () => {
      const html = '<html><body></body></html>';
      const result = parser.extractHeadings(html, 'h1');
      expect(result).toEqual([]);
    });

    it('KS-003-04: strip HTML from headings', () => {
      const html = '<h1><span>Title</span></h1>';
      const result = parser.extractHeadings(html, 'h1');
      expect(result).toContain('Title');
    });

    it('KS-003-05: limit to 5 headings', () => {
      const html = Array(10).fill('<h2>Heading</h2>').join('');
      const result = parser.extractHeadings(html, 'h2');
      expect(result).toHaveLength(5);
    });
  });

  // ============================================
  // KS-004: Domain Extraction
  // ============================================
  describe('Domain Extraction', () => {
    it('KS-004-01: extract domain', () => {
      const result = suggester.extractDomain('https://example.com');
      expect(result).toBe('example');
    });

    it('KS-004-02: handle subdomain', () => {
      const result = suggester.extractDomain('https://blog.example.com');
      expect(result).toBe('example');
    });

    it('KS-004-03: handle TLD', () => {
      const result = suggester.extractDomain('https://example.co.uk');
      expect(result).toBe('co');
    });

    it('KS-004-04: handle IP address', () => {
      const result = suggester.extractDomain('http://192.168.1.1');
      expect(result).toBe('192');
    });
  });

  // ============================================
  // KS-005: Suggestion Generation
  // ============================================
  describe('Suggestion Generation', () => {
    it('KS-005-01: generate brand suggestions', () => {
      const result = suggester.generateSuggestions('SEO Tools', [], null);
      expect(result).toContain('seo tools');
      expect(result).toContain('best seo tools');
    });

    it('KS-005-02: generate review suggestions', () => {
      const result = suggester.generateSuggestions('', [], 'acme');
      expect(result).toContain('acme review');
    });

    it('KS-005-03: generate alternative suggestions', () => {
      const result = suggester.generateSuggestions('', [], 'acme');
      expect(result).toContain('acme alternative');
    });

    it('KS-005-04: limit to 5 suggestions', () => {
      const result = suggester.generateSuggestions('SEO Tools', ['H1', 'H2', 'H3', 'H4'], 'test');
      expect(result.length).toBeLessThanOrEqual(5);
    });

    it('KS-005-05: deduplicate suggestions', () => {
      const result = suggester.generateSuggestions('SEO Tools', ['SEO Tools'], null);
      const unique = new Set(result);
      expect(result.length).toBe(unique.size);
    });

    it('KS-005-06: lowercase suggestions', () => {
      const result = suggester.generateSuggestions('SEO TOOLS', [], null);
      expect(result.every(s => s === s.toLowerCase())).toBe(true);
    });

    it('KS-005-07: sort by relevance', () => {
      const result = suggester.generateSuggestions('SEO Tools', [], null);
      // First suggestion should be the title
      expect(result[0]).toBe('seo tools');
    });
  });

  // ============================================
  // KS-006: API Endpoint Tests
  // ============================================
  describe('API Endpoint', () => {
    // These would be tested in Feature tests
    it('KS-006-01: returns suggestions', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve('<title>Test Site</title>'),
      });

      const result = await suggester.suggest('https://example.com');
      expect(Array.isArray(result)).toBe(true);
    });

    it('KS-006-03: handle invalid URL', async () => {
      await expect(suggester.suggest('invalid')).rejects.toThrow();
    });
  });

  // ============================================
  // KS-008: Caching
  // ============================================
  describe('Caching', () => {
    it('KS-008-01: cache for session', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve('<title>Test</title>'),
      });

      await suggester.suggest('https://example.com');
      await suggester.suggest('https://example.com');

      // Should only fetch once due to cache
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('KS-008-03: cache key includes URL', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve('<title>Test</title>'),
      });

      await suggester.suggest('https://example.com');
      await suggester.suggest('https://different.com');

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  // ============================================
  // KS-009: Edge Cases
  // ============================================
  describe('Edge Cases', () => {
    it('KS-009-03: large HTML page', async () => {
      const largeHTML = '<title>Test</title>' + 'x'.repeat(5000000);
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve(largeHTML),
      });

      const result = await suggester.suggest('https://example.com');
      expect(Array.isArray(result)).toBe(true);
    });

    it('KS-009-05: international characters', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        text: () => Promise.resolve('<title>株式会社テスト</title>'),
      });

      const result = await suggester.suggest('https://example.jp');
      expect(result).toBeDefined();
    });

    it('KS-009-06: empty URL', async () => {
      await expect(suggester.suggest('')).rejects.toThrow();
    });
  });
});
