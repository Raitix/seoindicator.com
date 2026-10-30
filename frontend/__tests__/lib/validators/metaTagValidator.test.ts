/**
 * Meta Tag Validator Tests
 * Task: TASK-16-1 - Meta Tag Analyzer Frontend
 *
 * @jest-environment jsdom
 */

// Validator implementation (will be moved to actual file)
interface ValidationResult {
  valid: boolean;
  error?: string;
  isEmpty?: boolean;
  sanitized?: string;
}

const validateUrl = (url: string): ValidationResult => {
  if (!url || url.trim() === '') {
    return { valid: false, error: 'URL is required' };
  }

  if (url.length > 2083) {
    return { valid: false, error: 'URL is too long (max 2083 characters)' };
  }

  try {
    const parsed = new URL(url);
    if (!['http:', 'https:'].includes(parsed.protocol)) {
      return { valid: false, error: 'URL must use http or https protocol' };
    }
    return { valid: true, sanitized: url.trim() };
  } catch {
    return { valid: false, error: 'Please enter a valid URL' };
  }
};

const validateHtml = (html: string): ValidationResult => {
  if (!html || html.trim() === '') {
    return { valid: true, isEmpty: true };
  }

  // Basic HTML structure validation
  const hasHtmlTag = /<html[\s>]/i.test(html) || /<!doctype\s+html/i.test(html);
  const hasHeadTag = /<head[\s>]/i.test(html);

  // Sanitize script tags (basic XSS prevention)
  let sanitized = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');

  // Remove event handlers
  sanitized = sanitized.replace(/\s*on\w+\s*=\s*["'][^"']*["']/gi, '');

  return {
    valid: true,
    isEmpty: false,
    sanitized,
  };
};

const sanitizeHtml = (html: string): string => {
  if (!html) return '';

  // Remove script tags
  let sanitized = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');

  // Remove event handlers
  sanitized = sanitized.replace(/\s*on\w+\s*=\s*["'][^"']*["']/gi, '');

  // Remove javascript: URLs
  sanitized = sanitized.replace(/javascript:/gi, '');

  // Remove data: URLs (potential XSS vector)
  sanitized = sanitized.replace(/data:\s*text\/html/gi, '');

  return sanitized;
};

