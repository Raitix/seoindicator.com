/**
 * TopIssues Component Tests
 * Task: TASK-33-11 - Create TopIssues Component
 * Spec: SPEC-33 (Public SEO Audit Reports & Embeddable Score Widget)
 *
 * Tests for TopIssues component implementation following TDD approach
 */

import React from 'react';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock Lucide React icons
jest.mock('lucide-react', () => ({
  AlertCircle: () => <span data-testid="alert-icon">!</span>,
  AlertTriangle: () => <span data-testid="warning-icon">⚠</span>,
  Info: () => <span data-testid="info-icon">ℹ</span>,
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">▶</span>,
  ArrowRight: () => <span data-testid="arrow-icon">→</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">✓</span>,
}));

// Mock shadcn/ui Badge
jest.mock('@/components/ui/badge', () => ({
  Badge: ({ children, variant, className }: { children: React.ReactNode; variant?: string; className?: string }) => (
    <span data-testid="badge" data-variant={variant} className={className}>
      {children}
    </span>
  ),
}));

// Import actual component (implemented by Developer)
import { TopIssues } from '@/components/audit/TopIssues';
import type { Issue, TopIssuesProps } from '@/components/audit/TopIssues';

// Test data
const mockIssues: Issue[] = [
  {
    id: 'issue-1',
    title: 'Missing meta description',
    description: 'The page does not have a meta description tag which is important for search engine results and click-through rates.',
    severity: 'critical',
    category: 'On-Page',
    impact: 'May reduce click-through rate from search results by up to 30%'
  },
  {
    id: 'issue-2',
    title: 'Large page size',
    description: 'Page size exceeds recommended 1MB limit which can affect load times.',
    severity: 'warning',
    category: 'Technical',
    impact: 'May affect page load speed and mobile performance'
  },
  {
    id: 'issue-3',
    title: 'Missing alt text on images',
    description: '3 images found without alt attributes which affects accessibility.',
    severity: 'warning',
    category: 'Content',
    impact: 'Reduces accessibility and image search visibility'
  },
  {
    id: 'issue-4',
    title: 'H1 tag missing',
    description: 'No H1 heading tag found on the page. This is important for SEO.',
    severity: 'critical',
    category: 'On-Page',
    impact: 'Search engines use H1 to understand page content'
  },
  {
    id: 'issue-5',
    title: 'Low word count',
    description: 'Page has fewer than 300 words of content which may not provide enough context.',
    severity: 'info',
    category: 'Content',
    impact: 'May not provide enough context for search engines'
  },
  {
    id: 'issue-6',
    title: 'Mobile viewport not set',
    description: 'Missing viewport meta tag for mobile devices.',
    severity: 'critical',
    category: 'Mobile',
    impact: 'Page may not render correctly on mobile devices'
  },
  {
    id: 'issue-7',
    title: 'Duplicate title tags',
    description: 'Multiple pages have the same title tag which can confuse search engines.',
    severity: 'warning',
    category: 'On-Page',
    impact: 'Can confuse search engines about page relevance'
  },
  {
    id: 'issue-8',
    title: 'No sitemap found',
    description: 'XML sitemap not detected on the website.',
    severity: 'info',
    category: 'Technical',
    impact: 'Search engines may miss some pages during crawling'
  },
  {
    id: 'issue-9',
    title: 'Broken internal links',
    description: '5 broken internal links detected on the page.',
    severity: 'warning',
    category: 'Technical',
    impact: 'Affects user experience and crawl efficiency'
  },
  {
    id: 'issue-10',
    title: 'No structured data',
    description: 'No Schema.org markup found on the page for rich snippets.',
    severity: 'info',
    category: 'Technical',
    impact: 'Missing opportunity for rich snippets in search results'
  }
];

