/**
 * Jest Tests for TASK-20-26: Apache .htaccess Clean URLs for SEO Guides
 * 
 * Production route verification tests for SEO guide clean URL routing.
 * Tests 301 redirects, trailing slash handling, and correct page serving.
 * 
 * Usage:
 *   PROD_BASE_URL=https://seoindicator.com npx jest tests/api/TASK-20-26-htaccess-routes.test.js
 *   LOCAL_BASE_URL=http://localhost:3000 npx jest tests/api/TASK-20-26-htaccess-routes.test.js
 */

const https = require('https');
const http = require('http');
const { URL } = require('url');

// Configuration
const BASE_URL = process.env.PROD_BASE_URL || process.env.LOCAL_BASE_URL || 'http://localhost:3000';
const TIMEOUT = 10000; // 10 seconds

// All 17 guide slugs
const GUIDE_SLUGS = [
  'meta-title',
  'meta-description',
  'h1-headings',
  'heading-hierarchy',
  'image-alt-text',
  'image-optimization',
  'internal-links',
  'external-links',
  'page-speed',
  'mobile-friendly',
  'https-security',
  'structured-data',
  'canonical-urls',
  'open-graph',
  'twitter-cards',
  'robots-txt',
  'xml-sitemap',
];

/**
 * Helper function to make HTTP requests
 */
function makeRequest(urlString, options = {}) {
  return new Promise((resolve, reject) => {
    const url = new URL(urlString, BASE_URL);
    const isHttps = url.protocol === 'https:';
    const lib = isHttps ? https : http;
    
    const requestOptions = {
      hostname: url.hostname,
      port: url.port || (isHttps ? 443 : 80),
      path: url.pathname + url.search,
      method: options.method || 'GET',
      headers: {
        'User-Agent': 'SEOIndicator-Test/1.0',
        ...options.headers,
      },
      timeout: TIMEOUT,
    };

    const req = lib.request(requestOptions, (res) => {
      let data = '';
      res.on('data', (chunk) => (data += chunk));
      res.on('end', () => {
        resolve({
          status: res.statusCode,
          headers: res.headers,
          body: data,
          location: res.headers.location,
        });
      });
    });

    req.on('error', reject);
    req.on('timeout', () => {
      req.destroy();
      reject(new Error('Request timeout'));
    });
    req.end();
  });
}

/**
 * Test suite for SEO Guides Clean URL Routing
 */
