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
  assert(
    homeText.includes('Find out what is holding your'),
    'Homepage missing expected hero text'
  );
  // The status bar reads the real backend health endpoint, so it can report
  // "Unreachable" when the API is down. Any of the known states is a pass -
  // what matters is that the bar rendered at all.
  assert(
    /System Status: (Operational|Degraded|Unreachable)/.test(homeText),
    'Homepage missing system status text'
  );
  console.log('✓ Homepage loaded');

  // The category filters must be present and usable.
  const tabLabels = await page.locator('[role="tab"]').allInnerTexts();
  for (const label of ['All', 'Rankings', 'Traffic', 'Technical']) {
    assert(tabLabels.includes(label), `Homepage missing "${label}" filter`);
  }
  console.log('✓ Homepage filters present');

  await page.goto(`${baseUrl}/login/`, { waitUntil: 'networkidle' });
  assert(/login/i.test(page.url()), `Login page URL mismatch: ${page.url()}`);
  const loginText = await page.locator('body').innerText();
  assert(/sign in/i.test(loginText), 'Login page missing sign in content');
  const emailInput = page.locator('input[name="email"], input[type="email"]');
  assert(await emailInput.count() > 0, 'Login page missing email input');
  console.log('✓ Login page loaded');

  // An anonymous visitor must be able to reach these pages. They used to
  // bounce to /login, which made the site impossible to browse.
  for (const path of ['/dashboard/', '/projects/', '/competitors/', '/notifications/']) {
    await page.goto(`${baseUrl}${path}`, { waitUntil: 'networkidle' });
    assert(
      !/^\/login\//.test(new URL(page.url()).pathname),
      `${path} redirected an anonymous visitor to the login page`
    );
  }
  console.log('✓ Guest-viewable pages reachable without an account');

  // The footer links must resolve - these 404'd before.
  for (const path of ['/privacy/', '/contact/', '/terms/']) {
    const response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded' });
    assert(
      response && response.status() === 200,
      `${path} returned ${response ? response.status() : 'no response'}`
    );
  }
  console.log('✓ Footer legal and contact links resolve');

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
