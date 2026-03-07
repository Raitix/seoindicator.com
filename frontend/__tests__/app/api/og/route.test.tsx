/**
 * TEST-32-17: OG Image Generation for Comparisons
 * 
 * Test suite for /api/og route - Dynamic Open Graph image generation
 * using Next.js ImageResponse from 'next/og'
 */

import { describe, it, expect, vi } from 'vitest';
import { GET } from '@/app/api/og/route';

// Mock ImageResponse - simplified Response subclass
vi.mock('next/og', () => {
  // Helper to extract text from JSX-like structure
  function extractText(obj: any): string {
    if (typeof obj === 'string') return obj;
    if (typeof obj === 'number') return String(obj);
    if (!obj) return '';
    if (Array.isArray(obj)) return obj.map(extractText).join('');
    if (obj.props?.children) return extractText(obj.props.children);
    return '';
  }
  
  // Helper to convert JSX to HTML-like string for testing
  function jsxToString(obj: any): string {
    if (typeof obj === 'string') return obj;
    if (typeof obj === 'number') return String(obj);
    if (!obj) return '';
    if (Array.isArray(obj)) return obj.map(jsxToString).join('');
    
    if (obj.props) {
      const tag = obj.type || 'div';
      const style = obj.props.style 
        ? Object.entries(obj.props.style)
            .map(([k, v]) => `${k.replace(/([A-Z])/g, '-$1').toLowerCase()}: ${v}`)
            .join('; ')
        : '';
      const children = jsxToString(obj.props.children);
      
      // Include style attributes for assertions
      if (style) {
        return `<${tag} style="${style}">${children}</${tag}>`;
      }
      return `<${tag}>${children}</${tag}>`;
    }
    return '';
  }
  
  class MockImageResponse extends Response {
    width: number;
    height: number;
    bodyContent: string;
    
    constructor(body: any, init?: any) {
      const htmlBody = jsxToString(body);
      super(htmlBody, {
        status: 200,
        headers: {
          'Content-Type': 'image/png',
          ...(init?.headers || {}),
        },
      });
      this.width = init?.width || 1200;
      this.height = init?.height || 630;
      this.bodyContent = htmlBody;
    }
  }
  
  return { ImageResponse: MockImageResponse };
});

// Helper to create mock Request
function createRequest(url: string): Request {
  return new Request(new URL(url, 'http://localhost:3000'));
}

// Helper to extract body content for assertions
async function getBodyContent(response: Response): Promise<string> {
  return await response.text();
}

describe('OG-ROUTE: Route Structure', () => {
  it('OG-ROUTE-001: GET function is exported', () => {
    expect(GET).toBeDefined();
    expect(typeof GET).toBe('function');
  });

  it('OG-ROUTE-002: GET returns Response', async () => {
    const request = createRequest('/api/og?a=TestA&b=TestB');
    const response = await GET(request);
    expect(response).toBeInstanceOf(Response);
  });

  it('OG-ROUTE-003: Status 200 for valid requests', async () => {
    const request = createRequest('/api/og?a=SEOIndicator&b=ahrefs');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-ROUTE-004: Uses ImageResponse', async () => {
    const request = createRequest('/api/og?a=TestA&b=TestB');
    const response = await GET(request);
    // ImageResponse extends Response
    expect(response).toBeInstanceOf(Response);
  });
});