describe('TASK-20-26: SEO Guides Clean URL Routing', () => {
  
  describe('TC-1: Index Page Redirect Tests', () => {
    
    test('TC-1.1: /seo-guides redirects to /seo-guides/ (301)', async () => {
      const response = await makeRequest('/seo-guides');
      
      expect(response.status).toBe(301);
      expect(response.location).toContain('/seo-guides/');
    });

    test('TC-1.2: Redirect preserves query params', async () => {
      const response = await makeRequest('/seo-guides?ref=nav');
      
      expect(response.status).toBe(301);
      expect(response.location).toContain('/seo-guides/');
      expect(response.location).toContain('ref=nav');
    });

    test('TC-1.3: Redirect is permanent (301, not 302)', async () => {
      const response = await makeRequest('/seo-guides');
      
      expect(response.status).toBe(301);
    });
  });

  describe('TC-2: Index Page Serving Tests', () => {
    
    test('TC-2.1: /seo-guides/ serves HTML content', async () => {
      const response = await makeRequest('/seo-guides/');
      
      expect(response.status).toBe(200);
      expect(response.headers['content-type']).toContain('text/html');
    });

    test('TC-2.2: Page contains guide references', async () => {
      const response = await makeRequest('/seo-guides/');
      
      expect(response.status).toBe(200);
      expect(response.body.toLowerCase()).toContain('guide');
    });

    test('TC-2.3: Page has correct title', async () => {
      const response = await makeRequest('/seo-guides/');
      
      expect(response.status).toBe(200);
      expect(response.body.toLowerCase()).toContain('<title>');
      expect(response.body.toLowerCase()).toContain('seo guide');
    });
  });

  describe('TC-3: Guide Page Redirect Tests', () => {
    
    test('TC-3.1: /seo-guides/meta-title redirects to /seo-guides/meta-title/', async () => {
      const response = await makeRequest('/seo-guides/meta-title');
      
      expect(response.status).toBe(301);
      expect(response.location).toContain('/seo-guides/meta-title/');
    });

    test('TC-3.2: Redirect preserves query params for guide pages', async () => {
      const response = await makeRequest('/seo-guides/meta-title?ref=card');
      
      expect(response.status).toBe(301);
      expect(response.location).toContain('/seo-guides/meta-title/');
      expect(response.location).toContain('ref=card');
    });

    test('TC-3.3: Redirect is permanent for guide pages', async () => {
      const response = await makeRequest('/seo-guides/meta-title');
      
      expect(response.status).toBe(301);
    });
  });

  describe('TC-4: Guide Page Serving Tests', () => {
    
    test('TC-4.1: /seo-guides/meta-title/ serves HTML content', async () => {
      const response = await makeRequest('/seo-guides/meta-title/');
      
      expect(response.status).toBe(200);
      expect(response.headers['content-type']).toContain('text/html');
    });

    test('TC-4.2: Page contains guide content', async () => {
      const response = await makeRequest('/seo-guides/meta-title/');
      
      expect(response.status).toBe(200);
      const body = response.body.toLowerCase();
      expect(
        body.includes('meta title') || 
        body.includes('title tag')
      ).toBe(true);
    });

    test('TC-4.3: Page has canonical URL', async () => {
      const response = await makeRequest('/seo-guides/meta-title/');
      
      expect(response.status).toBe(200);
      expect(response.body).toContain('rel="canonical"');
      expect(response.body).toContain('/seo-guides/meta-title/');
    });

    test('TC-4.4: Page has OG URL', async () => {
      const response = await makeRequest('/seo-guides/meta-title/');
      
      expect(response.status).toBe(200);
      expect(response.body).toContain('property="og:url"');
      expect(response.body).toContain('/seo-guides/meta-title/');
    });
  });

  describe('TC-5: All 17 Guide Routes Tests', () => {
    
    // Generate a test for each guide slug
    test.each(GUIDE_SLUGS.map((slug, index) => [`TC-5.${index + 1}`, slug]))(
      '%s: /seo-guides/%s/ returns 200',
      async (_, slug) => {
        const response = await makeRequest(`/seo-guides/${slug}/`);
        expect(response.status).toBe(200);
      }
    );
  });

  describe('TC-6: 301 Redirect Verification Tests', () => {
    
    test('TC-6.1: Index without trailing slash gets 301 redirect', async () => {
      const response = await makeRequest('/seo-guides');
      
      expect(response.status).toBe(301);
      expect(response.location).toBe('/seo-guides/');
    });

    test('TC-6.2: Guide without trailing slash gets 301 redirect', async () => {
      const response = await makeRequest('/seo-guides/meta-title');
      
      expect(response.status).toBe(301);
      expect(response.location).toBe('/seo-guides/meta-title/');
    });

    test('TC-6.3: Nested path returns 404', async () => {
      const response = await makeRequest('/seo-guides/meta-title/section');
      
      expect([301, 404]).toContain(response.status);
    });

    test('TC-6.4: No redirect chain for index page', async () => {
      const response = await makeRequest('/seo-guides');
      
      expect(response.status).toBe(301);
      
      // Follow the redirect
      const finalResponse = await makeRequest(response.location);
      expect(finalResponse.status).toBe(200);
    });
  });

  describe('TC-7: Existing .htaccess Compatibility Tests', () => {
    
    test('TC-7.1: API routes still work', async () => {
      const response = await makeRequest('/api/health');
      
      expect(response.status).toBe(200);
      expect(response.headers['content-type']).toContain('application/json');
    });

    test('TC-7.2: API auth still works', async () => {
      const response = await makeRequest('/api/v1/projects');
      
      // Should return 401 (unauthorized), not 404
      expect([401, 403]).toContain(response.status);
    });

    test('TC-7.4: Root redirect works', async () => {
      const response = await makeRequest('/');
      
      expect(response.status).toBe(200);
    });
  });

  describe('TC-8: Edge Cases Tests', () => {
    
    test('TC-8.1: Non-existent guide slug returns 404', async () => {
      const response = await makeRequest('/seo-guides/non-existent-guide/');
      
      expect(response.status).toBe(404);
    });

    test('TC-8.2: Path traversal attempt is blocked', async () => {
      const response = await makeRequest('/seo-guides/../../etc/passwd');
      
      expect([400, 403, 404]).toContain(response.status);
    });

    test('TC-8.3: SQL injection in slug returns 404', async () => {
      const response = await makeRequest("/seo-guides/1' OR '1'='1/");
      
      expect(response.status).toBe(404);
    });

    test('TC-8.4: XSS attempt in slug returns 404', async () => {
      const response = await makeRequest('/seo-guides/<script>alert(1)</script>/');
      
      expect(response.status).toBe(404);
    });

    test('TC-8.5: Double trailing slash returns 404 or redirect', async () => {
      const response = await makeRequest('/seo-guides/meta-title//');
      
      expect([301, 404]).toContain(response.status);
    });

    test('TC-8.8: Empty slug returns 404 or redirect', async () => {
      const response = await makeRequest('/seo-guides//');
      
      expect([301, 404]).toContain(response.status);
    });

    test('TC-8.9: Very long slug returns 404', async () => {
      const longSlug = 'a'.repeat(300);
      const response = await makeRequest(`/seo-guides/${longSlug}/`);
      
      expect(response.status).toBe(404);
    });
  });

  describe('TC-9: Security Tests', () => {
    
    test('TC-9.1: Path traversal blocked', async () => {
      const response = await makeRequest('/seo-guides/../../../etc/passwd');
      
      expect([400, 403, 404]).toContain(response.status);
    });

    test('TC-9.2: Directory listing disabled', async () => {
      const response = await makeRequest('/seo-guides/');
      
      expect(response.status).toBe(200);
      expect(response.body).not.toContain('Index of');
      expect(response.body).not.toContain('Parent Directory');
    });

    test('TC-9.3: Hidden files blocked', async () => {
      const response = await makeRequest('/seo-guides/.htaccess');
      
      expect([403, 404]).toContain(response.status);
    });

    test('TC-9.4: Backup files blocked', async () => {
      const response = await makeRequest('/seo-guides/index.html.bak');
      
      expect([403, 404]).toContain(response.status);
    });
  });

  describe('TC-10: SEO Tests', () => {
    
    test('TC-10.1: Canonical URL has trailing slash', async () => {
      const response = await makeRequest('/seo-guides/meta-title/');
      
      expect(response.status).toBe(200);
      // Check for canonical with trailing slash
      expect(response.body).toMatch(/rel="canonical"[^>]*\/seo-guides\/meta-title\/"/);
    });

    test('TC-10.2: OG URL has trailing slash', async () => {
      const response = await makeRequest('/seo-guides/meta-title/');
      
      expect(response.status).toBe(200);
      expect(response.body).toMatch(/property="og:url"[^>]*\/seo-guides\/meta-title\/"/);
    });

    test('TC-10.3: No duplicate content (non-trailing redirects)', async () => {
      const response = await makeRequest('/seo-guides/meta-title');
      
      // Should redirect, not serve content directly
      expect(response.status).toBe(301);
    });

    test('TC-10.4: Robots.txt accessible', async () => {
      const response = await makeRequest('/robots.txt');
      
      expect(response.status).toBe(200);
    });
  });
});

// Export for module usage
module.exports = {
  GUIDE_SLUGS,
  BASE_URL,
  makeRequest,
};
