// ai-meta-description.spec.js - Playwright test for Meta Description AI analysis

const { expect, test } = require('@playwright/test');

test.describe('Meta Description AI Analysis', () => {
  test('AI suggestion includes meta description analysis', async ({ page }) => {
    // Start analysis
    await page.goto('http://localhost/');
    await page.waitForSelector('#domain-input');
await page.fill('#domain-input', 'example.com');
    await page.click('#start-analysis-btn');

    // Wait for analysis completion
    await page.waitForSelector('#analysis-complete');

    // Check meta description analysis result
    const metaDescriptionAI = await page.textContent('#meta-description-result .ai-suggestion');
    expect(metaDescriptionAI).not.toBe('');
    expect(metaDescriptionAI).toContain('AI:');

    // Ensure static analysis and AI sections exist
    const staticAnalysis = await page.textContent('#meta-description-result .static-analysis');
    expect(staticAnalysis).toContain('Static Analysis:');
  });

  test('Standard meta description issues are flagged', async ({ page }) => {
    // Trigger with invalid meta description
    await page.goto('http://localhost/');
    await page.waitForSelector('#domain-input');
await page.fill('#domain-input', 'badexample.com');
    await page.click('#start-analysis-btn');

    // Wait for analysis completion
    await page.waitForSelector('#analysis-complete');

    // Check flagged issues
    const issues = await page.textContent('#meta-description-result .issues');
    expect(issues).toMatch(/too short|missing|too long/);
  });
});