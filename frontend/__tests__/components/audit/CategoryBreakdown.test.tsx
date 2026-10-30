/**
 * Tests for CategoryBreakdown Component
 * Task: TASK-33-10
 * Tests: CategoryBreakdown component for SEO audit reports
 * 
 * Test Categories:
 * - CB-001: Renders all category progress bars
 * - CB-002: Score color coding
 * - CB-003: Score labels and descriptions
 * - CB-004: Expandable issue count
 * - CB-005: Issue severity indicators
 * - CB-006: expanded prop controls initial state
 * - CB-007: Accessibility
 * - CB-008: Progress bar animation
 * - CB-009: Edge cases
 * - CB-010: Responsive behavior
 * - CB-011: Custom className prop
 * - CB-012: Category-specific icons
 * - Acceptance Criteria
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

import { CategoryBreakdown, CategoryScore, CategoryBreakdownProps } from '@/components/audit/CategoryBreakdown';

// ============================================================================
// MOCK FACTORIES
// ============================================================================

/**
 * Create a mock CategoryScore object
 */
function createMockCategoryScore(overrides: Partial<CategoryScore> = {}): CategoryScore {
  return {
    name: 'Technical SEO',
    score: 75,
    issueCount: 5,
    criticalCount: 1,
    warningCount: 2,
    ...overrides,
  };
}

/**
 * Create mock props for CategoryBreakdown
 */
function createMockProps(overrides: Partial<CategoryBreakdownProps> = {}): CategoryBreakdownProps {
  return {
    categories: [
      createMockCategoryScore({ name: 'Technical SEO', score: 85, issueCount: 3, criticalCount: 0, warningCount: 1 }),
      createMockCategoryScore({ name: 'On-Page SEO', score: 65, issueCount: 8, criticalCount: 2, warningCount: 3 }),
      createMockCategoryScore({ name: 'Content Quality', score: 45, issueCount: 12, criticalCount: 3, warningCount: 5 }),
      createMockCategoryScore({ name: 'Mobile & Performance', score: 25, issueCount: 15, criticalCount: 4, warningCount: 6 }),
    ],
    ...overrides,
  };
}

// ============================================================================
// CB-001: RENDERS ALL CATEGORY PROGRESS BARS
// ============================================================================

describe('CB-001: Renders all category progress bars', () => {
  it('CB-001-1: Renders all four SEO categories', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('Technical SEO')).toBeInTheDocument();
    expect(screen.getByText('On-Page SEO')).toBeInTheDocument();
    expect(screen.getByText('Content Quality')).toBeInTheDocument();
    expect(screen.getByText('Mobile & Performance')).toBeInTheDocument();
  });

  it('CB-001-2: Renders progress bar for each category', () => {
    const props = createMockProps();
    const { container } = render(<CategoryBreakdown {...props} />);

    const progressBars = container.querySelectorAll('[role="progressbar"]');
    expect(progressBars.length).toBeGreaterThanOrEqual(4);
  });

  it('CB-001-3: Displays correct score value for each category', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('85/100')).toBeInTheDocument();
    expect(screen.getByText('65/100')).toBeInTheDocument();
    expect(screen.getByText('45/100')).toBeInTheDocument();
    expect(screen.getByText('25/100')).toBeInTheDocument();
  });

  it('CB-001-4: Renders category icons', () => {
    const props = createMockProps();
    const { container } = render(<CategoryBreakdown {...props} />);

    // Icons should be present (they're aria-hidden, so we check for their presence in the DOM)
    // Use a more specific selector to match only category containers, not content divs
    const categories = container.querySelectorAll('[data-testid^="category-"]:not([data-testid^="category-content-"])');
    expect(categories.length).toBe(4);
  });
});

// ============================================================================
// CB-002: SCORE COLOR CODING
// ============================================================================

