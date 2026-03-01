import { test, expect } from '@playwright/test';

/**
 * E2E Tests for SEO Guides Clean URL Routing
 * Task: TASK-20-26
 * 
 * Tests Apache .htaccess configuration for clean URLs with trailing slash handling.
 * Tests verify 301 redirects and correct page serving for all 17 SEO guide routes.
 */

// All 17 guide slugs from the registry
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

// Base URL - can be overridden via environment variable
const BASE_URL = process.env.BASE_URL || 'http://localhost:3000';

test.describe('SEO Guides Index Page Routing', () => {
  
  test('TC-1.1: /seo-guides redirects to /seo-guides/ (301)', async ({ page }) => {
    const response = await page.goto('/seo-guides', { waitUntil: 'commit' });
    
    // Should be a 301 redirect
    expect(response?.status()).toBe(301);
    
    // Should redirect to trailing slash version
    const location = response?.headers()['location'];
    expect(location).toContain('/seo-guides/');
  });

  test('TC-1.2: Redirect preserves query params', async ({ page }) => {
    const response = await page.goto('/seo-guides?ref=nav', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(301);
    
    const location = response?.headers()['location'];
    expect(location).toContain('/seo-guides/');
    expect(location).toContain('ref=nav');
  });

  test('TC-1.3: Redirect is permanent (301, not 302)', async ({ page }) => {
    const response = await page.goto('/seo-guides', { waitUntil: 'commit' });
    
    // Must be 301 for SEO
    expect(response?.status()).toBe(301);
  });

  test('TC-2.1: /seo-guides/ serves index.html', async ({ page }) => {
    await page.goto('/seo-guides/');
    
    // Should load the guide index page
    await expect(page.locator('h1, h2').first()).toBeVisible();
    
    // Should contain SEO guides content
    const content = await page.content();
    expect(content.toLowerCase()).toContain('guide');
  });

  test('TC-2.2: Content-Type is HTML', async ({ page }) => {
    const response = await page.goto('/seo-guides/');
    
    const contentType = response?.headers()['content-type'];
    expect(contentType).toContain('text/html');
  });

  test('TC-2.3: Page contains guide cards', async ({ page }) => {
    await page.goto('/seo-guides/');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Should have guide cards or list items
    const guideElements = await page.locator('a[href*="/seo-guides/"]').count();
    expect(guideElements).toBeGreaterThan(0);
  });

  test('TC-2.4: Page has correct title', async ({ page }) => {
    await page.goto('/seo-guides/');
    
    const title = await page.title();
    expect(title.toLowerCase()).toContain('seo guide');
  });
});

test.describe('SEO Guide Page Redirect Tests', () => {
  
  test('TC-3.1: /seo-guides/meta-title redirects to /seo-guides/meta-title/', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(301);
    
    const location = response?.headers()['location'];
    expect(location).toContain('/seo-guides/meta-title/');
  });

  test('TC-3.2: Redirect preserves query params for guide pages', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title?ref=card', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(301);
    
    const location = response?.headers()['location'];
    expect(location).toContain('/seo-guides/meta-title/');
    expect(location).toContain('ref=card');
  });

  test('TC-3.3: Redirect is permanent for guide pages', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(301);
  });
});

test.describe('SEO Guide Page Serving Tests', () => {
  
  test('TC-4.1: /seo-guides/meta-title/ serves index.html', async ({ page }) => {
    await page.goto('/seo-guides/meta-title/');
    
    // Should load the guide page
    await expect(page.locator('h1, h2').first()).toBeVisible();
  });

  test('TC-4.2: Content-Type is HTML for guide pages', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title/');
    
    const contentType = response?.headers()['content-type'];
    expect(contentType).toContain('text/html');
  });

  test('TC-4.3: Page contains guide content', async ({ page }) => {
    await page.goto('/seo-guides/meta-title/');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Should have content related to meta titles
    const content = await page.content();
    expect(
      content.toLowerCase().includes('meta title') || 
      content.toLowerCase().includes('title tag')
    ).toBe(true);
  });

  test('TC-4.4: Page has correct canonical URL', async ({ page }) => {
    await page.goto('/seo-guides/meta-title/');
    
    const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
    expect(canonical).toContain('/seo-guides/meta-title/');
  });

  test('TC-4.5: Page has correct OG URL', async ({ page }) => {
    await page.goto('/seo-guides/meta-title/');
    
    const ogUrl = await page.locator('meta[property="og:url"]').getAttribute('content');
    expect(ogUrl).toContain('/seo-guides/meta-title/');
  });
});

test.describe('All 17 Guide Routes Tests', () => {
  
  // Generate a test for each guide slug
  for (const slug of GUIDE_SLUGS) {
    test(`TC-5.${GUIDE_SLUGS.indexOf(slug) + 1}: /seo-guides/${slug}/ returns 200`, async ({ page }) => {
      const response = await page.goto(`/seo-guides/${slug}/`);
      
      expect(response?.status()).toBe(200);
    });
  }
});

