const { test, expect } = require('@playwright/test');

test.describe('Debug Mode Flow', () => {
  test('should complete debug mode flow from activation to form submission', async ({ page }) => {
    // 1) Open a new headed window with a fresh session
    // (Playwright automatically provides a fresh session for each test)
    
    // 2) Go to page "/?debug=1"
    await page.goto('/?debug=1');
    
    // 3) Make sure the div "Debug Mode Active" (having CSS class "alert-warning") is visible
    const debugBanner = page.locator('.alert-warning:has-text("Debug Mode Active")');
    await expect(debugBanner).toBeVisible();
    
    // 4) Enter domain "raitisozols.com" in the input form
    const domainInput = page.locator('input[name="domain"]');
    await domainInput.fill('raitisozols.com');
    
    // 5) Click on submit button
    const submitButton = page.locator('button[type="submit"]');
    await submitButton.click();
    
    // 6) Make sure the submit of the form is successfully completed
    // Wait for the page to redirect and load
    await page.waitForLoadState('networkidle');
    
    // 7) Make sure the request is processed by seeing the user's entered domain name in the overall score container
    // Wait for the domain name to appear in the strong text within the h2 element
    await expect(page.locator('.overall-score-container h2 strong:has-text("raitisozols.com")')).toBeVisible();
    
    // 8) Verify that debug mode is still active after form submission
    await expect(page.locator('.alert-warning:has-text("Debug Mode Active")')).toBeVisible();

    // 9) Look for any debug information of OpenRouter API calls
      const debugMessage = page.locator('text=OpenRouter API Status');
    await expect(debugMessage).toBeVisible();
  });
});
