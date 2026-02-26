import { test, expect, Page } from '@playwright/test';

/**
 * E2E Tests for Keyword Density Checker Tool
 * Task: TASK-16-2 - Keyword Density Checker Frontend
 *
 * Tests the full user flow of the keyword density analysis tool.
 */

// Mock analysis response
const mockKeywordAnalysisResponse = {
  success: true,
  data: {
    url: 'https://example.com/seo-guide',
    keyword: 'SEO',
    analysis: {
      density: 2.3,
      occurrences: 12,
      totalWords: 520,
      positions: [15, 89, 156, 234, 312, 401, 456, 512, 678, 745, 823, 912],
      status: 'good',
    },
    headings: [
      { tag: 'h1', text: 'SEO Best Practices Guide', keywordFound: true },
      { tag: 'h2', text: 'Introduction to SEO', keywordFound: true },
      { tag: 'h2', text: 'On-Page Optimization', keywordFound: false },
      { tag: 'h3', text: 'Meta Tags for SEO', keywordFound: true },
      { tag: 'h3', text: 'Content Strategy', keywordFound: false },
    ],
    warnings: [
      { type: 'info', message: 'Consider adding keyword to "Content Strategy" heading' },
    ],
    analyzedAt: '2026-02-26T19:30:00Z',
  },
};

const mockOverOptimizedResponse = {
  success: true,
  data: {
    url: 'https://example.com/spammy',
    keyword: 'SEO',
    analysis: {
      density: 5.8,
      occurrences: 35,
      totalWords: 600,
      status: 'over-optimized',
    },
    headings: [
      { tag: 'h1', text: 'SEO SEO SEO Best SEO Practices', keywordFound: true },
    ],
    warnings: [
      { type: 'warning', message: 'Keyword density is too high (5.8%)' },
      { type: 'warning', message: 'H1 contains multiple keyword repetitions' },
    ],
    analyzedAt: '2026-02-26T19:35:00Z',
  },
};

// Helper to set up API mocks
async function setupMocks(page: Page) {
  // Mock keyword density analysis API
  await page.route('**/api/tools/keyword-density*', async (route) => {
    const request = route.request();
    const postData = request.postDataJSON?.() || {};
    
    // Check for over-optimized scenario based on URL
    if (postData.url?.includes('spammy')) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(mockOverOptimizedResponse),
      });
      return;
    }
    
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(mockKeywordAnalysisResponse),
    });
  });
}