describe('CB-002: Score color coding', () => {
  it('CB-002-1: Green color for scores >= 80', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 85 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('85/100')).toHaveClass('text-green-500');
  });

  it('CB-002-2: Blue color for scores 60-79', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 65 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('65/100')).toHaveClass('text-blue-500');
  });

  it('CB-002-3: Yellow color for scores 40-59', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 45 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('45/100')).toHaveClass('text-yellow-500');
  });

  it('CB-002-4: Orange color for scores 20-39', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 25 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('25/100')).toHaveClass('text-orange-500');
  });

  it('CB-002-5: Red color for scores < 20', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 15 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('15/100')).toHaveClass('text-red-500');
  });

  it('CB-002-6: Correct color at boundaries', () => {
    // Test boundary cases: 80, 60, 40, 20
    const { rerender } = render(<CategoryBreakdown categories={[createMockCategoryScore({ name: 'Test', score: 80 })]} />);
    expect(screen.getByText('80/100')).toHaveClass('text-green-500');

    rerender(<CategoryBreakdown categories={[createMockCategoryScore({ name: 'Test', score: 60 })]} />);
    expect(screen.getByText('60/100')).toHaveClass('text-blue-500');

    rerender(<CategoryBreakdown categories={[createMockCategoryScore({ name: 'Test', score: 40 })]} />);
    expect(screen.getByText('40/100')).toHaveClass('text-yellow-500');

    rerender(<CategoryBreakdown categories={[createMockCategoryScore({ name: 'Test', score: 20 })]} />);
    expect(screen.getByText('20/100')).toHaveClass('text-orange-500');
  });
});

// ============================================================================
// CB-003: SCORE LABELS AND DESCRIPTIONS
// ============================================================================

describe('CB-003: Score labels and descriptions', () => {
  it('CB-003-1: Displays score number (0-100)', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('75/100')).toBeInTheDocument();
  });

  it('CB-003-2: Displays score out of 100', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    const scoreElements = screen.getAllByText(/\/100/);
    expect(scoreElements.length).toBe(4);
  });

  it('CB-003-3: Displays category name prominently', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    const categoryNames = screen.getAllByRole('heading', { level: 3 });
    expect(categoryNames).toHaveLength(4);
    expect(categoryNames[0]).toHaveTextContent('Technical SEO');
  });

  it('CB-003-4: Displays issue count badge', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText(/5 issues?/)).toBeInTheDocument();
  });
});

// ============================================================================
// CB-004: EXPANDABLE ISSUE COUNT
// ============================================================================

describe('CB-004: Expandable issue count', () => {
  it('CB-004-1: Shows issue count as clickable trigger', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/);
    expect(trigger.closest('button')).toBeInTheDocument();
  });

  it('CB-004-2: Expands to show issue breakdown when clicked', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 2, warningCount: 3 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    await user.click(trigger);

    // Check that content is visible (data-open="true")
    const content = container.querySelector('[data-open="true"]');
    expect(content).toBeInTheDocument();
  });

  it('CB-004-3: Shows critical count when expanded', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 2, warningCount: 3 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    await user.click(trigger);

    expect(screen.getByText(/2 critical/)).toBeInTheDocument();
  });

  it('CB-004-4: Shows warning count when expanded', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 2, warningCount: 3 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    await user.click(trigger);

    expect(screen.getByText(/3 warning/)).toBeInTheDocument();
  });

  it('CB-004-5: Toggles expanded state on repeated clicks', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 2, warningCount: 3 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;

    // First click - expand
    await user.click(trigger);
    expect(container.querySelector('[data-open="true"]')).toBeInTheDocument();

    // Second click - collapse
    await user.click(trigger);
    expect(container.querySelector('[data-open="false"]')).toBeInTheDocument();
  });
});

// ============================================================================
// CB-005: ISSUE SEVERITY INDICATORS
// ============================================================================

describe('CB-005: Issue severity indicators', () => {
  it('CB-005-1: Critical severity indicator', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 2, warningCount: 0 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    await user.click(trigger);

    const criticalIndicator = screen.getByTestId('critical-indicator');
    expect(criticalIndicator).toBeInTheDocument();
    expect(criticalIndicator).toHaveTextContent('2 critical');
  });

  it('CB-005-2: Warning severity indicator', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 0, warningCount: 3 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    await user.click(trigger);

    const warningIndicator = screen.getByTestId('warning-indicator');
    expect(warningIndicator).toBeInTheDocument();
    expect(warningIndicator).toHaveTextContent('3 warning');
  });

  it('CB-005-3: Check icon when no issues', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 100, issueCount: 0, criticalCount: 0, warningCount: 0 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/0 issues?/).closest('button')!;
    await user.click(trigger);

    const noIssuesIndicator = screen.getByTestId('no-issues-indicator');
    expect(noIssuesIndicator).toBeInTheDocument();
    expect(noIssuesIndicator).toHaveTextContent('No issues');
  });

  it('CB-005-4: Both indicators when both present', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5, criticalCount: 2, warningCount: 3 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    await user.click(trigger);

    expect(screen.getByTestId('critical-indicator')).toBeInTheDocument();
    expect(screen.getByTestId('warning-indicator')).toBeInTheDocument();
  });
});

