import { test, expect, Page } from '@playwright/test';

/**
 * E2E Tests for Ranking History Flow
 * Tests: View ranking history, manual rank check, chart interactions
 */

const SITE_ID = '1';
const KEYWORD_ID = '1';

// Mock ranking history data
const mockRankingHistory = [
  {
    id: 1,
    keywordId: 1,
    position: 5,
    urlFound: 'https://example.com/seo-tools',
    checkedAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    keywordId: 1,
    position: 7,
    urlFound: 'https://example.com/seo-tools',
    checkedAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    keywordId: 1,
    position: 10,
    urlFound: 'https://example.com/seo-tools',
    checkedAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 4,
    keywordId: 1,
    position: 8,
    urlFound: 'https://example.com/seo-tools',
    checkedAt: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 5,
    keywordId: 1,
    position: 12,
    urlFound: 'https://example.com/seo-tools',
    checkedAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
  },
];

const mockKeyword = {
  id: 1,
  keyword: 'seo tools',
  targetUrl: 'https://example.com/seo-tools',
  searchEngine: 'google',
  location: 'us',
  device: 'desktop',
  status: 'active',
  currentPosition: 5,
  previousPosition: 7,
  positionChange: -2,
  lastCheckedAt: new Date().toISOString(),
};

// Helper to set up API mocks
async function setupMocks(page: Page) {
  // Mock SERP features metadata (most specific, set up first)
  await page.route('**/api/v1/metadata/serp-features**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        serpFeatures: [],
      }),
    });
  });

  // Mock ranking history API (specific pattern)
  await page.route('**/api/v1/keywords/**/history**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: {
          keyword: mockKeyword,
          history: mockRankingHistory,
          summary: {
            currentPosition: 5,
            bestPosition: 5,
            worstPosition: 12,
            averagePosition: 8.4,
            totalChecks: 5,
          },
        },
      }),
    });
  });

  // Mock manual rank check API
  await page.route('**/api/v1/keywords/**/check**', async (route) => {
    if (route.request().method() === 'POST') {
      await route.fulfill({
        status: 202,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            jobId: 'check-test-123',
            status: 'queued',
          },
        }),
      });
    } else {
      await route.continue();
    }
  });

  // Mock keyword detail API - use wildcard pattern for flexibility
  // This handles GET /api/v1/keywords/1 and PUT /api/v1/keywords/1
  await page.route('**/api/v1/keywords/*', async (route) => {
    const url = route.request().url();
    const method = route.request().method();
    
    // Skip history and check routes (handled by other mocks)
    if (url.includes('/history') || url.includes('/check')) {
      await route.continue();
      return;
    }
    
    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: mockKeyword,
        }),
      });
    } else if (method === 'PUT') {
      const body = route.request().postDataJSON();
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: { ...mockKeyword, ...body },
        }),
      });
    } else if (method === 'DELETE') {
      await route.fulfill({
        status: 204,
      });
    } else {
      await route.continue();
    }
  });

  // Mock keywords list for navigation
  await page.route('**/api/v1/sites/*/keywords**', async (route) => {
    if (route.request().method() === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: [mockKeyword],
          meta: { total: 1, page: 1, perPage: 20 },
        }),
      });
    } else {
      await route.continue();
    }
  });
}

