const { test, expect } = require('@playwright/test');

test.describe('Debug Mode Flow', () => {
  test('should complete debug mode flow from activation to form submission', async ({ page }) => {
    // 1) Open a new headed window with a fresh session
    // (Playwright automatically provides a fresh session for each test)
    
    // 2) Go to page "/?debug=1" first
    await page.goto('/?debug=1');

    // 3) Verify that the URL indicator shows initial values
    const urlRow = page.locator('tr[data-indicator="url"]');
    await expect(urlRow.locator('.score-cell')).toContainText('-');
    await expect(urlRow.locator('.result-cell')).toContainText('-');
    await expect(urlRow.locator('.suggestion-cell')).toContainText('-');
    
    // 4) Make sure the div "Debug Mode Active" (having CSS class "alert-warning") is visible
    const debugBanner = page.locator('.alert-warning:has-text("Debug Mode Active")');
    await expect(debugBanner).toBeVisible();
    
    // 5) Enter domain "raitisozols.com" in the input form
    const domainInput = page.locator('input[name="domain"]');
    await domainInput.fill('raitisozols.com');
    
    // 6) Click on submit button
    const submitButton = page.locator('button[type="submit"]');
    await submitButton.click();
    
    // 7) Make sure the submit of the form is successfully completed
    // Wait for the page to redirect and load
    await page.waitForLoadState('networkidle');
    
    // 8) Make sure the request is processed by seeing the user's entered domain name in the overall score container
    // Wait for the domain name to appear in the strong text within the h2 element
    await expect(page.locator('.overall-score-container h2 strong:has-text("raitisozols.com")')).toBeVisible();
    
    // 9) Verify that debug mode is still active after form submission
    await expect(page.locator('.alert-warning:has-text("Debug Mode Active")')).toBeVisible();

    // 10) Wait for URL indicator to be processed and show results
    await page.waitForTimeout(10000); // Wait longer for analysis to complete
    
    // Check console logs for debugging
    const logs = [];
    page.on('console', msg => {
        logs.push(msg.text());
        console.log('Browser console:', msg.text());
    });
    
    // Wait for console logs to appear
    await page.waitForTimeout(2000);
    
    // Check if URL indicator has been updated (not showing "-" anymore)
    const urlRowAfter = page.locator('tr[data-indicator="url"]');
    const scoreCell = urlRowAfter.locator('.score-cell');
    const resultCell = urlRowAfter.locator('.result-cell');
    
    // Log the current state
    const scoreText = await scoreCell.textContent();
    const resultText = await resultCell.textContent();
    const suggestionText = await urlRowAfter.locator('.suggestion-cell').textContent();
    console.log('URL indicator score:', scoreText);
    console.log('URL indicator result:', resultText);
    console.log('URL indicator suggestion:', suggestionText);
    
    // Check if DEBUG output is visible
    const debugOutput = page.locator('td:has-text("DEBUG:")');
    const isDebugVisible = await debugOutput.isVisible();
    if (isDebugVisible) {
        const debugText = await debugOutput.textContent();
        console.log('✅ Found DEBUG output in table:', debugText);
    } else {
        console.log('❌ DEBUG output not found in table');
    }
    
    // Check for URL analysis debug section
    const urlDebugSection = page.locator('.alert-success:has-text("URL Analysis Debug")');
    const isUrlDebugVisible = await urlDebugSection.isVisible();
    
    if (isUrlDebugVisible) {
        console.log('✅ URL analysis debug section is visible');
        const debugContent = await urlDebugSection.textContent();
        console.log('URL analysis debug content:', debugContent);
    } else {
        console.log('❌ URL analysis debug section is NOT visible');
    }
    
    // Check the HTML source for debug comments
    const htmlContent = await page.content();
    
    // Check for the table generation debug comment
    const tableDebugMatch = htmlContent.match(/<!-- DEBUG: URL result at table generation = (.+?) -->/);
    if (tableDebugMatch) {
        console.log('✅ Found table generation debug comment:', tableDebugMatch[1]);
    } else {
        console.log('❌ Table generation debug comment not found');
    }
    
    // Check for the original debug comment
    const debugCommentMatch = htmlContent.match(/<!-- DEBUG: URL result = (.+?) -->/);
    if (debugCommentMatch) {
        console.log('✅ Found URL debug comment in HTML:', debugCommentMatch[1]);
    } else {
        console.log('❌ URL debug comment not found in HTML');
    }
    
    // Check if URL indicator has been updated
    if (scoreText !== '-') {
        console.log('✅ URL indicator has been updated with score:', scoreText);
    } else {
        console.log('❌ URL indicator still shows "-" - analysis may not be working');
    }

  });
});
