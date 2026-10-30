import { chromium } from 'playwright';

const baseUrl = process.env.PLAYWRIGHT_BASE_URL || 'https://seoindicator.com';

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

async function checkPages(browser) {
  const page = await browser.newPage();

  await page.goto(baseUrl, { waitUntil: 'networkidle' });
  const homeTitle = await page.title();
  assert(/SEOIndicator/i.test(homeTitle), `Homepage title mismatch: ${homeTitle}`);
  const homeText = await page.locator('body').innerText();
  assert(homeText.includes('Track Your SEO Performance'), 'Homepage missing expected hero text');
  assert(homeText.includes('System Status: Operational'), 'Homepage missing system status text');
  console.log('✓ Homepage loaded');

  await page.goto(`${baseUrl}/login/`, { waitUntil: 'networkidle' });
  assert(/login/i.test(page.url()), `Login page URL mismatch: ${page.url()}`);
  const loginText = await page.locator('body').innerText();
  assert(/sign in/i.test(loginText), 'Login page missing sign in content');
  const emailInput = page.locator('input[name="email"], input[type="email"]');
  assert(await emailInput.count() > 0, 'Login page missing email input');
  console.log('✓ Login page loaded');

  await page.close();
}

async function main() {
  console.log(`Running production smoke checks against ${baseUrl}`);

  const browser = await chromium.launch({ headless: true });
  try {
    await checkPages(browser);
  } finally {
    await browser.close();
  }

  console.log('✓ Production smoke checks passed');
}

main().catch((error) => {
  console.error(error.stack || error.message || String(error));
  process.exit(1);
});