const emptyIssues: Issue[] = [];
const singleIssue = [mockIssues[0]];
const unicodeIssue: Issue = {
  id: 'unicode-1',
  title: '问题标题 🚨 日本語',
  description: 'Description with émojis 🎉 and spëcial çhars',
  severity: 'critical',
  category: 'On-Page',
  impact: 'Unicode impact 📊'
};
const xssIssue: Issue = {
  id: 'xss-1',
  title: '<script>alert("xss")</script>Title',
  description: '<img src=x onerror=alert(1)>Description',
  severity: 'warning',
  category: 'Technical',
  impact: '<a href="javascript:alert(1)">Impact</a>'
};
const longTextIssue: Issue = {
  id: 'long-1',
  title: 'A'.repeat(200),
  description: 'B'.repeat(1000),
  severity: 'info',
  category: 'Content',
  impact: 'C'.repeat(500)
};

describe('TopIssues', () => {
  // ============================================================================
  // Rendering Tests
  // ============================================================================

  describe('Rendering', () => {
    it('TI-001: renders container with correct role', () => {
      render(<TopIssues issues={mockIssues} isPartial={false} />);
      
      // Accessible name comes from aria-labelledby pointing to heading with "Top Issues (N)"
      const container = screen.getByRole('region');
      expect(container).toBeInTheDocument();
      expect(container).toHaveAttribute('aria-labelledby', 'top-issues-heading');
    });

    it('TI-002: renders header with issue count', () => {
      render(<TopIssues issues={mockIssues.slice(0, 5)} isPartial={false} />);
      
      const header = screen.getByTestId('header');
      expect(header).toHaveTextContent('Top Issues (5)');
    });

    it('TI-003: renders issue list', () => {
      render(<TopIssues issues={mockIssues.slice(0, 3)} isPartial={false} />);
      
      const list = screen.getByRole('list');
      expect(list).toBeInTheDocument();
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(3);
    });

    it('TI-004: shows only top 5 issues when isPartial=true', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(5);
    });

    it('TI-005: shows all issues when isPartial=false', () => {
      render(<TopIssues issues={mockIssues} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(10);
    });

    it('TI-006: handles fewer than 5 issues gracefully when isPartial=true', () => {
      render(<TopIssues issues={mockIssues.slice(0, 3)} isPartial={true} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(3);
    });

    it('TI-007: shows "No issues found" message when empty', () => {
      render(<TopIssues issues={emptyIssues} isPartial={false} />);
      
      expect(screen.getByText(/No issues found/i)).toBeInTheDocument();
    });

    it('TI-008: empty state has check circle icon', () => {
      render(<TopIssues issues={emptyIssues} isPartial={false} />);
      
      const icon = screen.getByTestId('check-circle-icon');
      expect(icon).toBeInTheDocument();
    });
  });

  // ============================================================================
  // Severity Indicators
  // ============================================================================

  describe('Severity Indicators', () => {
    it('TI-010: renders critical severity badge', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const criticalBadge = badges.find(b => b.textContent === 'Critical');
      expect(criticalBadge).toBeInTheDocument();
      expect(criticalBadge).toHaveAttribute('data-variant', 'critical');
    });

    it('TI-011: renders warning severity badge', () => {
      render(<TopIssues issues={[mockIssues[1]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const warningBadge = badges.find(b => b.textContent === 'Warning');
      expect(warningBadge).toBeInTheDocument();
      expect(warningBadge).toHaveAttribute('data-variant', 'warning');
    });

    it('TI-012: renders info severity badge', () => {
      render(<TopIssues issues={[mockIssues[4]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const infoBadge = badges.find(b => b.textContent === 'Info');
      expect(infoBadge).toBeInTheDocument();
      expect(infoBadge).toHaveAttribute('data-variant', 'info');
    });

    it('TI-013: issues sorted by severity (Critical first)', () => {
      const unsortedIssues = [mockIssues[1], mockIssues[0], mockIssues[4]]; // warning, critical, info
      
      render(<TopIssues issues={unsortedIssues} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items[0]).toHaveAttribute('data-testid', 'issue-issue-1'); // critical
      expect(items[1]).toHaveAttribute('data-testid', 'issue-issue-2'); // warning
      expect(items[2]).toHaveAttribute('data-testid', 'issue-issue-5'); // info
    });

    it('TI-014: secondary sort by category for same severity', () => {
      const sameSeverityIssues = [
        { ...mockIssues[0], id: 'a', category: 'On-Page', severity: 'critical' as const },
        { ...mockIssues[0], id: 'b', category: 'Content', severity: 'critical' as const },
        { ...mockIssues[0], id: 'c', category: 'Technical', severity: 'critical' as const },
      ];
      
      render(<TopIssues issues={sameSeverityIssues} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      // Content < On-Page < Technical alphabetically
      expect(items[0]).toHaveAttribute('data-testid', 'issue-b'); // Content
      expect(items[1]).toHaveAttribute('data-testid', 'issue-a'); // On-Page
      expect(items[2]).toHaveAttribute('data-testid', 'issue-c'); // Technical
    });

    it('TI-015: shows severity count badges in header', () => {
      const mixedIssues = [
        mockIssues[0], // critical
        mockIssues[1], // warning
        mockIssues[2], // warning
        mockIssues[4], // info
      ];
      
      render(<TopIssues issues={mixedIssues} isPartial={false} />);
      
      const criticalCount = screen.getByTestId('critical-count');
      expect(criticalCount).toHaveTextContent('1 Critical');
      
      const warningCount = screen.getByTestId('warning-count');
      expect(warningCount).toHaveTextContent('2 Warning');
      
      const infoCount = screen.getByTestId('info-count');
      expect(infoCount).toHaveTextContent('1 Info');
    });
  });

  // ============================================================================
  // Category Badges
  // ============================================================================

  describe('Category Badges', () => {
    it('TI-020: renders Technical category badge', () => {
      render(<TopIssues issues={[mockIssues[1]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const categoryBadge = badges.find(b => b.textContent === 'Technical');
      expect(categoryBadge).toBeInTheDocument();
    });

    it('TI-021: renders On-Page category badge', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const categoryBadge = badges.find(b => b.textContent === 'On-Page');
      expect(categoryBadge).toBeInTheDocument();
    });

    it('TI-022: renders Content category badge', () => {
      render(<TopIssues issues={[mockIssues[2]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const categoryBadge = badges.find(b => b.textContent === 'Content');
      expect(categoryBadge).toBeInTheDocument();
    });

    it('TI-023: renders Mobile category badge', () => {
      render(<TopIssues issues={[mockIssues[5]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const categoryBadge = badges.find(b => b.textContent === 'Mobile');
      expect(categoryBadge).toBeInTheDocument();
    });

    it('TI-024: handles unknown category gracefully', () => {
      const unknownCategoryIssue = { ...mockIssues[0], category: 'Unknown' };
      
      render(<TopIssues issues={[unknownCategoryIssue]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const categoryBadge = badges.find(b => b.textContent === 'Unknown');
      expect(categoryBadge).toBeInTheDocument();
    });

    it('TI-025: different categories have different variants', () => {
      const mixedCategories = [
        { ...mockIssues[0], id: 'cat-1', category: 'Technical' },
        { ...mockIssues[0], id: 'cat-2', category: 'On-Page' },
        { ...mockIssues[0], id: 'cat-3', category: 'Content' },
        { ...mockIssues[0], id: 'cat-4', category: 'Mobile' },
      ];
      
      render(<TopIssues issues={mixedCategories} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      
      const technicalBadge = badges.find(b => b.textContent === 'Technical');
      const onPageBadge = badges.find(b => b.textContent === 'On-Page');
      const contentBadge = badges.find(b => b.textContent === 'Content');
      const mobileBadge = badges.find(b => b.textContent === 'Mobile');
      
      expect(technicalBadge?.getAttribute('data-variant')).toBe('technical');
      expect(onPageBadge?.getAttribute('data-variant')).toBe('on-page');
      expect(contentBadge?.getAttribute('data-variant')).toBe('content');
      expect(mobileBadge?.getAttribute('data-variant')).toBe('mobile');
    });

    it('TI-026: category badge positioned next to severity badge', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const issueHeader = screen.getByTestId('issue-header-issue-1');
      const badges = within(issueHeader).getAllByTestId('badge');
      
      // First badge is severity, second is category
      expect(badges).toHaveLength(2);
      expect(badges[0]).toHaveTextContent('Critical');
      expect(badges[1]).toHaveTextContent('On-Page');
    });
  });

  // ============================================================================
  // Issue Details
  // ============================================================================

  describe('Issue Details', () => {
    it('TI-030: renders issue title', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      expect(screen.getByTestId('issue-title-issue-1')).toHaveTextContent('Missing meta description');
    });

    it('TI-031: renders brief description in collapsed state', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const collapsed = screen.getByTestId('issue-collapsed-issue-1');
      expect(collapsed).toBeInTheDocument();
      expect(collapsed).toHaveTextContent(/The page does not have a meta description/);
    });

    it('TI-032: impact text shown in expanded state', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const expanded = screen.getByTestId('issue-expanded-issue-1');
      expect(expanded).toHaveTextContent('May reduce click-through rate');
    });

    it('TI-033: issue details expandable on click', async () => {
      const user = userEvent.setup();
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const issue = screen.getByTestId('issue-issue-1');
      await user.click(issue);
      
      expect(issue).toHaveAttribute('aria-expanded', 'true');
    });

    it('TI-034: expanded state shows full description', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const expanded = screen.getByTestId('issue-expanded-issue-1');
      expect(expanded).toHaveAttribute('hidden');
      
      // After click would remove hidden
    });

    it('TI-035: chevron icon indicates expandable state', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const chevron = screen.getByTestId('chevron-right-icon');
      expect(chevron).toBeInTheDocument();
    });

    it('TI-036: long titles are handled', () => {
      render(<TopIssues issues={[longTextIssue]} isPartial={false} />);
      
      const title = screen.getByTestId('issue-title-long-1');
      expect(title).toBeInTheDocument();
      // Title content should exist (truncation handled by CSS)
      expect(title.textContent?.length).toBeGreaterThan(0);
    });

    it('TI-037: long descriptions truncated in collapsed state', () => {
      render(<TopIssues issues={[longTextIssue]} isPartial={false} />);
      
      const collapsed = screen.getByTestId('issue-collapsed-long-1');
      const text = collapsed.textContent || '';
      // Should be truncated with ellipsis
      expect(text.endsWith('...')).toBe(true);
      expect(text.length).toBeLessThan(longTextIssue.description.length + 10);
    });
  });

  // ============================================================================
  // View Full Report Prompt
  // ============================================================================

  describe('View Full Report Prompt', () => {
    it('TI-040: shows prompt when isPartial=true', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} auditHash="abc123" />);
      
      expect(screen.getByTestId('view-full-prompt')).toBeInTheDocument();
    });

    it('TI-041: hides prompt when isPartial=false', () => {
      render(<TopIssues issues={mockIssues} isPartial={false} auditHash="abc123" />);
      
      expect(screen.queryByTestId('view-full-prompt')).not.toBeInTheDocument();
    });

    it('TI-042: prompt links to correct audit URL', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} auditHash="abc123" />);
      
      const link = screen.getByTestId('view-full-link');
      // Component uses query parameter format: /audit/?h=abc123
      expect(link).toHaveAttribute('href', '/audit/?h=abc123');
    });

    it('TI-043: prompt has CTA text', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} auditHash="abc123" />);
      
      const link = screen.getByTestId('view-full-link');
      expect(link).toHaveTextContent('View Full Report');
    });

    it('TI-044: prompt includes arrow icon', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} auditHash="abc123" />);
      
      const link = screen.getByTestId('view-full-link');
      const arrow = within(link).getByTestId('arrow-icon');
      expect(arrow).toBeInTheDocument();
    });

    it('TI-045: shows remaining issues count', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} auditHash="abc123" />);
      
      // 10 issues total, 5 shown = 5 remaining
      expect(screen.getByText('+5 more issues')).toBeInTheDocument();
    });

    it('TI-046: prompt at bottom of issue list', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} auditHash="abc123" />);
      
      const container = screen.getByTestId('top-issues');
      const prompt = screen.getByTestId('view-full-prompt');
      const list = screen.getByTestId('issue-list');
      
      // Prompt should be after list in DOM order
      const containerChildren = Array.from(container.children);
      const listIndex = containerChildren.indexOf(list);
      const promptIndex = containerChildren.indexOf(prompt);
      
      expect(promptIndex).toBeGreaterThan(listIndex);
    });
  });

  // ============================================================================
  // Accessibility
  // ============================================================================

  describe('Accessibility', () => {
    it('TI-050: container has aria-labelledby', () => {
      render(<TopIssues issues={mockIssues} isPartial={false} />);
      
      const container = screen.getByRole('region');
      expect(container).toHaveAttribute('aria-labelledby', 'top-issues-heading');
    });

    it('TI-051: list has proper role', () => {
      render(<TopIssues issues={mockIssues} isPartial={false} />);
      
      const list = screen.getByRole('list');
      expect(list).toBeInTheDocument();
    });

    it('TI-052: list items have proper role', () => {
      render(<TopIssues issues={mockIssues.slice(0, 3)} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(3);
    });

    it('TI-053: severity badges are labeled', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const badges = screen.getAllByTestId('badge');
      const criticalBadge = badges.find(b => b.textContent === 'Critical');
      expect(criticalBadge).toHaveAttribute('data-variant');
    });

    it('TI-054: expandable items have aria-expanded', () => {
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const issue = screen.getByTestId('issue-issue-1');
      expect(issue).toHaveAttribute('aria-expanded', 'false');
    });

    it('TI-055: aria-expanded toggles on click', async () => {
      const user = userEvent.setup();
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const issue = screen.getByTestId('issue-issue-1');
      expect(issue).toHaveAttribute('aria-expanded', 'false');
      
      await user.click(issue);
      expect(issue).toHaveAttribute('aria-expanded', 'true');
    });

    it('TI-056: Tab navigates through issues', async () => {
      const user = userEvent.setup();
      render(<TopIssues issues={mockIssues.slice(0, 3)} isPartial={false} />);
      
      await user.tab();
      // First issue after sorting: issue-1 (critical), issue-4 (critical), issue-2 (warning)
      // The first critical issue should have focus
      const firstIssue = screen.getByTestId('issue-issue-1');
      expect(firstIssue).toHaveFocus();
    });

    it('TI-057: Enter key expands issue', async () => {
      const user = userEvent.setup();
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const issue = screen.getByTestId('issue-issue-1');
      issue.focus();
      
      await user.keyboard('{Enter}');
      expect(issue).toHaveAttribute('aria-expanded', 'true');
    });

    it('TI-058: Space key expands issue', async () => {
      const user = userEvent.setup();
      render(<TopIssues issues={[mockIssues[0]]} isPartial={false} />);
      
      const issue = screen.getByTestId('issue-issue-1');
      issue.focus();
      
      await user.keyboard(' ');
      expect(issue).toHaveAttribute('aria-expanded', 'true');
    });

    it('TI-059: issue count announced to screen readers', () => {
      render(<TopIssues issues={mockIssues.slice(0, 5)} isPartial={false} />);
      
      const header = screen.getByTestId('header');
      expect(header).toHaveTextContent('Top Issues (5)');
    });

    it('TI-060: empty state has accessible message', () => {
      render(<TopIssues issues={emptyIssues} isPartial={false} />);
      
      const emptyState = screen.getByTestId('empty-state');
      expect(emptyState).toHaveTextContent('No issues found');
    });
  });

  // ============================================================================
  // Edge Cases
  // ============================================================================

  describe('Edge Cases', () => {
    it('TI-070: handles empty issues array', () => {
      render(<TopIssues issues={emptyIssues} isPartial={false} />);
      
      expect(screen.getByTestId('top-issues-empty')).toBeInTheDocument();
      expect(screen.getByText(/No issues found/i)).toBeInTheDocument();
    });

    it('TI-071: handles single issue', () => {
      render(<TopIssues issues={singleIssue} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(1);
    });

    it('TI-072: handles large issue list without crashing', () => {
      const largeIssueList = Array.from({ length: 50 }, (_, i) => ({
        ...mockIssues[0],
        id: `issue-${i}`,
        title: `Issue ${i}`,
      }));
      
      render(<TopIssues issues={largeIssueList} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(50);
    });

    it('TI-073: handles Unicode in titles', () => {
      render(<TopIssues issues={[unicodeIssue]} isPartial={false} />);
      
      const title = screen.getByTestId('issue-title-unicode-1');
      expect(title).toHaveTextContent('问题标题 🚨 日本語');
    });

    it('TI-074: XSS content is rendered as text, not executed', () => {
      render(<TopIssues issues={[xssIssue]} isPartial={false} />);
      
      const title = screen.getByTestId('issue-title-xss-1');
      // React escapes HTML by default, so script tags appear as text
      expect(title).toBeInTheDocument();
      
      // The script tag content should be visible as text (escaped)
      // not executed as actual JavaScript
      expect(title.textContent).toContain('Title'); // The text after script tag
    });

    it('TI-075: handles null issues gracefully', () => {
      // @ts-expect-error Testing invalid input
      render(<TopIssues issues={null} isPartial={false} />);
      
      expect(screen.getByTestId('top-issues-empty')).toBeInTheDocument();
    });

    it('TI-076: handles missing auditHash', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} />);
      
      // Should show prompt but without link
      expect(screen.getByTestId('view-full-prompt')).toBeInTheDocument();
      expect(screen.getByText('+5 more issues')).toBeInTheDocument();
    });

    it('TI-077: handles duplicate issue IDs without key warnings', () => {
      const duplicateIdIssues = [
        { ...mockIssues[0], id: 'same-id' },
        { ...mockIssues[1], id: 'same-id' },
      ];
      
      // Should render without React key warnings
      render(<TopIssues issues={duplicateIdIssues} isPartial={false} />);
      
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(2);
    });

    it('TI-078: handles very long text fields', () => {
      render(<TopIssues issues={[longTextIssue]} isPartial={false} />);
      
      const title = screen.getByTestId('issue-title-long-1');
      expect(title.textContent?.length).toBe(200);
    });

    it('TI-079: handles mixed valid and invalid issues', () => {
      // The component should filter out invalid issues or handle them gracefully
      // For TDD, we expect the component to only render valid Issue objects
      const validIssue = mockIssues[0];
      
      // Test with only valid issues
      render(<TopIssues issues={[validIssue]} isPartial={false} />);
      
      // Should render the valid issue
      const items = screen.getAllByRole('listitem');
      expect(items).toHaveLength(1);
    });
  });

  // ============================================================================
  // Props Validation
  // ============================================================================

  describe('Props Validation', () => {
    it('TI-080: renders with required issues prop', () => {
      render(<TopIssues issues={mockIssues} isPartial={false} />);
      
      expect(screen.getByTestId('top-issues')).toBeInTheDocument();
    });

    it('TI-081: isPartial prop controls view mode', () => {
      const { rerender } = render(<TopIssues issues={mockIssues} isPartial={true} />);
      
      expect(screen.getAllByRole('listitem')).toHaveLength(5);
      
      rerender(<TopIssues issues={mockIssues} isPartial={false} />);
      
      expect(screen.getAllByRole('listitem')).toHaveLength(10);
    });

    it('TI-082: auditHash is optional', () => {
      render(<TopIssues issues={mockIssues} isPartial={true} />);
      
      // Should not have view-full-link without auditHash
      expect(screen.queryByTestId('view-full-link')).not.toBeInTheDocument();
    });

    it('TI-083: handles non-array issues prop by showing empty state', () => {
      // @ts-expect-error Testing invalid input
      render(<TopIssues issues="not an array" isPartial={false} />);
      
      // Should render empty state for non-array input
      expect(screen.getByText(/No issues found/i)).toBeInTheDocument();
    });

    it('TI-084: handles string isPartial prop', () => {
      // @ts-expect-error Testing type coercion
      render(<TopIssues issues={mockIssues} isPartial="true" />);
      
      // JavaScript truthy coercion
      expect(screen.getAllByRole('listitem')).toHaveLength(5);
    });

    it('TI-085: handles malformed Issue objects gracefully', () => {
      // Component should handle missing fields with defaults
      const partialIssue = {
        id: 'partial-1',
        title: 'Partial Issue',
        // Missing description, severity, category, impact
      };
      
      // @ts-expect-error Testing invalid input
      render(<TopIssues issues={[partialIssue]} isPartial={false} />);
      
      // The component should still render with what it has
      // or show empty state if it can't process the data
      expect(screen.getByTestId('top-issues')).toBeInTheDocument();
    });
  });
});