describe('Meta Tag Validator', () => {
  describe('URL Validation', () => {
    // UT-031: Valid URL with https
    it('should validate URL with https protocol', () => {
      const result = validateUrl('https://example.com');
      expect(result.valid).toBe(true);
      expect(result.error).toBeUndefined();
    });

    // UT-032: Valid URL with http
    it('should validate URL with http protocol', () => {
      const result = validateUrl('http://example.com');
      expect(result.valid).toBe(true);
      expect(result.error).toBeUndefined();
    });

    // UT-033: Invalid URL - no protocol
    it('should reject URL without protocol', () => {
      const result = validateUrl('example.com');
      expect(result.valid).toBe(false);
      expect(result.error).toContain('valid URL');
    });

    // UT-034: Invalid URL - special chars
    it('should reject URL with special characters', () => {
      const result = validateUrl('https://exa<mple.com');
      expect(result.valid).toBe(false);
      expect(result.error).toContain('valid URL');
    });

    // UT-035: URL with path and query
    it('should validate URL with path and query string', () => {
      const result = validateUrl('https://example.com/page?query=1&sort=asc');
      expect(result.valid).toBe(true);
    });

    // UT-036: URL max length validation
    it('should reject URLs longer than 2083 characters', () => {
      const longUrl = 'https://example.com/' + 'a'.repeat(2100);
      const result = validateUrl(longUrl);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('too long');
    });

    // Additional: Empty URL
    it('should reject empty URL', () => {
      const result = validateUrl('');
      expect(result.valid).toBe(false);
      expect(result.error).toContain('required');
    });

    // Additional: Whitespace-only URL
    it('should reject whitespace-only URL', () => {
      const result = validateUrl('   ');
      expect(result.valid).toBe(false);
    });

    // Additional: URL with fragment
    it('should validate URL with fragment', () => {
      const result = validateUrl('https://example.com/page#section');
      expect(result.valid).toBe(true);
    });

    // Additional: URL with port
    it('should validate URL with port', () => {
      const result = validateUrl('https://example.com:8080/page');
      expect(result.valid).toBe(true);
    });

    // Additional: URL with subdomain
    it('should validate URL with subdomain', () => {
      const result = validateUrl('https://blog.example.com/post');
      expect(result.valid).toBe(true);
    });

    // Additional: Reject non-http(s) protocols
    it('should reject ftp protocol', () => {
      const result = validateUrl('ftp://files.example.com');
      expect(result.valid).toBe(false);
      expect(result.error).toContain('http or https');
    });

    // Additional: Reject javascript: protocol
    it('should reject javascript protocol', () => {
      const result = validateUrl('javascript:alert(1)');
      expect(result.valid).toBe(false);
    });

    // Additional: URL with encoded characters
    it('should validate URL with encoded characters', () => {
      const result = validateUrl('https://example.com/search?q=hello%20world');
      expect(result.valid).toBe(true);
    });

    // Additional: URL with unicode domain
    it('should validate URL with unicode characters in domain', () => {
      const result = validateUrl('https://例え.jp');
      expect(result.valid).toBe(true);
    });
  });

  describe('HTML Validation', () => {
    // UT-037: HTML with valid structure
    it('should validate HTML with valid structure', () => {
      const html = '<html><head><title>Test</title></head><body></body></html>';
      const result = validateHtml(html);
      expect(result.valid).toBe(true);
      expect(result.isEmpty).toBe(false);
    });

    // UT-038: HTML with script tags (sanitization)
    it('should sanitize script tags from HTML', () => {
      const html = '<html><script>alert("xss")</script><body>Content</body></html>';
      const result = validateHtml(html);
      expect(result.valid).toBe(true);
      expect(result.sanitized).not.toContain('<script>');
      expect(result.sanitized).not.toContain('alert');
    });

    // UT-039: HTML with inline styles
    it('should preserve inline styles in HTML', () => {
      const html = '<html><head><style>body { color: red; }</style></head></html>';
      const result = validateHtml(html);
      expect(result.valid).toBe(true);
      expect(result.sanitized).toContain('<style>');
    });

    // UT-040: Empty HTML input
    it('should accept empty HTML input', () => {
      const result = validateHtml('');
      expect(result.valid).toBe(true);
      expect(result.isEmpty).toBe(true);
    });

    // Additional: HTML with event handlers
    it('should remove event handlers from HTML', () => {
      const html = '<div onclick="alert(1)">Click me</div>';
      const result = validateHtml(html);
      expect(result.sanitized).not.toContain('onclick');
    });

    // Additional: HTML fragment (no html/head tags)
    it('should accept HTML fragments', () => {
      const html = '<div>Just a fragment</div>';
      const result = validateHtml(html);
      expect(result.valid).toBe(true);
    });

    // Additional: HTML with meta tags
    it('should validate HTML with meta tags', () => {
      const html = `
        <html>
          <head>
            <meta charset="UTF-8">
            <meta name="description" content="Test description">
          </head>
        </html>
      `;
      const result = validateHtml(html);
      expect(result.valid).toBe(true);
    });

    // Additional: HTML with DOCTYPE
    it('should validate HTML with DOCTYPE', () => {
      const html = '<!DOCTYPE html><html><head></head><body></body></html>';
      const result = validateHtml(html);
      expect(result.valid).toBe(true);
    });
  });

  describe('HTML Sanitization', () => {
    it('should remove script tags', () => {
      const html = '<script>document.cookie</script><p>Safe content</p>';
      const sanitized = sanitizeHtml(html);
      expect(sanitized).not.toContain('<script>');
      expect(sanitized).not.toContain('document.cookie');
      expect(sanitized).toContain('<p>Safe content</p>');
    });

    it('should remove onclick handlers', () => {
      const html = '<button onclick="evil()">Click</button>';
      const sanitized = sanitizeHtml(html);
      expect(sanitized).not.toContain('onclick');
    });

    it('should remove javascript: URLs', () => {
      const html = '<a href="javascript:alert(1)">Link</a>';
      const sanitized = sanitizeHtml(html);
      expect(sanitized).not.toContain('javascript:');
    });

    it('should remove data:text/html URLs', () => {
      const html = '<iframe src="data:text/html,<script>alert(1)</script>"></iframe>';
      const sanitized = sanitizeHtml(html);
      expect(sanitized).not.toContain('data:text/html');
    });

    it('should preserve safe attributes', () => {
      const html = '<a href="https://example.com" class="link" id="main-link">Link</a>';
      const sanitized = sanitizeHtml(html);
      expect(sanitized).toContain('href="https://example.com"');
      expect(sanitized).toContain('class="link"');
      expect(sanitized).toContain('id="main-link"');
    });

    it('should handle empty input', () => {
      const sanitized = sanitizeHtml('');
      expect(sanitized).toBe('');
    });

    it('should handle null/undefined input', () => {
      // @ts-expect-error Testing null handling
      expect(sanitizeHtml(null)).toBe('');
      // @ts-expect-error Testing undefined handling
      expect(sanitizeHtml(undefined)).toBe('');
    });
  });
});
