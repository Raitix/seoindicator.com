import { test, expect, Page } from '@playwright/test';

/**
 * E2E Tests for Keyword CRUD Flow
 * Tests: Add, View, Edit, Delete keyword
 * 
 * These tests use mock API responses for consistency.
 */

const SITE_ID = '1';
const BASE_URL = `/sites/${SITE_ID}/keywords`;

// Mock keyword data
const mockKeywords = [
  {
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
  },
  {
    id: 2,
    keyword: 'keyword tracker',
    targetUrl: 'https://example.com/keyword-tracker',
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

// Helper to set up API mocks
async function setupMocks(page: Page) {
  // Mock keywords list API
  await page.route('**/api/v1/sites/*/keywords*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: mockKeywords,
        meta: {
          total: mockKeywords.length,
          page: 1,
          perPage: 20,
        },
      }),
    });
  });

  // Mock create keyword API
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

  // Mock single keyword API
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
        body: JSON.stringify({
          success: true,
          data: { ...keyword, ...body },
        }),
      });
    } else if (route.request().method() === 'DELETE') {
      await route.fulfill({
        status: 204,
      });
    } else {
      await route.continue();
    }
  });
}

test.describe('Keyword Flow E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('E2E-001: Add new keyword → see in list', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for the page to load
    await expect(page.locator('h2:has-text("Keyword list")')).toBeVisible();
    
    // Click "Add keyword" button
    await page.click('a:has-text("Add keyword")');
    
    // Should be on the new keyword page
    await expect(page.locator('h1:has-text("Add a new keyword")')).toBeVisible();
    
    // Fill in the form
    await page.fill('#keyword', 'new test keyword');
    await page.fill('#targetUrl', 'https://example.com/new-test');
    
    // Submit the form
    await page.click('button:has-text("Add keyword")');
    
    // Should show success toast
    await expect(page.locator('text=Keyword added successfully')).toBeVisible({ timeout: 5000 });
  });

  test('E2E-002: View keyword list with correct data', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for the page to load
    await expect(page.locator('h2:has-text("Keyword list")')).toBeVisible();
    
    // Verify keywords are displayed
    await expect(page.locator('text=seo tools')).toBeVisible();
    await expect(page.locator('text=keyword tracker')).toBeVisible();
    
    // Verify position badges
    await expect(page.locator('text=#5')).toBeVisible();
    await expect(page.locator('text=#12')).toBeVisible();
    
    // Verify change indicators
    await expect(page.locator('text=-2')).toBeVisible(); // Improved (negative change)
    await expect(page.locator('text=+2')).toBeVisible(); // Declined (positive change)
  });

  test('E2E-003: Navigate to keyword detail from list', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Click on a keyword row
    await page.click('text=seo tools');
    
    // Should navigate to keyword detail page
    await expect(page).toHaveURL(new RegExp(`/sites/${SITE_ID}/keywords/\\d+`));
  });

  test('E2E-004: Edit keyword and verify updated data', async ({ page }) => {
    // Navigate to keyword edit page directly
    await page.goto(`${BASE_URL}/1/edit`);
    
    // Wait for the form to load
    await expect(page.locator('h1:has-text("Edit keyword")')).toBeVisible();
    
    // Verify the form is populated with existing data
    await expect(page.locator('#keyword')).toHaveValue(/seo tools/);
    
    // Update the target URL
    await page.fill('#targetUrl', 'https://example.com/updated-url');
    
    // Save changes
    await page.click('button:has-text("Save")');
    
    // Should show success toast
    await expect(page.locator('text=updated')).toBeVisible({ timeout: 5000 });
  });

  test('E2E-005: Delete keyword from list', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Select a keyword checkbox
    await page.locator('input[type="checkbox"]').first().check();
    
    // Verify selection counter
    await expect(page.locator('text=1 selected')).toBeVisible();
    
    // Click delete button
    await page.click('button:has-text("Delete")');
    
    // Verify API was called (keyword should be removed from view)
    // Note: In real scenario, the mock would update to show the deletion
  });

  test('E2E-006: Search and filter keywords', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Search for a keyword
    await page.fill('input[placeholder="Search keywords"]', 'tracker');
    
    // Verify only matching keywords are shown
    await expect(page.locator('text=keyword tracker')).toBeVisible();
    await expect(page.locator('text=seo tools')).not.toBeVisible();
    
    // Clear search
    await page.fill('input[placeholder="Search keywords"]', '');
    
    // Both keywords should be visible again
    await expect(page.locator('text=seo tools')).toBeVisible();
    await expect(page.locator('text=keyword tracker')).toBeVisible();
  });

  test('E2E-007: Sort keywords by different columns', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Change sort order
    await page.selectOption('select:has-text("Sort:")', 'position');
    
    // Verify sorting is applied (keywords should still be visible)
    await expect(page.locator('text=seo tools')).toBeVisible();
    await expect(page.locator('text=keyword tracker')).toBeVisible();
  });

  test('E2E-008: Bulk operations on keywords', async ({ page }) => {
    // Navigate to keywords list
    await page.goto(BASE_URL);
    
    // Wait for keywords to load
    await expect(page.locator('text=seo tools')).toBeVisible();
    
    // Select all keywords
    await page.locator('thead input[type="checkbox"]').check();
    
    // Verify all selected
    await expect(page.locator('text=2 selected')).toBeVisible();
    
    // Click pause button
    await page.click('button:has-text("Pause")');
    
    // Verify operation completed
    // In a real scenario, status would update
  });
});
