import { test, expect, Page } from '@playwright/test';

/**
 * E2E Tests for Analysis History Page Flow
 * Tests: History page loads, filters, pagination, details view, delete
 */

// Mock analysis history data
const mockAnalysesResponse = {
  success: true,
  data: [
    {
      id: 127,
      toolName: "meta-tag-analyzer",
      toolDisplayName: "Meta Tag Analyzer",
      inputUrl: "https://example.com",
      status: "completed",
      cost: { total: 0.05, currency: "USD" },
      analysisDurationMs: 1234,
      analyzedAt: "2026-03-01T10:00:00Z",
    },
    {
      id: 126,
      toolName: "keyword-density",
      toolDisplayName: "Keyword Density Checker",
      inputUrl: "https://test.com/blog/post",
      status: "failed",
      cost: { total: 0, currency: "USD" },
      analysisDurationMs: 5000,
      analyzedAt: "2026-03-01T09:30:00Z",
      errorMessage: "Connection timeout",
    },
    {
      id: 125,
      toolName: "mobile-friendly",
      toolDisplayName: "Mobile-Friendly Checker",
      inputUrl: "https://demo.org",
      status: "pending",
      cost: { total: 0, currency: "USD" },
      analysisDurationMs: null,
      analyzedAt: "2026-03-01T09:00:00Z",
    },
  ],
  meta: {
    total: 50,
    page: 1,
    perPage: 15,
    lastPage: 4,
  },
};

const mockAnalysisDetail = {
  success: true,
  data: {
    id: 127,
    toolName: "meta-tag-analyzer",
    toolDisplayName: "Meta Tag Analyzer",
    inputUrl: "https://example.com",
    status: "completed",
    cost: {
      total: 0.05,
      breakdown: [
        { type: "base", amount: 0.01, description: "Base analysis cost" },
        { type: "compute", amount: 0.04, description: "Processing time" },
      ],
      currency: "USD",
    },
    analysisDurationMs: 1234,
    analyzedAt: "2026-03-01T10:00:00Z",
    results: {
      title: { content: "Example Domain", length: 14 },
      metaDescription: { content: "This domain is for use in illustrative examples", length: 50 },
      h1: { count: 1, content: ["Example Domain"] },
    },
  },
};

// Helper to set up API mocks
async function setupMocks(page: Page) {
  // Mock analyses list API
  await page.route('**/api/v1/analyses*', async (route) => {
    const url = new URL(route.request().url());
    const pageParam = url.searchParams.get('page') || '1';
    
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        ...mockAnalysesResponse,
        meta: {
          ...mockAnalysesResponse.meta,
          page: parseInt(pageParam),
        },
      }),
    });
  });

  // Mock analysis detail API
  await page.route('**/api/v1/analyses/*', async (route) => {
    const url = route.request().url();
    
    if (route.request().method() === 'DELETE') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true }),
      });
    } else {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(mockAnalysisDetail),
      });
    }
  });

  // Mock tools metadata
  await page.route('**/api/v1/metadata/tools*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        tools: [
          { value: "meta-tag-analyzer", label: "Meta Tag Analyzer" },
          { value: "keyword-density", label: "Keyword Density Checker" },
          { value: "mobile-friendly", label: "Mobile-Friendly Checker" },
        ],
      }),
    });
  });
}

