# Automated Tests

This directory contains automated tests for the SEO Indicator application.

## Debug Mode Flow Test

**File:** `debug-mode-flow.spec.js`

**Purpose:** Tests the complete debug mode functionality from activation to form submission.

**Test Steps:**
1. Opens a new headed browser window with a fresh session
2. Navigates to `/?debug=1` to activate debug mode
3. Verifies the "Debug Mode Active" banner is visible (CSS class: `alert-warning`)
4. Enters domain "raitisozols.com" in the input form
5. Clicks the submit button
6. Verifies the form submission is successful (waits for network idle)
7. Verifies the request is processed by checking that the domain name appears in the overall score container (`<strong>raitisozols.com</strong>`)
8. Closes the browser window (automatic cleanup)

**Requirements:**
- XAMPP server running on `http://localhost/`
- Playwright installed (`npm install @playwright/test`)
- Run with: `npx playwright test tests/debug-mode-flow.spec.js --headed --reporter=list`

**Expected Behavior:**
- Debug banner should be visible when `?debug=1` is in the URL
- Form submission should work correctly
- The domain name should appear in the overall score container after processing
- Test execution time is optimized (no unnecessary waits)