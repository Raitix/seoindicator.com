const { test, expect } = require('@playwright/test');

test.describe('UI Responsiveness Check', () => {
  test('Home page layout adjusts dynamically', async ({ page }) => {
    // Modify base URL to match production/local endpoint as needed
    await page.goto('/');

    // Check desktop view responsiveness
    await page.setViewportSize({ width: 1920, height: 1080 });
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();

    // Check tablet view responsiveness
    await page.setViewportSize({ width: 768, height: 1024 });
    await expect(page.locator('nav')).toBeVisible(); // Example navbar breakpoint visibility

    // Check mobile view responsiveness
    await page.setViewportSize({ width: 375, height: 667 });
    await expect(page.locator('button.menu-toggle')).toBeVisible();
  });
});