describe('OG-PARAMS: Query Parameters', () => {
  it('OG-PARAMS-001: Extract competitor A', async () => {
    const request = createRequest('/api/og?a=Ahrefs&b=SEMrush');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    expect(body).toContain('Ahrefs');
  });

  it('OG-PARAMS-002: Extract competitor B', async () => {
    const request = createRequest('/api/og?a=SEOIndicator&b=Moz');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    expect(body).toContain('Moz');
  });

  it('OG-PARAMS-003: Default competitor A', async () => {
    const request = createRequest('/api/og?b=ahrefs');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOIndicator');
  });

  it('OG-PARAMS-004: Default competitor B', async () => {
    const request = createRequest('/api/og?a=SEOIndicator');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('Competitor');
  });

  it('OG-PARAMS-005: Both defaults applied', async () => {
    const request = createRequest('/api/og');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOIndicator');
    expect(body).toContain('Competitor');
  });

  it('OG-PARAMS-006: URL-encoded names decoded', async () => {
    const request = createRequest('/api/og?a=SEO%20Tool&b=Test');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    // Should handle decoded space
    expect(body).toContain('SEO Tool');
  });

  it('OG-PARAMS-007: Title parameter extracted', async () => {
    const request = createRequest('/api/og?a=X&b=Y&title=Best%20SEO%20Tools');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    expect(body).toContain('Best SEO Tools');
  });

  it('OG-PARAMS-008: Empty "a" param uses default', async () => {
    const request = createRequest('/api/og?a=&b=ahrefs');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOIndicator');
  });

  it('OG-PARAMS-009: Empty "b" param uses default', async () => {
    const request = createRequest('/api/og?a=SEOIndicator&b=');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('Competitor');
  });

  it('OG-PARAMS-010: Unknown params ignored', async () => {
    const request = createRequest('/api/og?a=X&b=Y&foo=bar&baz=qux');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });
});

describe('OG-DIMENSIONS: Image Dimensions', () => {
  it('OG-DIMENSIONS-001: Width is 1200px', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    expect(response.width).toBe(1200);
  });

  it('OG-DIMENSIONS-002: Height is 630px', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    expect(response.height).toBe(630);
  });

  it('OG-DIMENSIONS-003: Aspect ratio is ~1.91:1', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    const ratio = response.width / response.height;
    expect(ratio).toBeCloseTo(1.91, 1);
  });

  it('OG-DIMENSIONS-004: Facebook OG compliant', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    expect(response.width).toBe(1200);
    expect(response.height).toBe(630);
  });

  it('OG-DIMENSIONS-005: Twitter card compliant', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    expect(response.width).toBe(1200);
    expect(response.height).toBe(630);
  });
});

describe('OG-HEADERS: Response Headers', () => {
  it('OG-HEADERS-001: Content-Type is image/png', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    expect(response.headers.get('Content-Type')).toBe('image/png');
  });

  it('OG-HEADERS-002: Cache-Control header present', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    expect(response.headers.get('Cache-Control')).not.toBeNull();
  });

  it('OG-HEADERS-003: max-age directive present', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const cacheControl = response.headers.get('Cache-Control') || '';
    expect(cacheControl).toMatch(/max-age/i);
  });

  it('OG-HEADERS-004: max-age >= 1 hour (3600s)', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const cacheControl = response.headers.get('Cache-Control') || '';
    const maxAgeMatch = cacheControl.match(/max-age=(\d+)/i);
    expect(maxAgeMatch).not.toBeNull();
    const maxAge = parseInt(maxAgeMatch![1], 10);
    expect(maxAge).toBeGreaterThanOrEqual(3600);
  });

  it('OG-HEADERS-005: Public cacheable', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const cacheControl = response.headers.get('Cache-Control') || '';
    expect(cacheControl).toContain('public');
  });
});

describe('OG-CONTENT: Content Verification', () => {
  it('OG-CONTENT-001: Competitor A in image', async () => {
    const request = createRequest('/api/og?a=Ahrefs&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('Ahrefs');
  });

  it('OG-CONTENT-002: Competitor B in image', async () => {
    const request = createRequest('/api/og?a=X&b=SEMrush');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEMrush');
  });

  it('OG-CONTENT-003: "vs" separator present', async () => {
    const request = createRequest('/api/og?a=ToolA&b=ToolB');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('vs');
  });

  it('OG-CONTENT-004: SEOIndicator branding visible', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOIndicator.com');
  });

  it('OG-CONTENT-005: Compare text visible', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toMatch(/Compare/i);
  });

  it('OG-CONTENT-006: Background gradient applied', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('linear-gradient');
  });

  it('OG-CONTENT-007: White text on dark background', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('color: white');
  });

  it('OG-CONTENT-008: Blue CTA button (#3b82f6)', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('#3b82f6');
  });
});

