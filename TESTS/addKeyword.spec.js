const { test, expect } = require('@playwright/test');

test('E2E: Add new keyword → view list → see history', async ({ page }) => {
  // Navigate to the app
  await page.goto('http://localhost:3000');

  // Add a new keyword
  await page.fill('#keywordInput', 'playwright-test-keyword');
  await page.click('#submitKeyword');

  // Verify the keyword is in the list
  const keyword = await page.textContent('#keywordList > li:last-child');
  expect(keyword).toBe('playwright-test-keyword');

  // View keyword history
  await page.click('#keywordList > li:last-child > .viewHistory');
  const historyExists = await page.isVisible('#historyModal');
  expect(historyExists).toBe(true);
});