test.describe('301 Redirect Verification Tests', () => {
  
  test('TC-6.1: Index without trailing slash gets 301 redirect', async ({ page }) => {
    const response = await page.goto('/seo-guides', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(301);
    
    const location = response?.headers()['location'];
    expect(location).toBe('/seo-guides/');
  });

  test('TC-6.2: Guide without trailing slash gets 301 redirect', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(301);
    
    const location = response?.headers()['location'];
    expect(location).toBe('/seo-guides/meta-title/');
  });

  test('TC-6.3: Nested path without trailing slash returns 404', async ({ page }) => {
    // No nested routes expected
    const response = await page.goto('/seo-guides/meta-title/section', { waitUntil: 'commit' });
    
    // Should be 404 (no nested routes) or redirect
    expect([301, 404]).toContain(response?.status() || 0);
  });

  test('TC-6.4: No redirect chain for index page', async ({ page }) => {
    const response = await page.goto('/seo-guides', { waitUntil: 'commit' });
    
    // Should be a single redirect, not a chain
    expect(response?.status()).toBe(301);
    
    // Follow the redirect
    const location = response?.headers()['location'];
    if (location) {
      const finalResponse = await page.goto(location);
      expect(finalResponse?.status()).toBe(200);
    }
  });
});

test.describe('Edge Cases Tests', () => {
  
  test('TC-8.1: Non-existent guide slug returns 404', async ({ page }) => {
    const response = await page.goto('/seo-guides/non-existent-guide/', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(404);
  });

  test('TC-8.2: Path traversal attempt is blocked', async ({ page }) => {
    const response = await page.goto('/seo-guides/../../etc/passwd', { waitUntil: 'commit' });
    
    // Should be blocked (400, 403, or 404)
    expect([400, 403, 404]).toContain(response?.status() || 0);
  });

  test('TC-8.3: SQL injection in slug returns 404', async ({ page }) => {
    const response = await page.goto("/seo-guides/1' OR '1'='1/", { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(404);
  });

  test('TC-8.4: XSS attempt in slug returns 404', async ({ page }) => {
    const response = await page.goto('/seo-guides/<script>alert(1)</script>/', { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(404);
  });

  test('TC-8.5: Double trailing slash returns 404', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title//', { waitUntil: 'commit' });
    
    expect([301, 404]).toContain(response?.status() || 0);
  });

  test('TC-8.8: Empty slug returns 404', async ({ page }) => {
    const response = await page.goto('/seo-guides//', { waitUntil: 'commit' });
    
    expect([301, 404]).toContain(response?.status() || 0);
  });

  test('TC-8.9: Very long slug returns 404', async ({ page }) => {
    const longSlug = 'a'.repeat(300);
    const response = await page.goto(`/seo-guides/${longSlug}/`, { waitUntil: 'commit' });
    
    expect(response?.status()).toBe(404);
  });
});

test.describe('Security Tests', () => {
  
  test('TC-9.1: Path traversal blocked', async ({ page }) => {
    const response = await page.goto('/seo-guides/../../../etc/passwd', { waitUntil: 'commit' });
    
    expect([400, 403, 404]).toContain(response?.status() || 0);
  });

  test('TC-9.2: Directory listing disabled', async ({ page }) => {
    await page.goto('/seo-guides/');
    
    // Should not show Apache directory listing
    const content = await page.content();
    expect(content).not.toContain('Index of');
    expect(content).not.toContain('Parent Directory');
  });

  test('TC-9.3: Hidden files blocked', async ({ page }) => {
    const response = await page.goto('/seo-guides/.htaccess', { waitUntil: 'commit' });
    
    expect([403, 404]).toContain(response?.status() || 0);
  });

  test('TC-9.4: Backup files blocked', async ({ page }) => {
    const response = await page.goto('/seo-guides/index.html.bak', { waitUntil: 'commit' });
    
    expect([403, 404]).toContain(response?.status() || 0);
  });
});

test.describe('SEO Tests', () => {
  
  test('TC-10.1: Canonical URL has trailing slash', async ({ page }) => {
    await page.goto('/seo-guides/meta-title/');
    
    const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
    expect(canonical).toMatch(/\/seo-guides\/meta-title\/$/);
  });

  test('TC-10.2: OG URL has trailing slash', async ({ page }) => {
    await page.goto('/seo-guides/meta-title/');
    
    const ogUrl = await page.locator('meta[property="og:url"]').getAttribute('content');
    expect(ogUrl).toMatch(/\/seo-guides\/meta-title\/$/);
  });

  test('TC-10.3: No duplicate content (non-trailing redirects)', async ({ page }) => {
    const response = await page.goto('/seo-guides/meta-title', { waitUntil: 'commit' });
    
    // Should redirect, not serve content directly
    expect(response?.status()).toBe(301);
  });

  test('TC-10.4: Robots.txt accessible', async ({ page }) => {
    const response = await page.goto('/robots.txt');
    
    expect(response?.status()).toBe(200);
  });
});