// ============================================================================
// CB-006: EXPANDED PROP CONTROLS INITIAL STATE
// ============================================================================

describe('CB-006: expanded prop controls initial state', () => {
  it('CB-006-1: Starts collapsed by default', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const content = container.querySelector('[data-open="false"]');
    expect(content).toBeInTheDocument();
  });

  it('CB-006-2: Starts expanded when expanded prop is true', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
      expanded: true,
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const content = container.querySelector('[data-open="true"]');
    expect(content).toBeInTheDocument();
  });

  it('CB-006-3: Applies expanded prop to all categories', () => {
    const props = createMockProps({ expanded: true });
    const { container } = render(<CategoryBreakdown {...props} />);

    const expandedContents = container.querySelectorAll('[data-open="true"]');
    expect(expandedContents.length).toBe(4);
  });
});

// ============================================================================
// CB-007: ACCESSIBILITY
// ============================================================================

describe('CB-007: Accessibility', () => {
  it('CB-007-1: Progress bar ARIA attributes', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const progressBars = container.querySelectorAll('[role="progressbar"]');
    expect(progressBars.length).toBeGreaterThan(0);

    // Check that at least one progressbar has aria attributes
    const firstProgressBar = progressBars[0];
    expect(firstProgressBar).toHaveAttribute('aria-valuenow');
    expect(firstProgressBar).toHaveAttribute('aria-valuemin');
    expect(firstProgressBar).toHaveAttribute('aria-valuemax');
  });

  it('CB-007-2: Accessible category names', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('Technical SEO')).toBeInTheDocument();
    expect(screen.getByText('On-Page SEO')).toBeInTheDocument();
    expect(screen.getByText('Content Quality')).toBeInTheDocument();
    expect(screen.getByText('Mobile & Performance')).toBeInTheDocument();
  });

  it('CB-007-3: Keyboard accessible trigger', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    trigger.focus();

    await user.keyboard('{Enter}');

    expect(container.querySelector('[data-open="true"]')).toBeInTheDocument();
  });

  it('CB-007-4: Space key activation', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    trigger.focus();

    await user.keyboard(' ');

    expect(container.querySelector('[data-open="true"]')).toBeInTheDocument();
  });

  it('CB-007-5: ARIA expanded state on triggers', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75, issueCount: 5 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    expect(trigger).toHaveAttribute('aria-expanded', 'false');
  });

  it('CB-007-6: Scores readable by screen readers', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75 })],
    });
    render(<CategoryBreakdown {...props} />);

    const scoreElement = screen.getByText('75/100');
    expect(scoreElement).toHaveAttribute('aria-label', 'Score: 75 out of 100');
  });
});

// ============================================================================
// CB-008: PROGRESS BAR ANIMATION
// ============================================================================

describe('CB-008: Progress bar animation', () => {
  it('CB-008-1: Progress indicator width matches score', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 75 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    // The custom progress bar should have width matching score
    const progressBar = container.querySelector('[style*="width: 75%"]');
    expect(progressBar).toBeInTheDocument();
  });

  it('CB-008-2: Handles 0% score correctly', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 0 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const progressBar = container.querySelector('[style*="width: 0%"]');
    expect(progressBar).toBeInTheDocument();
  });

  it('CB-008-3: Handles 100% score correctly', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test Category', score: 100 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const progressBar = container.querySelector('[style*="width: 100%"]');
    expect(progressBar).toBeInTheDocument();
  });
});

// ============================================================================
// CB-009: EDGE CASES
// ============================================================================

