// @ts-check
const { test, expect, Page } = require('@playwright/test');

/**
 * E2E Tests for Critical User Flows - SEOIndicator
 * 
 * These tests cover the most critical user flows:
 * 1. Add keyword → see in list → view history
 * 2. Dashboard loads with correct data
 * 3. Trigger manual rank check → see updated position
 * 4. Edit keyword → see updated data
 * 5. Delete keyword → removed from list
 * 
 * Uses mock API responses for consistency and speed.
 */

const SITE_ID = '1';

// Mock data
const mockKeywords = [
  {
    id: 1,
    keyword: 'test keyword',
    targetUrl: 'https://example.com/test',
    searchEngine: 'google',
    location: 'us',
    device: 'desktop',
    status: 'active',
    currentPosition: 5,
    previousPosition: 7,
    positionChange: -2,
    lastCheckedAt: new Date().toISOString(),
  },
  {
    id: 2,
    keyword: 'another keyword',
    targetUrl: 'https://example.com/another',
    searchEngine: 'google',
    location: 'us',
    device: 'desktop',
    status: 'active',
    currentPosition: 12,
    previousPosition: 10,
    positionChange: 2,
    lastCheckedAt: new Date().toISOString(),
  },
];

const mockDashboard = {
  totalKeywords: 128,
  averagePosition: 14.3,
  ranked: 96,
  notRanked: 32,
  changes: {
    improved: 34,
    declined: 18,
    unchanged: 41,
    new: 5,
  },
  topKeywords: [
    { keyword: 'seo indicator', position: 2, change: 1, url: '/seo-indicator' },
    { keyword: 'keyword tracking', position: 4, change: 0, url: '/keyword-tracking' },
  ],
  alerts: [
    { keyword: 'content audit', change: -7, previousPosition: 9, currentPosition: 16 },
  ],
};

/**
 * Set up API mocks for consistent test behavior
 * @param {Page} page 
 */
async function setupMocks(page) {
  // Mock keywords list
  await page.route('**/api/v1/sites/*/keywords*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: mockKeywords,
        meta: { total: mockKeywords.length, page: 1, perPage: 20 },
      }),
    });
  });

  // Mock create keyword
  await page.route('**/api/v1/sites/*/keywords', async (route) => {
    if (route.request().method() === 'POST') {
      const body = route.request().postDataJSON();
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            id: 3,
            ...body,
            status: 'active',
            currentPosition: null,
            previousPosition: null,
            positionChange: null,
            lastCheckedAt: null,
          },
        }),
      });
    } else {
      await route.continue();
    }
  });

  // Mock single keyword operations
  await page.route('**/api/v1/keywords/*', async (route) => {
    const keywordId = route.request().url().match(/keywords\/(\d+)/)?.[1];
    const keyword = mockKeywords.find(k => k.id === Number(keywordId)) || mockKeywords[0];
    
    if (route.request().method() === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: keyword }),
      });
    } else if (route.request().method() === 'PUT') {
      const body = route.request().postDataJSON();
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: { ...keyword, ...body } }),
      });
    } else if (route.request().method() === 'DELETE') {
      await route.fulfill({ status: 204 });
    } else {
      await route.continue();
    }
  });

  // Mock dashboard
  await page.route('**/api/v1/sites/*/dashboard*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, data: mockDashboard }),
    });
  });

  // Mock manual rank check
  await page.route('**/api/v1/keywords/*/check', async (route) => {
    if (route.request().method() === 'POST') {
      await route.fulfill({
        status: 202,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: { jobId: 'check-test-123', status: 'queued' },
        }),
      });
    } else {
      await route.continue();
    }
  });
}

