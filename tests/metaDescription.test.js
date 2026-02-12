// meta-description-indicator.spec.js

const { test } = require('@playwright/test');

test.describe('SEO Indicator Tests', () => {
  test('Meta Description AI Analysis Validation', async ({ page }) => {
    await page.goto('http://localhost/');

    await page.fill('#domain-input', 'example.com');
    await page.click('#start-analysis-btn');
    await page.waitForSelector('.progress-completed');

    const result = await page.textContent('.indicator-meta-description');
    expect(result).toContain('Static Analysis:');
    expect(result).toContain('AI:');
  });
});