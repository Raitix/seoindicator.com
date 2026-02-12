const { test, expect } = require('@playwright/test');

test('Basic Test for Framework Validation', async ({ page }) => {
  await page.goto('http://localhost/');
  const title = await page.title();
  console.log(`Page title: ${title}`);
  expect(title).not.toBeNull();
});