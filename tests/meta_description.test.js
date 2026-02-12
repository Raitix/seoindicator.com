// This suite tests functionality for Meta Description indicator expansion

const { test, expect } = require('@playwright/test');

test.describe('Meta Description Analysis', async () => {
  test('Check AI integration with meta description analysis results', async ({ page }) => {
    // Load the testing URL
    await page.goto('http://localhost:3000');

    // Enter website URL for scanning
    await page.fill('#website-url-input', 'https://example.com');
    await page.click('#start-analysis-btn');

    // Fetch Meta Description Analysis Results
    const metaDescriptionElement = await page.locator('#meta-description-analysis');
    await expect(metaDescriptionElement).toBeVisible();

    // Ensure AI-generated meta description suggestion exists
    const generatedSuggestion = await metaDescriptionElement.textContent();
    expect(generatedSuggestion).not.toBeNull();
    expect(generatedSuggestion).toContain('AI Analysis: Meta description covers keywords well. Suggestions follow ..etc');
 }); });