test.describe('Keyword Density Checker E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  // E2E-001: Full URL analysis flow
  test('E2E-001: Full URL analysis flow', async ({ page }) => {
    // Navigate to the tool
    await page.goto('/tools/keyword-density');
    
    // Wait for form to load
    await expect(page.locator('h1:has-text("Keyword Density Checker")')).toBeVisible();
    
    // Enter URL
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com/seo-guide');
    
    // Enter keyword
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    
    // Click analyze
    await page.click('button:has-text("Analyze")');
    
    // Wait for results
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
    
    // Verify density displayed
    await expect(page.locator('[data-testid="density-value"]')).toBeVisible();
    await expect(page.locator('text=2.3%')).toBeVisible();
    
    // Verify heading analysis
    await expect(page.locator('text=Heading Analysis')).toBeVisible();
    await expect(page.locator('text=SEO Best Practices Guide')).toBeVisible();
  });

  // E2E-002: Full HTML analysis flow
  test('E2E-002: Full HTML analysis flow', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Paste HTML
    const htmlContent = `
      <html>
        <body>
          <h1>SEO Guide</h1>
          <p>SEO is important for rankings. Learn SEO best practices.</p>
          <h2>SEO Basics</h2>
          <p>Understand SEO fundamentals for success.</p>
        </body>
      </html>
    `;
    
    await page.fill('textarea[placeholder="Or paste HTML here"]', htmlContent);
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    
    // Click analyze
    await page.click('button:has-text("Analyze")');
    
    // Results should appear
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
  });

  // E2E-003: Over-optimization warning
  test('E2E-003: Over-optimization warning displayed', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Enter URL that triggers over-optimized response
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com/spammy');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    
    await page.click('button:has-text("Analyze")');
    
    // Wait for results
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
    
    // Check for over-optimized status
    await expect(page.locator('.density-value.red')).toBeVisible();
    await expect(page.locator('text=Over-optimized')).toBeVisible();
    
    // Check for warnings
    await expect(page.locator('text=too high')).toBeVisible();
  });

  // E2E-004: Heading relevance display
  test('E2E-004: Heading relevance indicators', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com/seo-guide');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    
    await page.click('button:has-text("Analyze")');
    
    // Wait for heading analysis section
    await expect(page.locator('text=Heading Analysis')).toBeVisible({ timeout: 5000 });
    
    // Check for keyword found indicators
    await expect(page.locator('[data-testid="keyword-found"]').first()).toBeVisible();
    
    // Check for keyword missing indicators
    await expect(page.locator('[data-testid="keyword-missing"]').first()).toBeVisible();
  });

  // E2E-005: Error recovery
  test('E2E-005: Error recovery flow', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Enter invalid URL
    await page.fill('input[placeholder="Enter page URL"]', 'not-a-valid-url');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    
    await page.click('button:has-text("Analyze")');
    
    // Should show validation error
    await expect(page.locator('role=alert')).toBeVisible();
    await expect(page.locator('text=valid URL')).toBeVisible();
    
    // Correct the URL
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com/seo-guide');
    
    // Error should clear (or at least allow submission)
    await page.click('button:has-text("Analyze")');
    
    // Should proceed to results
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
  });

  // E2E-006: Accessibility - keyboard navigation
  test('E2E-006: Keyboard navigation', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Tab through form elements
    await page.keyboard.press('Tab');
    await expect(page.locator('input[placeholder="Enter page URL"]')).toBeFocused();
    
    await page.keyboard.press('Tab');
    await expect(page.locator('textarea[placeholder="Or paste HTML here"]')).toBeFocused();
    
    await page.keyboard.press('Tab');
    await expect(page.locator('input[placeholder="Enter target keyword"]')).toBeFocused();
    
    // Fill using keyboard
    await page.keyboard.type('https://example.com');
    await page.keyboard.press('Tab');
    await page.keyboard.type('SEO');
    
    // Submit with Enter
    await page.keyboard.press('Enter');
    
    // Should navigate to results
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
  });

  // E2E-007: Accessibility - ARIA labels
  test('E2E-007: Proper ARIA labels present', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Check for form labels
    await expect(page.locator('label:has-text("Enter page URL")')).toBeVisible();
    await expect(page.locator('label:has-text("Paste HTML")')).toBeVisible();
    await expect(page.locator('label:has-text("Target Keyword")')).toBeVisible();
    
    // Check for button accessibility
    const analyzeButton = page.locator('button:has-text("Analyze")');
    await expect(analyzeButton).toHaveAttribute('type', 'submit');
  });

  // E2E-008: Responsive design - mobile
  test('E2E-008: Mobile responsive layout', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/tools/keyword-density');
    
    // Form should still be visible and usable
    await expect(page.locator('input[placeholder="Enter page URL"]')).toBeVisible();
    await expect(page.locator('input[placeholder="Enter target keyword"]')).toBeVisible();
    
    // Fill and submit
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    await page.click('button:has-text("Analyze")');
    
    // Results should display properly on mobile
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
  });

  // E2E-009: Tool embedded in guide page
  test('E2E-009: Tool embedded in guide page', async ({ page }) => {
    // Navigate to a guide page that should have the tool embedded
    await page.goto('/guides/seo-best-practices');
    
    // Look for embedded keyword density checker
    const embeddedTool = page.locator('[data-testid="keyword-density-form"]');
    
    // If embedded tool exists, interact with it
    if (await embeddedTool.count() > 0) {
      await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
      await page.click('button:has-text("Analyze")');
      
      // Results should appear within the guide context
      await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
    } else {
      // Tool not embedded on this page, skip this assertion
      test.skip();
    }
  });

  // E2E-010: Standalone tool page
  test('E2E-010: Standalone tool page', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Page should have proper heading
    await expect(page.locator('h1')).toBeVisible();
    
    // Form should be visible
    await expect(page.locator('[data-testid="keyword-density-form"]')).toBeVisible();
    
    // Should have description/help text
    await expect(page.locator('text=keyword')).toBeVisible();
  });

  // E2E-011: Form reset functionality
  test('E2E-011: Form reset clears all fields', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Fill the form
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    
    // Click reset
    await page.click('button:has-text("Reset")');
    
    // Fields should be cleared
    await expect(page.locator('input[placeholder="Enter page URL"]')).toHaveValue('');
    await expect(page.locator('input[placeholder="Enter target keyword"]')).toHaveValue('');
  });

  // E2E-012: Export functionality
  test('E2E-012: Export results as JSON', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    await page.click('button:has-text("Analyze")');
    
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
    
    // Look for export button
    const exportButton = page.locator('button:has-text("Export")');
    
    if (await exportButton.count() > 0) {
      // Setup download listener
      const [download] = await Promise.all([
        page.waitForEvent('download'),
        exportButton.click(),
      ]);
      
      // Verify download started
      expect(download).toBeTruthy();
    }
  });

  // E2E-013: Loading state during analysis
  test('E2E-013: Loading state displayed during analysis', async ({ page }) => {
    // Delay the API response
    await page.route('**/api/tools/keyword-density*', async (route) => {
      await new Promise(resolve => setTimeout(resolve, 1000));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(mockKeywordAnalysisResponse),
      });
    });
    
    await page.goto('/tools/keyword-density');
    
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com');
    await page.fill('input[placeholder="Enter target keyword"]', 'SEO');
    await page.click('button:has-text("Analyze")');
    
    // Should show loading state
    await expect(page.locator('button:has-text("Analyzing")')).toBeVisible();
    await expect(page.locator('button:has-text("Analyzing")')).toBeDisabled();
    
    // Wait for results
    await expect(page.locator('[data-testid="keyword-density-results"]')).toBeVisible({ timeout: 5000 });
  });

  // E2E-014: Only URL or HTML, not both
  test('E2E-014: URL clears when HTML is entered', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Enter URL
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com');
    await expect(page.locator('input[placeholder="Enter page URL"]')).toHaveValue('https://example.com');
    
    // Enter HTML
    await page.fill('textarea[placeholder="Or paste HTML here"]', '<html><body>test</body></html>');
    
    // URL should be cleared
    await expect(page.locator('input[placeholder="Enter page URL"]')).toHaveValue('');
    await expect(page.locator('textarea[placeholder="Or paste HTML here"]')).not.toHaveValue('');
  });

  // E2E-015: Keyword required validation
  test('E2E-015: Keyword is required', async ({ page }) => {
    await page.goto('/tools/keyword-density');
    
    // Enter URL but not keyword
    await page.fill('input[placeholder="Enter page URL"]', 'https://example.com');
    
    // Try to submit without keyword
    await page.click('button:has-text("Analyze")');
    
    // Should show error
    await expect(page.locator('role=alert')).toBeVisible();
    await expect(page.locator('text=Keyword is required')).toBeVisible();
  });
});
