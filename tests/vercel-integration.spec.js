const { test, expect } = require('@playwright/test');
const path = require('path');

test.describe('Vercel AI Integration Test', () => {
  test('should analyze raitisozols.com and get results from Vercel AI', async ({ page }) => {
    // Set longer timeout for this test
    test.setTimeout(120000); // 2 minutes
    
    // Navigate to the configured base URL
    console.log('Navigating to base URL');
    const response = await page.goto('/');
    console.log('Response status:', response.status());
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    console.log('Page loaded');
    
    // Check page title
    const title = await page.title();
    console.log('Page title:', title);
    
    // Get page content for debugging
    const bodyText = await page.locator('body').textContent();
    console.log('Body text (first 200 chars):', bodyText.substring(0, 200));
    
    // Check for PHP errors
    if (bodyText.includes('Parse error') || bodyText.includes('Fatal error') || bodyText.includes('Warning:')) {
      console.log('PHP ERROR DETECTED!');
      console.log(bodyText.substring(0, 1000));
      throw new Error('PHP error on page');
    }
    
    // Check that the form exists
    const form = page.locator('#seo-form');
    await expect(form).toBeVisible();
    console.log('Form is visible');
    
    // Enter domain
    const domainInput = page.locator('#domain-input');
    await domainInput.fill('raitisozols.com');
    console.log('Domain entered: raitisozols.com');
    
    // Listen for console messages and errors
    page.on('console', msg => console.log('BROWSER:', msg.text()));
    page.on('pageerror', err => console.log('PAGE ERROR:', err.message));
    
    // Submit form
    const submitButton = page.locator('button[type="submit"]');
    await submitButton.click();
    console.log('Form submitted');
    
    // Wait a bit to see what happens
    await page.waitForTimeout(2000);
    
    // Check progress bar visibility
    const progressContainer = page.locator('.progress-container');
    const isVisible = await progressContainer.isVisible();
    console.log('Progress bar visible:', isVisible);
    
    // Check for any error messages
    const errorMessages = page.locator('#error-messages');
    const errorText = await errorMessages.textContent();
    if (errorText) {
      console.log('Error message on page:', errorText);
    }
    
    // Try to wait for progress bar if not visible yet
    if (!isVisible) {
      console.log('Waiting for progress bar to appear...');
      await expect(progressContainer).toBeVisible({ timeout: 10000 });
    }
    console.log('Progress bar appeared');
    
    // Wait for analysis to complete (progress bar to disappear or reach 100%)
    console.log('Waiting for analysis to complete...');
    await page.waitForTimeout(5000); // Give it some time to start
    
    // Check if we have results
    const resultsTable = page.locator('#results-tbody tr');
    await expect(resultsTable.first()).toBeVisible({ timeout: 60000 });
    console.log('Results table visible');
    
    // Wait for at least some indicators to be processed
    await page.waitForTimeout(10000);
    
    // Check if URL indicator has been processed
    const urlRow = page.locator('tr[data-indicator="url"]');
    await expect(urlRow).toBeVisible();
    
    const scoreCell = urlRow.locator('.score-cell');
    const scoreCellText = await scoreCell.textContent();
    console.log('URL indicator score:', scoreCellText);
    
    // Check if score is not just empty stars
    expect(scoreCellText).not.toBe('☆☆☆');
    
    // Check if URL indicator has AI response in suggestion
    const suggestionCell = urlRow.locator('.suggestion-cell');
    const suggestionText = await suggestionCell.textContent();
    console.log('URL indicator suggestion:', suggestionText);
    
    // Verify that AI response is present (should contain "AI:" text)
    expect(suggestionText).toContain('AI:');
    expect(suggestionText).toContain('Static Analysis:');
    
    // Verify that AI suggestion is not just "No AI suggestion available"
    expect(suggestionText).not.toContain('No AI suggestion available');
    
    // Log all results for debugging
    const allRows = await resultsTable.count();
    console.log(`Total indicator rows: ${allRows}`);
    
    for (let i = 0; i < Math.min(allRows, 5); i++) {
      const row = resultsTable.nth(i);
      const indicator = await row.getAttribute('data-indicator');
      const score = await row.locator('.score-cell').textContent();
      const result = await row.locator('.result-cell').textContent();
      console.log(`${indicator}: ${score} - ${result.substring(0, 50)}...`);
    }
    
    // Take a screenshot for verification with timestamp
    const timestamp = new Date().toLocaleDateString('en-GB').replace(/\//g, '-') + '_' + 
                     new Date().toLocaleTimeString('en-GB', {hour12: false}).replace(/:/g, '-');
    
    // Determine output directory based on environment
    const isProduction = process.env.BASE_URL === 'https://seoindicator.com' || process.env.CI;
    const outputDir = isProduction ? 'prod-test-results' : 'local-test-results';
    const screenshotPath = path.join(__dirname, '..', outputDir, `vercel-test-result_${timestamp}.png`);
    
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log(`Screenshot saved: ${screenshotPath}`);
  });
});

