const { test, expect } = require('@playwright/test');

test.describe('Meta Description Analysis', () => {
  test('should analyze and provide recommendations for meta descriptions', async ({ page }) => {
    await page.goto('http://localhost:3000');

    // Navigate to the Meta Description Analyzer
    await page.click('text=Meta Description Analyzer');

    // Input meta description for analysis
    await page.fill('#meta-description-input', 'This is a sample meta description for SEO testing.');
    await page.click('#analyze-button');

    // Verify the analysis result
    const result = await page.textContent('#analysis-result');
    expect(result).toContain('recommendation'); // Ensure a recommendation is provided for improvement
  });
});