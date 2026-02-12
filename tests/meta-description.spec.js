import { test, expect } from '@playwright/test';

/**
 * Test for verifying the Meta Description analysis functionality
 */
test('Meta Description Analysis: should analyze meta description on a test page', async ({ page }) => {
    // Navigate to test page
    await page.goto('http://localhost:3000');

    // Input URL or meta description for analysis
    await page.fill('input[name="url-or-description"]', 'http://example.com');

    // Trigger the analysis
    await page.click('button#analyze-button');

    // Assert: Validate analysis results appear
    const analysisResults = await page.textContent('#analysis-results');
    expect(analysisResults).toContain('Meta Description is optimized');
});