test.describe('Ranking History E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('E2E-010: Navigate to keyword detail from list', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Click on keyword to view detail
    await page.click('a:has-text("seo tools")');
    
    // Should navigate to keyword detail page
    await expect(page).toHaveURL(new RegExp(`/sites/${SITE_ID}/keywords/\\d+`));
  });

  test('E2E-011: Keyword detail page loads correctly', async ({ page }) => {
    // Navigate directly to keyword detail
    await page.goto(`/sites/${SITE_ID}/keywords/${KEYWORD_ID}`);
    
    // Wait for loading to complete - the page shows skeletons during loading
    // Wait for the actual content (h1 with keyword name)
    await expect(page.locator('h1:has-text("seo tools")')).toBeVisible({ timeout: 10000 });
    
    // Verify keyword information is displayed - look for the card title
    await expect(page.getByText('Current Position', { exact: false })).toBeVisible();
  });

  test('E2E-012: Navigate to ranking history page', async ({ page }) => {
    // Navigate to ranking history page
    await page.goto('/ranking-history');
    
    // Verify the page loads - check for h1
    await expect(page.locator('h1:has-text("Ranking History")')).toBeVisible();
  });

  test('E2E-020: Trigger manual rank check', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Click on keyword to view detail
    await page.click('a:has-text("seo tools")');
    
    // Wait for detail page
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Look for manual check button
    await page.click('button:has-text("Check now")');
  });

  test('E2E-021: View keyword edit page', async ({ page }) => {
    // Navigate to keyword edit page
    await page.goto(`/sites/${SITE_ID}/keywords/${KEYWORD_ID}/edit`);
    
    // Verify edit form loads
    await expect(page.locator('h1:has-text("Edit keyword")')).toBeVisible();
    
    // Verify form fields are present
    await expect(page.locator('#keyword')).toBeVisible();
    await expect(page.locator('#targetUrl')).toBeVisible();
  });

  test('E2E-022: Update keyword and verify success', async ({ page }) => {
    // Navigate to keyword edit page
    await page.goto(`/sites/${SITE_ID}/keywords/${KEYWORD_ID}/edit`);
    
    // Wait for form to load
    await expect(page.locator('#targetUrl')).toBeVisible();
    
    // Update the target URL
    await page.fill('#targetUrl', 'https://example.com/new-seo-tools');
    
    // Save changes - button text is "Save changes"
    await page.click('button:has-text("Save")');
    
    // Wait for success indication
    await expect(page.locator('text=Keyword updated')).toBeVisible({ timeout: 10000 });
  });

  test('E2E-030: Cancel keyword edit', async ({ page }) => {
    // Navigate to keyword edit page
    await page.goto(`/sites/${SITE_ID}/keywords/${KEYWORD_ID}/edit`);
    
    // Wait for form to be fully loaded and interactive
    await expect(page.locator('h1:has-text("Edit keyword")')).toBeVisible();
    await expect(page.locator('#keyword')).toBeVisible();
    await expect(page.locator('#targetUrl')).toBeVisible();
    
    // Click cancel button - use more specific selector
    const cancelButton = page.locator('button:has-text("Cancel")');
    await expect(cancelButton).toBeEnabled();
    
    // Click and wait for navigation
    await Promise.all([
      page.waitForURL(/\/sites\/\d+\/keywords/, { timeout: 15000 }),
      cancelButton.click(),
    ]);
    
    // Verify we're on the keywords list page
    await expect(page.locator('h2:has-text("Keyword list")')).toBeVisible({ timeout: 5000 });
  });

  test('E2E-040: Delete keyword via bulk action', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Select keyword checkbox in tbody
    await page.locator('tbody input[type="checkbox"]').first().check();
    
    // Verify selection
    await expect(page.locator('text=selected')).toBeVisible();
    
    // Click delete button
    await page.click('button:has-text("Delete")');
  });

  test('E2E-041: Bulk pause keywords', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Select keyword checkbox in tbody
    await page.locator('tbody input[type="checkbox"]').first().check();
    
    // Click pause button
    await page.click('button:has-text("Pause")');
  });

  test('E2E-042: Bulk resume keywords', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Select keyword checkbox in tbody
    await page.locator('tbody input[type="checkbox"]').first().check();
    
    // Click resume button
    await page.click('button:has-text("Resume")');
  });

  test('E2E-043: Filter keywords by status', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Select status filter
    await page.selectOption('select:has-text("All Status")', 'active');
    
    // Verify filter is applied
    await expect(page.locator('text=seo tools')).toBeVisible();
  });
});
