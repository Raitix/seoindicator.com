/**
 * End-to-End Tests for AI Suggestions Feature
 * Task: TASK-21-12
 * 
 * Tests the complete user flow for AI-powered SEO suggestions
 * including button interactions, loading states, error handling,
 * and suggestion display.
 */

import { test, expect } from '@playwright/test';

const BASE_URL = process.env.E2E_BASE_URL || 'http://localhost:3000';

// Test credentials
const TEST_USER = {
  email: process.env.E2E_TEST_USER_EMAIL || 'test@example.com',
  password: process.env.E2E_TEST_USER_PASSWORD || 'password123',
};

test.describe('AI Suggestions E2E Tests', () => {
  let page: import('@playwright/test').Page;

  test.beforeEach(async ({ browser }) => {
    page = await browser.newPage();
    
    // Login before each test
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', TEST_USER.email);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL(/.*dashboard.*/, { timeout: 10000 });
  });

  test.afterEach(async () => {
    await page.close();
  });

  // ============================================
  // E2E-001: Title Tag Suggestions
  // ============================================

  test('E2E-001-01: Should display AI suggestion button for title tag tool', async () => {
    // Navigate to keyword density checker (has AI suggestion integration)
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    // Look for AI suggestion button
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    
    await expect(aiButton).toBeVisible({ timeout: 5000 });
    await expect(aiButton).toContainText(/get.*suggestion/i);
  });

  test('E2E-001-02: Should generate title tag suggestion', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    // Find and click the AI suggestion button
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    // Should show loading state
    await expect(page.locator('[data-testid="ai-suggestion-loading"]')).toBeVisible({ timeout: 2000 });
    
    // Wait for suggestion panel to appear
    const suggestionPanel = page.locator('[data-testid="ai-suggestion-panel"]');
    await expect(suggestionPanel).toBeVisible({ timeout: 15000 });
    
    // Verify suggestion content
    await expect(suggestionPanel.locator('[data-testid="suggestion-score"]')).toBeVisible();
    await expect(suggestionPanel.locator('[data-testid="suggestion-issues"]')).toBeVisible();
    await expect(suggestionPanel.locator('[data-testid="suggestion-suggestions"]')).toBeVisible();
  });

  test('E2E-001-03: Should display score within valid range', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    const suggestionPanel = page.locator('[data-testid="ai-suggestion-panel"]');
    await expect(suggestionPanel).toBeVisible({ timeout: 15000 });
    
    // Get score value
    const scoreElement = suggestionPanel.locator('[data-testid="suggestion-score"]');
    const scoreText = await scoreElement.textContent();
    const score = parseInt(scoreText || '0', 10);
    
    expect(score).toBeGreaterThanOrEqual(0);
    expect(score).toBeLessThanOrEqual(100);
  });

  // ============================================
  // E2E-002: Loading States
  // ============================================

  test('E2E-002-01: Should show loading spinner during suggestion fetch', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    
    // Click and immediately check for loading state
    await Promise.all([
      aiButton.click(),
      expect(page.locator('[data-testid="ai-suggestion-loading"]')).toBeVisible({ timeout: 500 }),
    ]);
  });

  test('E2E-002-02: Should disable button while loading', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    // Button should be disabled during loading
    await expect(aiButton).toBeDisabled({ timeout: 1000 });
    
    // Wait for completion
    await expect(page.locator('[data-testid="ai-suggestion-panel"]')).toBeVisible({ timeout: 15000 });
    
    // Button should be enabled again
    await expect(aiButton).toBeEnabled({ timeout: 5000 });
  });

  // ============================================
  // E2E-003: Error Handling
  // ============================================

  test('E2E-003-01: Should display error message on API failure', async () => {
    // Simulate network failure by blocking the API
    await page.route('**/api/v1/ai/suggestions', (route) => {
      route.fulfill({
        status: 500,
        body: JSON.stringify({
          success: false,
          error: { message: 'Internal server error' },
        }),
      });
    });
    
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    // Should show error state
    await expect(page.locator('[data-testid="ai-suggestion-error"]')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('text=/error|failed|try again/i')).toBeVisible();
  });

  test('E2E-003-02: Should show retry button on error', async () => {
    let requestCount = 0;
    
    await page.route('**/api/v1/ai/suggestions', (route) => {
      requestCount++;
      if (requestCount === 1) {
        route.fulfill({
          status: 500,
          body: JSON.stringify({ success: false, error: { message: 'Error' } }),
        });
      } else {
        route.fulfill({
          status: 200,
          body: JSON.stringify({
            success: true,
            data: {
              indicator: 'title',
              score: 75,
              issues: [],
              suggestions: [],
              best_practices: [],
              provider: 'static_fallback',
              model: null,
              generated_at: new Date().toISOString(),
            },
          }),
        });
      }
    });
    
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    // Wait for error state
    await expect(page.locator('[data-testid="ai-suggestion-error"]')).toBeVisible({ timeout: 10000 });
    
    // Click retry button
    const retryButton = page.locator('button:has-text("retry")');
    await retryButton.click();
    
    // Should show suggestion panel after retry
    await expect(page.locator('[data-testid="ai-suggestion-panel"]')).toBeVisible({ timeout: 10000 });
  });

  // ============================================
  // E2E-004: Rate Limiting
  // ============================================

  test('E2E-004-01: Should show rate limit message when limited', async () => {
    await page.route('**/api/v1/ai/suggestions', (route) => {
      route.fulfill({
        status: 429,
        headers: { 'Retry-After': '30' },
        body: JSON.stringify({
          success: false,
          error: {
            code: 'RATE_LIMIT_EXCEEDED',
            message: 'Rate limit exceeded',
            retry_after: 30,
          },
        }),
      });
    });
    
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    // Should show rate limit message
    await expect(page.locator('text=/rate limit|too many|try again later/i')).toBeVisible({ timeout: 10000 });
  });

  // ============================================
  // E2E-005: Suggestion Panel Interaction
  // ============================================

  test('E2E-005-01: Should expand suggestion details', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    const suggestionPanel = page.locator('[data-testid="ai-suggestion-panel"]');
    await expect(suggestionPanel).toBeVisible({ timeout: 15000 });
    
    // Check for expandable sections
    const issuesSection = suggestionPanel.locator('[data-testid="suggestion-issues"]');
    if (await issuesSection.isVisible()) {
      // Expand issues section
      await issuesSection.click();
      await expect(suggestionPanel.locator('[data-testid="issues-list"]')).toBeVisible({ timeout: 2000 });
    }
  });

  test('E2E-005-02: Should display best practices section', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    const suggestionPanel = page.locator('[data-testid="ai-suggestion-panel"]');
    await expect(suggestionPanel).toBeVisible({ timeout: 15000 });
    
    // Check for best practices
    const bestPractices = suggestionPanel.locator('[data-testid="best-practices"]');
    await expect(bestPractices).toBeVisible({ timeout: 5000 });
  });

  // ============================================
  // E2E-006: Provider Badge
  // ============================================

  test('E2E-006-01: Should show provider badge in response', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    await aiButton.click();
    
    const suggestionPanel = page.locator('[data-testid="ai-suggestion-panel"]');
    await expect(suggestionPanel).toBeVisible({ timeout: 15000 });
    
    // Check for provider badge
    const providerBadge = suggestionPanel.locator('[data-testid="provider-badge"]');
    await expect(providerBadge).toBeVisible({ timeout: 5000 });
    
    // Should show provider name
    const badgeText = await providerBadge.textContent();
    expect(badgeText).toMatch(/openrouter|static|vercel/i);
  });

  // ============================================
  // E2E-007: Accessibility
  // ============================================

  test('E2E-007-01: Should be keyboard accessible', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    // Tab to AI suggestion button
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    
    // Check if button has focus
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]:focus').first();
    await expect(aiButton).toBeVisible({ timeout: 2000 });
    
    // Press Enter to activate
    await page.keyboard.press('Enter');
    
    // Should trigger action
    await expect(page.locator('[data-testid="ai-suggestion-loading"], [data-testid="ai-suggestion-panel"]')).toBeVisible({ timeout: 5000 });
  });

  test('E2E-007-02: Should have correct ARIA attributes', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    
    // Check ARIA attributes
    expect(await aiButton.getAttribute('aria-label')).toBeTruthy();
    
    // Check for aria-busy during loading
    await aiButton.click();
    await page.waitForTimeout(100);
    
    // Button should indicate busy state while loading
    const ariaBusy = await aiButton.getAttribute('aria-busy');
    expect(ariaBusy).toBe('true');
  });

  // ============================================
  // E2E-008: Multiple Indicators
  // ============================================

  test('E2E-008-01: Should support different indicator types', async () => {
    // Test with different tools/pages that have different indicators
    const toolPages = [
      { url: '/tools/keyword-density-checker', indicator: 'title' },
      // Add more tools as they integrate AI suggestions
    ];
    
    for (const tool of toolPages) {
      await page.goto(`${BASE_URL}${tool.url}`);
      
      const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
      if (await aiButton.isVisible({ timeout: 2000 })) {
        await aiButton.click();
        
        const suggestionPanel = page.locator('[data-testid="ai-suggestion-panel"]');
        await expect(suggestionPanel).toBeVisible({ timeout: 15000 });
        
        // Verify indicator type matches
        const indicatorBadge = suggestionPanel.locator('[data-testid="indicator-badge"]');
        if (await indicatorBadge.isVisible()) {
          const indicatorText = await indicatorBadge.textContent();
          expect(indicatorText?.toLowerCase()).toContain(tool.indicator.replace('_', ' '));
        }
      }
    }
  });

  // ============================================
  // E2E-009: Unauthenticated User
  // ============================================

  test('E2E-009-01: Should redirect to login for unauthenticated users', async ({ browser }) => {
    // Create new page without authentication
    const unauthPage = await browser.newPage();
    
    await unauthPage.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = unauthPage.locator('[data-testid="ai-suggestion-button"]').first();
    
    if (await aiButton.isVisible({ timeout: 2000 })) {
      await aiButton.click();
      
      // Should redirect to login or show auth required message
      await unauthPage.waitForURL(/.*login.*/, { timeout: 5000 }).catch(() => {
        // Or show auth required message
        return expect(unauthPage.locator('text=/sign in|log in|authenticate/i')).toBeVisible({ timeout: 5000 });
      });
    }
    
    await unauthPage.close();
  });

  // ============================================
  // E2E-010: Response Time
  // ============================================

  test('E2E-010-01: Should respond within acceptable time', async () => {
    await page.goto(`${BASE_URL}/tools/keyword-density-checker`);
    
    const aiButton = page.locator('[data-testid="ai-suggestion-button"]').first();
    
    const startTime = Date.now();
    await aiButton.click();
    
    // Wait for suggestion panel or error state
    await page.locator('[data-testid="ai-suggestion-panel"], [data-testid="ai-suggestion-error"]').waitFor({ timeout: 20000 });
    
    const endTime = Date.now();
    const responseTime = endTime - startTime;
    
    // Should respond within 15 seconds (including static fallback)
    expect(responseTime).toBeLessThan(15000);
  });
});