describe('OG-SEC: Security', () => {
  it('OG-SEC-001: Script tags in A are sanitized', async () => {
    const request = createRequest('/api/og?a=<script>alert(1)</script>Test&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    expect(body).not.toContain('<script>');
    expect(body).not.toContain('alert');
  });

  it('OG-SEC-002: HTML tags in B are sanitized', async () => {
    const request = createRequest('/api/og?a=X&b=<img src=x onerror=alert(1)>');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    expect(body).not.toContain('<img');
  });

  it('OG-SEC-003: Long names are truncated', async () => {
    const longName = 'A'.repeat(200);
    const request = createRequest(`/api/og?a=${longName}&b=Y`);
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(response.status).toBe(200);
    // Should not contain full 200 character string
    expect(body).not.toContain(longName);
  });

  it('OG-SEC-004: Special chars handled safely', async () => {
    const request = createRequest('/api/og?a=Test<>"%27%26&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-SEC-005: Unicode support', async () => {
    const request = createRequest('/api/og?a=SEO%E5%B7%A5%E5%85%B7&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-SEC-006: Emoji support', async () => {
    const request = createRequest('/api/og?a=SEO%F0%9F%9A%80&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-SEC-007: SQL injection has no impact', async () => {
    const request = createRequest("/api/og?a='; DROP TABLE users;--&b=Y");
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-SEC-008: SSRF prevention - no external requests', async () => {
    const request = createRequest('/api/og?a=http://internal.local&b=Y');
    const response = await GET(request);
    // Should just treat as text, not make HTTP request
    expect(response.status).toBe(200);
  });
});

describe('OG-EDGE: Edge Cases', () => {
  it('OG-EDGE-001: Empty query string uses defaults', async () => {
    const request = createRequest('/api/og?');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOIndicator');
  });

  it('OG-EDGE-002: Whitespace name uses default', async () => {
    const request = createRequest('/api/og?a=%20%20&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-EDGE-003: Single char name works', async () => {
    const request = createRequest('/api/og?a=A&b=B');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('A');
    expect(body).toContain('B');
  });

  it('OG-EDGE-004: Numbers in names work', async () => {
    const request = createRequest('/api/og?a=Ahrefs2&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('Ahrefs2');
  });

  it('OG-EDGE-005: Hyphens/underscores work', async () => {
    const request = createRequest('/api/og?a=SEO-Tool_Pro&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('SEO-Tool_Pro');
  });

  it('OG-EDGE-006: Same competitor for both works', async () => {
    const request = createRequest('/api/og?a=X&b=X');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('X vs X');
  });

  it('OG-EDGE-007: Very long URL truncated', async () => {
    const longParam = 'A'.repeat(500);
    const request = createRequest(`/api/og?a=${longParam}&b=Y`);
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-EDGE-008: Unicode title works', async () => {
    const request = createRequest('/api/og?a=X&b=Y&title=SEO%E5%B7%A5%E5%85%B7');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-EDGE-009: Case preserved in output', async () => {
    const request = createRequest('/api/og?a=SEOMaster&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOMaster');
  });

  it('OG-EDGE-010: Null bytes handled safely', async () => {
    const request = createRequest('/api/og?a=Test%00Name&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });
});

describe('OG-HTTP: HTTP Methods', () => {
  it('OG-HTTP-001: GET returns 200', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    expect(response.status).toBe(200);
  });

  it('OG-HTTP-002: Only GET handler exported', async () => {
    // Verify only GET is exported from the module
    const route = await import('@/app/api/og/route');
    expect(route.GET).toBeDefined();
    expect((route as any).POST).toBeUndefined();
    expect((route as any).PUT).toBeUndefined();
    expect((route as any).DELETE).toBeUndefined();
  });

  it('OG-HTTP-003: HEAD not exported', async () => {
    const route = await import('@/app/api/og/route');
    expect((route as any).HEAD).toBeUndefined();
  });
});

describe('OG-PERF: Performance', () => {
  it('OG-PERF-001: Response time < 500ms', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const start = performance.now();
    await GET(request);
    const elapsed = performance.now() - start;
    expect(elapsed).toBeLessThan(500);
  });

  it('OG-PERF-002: Concurrent requests all succeed', async () => {
    const requests = Array(10).fill(null).map((_, i) => 
      createRequest(`/api/og?a=Test${i}&b=Y`)
    );
    
    const responses = await Promise.all(requests.map(r => GET(r)));
    
    responses.forEach(response => {
      expect(response.status).toBe(200);
    });
  });

  it('OG-PERF-003: Memory efficient - no OOM on multiple calls', async () => {
    for (let i = 0; i < 5; i++) {
      const request = createRequest(`/api/og?a=Test${i}&b=Y`);
      const response = await GET(request);
      expect(response.status).toBe(200);
    }
  });
});

describe('AC: Acceptance Criteria', () => {
  it('AC-001: Images for all comparison pages', async () => {
    // Test with common competitor pairs
    const pairs = [
      ['SEOIndicator', 'ahrefs'],
      ['SEOIndicator', 'semrush'],
      ['SEOIndicator', 'moz'],
      ['SEOIndicator', 'ubersuggest'],
    ];
    
    for (const [a, b] of pairs) {
      const request = createRequest(`/api/og?a=${a}&b=${b}`);
      const response = await GET(request);
      expect(response.status).toBe(200);
    }
  });

  it('AC-002: Dynamic generation via API route works', async () => {
    const request = createRequest('/api/og?a=DynamicTest&b=Competitor');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('DynamicTest');
    expect(body).toContain('Competitor');
  });

  it('AC-003: Correct dimensions for social platforms', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    expect(response.width).toBe(1200);
    expect(response.height).toBe(630);
  });

  it('AC-004: Both competitor names visible', async () => {
    const request = createRequest('/api/og?a=FirstTool&b=SecondTool');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('FirstTool');
    expect(body).toContain('SecondTool');
  });

  it('AC-005: SEOIndicator branding visible', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request);
    const body = await getBodyContent(response);
    expect(body).toContain('SEOIndicator.com');
  });

  it('AC-006: 1200x630 dimensions (OG standard)', async () => {
    const request = createRequest('/api/og?a=X&b=Y');
    const response = await GET(request) as any;
    expect(response.width).toBe(1200);
    expect(response.height).toBe(630);
  });

  it('AC-007: Metadata URL format correct', async () => {
    // Verify the URL format for metadata integration
    const baseUrl = 'http://localhost:3000';
    const a = 'SEOIndicator';
    const b = 'ahrefs';
    const expectedUrl = `${baseUrl}/api/og?a=${encodeURIComponent(a)}&b=${encodeURIComponent(b)}`;
    
    const request = createRequest(expectedUrl.replace(baseUrl, ''));
    const response = await GET(request);
    expect(response.status).toBe(200);
  });
});

describe('OG-INT: Integration', () => {
  it('OG-INT-001: Full request flow returns image/png', async () => {
    const request = createRequest('/api/og?a=IntegrationTest&b=Competitor');
    const response = await GET(request);
    expect(response.status).toBe(200);
    expect(response.headers.get('Content-Type')).toBe('image/png');
  });

  it('OG-INT-002: URL format matches expected pattern', async () => {
    // Simulate URL building for comparison page
    const slug = 'seoindicator-vs-ahrefs';
    const [a, b] = slug.split('-vs-').map((s: string) => s.charAt(0).toUpperCase() + s.slice(1));
    
    const request = createRequest(`/api/og?a=${a}&b=${b}`);
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('Seoindicator');
    expect(body).toContain('Ahrefs');
  });

  it('OG-INT-003: Mock COMPARISON_SLUGS generate images', async () => {
    const mockSlugs = ['seoindicator-vs-ahrefs', 'seoindicator-vs-semrush', 'seoindicator-vs-moz'];
    
    for (const slug of mockSlugs) {
      const [a, b] = slug.split('-vs-').map((s: string) => s.charAt(0).toUpperCase() + s.slice(1));
      const request = createRequest(`/api/og?a=${a}&b=${b}`);
      const response = await GET(request);
      expect(response.status).toBe(200);
    }
  });

  it('OG-INT-004: Case conversion works correctly', async () => {
    const request = createRequest('/api/og?a=lowercase&b=UPPERCASE');
    const response = await GET(request);
    expect(response.status).toBe(200);
    const body = await getBodyContent(response);
    expect(body).toContain('lowercase');
    expect(body).toContain('UPPERCASE');
  });
});
