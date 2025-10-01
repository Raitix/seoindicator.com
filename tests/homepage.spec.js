const { test, expect } = require('@playwright/test');

test.describe('SEOIndicator.com Homepage', () => {
  test('should have correct page title', async ({ page }) => {
    // Navigate to the homepage
    await page.goto('/');
    
    // Wait for the page to load completely
    await page.waitForLoadState('networkidle');
    
    // Check that the page title is correct
    await expect(page).toHaveTitle('SEOIndicator.com - AI Powered SEO Analysis');
  });

  test('should load the homepage successfully', async ({ page }) => {
    // Navigate to the homepage
    const response = await page.goto('/');
    
    // Check that the page loads successfully (status 200)
    expect(response.status()).toBe(200);
    
    // Wait for the page to load completely
    await page.waitForLoadState('networkidle');
    
    // Check that the page title is present
    await expect(page).toHaveTitle(/SEOIndicator.com/);
  });

  test('should have visible content on the page', async ({ page }) => {
    // Navigate to the homepage
    await page.goto('/');
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    
    // Check that the page has some visible content
    const body = page.locator('body');
    await expect(body).toBeVisible();
    
    // Check that the page is not empty
    const textContent = await body.textContent();
    expect(textContent.length).toBeGreaterThan(0);
  });
});