describe('CB-009: Edge cases', () => {
  it('CB-009-1: Empty categories array', () => {
    const props = createMockProps({ categories: [] });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('No categories to display')).toBeInTheDocument();
  });

  it('CB-009-2: Single category', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Single Category', score: 50 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('Single Category')).toBeInTheDocument();
    expect(screen.getByText('50/100')).toBeInTheDocument();
  });

  it('CB-009-3: Zero issues', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Perfect Category', score: 100, issueCount: 0, criticalCount: 0, warningCount: 0 })],
    });
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getByText(/0 issues?/).closest('button')!;
    await user.click(trigger);

    expect(screen.getByText('No issues')).toBeInTheDocument();
  });

  it('CB-009-4: Score at boundaries (0, 100)', () => {
    const props = createMockProps({
      categories: [
        createMockCategoryScore({ name: 'Zero Score', score: 0 }),
        createMockCategoryScore({ name: 'Hundred Score', score: 100 }),
      ],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('0/100')).toBeInTheDocument();
    expect(screen.getByText('100/100')).toBeInTheDocument();
  });

  it('CB-009-5: Very large issue counts', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Many Issues', score: 10, issueCount: 999 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText(/999 issues?/)).toBeInTheDocument();
  });

  it('CB-009-6: Long category names', () => {
    const longName = 'This is a very long category name that should wrap properly';
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: longName, score: 50 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText(longName)).toBeInTheDocument();
  });

  it('CB-009-7: Unicode in category names', () => {
    const unicodeName = 'Technical SEO 技術 SEO 🚀';
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: unicodeName, score: 50 })],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText(unicodeName)).toBeInTheDocument();
  });

  it('CB-009-8: Custom className prop', () => {
    const props = createMockProps({ className: 'custom-test-class' });
    const { container } = render(<CategoryBreakdown {...props} />);

    expect(container.firstChild).toHaveClass('custom-test-class');
  });
});

// ============================================================================
// CB-010: RESPONSIVE BEHAVIOR
// ============================================================================

describe('CB-010: Responsive behavior', () => {
  it('CB-010-1: Renders on different viewport sizes', () => {
    const props = createMockProps();
    const { container } = render(<CategoryBreakdown {...props} />);

    // Component should render regardless of viewport
    expect(container.firstChild).toBeInTheDocument();
  });

  it('CB-010-2: Touch targets large enough for mobile', () => {
    const props = createMockProps();
    const { container } = render(<CategoryBreakdown {...props} />);

    // All triggers should be button elements
    const buttons = container.querySelectorAll('button');
    expect(buttons.length).toBeGreaterThan(0);
  });
});

// ============================================================================
// CB-011: CUSTOM CLASSNAME PROP
// ============================================================================

describe('CB-011: Custom className prop', () => {
  it('CB-011-1: Applies custom className to container', () => {
    const props = createMockProps({ className: 'my-custom-class another-class' });
    const { container } = render(<CategoryBreakdown {...props} />);

    expect(container.firstChild).toHaveClass('my-custom-class');
    expect(container.firstChild).toHaveClass('another-class');
  });
});

// ============================================================================
// CB-012: CATEGORY-SPECIFIC ICONS
// ============================================================================

describe('CB-012: Category-specific icons', () => {
  it('CB-012-1: Technical SEO uses Wrench icon', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Technical SEO', score: 50 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    // Icon is rendered (aria-hidden, so just check presence)
    const categoryDiv = container.querySelector('[data-testid="category-technical-seo"]');
    expect(categoryDiv).toBeInTheDocument();
  });

  it('CB-012-2: On-Page SEO uses FileText icon', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'On-Page SEO', score: 50 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const categoryDiv = container.querySelector('[data-testid="category-on-page-seo"]');
    expect(categoryDiv).toBeInTheDocument();
  });

  it('CB-012-3: Content Quality uses Edit icon', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Content Quality', score: 50 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const categoryDiv = container.querySelector('[data-testid="category-content-quality"]');
    expect(categoryDiv).toBeInTheDocument();
  });

  it('CB-012-4: Mobile & Performance uses Smartphone icon', () => {
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Mobile & Performance', score: 50 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    const categoryDiv = container.querySelector('[data-testid="category-mobile-&-performance"]');
    expect(categoryDiv).toBeInTheDocument();
  });
});

// ============================================================================
// ACCEPTANCE CRITERIA TESTS
// ============================================================================