test.describe('Analysis History E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await setupMocks(page);
  });

  test('E2E-001: History page loads with correct data', async ({ page }) => {
    // Navigate to history page
    await page.goto('/dashboard/history');
    
    // Wait for the page to load
    await expect(page.locator('h1:has-text("Analysis History")')).toBeVisible();
    
    // Verify table is visible
    await expect(page.getByRole('table')).toBeVisible();
    
    // Verify column headers
    await expect(page.getByRole('columnheader', { name: 'Tool' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'URL' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible();
    
    // Verify data rows
    await expect(page.getByText('Meta Tag Analyzer')).toBeVisible();
    await expect(page.getByText('Keyword Density Checker')).toBeVisible();
  });

  test('E2E-002: Filter by tool', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    // Wait for page load
    await expect(page.getByRole('table')).toBeVisible();
    
    // Select tool filter
    const toolFilter = page.getByLabel(/filter by tool/i);
    await toolFilter.selectOption('meta-tag-analyzer');
    
    // URL should contain filter param
    await expect(page).toHaveURL(/tool=meta-tag-analyzer/);
  });

  test('E2E-003: Filter by status', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Select status filter
    const statusFilter = page.getByLabel(/filter by status/i);
    await statusFilter.selectOption('completed');
    
    // URL should contain filter param
    await expect(page).toHaveURL(/status=completed/);
  });

  test('E2E-004: Date range filter', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Set date range
    const fromDate = page.getByLabel(/from date/i);
    const toDate = page.getByLabel(/to date/i);
    
    await fromDate.fill('2026-02-01');
    await toDate.fill('2026-02-28');
    
    // URL should contain date params
    await expect(page).toHaveURL(/from=2026-02-01/);
    await expect(page).toHaveURL(/to=2026-02-28/);
  });

  test('E2E-005: Clear filters', async ({ page }) => {
    // Start with filters in URL
    await page.goto('/dashboard/history?tool=meta-tag-analyzer&status=completed');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Click clear filters button
    await page.click('button:has-text("Clear Filters")');
    
    // URL should be clean
    await expect(page).toHaveURL(/\/dashboard\/history$/);
  });

  test('E2E-006: Pagination navigation', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Click next page
    await page.click('button:has-text("Next")');
    
    // URL should contain page param
    await expect(page).toHaveURL(/page=2/);
    
    // Previous button should be enabled now
    const prevButton = page.getByRole('button', { name: /previous/i });
    await expect(prevButton).toBeEnabled();
  });

  test('E2E-007: View analysis details', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Click view button on first row
    await page.click('button:has-text("View")');
    
    // Details modal should open
    await expect(page.getByRole('dialog')).toBeVisible();
    
    // Verify detail content
    await expect(page.getByText('Example Domain')).toBeVisible();
  });

  test('E2E-008: Delete analysis', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Count initial rows
    const initialRows = await page.getByRole('row').count();
    
    // Click delete button on first row
    await page.click('button[aria-label*="delete"]:first-of-type');
    
    // Confirmation dialog should appear
    await expect(page.getByRole('alertdialog')).toBeVisible();
    await expect(page.getByText(/are you sure/i)).toBeVisible();
    
    // Confirm delete
    await page.click('button:has-text("Delete")');
    
    // Dialog should close
    await expect(page.getByRole('alertdialog')).not.toBeVisible();
    
    // Success message should appear
    await expect(page.getByText(/deleted successfully/i)).toBeVisible();
  });

  test('E2E-009: Cancel delete', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Click delete button
    await page.click('button[aria-label*="delete"]:first-of-type');
    
    // Confirmation dialog should appear
    await expect(page.getByRole('alertdialog')).toBeVisible();
    
    // Click cancel
    await page.click('button:has-text("Cancel")');
    
    // Dialog should close without deleting
    await expect(page.getByRole('alertdialog')).not.toBeVisible();
    
    // Rows should still be present
    const rows = await page.getByRole('row').count();
    expect(rows).toBeGreaterThan(1);
  });

  test('E2E-010: Empty state with no analyses', async ({ page }) => {
    // Override mock to return empty data
    await page.route('**/api/v1/analyses*', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: [],
          meta: { total: 0, page: 1, perPage: 15, lastPage: 1 },
        }),
      });
    });
    
    await page.goto('/dashboard/history');
    
    // Empty state should be visible
    await expect(page.getByText(/no analyses/i)).toBeVisible();
    
    // CTA button should be present
    await expect(page.getByRole('link', { name: /run.*analysis/i }) ||
                page.getByRole('button', { name: /run.*analysis/i })).toBeVisible();
  });

  test('E2E-011: Error state handling', async ({ page }) => {
    // Override mock to return error
    await page.route('**/api/v1/analyses*', async (route) => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          success: false,
          message: 'Internal server error',
        }),
      });
    });
    
    await page.goto('/dashboard/history');
    
    // Error message should be visible
    await expect(page.getByText(/error|failed/i)).toBeVisible();
    
    // Retry button should be present
    await expect(page.getByRole('button', { name: /retry/i })).toBeVisible();
  });

  test('E2E-012: Status badges with correct colors', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Completed badge should be green
    const completedBadge = page.getByText('Completed');
    await expect(completedBadge).toHaveClass(/emerald|green/i);
    
    // Failed badge should be red
    const failedBadge = page.getByText('Failed');
    await expect(failedBadge).toHaveClass(/rose|red/i);
    
    // Pending badge should be yellow
    const pendingBadge = page.getByText('Pending');
    await expect(pendingBadge).toHaveClass(/amber|yellow/i);
  });

  test('E2E-013: Responsive layout on mobile', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/dashboard/history');
    
    // Page should render without horizontal overflow
    await expect(page.locator('h1:has-text("Analysis History")')).toBeVisible();
    
    // Table should be horizontally scrollable or have card layout
    const container = page.getByTestId('table-container') || 
                      page.locator('.overflow-x-auto');
    await expect(container).toBeVisible();
  });

  test('E2E-014: URL state persistence', async ({ page }) => {
    // Navigate with filters in URL
    await page.goto('/dashboard/history?tool=meta-tag-analyzer&status=completed&page=2');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Filters should be pre-populated
    const toolFilter = page.getByLabel(/filter by tool/i);
    await expect(toolFilter).toHaveValue('meta-tag-analyzer');
    
    const statusFilter = page.getByLabel(/filter by status/i);
    await expect(statusFilter).toHaveValue('completed');
  });

  test('E2E-015: Keyboard navigation', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Tab to first interactive element
    await page.keyboard.press('Tab');
    
    // Should be able to navigate through filters and table
    const focusedElement = page.locator(':focus');
    await expect(focusedElement).toBeVisible();
  });

  test('E2E-016: Details modal keyboard interactions', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Open details modal
    await page.click('button:has-text("View")');
    await expect(page.getByRole('dialog')).toBeVisible();
    
    // Press Escape to close
    await page.keyboard.press('Escape');
    
    // Modal should close
    await expect(page.getByRole('dialog')).not.toBeVisible();
  });

  test('E2E-017: Delete confirmation keyboard interactions', async ({ page }) => {
    await page.goto('/dashboard/history');
    
    await expect(page.getByRole('table')).toBeVisible();
    
    // Open delete dialog
    await page.click('button[aria-label*="delete"]:first-of-type');
    await expect(page.getByRole('alertdialog')).toBeVisible();
    
    // Press Escape to cancel
    await page.keyboard.press('Escape');
    
    // Dialog should close
    await expect(page.getByRole('alertdialog')).not.toBeVisible();
  });
});