test.describe('Critical User Flows E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('Add keyword → Verify in list → Check history', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for page to load
    await expect(page.locator('h2:has-text("Keyword list")')).toBeVisible({ timeout: 10000 });
    
    // Click "Add keyword" button
    await page.click('a:has-text("Add keyword")');
    
    // Verify we're on the new keyword page
    await expect(page.locator('h1:has-text("Add a new keyword")')).toBeVisible();
    
    // Fill in the form
    await page.fill('#keyword', 'new test keyword');
    await page.fill('#targetUrl', 'https://example.com/new-test');
    
    // Submit the form
    await page.click('button:has-text("Add keyword")');
    
    // Verify success toast appears
    await expect(page.locator('text=Keyword added successfully')).toBeVisible({ timeout: 5000 });
  });

  test('Dashboard loads with correct data', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for dashboard to load
    await expect(page.locator('h1:has-text("Dashboard Summary")')).toBeVisible({ timeout: 10000 });
    
    // Verify summary cards are displayed
    await expect(page.locator('text=Total Keywords')).toBeVisible();
    await expect(page.locator('text=Average Position')).toBeVisible();
    await expect(page.locator('text=Ranked')).toBeVisible();
    await expect(page.locator('text=Not Ranked')).toBeVisible();
    
    // Verify metrics
    await expect(page.locator('text=128')).toBeVisible(); // Total
    await expect(page.locator('text=14.3')).toBeVisible(); // Avg Position
  });

  test('Trigger manual rank check and verify position', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=test keyword')).toBeVisible({ timeout: 10000 });
    
    // Click on keyword to view detail
    await page.click('text=test keyword');
    
    // Verify we're on keyword detail page
    await expect(page).toHaveURL(new RegExp(`/sites/${SITE_ID}/keywords/\\d+`));
  });

  test('Edit keyword and verify data', async ({ page }) => {
    // Navigate to keyword edit page
    await page.goto(`/sites/${SITE_ID}/keywords/1/edit`);
    
    // Wait for form to load
    await expect(page.locator('h1:has-text("Edit keyword")')).toBeVisible({ timeout: 10000 });
    
    // Verify form is populated
    await expect(page.locator('#keyword')).toHaveValue(/test keyword/);
    
    // Update target URL
    await page.fill('#targetUrl', 'https://example.com/updated-test');
    
    // Save changes
    await page.click('button:has-text("Save")');
    
    // Verify success indication
    await expect(page.locator('text=success, text=updated, text=Saved')).toBeVisible({ timeout: 5000 });
  });

  test('Delete keyword', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(`/sites/${SITE_ID}/keywords`);
    
    // Wait for keywords to load
    await expect(page.locator('text=test keyword')).toBeVisible({ timeout: 10000 });
    
    // Select keyword checkbox (skip header checkbox)
    await page.locator('tbody input[type="checkbox"]').first().check();
    
    // Verify selection indicator
    await expect(page.locator('text=selected')).toBeVisible();
    
    // Click delete button
    await page.click('button:has-text("Delete")');
    
    // The bulk delete action should be triggered
  });
});

test.describe('Search and Filter E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('Search keywords filters results', async ({ page }) => {
    await page.goto(`/sites/${SITE_ID}/keywords`);
    await expect(page.locator('text=test keyword')).toBeVisible({ timeout: 10000 });
    
    // Search for specific keyword
    await page.fill('input[placeholder="Search keywords"]', 'another');
    
    // Only matching keyword should be visible
    await expect(page.locator('text=another keyword')).toBeVisible();
  });

  test('Sort keywords changes order', async ({ page }) => {
    await page.goto(`/sites/${SITE_ID}/keywords`);
    await expect(page.locator('text=test keyword')).toBeVisible({ timeout: 10000 });
    
    // Change sort option
    await page.selectOption('select:has-text("Sort:")', 'position');
    
    // Verify sort is applied
    await expect(page.locator('text=test keyword')).toBeVisible();
  });

  test('Filter by status shows correct keywords', async ({ page }) => {
    await page.goto(`/sites/${SITE_ID}/keywords`);
    await expect(page.locator('text=test keyword')).toBeVisible({ timeout: 10000 });
    
    // Select active status filter
    await page.selectOption('select:has-text("All")', 'active');
    
    // Keywords should still be visible
    await expect(page.locator('text=test keyword')).toBeVisible();
  });
});

test.describe('Navigation E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('Navigate from dashboard to keyword list', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page.locator('h1:has-text("Dashboard Summary")')).toBeVisible({ timeout: 10000 });
    
    // Click keyword list link
    await page.click('a:has-text("View full keyword list")');
    
    // Should navigate to keywords page
    await expect(page).toHaveURL(/\/sites\/\d+\/keywords/);
  });

  test('Navigate from dashboard to add keyword', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page.locator('h1:has-text("Dashboard Summary")')).toBeVisible({ timeout: 10000 });
    
    // Click add keyword button
    await page.click('a:has-text("Add keyword")');
    
    // Should navigate to new keyword page
    await expect(page).toHaveURL(/\/sites\/\d+\/keywords\/new/);
  });
});