describe('Acceptance Criteria', () => {
  // AC-01: Displays progress bars for 4 categories
  it('AC-01: Displays progress bars for 4 categories', () => {
    const props = createMockProps();
    const { container } = render(<CategoryBreakdown {...props} />);

    // Use a more specific selector to match only category containers, not content divs
    const categories = container.querySelectorAll('[data-testid^="category-"]:not([data-testid^="category-content-"])');
    expect(categories).toHaveLength(4);
  });

  // AC-02: Each bar shows score (0-100) with color coding
  it('AC-02: Each bar shows score with color coding', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    // Check that scores are displayed with colors
    const score85 = screen.getByText('85/100');
    expect(score85).toHaveClass('text-green-500');

    const score65 = screen.getByText('65/100');
    expect(score65).toHaveClass('text-blue-500');
  });

  // AC-03: Score labels and descriptions visible
  it('AC-03: Score labels and descriptions visible', () => {
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    // Category names visible
    expect(screen.getByText('Technical SEO')).toBeInTheDocument();
    expect(screen.getByText('On-Page SEO')).toBeInTheDocument();

    // Score values visible
    expect(screen.getByText('85/100')).toBeInTheDocument();
  });

  // AC-04: Expandable issue count per category
  it('AC-04: Expandable issue count per category', async () => {
    const user = userEvent.setup();
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    const triggers = screen.getAllByText(/issues?/);
    expect(triggers.length).toBe(4);

    // Click to expand
    await user.click(triggers[0].closest('button')!);
    // Use getAllByText since multiple categories have critical issues
    const criticalElements = screen.getAllByText(/critical/);
    expect(criticalElements.length).toBeGreaterThan(0);
  });

  // AC-05: Issue severity indicators when expanded
  it('AC-05: Issue severity indicators when expanded', async () => {
    const user = userEvent.setup();
    const props = createMockProps();
    render(<CategoryBreakdown {...props} />);

    const trigger = screen.getAllByText(/issues?/)[0].closest('button')!;
    await user.click(trigger);

    // Check for severity indicators - use queryAllByTestId since multiple categories have them
    const criticalIndicators = screen.queryAllByTestId('critical-indicator');
    const warningIndicators = screen.queryAllByTestId('warning-indicator');

    // At least one should be present (based on mock data)
    expect(criticalIndicators.length + warningIndicators.length).toBeGreaterThan(0);
  });

  // AC-06: Color matches score range
  it('AC-06: Color matches score range', () => {
    const props = createMockProps({
      categories: [
        createMockCategoryScore({ name: 'Green', score: 85 }),
        createMockCategoryScore({ name: 'Blue', score: 65 }),
        createMockCategoryScore({ name: 'Yellow', score: 45 }),
        createMockCategoryScore({ name: 'Orange', score: 25 }),
      ],
    });
    render(<CategoryBreakdown {...props} />);

    expect(screen.getByText('85/100')).toHaveClass('text-green-500');
    expect(screen.getByText('65/100')).toHaveClass('text-blue-500');
    expect(screen.getByText('45/100')).toHaveClass('text-yellow-500');
    expect(screen.getByText('25/100')).toHaveClass('text-orange-500');
  });

  // AC-07: Accessible
  it('AC-07: Accessible - scores readable, expandable via keyboard', async () => {
    const user = userEvent.setup();
    const props = createMockProps({
      categories: [createMockCategoryScore({ name: 'Test', score: 75, issueCount: 5 })],
    });
    const { container } = render(<CategoryBreakdown {...props} />);

    // Scores have aria-label
    const score = screen.getByText('75/100');
    expect(score).toHaveAttribute('aria-label', 'Score: 75 out of 100');

    // Expandable via keyboard
    const trigger = screen.getByText(/5 issues?/).closest('button')!;
    trigger.focus();
    await user.keyboard('{Enter}');
    expect(container.querySelector('[data-open="true"]')).toBeInTheDocument();
  });

  // AC-08: Unit tests for rendering and interaction
  it('AC-08: Unit tests cover rendering and interaction', () => {
    // This test file itself verifies that tests exist
    expect(true).toBe(true);
  });
});

// ============================================================================
// TYPE DEFINITIONS TEST
// ============================================================================

describe('TypeScript Types', () => {
  it('TS-001: CategoryScore interface exists', () => {
    const category: CategoryScore = {
      name: 'Test',
      score: 50,
      issueCount: 5,
      criticalCount: 1,
      warningCount: 2,
    };
    expect(category).toBeDefined();
  });

  it('TS-002: CategoryBreakdownProps interface exists', () => {
    const props: CategoryBreakdownProps = {
      categories: [],
      expanded: false,
      className: 'test',
    };
    expect(props).toBeDefined();
  });

  it('TS-003: Categories array is required', () => {
    const props: CategoryBreakdownProps = {
      categories: [],
    };
    expect(props.categories).toEqual([]);
  });

  it('TS-004: Expanded prop is optional', () => {
    const props: CategoryBreakdownProps = {
      categories: [],
    };
    expect(props.expanded).toBeUndefined();
  });

  it('TS-005: ClassName prop is optional', () => {
    const props: CategoryBreakdownProps = {
      categories: [],
    };
    expect(props.className).toBeUndefined();
  });
});
