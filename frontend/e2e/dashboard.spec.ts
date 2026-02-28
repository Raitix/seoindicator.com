import { test, expect, Page } from '@playwright/test';

/**
 * E2E Tests for Dashboard Flow
 * Tests: Dashboard loads, displays correct data, navigation to keywords
 */

const SITE_ID = '1';

// Mock dashboard data - matches DashboardSummary component
const mockDashboardData = {
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
    { keyword: 'rank monitoring', position: 6, change: 2, url: '/rank-monitoring' },
    { keyword: 'seo dashboard', position: 8, change: -1, url: '/seo-dashboard' },
    { keyword: 'competitor analysis', position: 9, change: 1, url: '/competitor-analysis' },
  ],
  alerts: [
    {
      keyword: 'content audit',
      change: -7,
      previousPosition: 9,
      currentPosition: 16,
    },
    {
      keyword: 'backlink checker',
      change: -5,
      previousPosition: 11,
      currentPosition: 16,
    },
  ],
};

// Helper to set up API mocks
async function setupMocks(page: Page) {
  // Mock dashboard API (not currently used - component uses static data)
  await page.route('**/api/v1/sites/*/dashboard*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: mockDashboardData,
      }),
    });
  });

  // Mock keywords API for navigation
  await page.route('**/api/v1/sites/*/keywords*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: [],
        meta: { total: 0, page: 1, perPage: 20 },
      }),
    });
  });

  // Mock SERP features metadata
  await page.route('**/api/v1/metadata/serp-features*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        serpFeatures: [],
      }),
    });
  });
}

test.describe('Dashboard E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('E2E-050: Dashboard loads with correct data', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('h1:has-text("Dashboard Summary")')).toBeVisible();
    
    // Verify summary cards are visible (using more specific selectors)
    await expect(page.locator('text=Total Keywords').first()).toBeVisible();
    await expect(page.locator('text=Average Position')).toBeVisible();
    await expect(page.getByText('Ranked', { exact: true })).toBeVisible();
    await expect(page.locator('text=Not Ranked')).toBeVisible();
    
    // Verify metrics values (from component's baseData)
    await expect(page.locator('text=128')).toBeVisible(); // Total Keywords
    await expect(page.locator('text=14.3')).toBeVisible(); // Average Position
    await expect(page.locator('text=96')).toBeVisible(); // Ranked
    await expect(page.locator('text=32')).toBeVisible(); // Not Ranked
  });

  test('E2E-051: View position changes section', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Position Changes')).toBeVisible();
    
    // Verify change categories
    await expect(page.locator('text=Improved')).toBeVisible();
    await expect(page.locator('text=Declined')).toBeVisible();
    await expect(page.locator('text=Unchanged')).toBeVisible();
    await expect(page.locator('text=New')).toBeVisible();
    
    // Verify values
    await expect(page.locator('text=34')).toBeVisible(); // Improved
    await expect(page.locator('text=18')).toBeVisible(); // Declined
  });

  test('E2E-052: View top keywords section', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Top Keywords')).toBeVisible();
    
    // Verify table headers (using role-based selectors for specificity)
    await expect(page.getByRole('columnheader', { name: 'Keyword' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Position' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Change' })).toBeVisible();
    
    // Verify top keywords are displayed
    await expect(page.locator('text=seo indicator')).toBeVisible();
    await expect(page.locator('text=keyword tracking')).toBeVisible();
    await expect(page.locator('text=rank monitoring')).toBeVisible();
  });

  test('E2E-053: View alerts section', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Alerts')).toBeVisible();
    
    // Verify alert items are displayed
    await expect(page.locator('text=content audit')).toBeVisible();
    await expect(page.locator('text=backlink checker')).toBeVisible();
    
    // Verify position change indicators
    await expect(page.locator('text=-7')).toBeVisible();
    await expect(page.locator('text=-5')).toBeVisible();
  });

  test('E2E-054: Navigate to keyword list from dashboard', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Dashboard Summary')).toBeVisible();
    
    // Click "View full keyword list" button and wait for navigation
    await Promise.all([
      page.waitForURL(/\/sites\/\d+\/keywords/, { timeout: 10000 }),
      page.click('a:has-text("View full keyword list")'),
    ]);
    
    // Verify we're on the keywords page
    await expect(page.locator('h2:has-text("Keyword list")')).toBeVisible();
  });

  test('E2E-055: Navigate to add keyword from dashboard', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Dashboard Summary')).toBeVisible();
    
    // Click "Add keyword" button
    await page.click('a:has-text("Add keyword")');
    
    // Should navigate to new keyword page
    await expect(page).toHaveURL(/\/sites\/\d+\/keywords\/new/);
    await expect(page.locator('h1:has-text("Add a new keyword")')).toBeVisible();
  });

  test('E2E-056: Refresh dashboard data', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Dashboard Summary')).toBeVisible();
    
    // Click refresh button
    await page.click('button:has-text("Refresh all")');
    
    // Should show refreshing state
    await expect(page.locator('text=Refreshing')).toBeVisible();
    
    // Wait for refresh to complete
    await expect(page.locator('text=Refresh all')).toBeVisible({ timeout: 5000 });
  });

  test('E2E-057: Toggle auto-refresh', async ({ page }) => {
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Wait for the dashboard to load
    await expect(page.locator('text=Dashboard Summary')).toBeVisible();
    
    // Check the auto-refresh checkbox
    const autoRefreshCheckbox = page.locator('input[type="checkbox"]').first();
    await autoRefreshCheckbox.check();
    
    // Verify it's checked
    await expect(autoRefreshCheckbox).toBeChecked();
    
    // Uncheck it
    await autoRefreshCheckbox.uncheck();
    await expect(autoRefreshCheckbox).not.toBeChecked();
  });

  test('E2E-058: Dashboard displays loading state', async ({ page }) => {
    // Slow down the API response
    await page.route('**/api/v1/sites/*/dashboard*', async (route) => {
      await new Promise(resolve => setTimeout(resolve, 1000));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: mockDashboardData,
        }),
      });
    });
    
    // Navigate to dashboard
    await page.goto('/dashboard');
    
    // Should show loading skeletons initially (use .first() to handle multiple elements)
    await expect(page.locator('.animate-pulse').first()).toBeVisible();
    
    // Wait for data to load
    await expect(page.locator('h1:has-text("Dashboard Summary")')).toBeVisible({ timeout: 5000 });
  });
});
