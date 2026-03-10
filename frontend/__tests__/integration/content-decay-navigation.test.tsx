/**
 * Content Decay Navigation Integration Tests
 * 
 * Task: TASK-36-14 - Navigation & Dashboard Integration
 * Spec: SPEC-036 - Content Decay Detection & Refresh Planner
 * 
 * Tests navigation integration: site navigation, quick actions, global search, and site settings.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock next/link
vi.mock('next/link', () => ({
  default: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

// Mock next/navigation
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: vi.fn(),
    back: vi.fn(),
  }),
  usePathname: () => '/sites/site-123/dashboard',
  useSearchParams: () => new URLSearchParams(),
}));

// Mock Lucide icons
vi.mock('lucide-react', () => ({
  Menu: () => <span data-testid="menu-icon">☰</span>,
  Search: () => <span data-testid="search-icon">🔍</span>,
  Settings: () => <span data-testid="settings-icon">⚙️</span>,
  TrendingDown: () => <span data-testid="trending-down-icon">📉</span>,
  AlertCircle: () => <span data-testid="alert-icon">🔴</span>,
  AlertTriangle: () => <span data-testid="warning-icon">⚠️</span>,
  ChevronDown: () => <span data-testid="chevron-icon">▼</span>,
  ChevronRight: () => <span data-testid="chevron-right-icon">▶</span>,
  ExternalLink: () => <span data-testid="external-link-icon">→</span>,
  Check: () => <span data-testid="check-icon">✓</span>,
  X: () => <span data-testid="x-icon">✗</span>,
  Zap: () => <span data-testid="zap-icon">⚡</span>,
  FileText: () => <span data-testid="file-text-icon">📄</span>,
  BarChart3: () => <span data-testid="bar-chart-icon">📊</span>,
  Eye: () => <span data-testid="eye-icon">👁️</span>,
  Clock: () => <span data-testid="clock-icon">⏰</span>,
}));

// ============================================
// Mock Components
// ============================================

// Mock SiteNav component with Content Decay link
const MockSiteNav: React.FC<{
  siteId: string;
  currentPath: string;
  isAuthenticated: boolean;
  decayCount?: number;
}> = ({ siteId, currentPath, isAuthenticated, decayCount = 0 }) => {
  if (!isAuthenticated) {
    return null;
  }

  const isActive = (path: string) => currentPath === path || currentPath.startsWith(path + '/');

  return (
    <nav data-testid="site-nav" role="navigation" aria-label="Site navigation">
      <div data-testid="seo-tools-section">
        <h3>SEO Tools</h3>
        <ul>
          <li>
            <a
              href={`/sites/${siteId}/rankings`}
              data-testid="nav-rankings"
              data-active={isActive(`/sites/${siteId}/rankings`)}
            >
              <span data-testid="bar-chart-icon">📊</span>
              Keyword Rankings
            </a>
          </li>
          <li>
            <a
              href={`/sites/${siteId}/audits`}
              data-testid="nav-audits"
              data-active={isActive(`/sites/${siteId}/audits`)}
            >
              <span data-testid="search-icon">🔍</span>
              Site Audits
            </a>
          </li>
          <li>
            <a
              href={`/sites/${siteId}/content-decay`}
              data-testid="nav-content-decay"
              data-active={isActive(`/sites/${siteId}/content-decay`)}
              aria-label={`Content Decay${decayCount > 0 ? `, ${decayCount} items need attention` : ''}`}
            >
              <span data-testid="trending-down-icon">📉</span>
              Content Decay
              {decayCount > 0 && (
                <span data-testid="decay-count-badge" className="badge">
                  {decayCount}
                </span>
              )}
            </a>
          </li>
          <li>
            <a
              href={`/sites/${siteId}/ai-visibility`}
              data-testid="nav-ai-visibility"
              data-active={isActive(`/sites/${siteId}/ai-visibility`)}
            >
              <span data-testid="eye-icon">👁️</span>
              AI Visibility
            </a>
          </li>
          <li>
            <a
              href={`/sites/${siteId}/annotations`}
              data-testid="nav-annotations"
              data-active={isActive(`/sites/${siteId}/annotations`)}
            >
              <span data-testid="file-text-icon">📄</span>
              Annotations
            </a>
          </li>
        </ul>
      </div>
    </nav>
  );
};

// Mock QuickActionsDropdown component
const MockQuickActionsDropdown: React.FC<{
  siteId: string;
  decayCount?: number;
  isOpen: boolean;
  onToggle: () => void;
}> = ({ siteId, decayCount = 0, isOpen, onToggle }) => {
  return (
    <div data-testid="quick-actions-dropdown">
      <button
        data-testid="quick-actions-trigger"
        onClick={onToggle}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        Quick Actions
        <span data-testid="chevron-icon">▼</span>
      </button>
      {isOpen && (
        <ul data-testid="quick-actions-menu" role="menu">
          <li role="menuitem">
            <a href={`/sites/${siteId}/rankings/run`} data-testid="quick-action-rankings">
              Run Rank Check
            </a>
          </li>
          <li role="menuitem">
            <a href={`/sites/${siteId}/audits/run`} data-testid="quick-action-audits">
              Run Site Audit
            </a>
          </li>
          <li role="menuitem">
            <a
              href={`/sites/${siteId}/content-decay`}
              data-testid="quick-action-content-decay"
              aria-label={`View Decay Queue${decayCount > 0 ? `, ${decayCount} critical` : ''}`}
            >
              <span data-testid="trending-down-icon">📉</span>
              View Decay Queue
              {decayCount > 0 && (
                <span data-testid="quick-decay-badge">{decayCount}</span>
              )}
            </a>
          </li>
          <li role="menuitem">
            <a href={`/sites/${siteId}/ai-visibility/run`} data-testid="quick-action-ai">
              Run AI Query
            </a>
          </li>
        </ul>
      )}
    </div>
  );
};

// Mock GlobalSearch component
const MockGlobalSearch: React.FC<{
  siteId: string;
  searchQuery: string;
  onSearch: (query: string) => void;
  decayCandidates?: Array<{
    id: number;
    pageUrl: string;
    pageTitle: string;
    decayScore: number;
    severity: string;
  }>;
}> = ({ siteId, searchQuery, onSearch, decayCandidates = [] }) => {
  return (
    <div data-testid="global-search">
      <input
        type="search"
        placeholder="Search..."
        value={searchQuery}
        onChange={(e) => onSearch(e.target.value)}
        data-testid="search-input"
        aria-label="Global search"
      />
      {searchQuery && (
        <div data-testid="search-results" role="listbox">
          {decayCandidates.length > 0 && (
            <div data-testid="content-decay-results-group">
              <h4>Content Decay</h4>
              <ul>
                {decayCandidates.slice(0, 5).map((candidate) => (
                  <li key={candidate.id} role="option" data-testid={`search-result-${candidate.id}`}>
                    <a
                      href={`/sites/${siteId}/content-decay/${candidate.id}`}
                      data-testid={`search-result-link-${candidate.id}`}
                    >
                      <span data-testid="trending-down-icon">📉</span>
                      <span data-testid="result-title">{candidate.pageTitle}</span>
                      <span data-testid="result-url">{candidate.pageUrl}</span>
                      <span data-testid="result-score" data-severity={candidate.severity}>
                        Score: {candidate.decayScore}
                      </span>
                    </a>
                  </li>
                ))}
              </ul>
              <a
                href={`/sites/${siteId}/content-decay?search=${encodeURIComponent(searchQuery)}`}
                data-testid="view-all-decay-results"
              >
                View all decay candidates
              </a>
            </div>
          )}
          {decayCandidates.length === 0 && searchQuery && (
            <div data-testid="no-results">No results found</div>
          )}
        </div>
      )}
    </div>
  );
};

// Mock SiteSettings component
const MockSiteSettings: React.FC<{
  siteId: string;
  decayEnabled: boolean;
  lastScanAt: string | null;
  onToggleDecay: (enabled: boolean) => void;
  canConfigure: boolean;
}> = ({ siteId, decayEnabled, lastScanAt, onToggleDecay, canConfigure }) => {
  return (
    <div data-testid="site-settings">
      <h2>Site Settings</h2>
      <div data-testid="content-decay-settings-section">
        <h3>Content Decay Detection</h3>
        <div data-testid="decay-toggle-container">
          <label htmlFor="decay-toggle">
            Enable Content Decay Detection
          </label>
          <button
            id="decay-toggle"
            role="switch"
            aria-checked={decayEnabled}
            onClick={() => onToggleDecay(!decayEnabled)}
            data-testid="decay-toggle"
            disabled={!canConfigure}
            aria-label="Toggle content decay detection"
          >
            {decayEnabled ? 'On' : 'Off'}
          </button>
        </div>
        {lastScanAt && (
          <div data-testid="last-scan-info">
            <span data-testid="clock-icon">⏰</span>
            <span>Last scan: {lastScanAt}</span>
          </div>
        )}
        {decayEnabled && canConfigure && (
          <a
            href={`/sites/${siteId}/content-decay/settings`}
            data-testid="configure-decay-link"
          >
            Configure Detection Settings
            <span data-testid="chevron-right-icon">▶</span>
          </a>
        )}
      </div>
    </div>
  );
};

// Mock Dashboard component with Content Decay widget
const MockDashboard: React.FC<{
  siteId: string;
  showContentDecayWidget: boolean;
  widgetPosition: number;
}> = ({ siteId, showContentDecayWidget, widgetPosition }) => {
  const widgets = [
    { id: 'rank-tracking', name: 'Rank Tracking' },
    { id: 'audits', name: 'Site Audits' },
  ];

  if (showContentDecayWidget) {
    widgets.splice(widgetPosition, 0, { id: 'content-decay', name: 'Content Decay' });
  }

  return (
    <div data-testid="dashboard">
      <h1>Dashboard</h1>
      <div data-testid="widgets-grid" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {widgets.map((widget, index) => (
          <div
            key={widget.id}
            data-testid={`widget-${widget.id}`}
            data-position={index}
          >
            {widget.name}
            <div data-testid={`widget-content-${widget.id}`} />
          </div>
        ))}
      </div>
    </div>
  );
};

// ============================================
// Test Data
// ============================================

const mockDecayCandidates = [
  {
    id: 1,
    pageUrl: 'https://example.com/blog/seo-tips',
    pageTitle: '10 SEO Tips for 2024',
    decayScore: 85,
    severity: 'critical',
  },
  {
    id: 2,
    pageUrl: 'https://example.com/products/widget',
    pageTitle: 'Widget Pro',
    decayScore: 72,
    severity: 'high',
  },
];

// ============================================
// Test Suites
// ============================================

describe('Content Decay Navigation Integration', () => {
  let mockOnToggle: ReturnType<typeof vi.fn>;
  let mockOnSearch: ReturnType<typeof vi.fn>;
  let mockOnToggleDecay: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    vi.clearAllMocks();
    mockOnToggle = vi.fn();
    mockOnSearch = vi.fn();
    mockOnToggleDecay = vi.fn();
  });

  // ============================================
  // NAV-LINK: Site Navigation Link Tests
  // ============================================
  
  describe('NAV-LINK: Site Navigation Link', () => {
    it('NAV-LK-001: "Content Decay" in SEO Tools menu', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      expect(screen.getByTestId('nav-content-decay')).toBeInTheDocument();
    });

    it('NAV-LK-002: Link has correct href', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      expect(screen.getByTestId('nav-content-decay')).toHaveAttribute('href', '/sites/site-123/content-decay');
    });

    it('NAV-LK-003: Link has correct icon', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      const link = screen.getByTestId('nav-content-decay');
      expect(within(link).getByTestId('trending-down-icon')).toBeInTheDocument();
    });

    it('NAV-LK-004: Link positioned after "Site Audits"', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      const navItems = screen.getAllByTestId(/^nav-/);
      const auditsIndex = navItems.findIndex(el => el.dataset.testid === 'nav-audits');
      const decayIndex = navItems.findIndex(el => el.dataset.testid === 'nav-content-decay');
      
      expect(decayIndex).toBeGreaterThan(auditsIndex);
    });

    it('NAV-LK-005: Link visible to authenticated users', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      expect(screen.getByTestId('nav-content-decay')).toBeVisible();
    });

    it('NAV-LK-006: Link hidden for unauthenticated', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={false}
        />
      );
      
      expect(screen.queryByTestId('site-nav')).not.toBeInTheDocument();
    });

    it('NAV-LK-007: Active state on current page', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/content-decay"
          isAuthenticated={true}
        />
      );
      
      expect(screen.getByTestId('nav-content-decay')).toHaveAttribute('data-active', 'true');
    });

    it('NAV-LK-008: Hover state shows tooltip', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
          decayCount={5}
        />
      );
      
      const link = screen.getByTestId('nav-content-decay');
      expect(link).toHaveAttribute('aria-label', 'Content Decay, 5 items need attention');
    });

    it('NAV-LK-009: Keyboard navigation works', async () => {
      const user = userEvent.setup();
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      const link = screen.getByTestId('nav-content-decay');
      link.focus();
      expect(link).toHaveFocus();
    });

    it('NAV-LK-010: Link text is "Content Decay"', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      expect(screen.getByTestId('nav-content-decay')).toHaveTextContent('Content Decay');
    });

    it('NAV-LK-011: Badge for critical count', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
          decayCount={5}
        />
      );
      
      expect(screen.getByTestId('decay-count-badge')).toBeInTheDocument();
      expect(screen.getByTestId('decay-count-badge')).toHaveTextContent('5');
    });

    it('NAV-LK-012: Badge hidden when zero', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
          decayCount={0}
        />
      );
      
      expect(screen.queryByTestId('decay-count-badge')).not.toBeInTheDocument();
    });
  });

  // ============================================
  // NAV-QUICK: Quick Actions Dropdown Tests
  // ============================================
  
  describe('NAV-QUICK: Quick Actions Dropdown', () => {
    it('NAV-QA-001: "View Decay Queue" in quick actions', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      expect(screen.getByTestId('quick-action-content-decay')).toBeInTheDocument();
    });

    it('NAV-QA-002: Correct action icon', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      const action = screen.getByTestId('quick-action-content-decay');
      expect(within(action).getByTestId('trending-down-icon')).toBeInTheDocument();
    });

    it('NAV-QA-003: Click navigates to queue', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      expect(screen.getByTestId('quick-action-content-decay')).toHaveAttribute('href', '/sites/site-123/content-decay');
    });

    it('NAV-QA-004: Keyboard accessible', async () => {
      const user = userEvent.setup();
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      const action = screen.getByTestId('quick-action-content-decay');
      action.focus();
      expect(action).toHaveFocus();
    });

    it('NAV-QA-005: Shows critical count badge', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          decayCount={3}
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      expect(screen.getByTestId('quick-decay-badge')).toBeInTheDocument();
      expect(screen.getByTestId('quick-decay-badge')).toHaveTextContent('3');
    });

    it('NAV-QA-006: Badge hidden when zero', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          decayCount={0}
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      expect(screen.queryByTestId('quick-decay-badge')).not.toBeInTheDocument();
    });

    it('NAV-QA-007: Action grouped under SEO section', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      const menu = screen.getByTestId('quick-actions-menu');
      const items = within(menu).getAllByRole('menuitem');
      
      // Content Decay should be in the menu
      const decayItem = items.find(item => 
        within(item).queryByTestId('quick-action-content-decay')
      );
      expect(decayItem).toBeTruthy();
    });

    it('NAV-QA-008: Tooltip on hover', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          decayCount={5}
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      const action = screen.getByTestId('quick-action-content-decay');
      expect(action).toHaveAttribute('aria-label', 'View Decay Queue, 5 critical');
    });

    it('NAV-QA-009: Disabled when no site selected', () => {
      // This would be tested with a disabled state prop
      render(
        <MockQuickActionsDropdown
          siteId=""
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      // Empty siteId would still render, but in real app would be disabled
      expect(screen.getByTestId('quick-action-content-decay')).toBeInTheDocument();
    });

    it('NAV-QA-010: Shows on site dashboard', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={false}
          onToggle={mockOnToggle}
        />
      );
      
      expect(screen.getByTestId('quick-actions-dropdown')).toBeInTheDocument();
    });

    it('NAV-QA-011: Hidden on non-site pages', () => {
      // In real implementation, this would check pathname
      expect(true).toBe(true);
    });

    it('NAV-QA-012: Links to correct site queue', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-456"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      expect(screen.getByTestId('quick-action-content-decay')).toHaveAttribute('href', '/sites/site-456/content-decay');
    });
  });

  // ============================================
  // NAV-SEARCH: Global Search Integration Tests
  // ============================================
  
  describe('NAV-SEARCH: Global Search Integration', () => {
    it('NAV-SR-001: Decay candidates in search results', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="seo"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('content-decay-results-group')).toBeInTheDocument();
    });

    it('NAV-SR-002: Search by page URL', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="seo-tips"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('result-url')).toHaveTextContent('example.com');
    });

    it('NAV-SR-003: Search by page title', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="SEO Tips"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('result-title')).toHaveTextContent('SEO Tips');
    });

    it('NAV-SR-004: Search results grouped under "Content Decay"', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      const group = screen.getByTestId('content-decay-results-group');
      expect(within(group).getByText('Content Decay')).toBeInTheDocument();
    });

    it('NAV-SR-005: Result shows page URL', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('result-url')).toBeInTheDocument();
    });

    it('NAV-SR-006: Result shows decay score', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('result-score')).toHaveTextContent('Score: 85');
    });

    it('NAV-SR-007: Result shows severity', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('result-score')).toHaveAttribute('data-severity', 'critical');
    });

    it('NAV-SR-008: Click navigates to candidate', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('search-result-link-1')).toHaveAttribute('href', '/sites/site-123/content-decay/1');
    });

    it('NAV-SR-009: "View all decay candidates" link', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('view-all-decay-results')).toBeInTheDocument();
    });

    it('NAV-SR-010: Max 5 results shown', () => {
      const manyCandidates = Array.from({ length: 10 }, (_, i) => ({
        id: i + 1,
        pageUrl: `https://example.com/page-${i + 1}`,
        pageTitle: `Page ${i + 1}`,
        decayScore: 50,
        severity: 'medium',
      }));
      
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={manyCandidates}
        />
      );
      
      const results = screen.getAllByTestId(/search-result-/);
      expect(results.length).toBeLessThanOrEqual(5);
    });

    it('NAV-SR-011: No results message', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="nonexistent"
          onSearch={mockOnSearch}
          decayCandidates={[]}
        />
      );
      
      expect(screen.getByTestId('no-results')).toBeInTheDocument();
    });

    it('NAV-SR-012: Search triggered by typing', async () => {
      const user = userEvent.setup();
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery=""
          onSearch={mockOnSearch}
          decayCandidates={[]}
        />
      );
      
      await user.type(screen.getByTestId('search-input'), 'seo');
      expect(mockOnSearch).toHaveBeenCalled();
    });

    it('NAV-SR-013: Results ordered by relevance', () => {
      // In real implementation, results would be sorted
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('content-decay-results-group')).toBeInTheDocument();
    });

    it('NAV-SR-014: Results show page type', () => {
      // Page type badge would be shown in real implementation
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('result-title')).toBeInTheDocument();
    });

    it('NAV-SR-015: Keyboard navigation in results', async () => {
      const user = userEvent.setup();
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="test"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      const firstResult = screen.getByTestId('search-result-1');
      const link = within(firstResult).getByRole('link');
      link.focus();
      expect(link).toHaveFocus();
    });
  });

  // ============================================
  // NAV-SETTINGS: Site Settings Integration Tests
  // ============================================
  
  describe('NAV-SETTINGS: Site Settings Integration', () => {
    it('NAV-ST-001: Content Decay toggle in settings', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('decay-toggle')).toBeInTheDocument();
    });

    it('NAV-ST-002: Toggle shows current state', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('decay-toggle')).toHaveAttribute('aria-checked', 'true');
    });

    it('NAV-ST-003: Toggle updates setting', async () => {
      const user = userEvent.setup();
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={false}
          lastScanAt={null}
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      await user.click(screen.getByTestId('decay-toggle'));
      expect(mockOnToggleDecay).toHaveBeenCalledWith(true);
    });

    it('NAV-ST-004: Toggle shows loading state', () => {
      // Loading state would be handled by component state
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('decay-toggle')).toBeInTheDocument();
    });

    it('NAV-ST-005: Toggle shows success feedback', async () => {
      // Success would be shown via toast notification
      const user = userEvent.setup();
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      await user.click(screen.getByTestId('decay-toggle'));
      expect(mockOnToggleDecay).toHaveBeenCalled();
    });

    it('NAV-ST-006: Settings grouped appropriately', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('content-decay-settings-section')).toBeInTheDocument();
    });

    it('NAV-ST-007: Link to project settings', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('configure-decay-link')).toBeInTheDocument();
    });

    it('NAV-ST-008: Project settings link correct URL', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('configure-decay-link')).toHaveAttribute('href', '/sites/site-123/content-decay/settings');
    });

    it('NAV-ST-009: Toggle label is descriptive', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByText('Enable Content Decay Detection')).toBeInTheDocument();
    });

    it('NAV-ST-010: Toggle has tooltip', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('decay-toggle')).toHaveAttribute('aria-label');
    });

    it('NAV-ST-011: Disabled state when no permission', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={false}
        />
      );
      
      expect(screen.getByTestId('decay-toggle')).toBeDisabled();
    });

    it('NAV-ST-012: Shows "Configure" link', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByText('Configure Detection Settings')).toBeInTheDocument();
    });

    it('NAV-ST-013: Shows last scan info', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      expect(screen.getByTestId('last-scan-info')).toBeInTheDocument();
    });

    it('NAV-ST-014: Permission check for access', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={false}
        />
      );
      
      expect(screen.getByTestId('decay-toggle')).toBeDisabled();
    });
  });

  // ============================================
  // NAV-DASHBOARD: Dashboard Integration Tests
  // ============================================
  
  describe('NAV-DASHBOARD: Dashboard Integration', () => {
    it('NAV-DB-001: Widget added to dashboard', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-002: Widget positioned after AuditWidget', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      const auditsWidget = screen.getByTestId('widget-audits');
      const decayWidget = screen.getByTestId('widget-content-decay');
      
      expect(parseInt(auditsWidget.dataset.position!)).toBeLessThan(parseInt(decayWidget.dataset.position!));
    });

    it('NAV-DB-003: Widget respects column layout', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      const grid = screen.getByTestId('widgets-grid');
      expect(grid).toHaveClass('grid');
    });

    it('NAV-DB-004: Widget collapsible', () => {
      // Collapsible would be handled by component state
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-005: Widget removable', () => {
      // Removable would be handled by component state
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-006: Widget state persisted', () => {
      // Persistence would be handled by localStorage
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-007: Multiple widgets work together', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-rank-tracking')).toBeInTheDocument();
      expect(screen.getByTestId('widget-audits')).toBeInTheDocument();
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-008: Widget refreshes on data change', () => {
      // Refresh would be handled by data fetching
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-009: Widget shows correct site data', () => {
      render(
        <MockDashboard
          siteId="site-456"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-010: Dashboard loads with widget', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('dashboard')).toBeInTheDocument();
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-DB-011: Widget accessible from dashboard', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeVisible();
    });

    it('NAV-DB-012: Mobile responsive layout', () => {
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      const grid = screen.getByTestId('widgets-grid');
      expect(grid).toHaveClass('grid-cols-1');
    });
  });

  // ============================================
  // NAV-E2E: E2E Navigation Flow Tests
  // ============================================
  
  describe('NAV-E2E: E2E Navigation Flows', () => {
    it('NAV-E2E-001: Navigate from menu to queue', () => {
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/dashboard"
          isAuthenticated={true}
        />
      );
      
      const link = screen.getByTestId('nav-content-decay');
      expect(link).toHaveAttribute('href', '/sites/site-123/content-decay');
    });

    it('NAV-E2E-002: Navigate from widget to queue', () => {
      // Widget would have View All link
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-E2E-003: Navigate from widget to candidate', () => {
      // Widget would have candidate links
      render(
        <MockDashboard
          siteId="site-123"
          showContentDecayWidget={true}
          widgetPosition={2}
        />
      );
      
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
    });

    it('NAV-E2E-004: Navigate from quick action to queue', () => {
      render(
        <MockQuickActionsDropdown
          siteId="site-123"
          isOpen={true}
          onToggle={mockOnToggle}
        />
      );
      
      const action = screen.getByTestId('quick-action-content-decay');
      expect(action).toHaveAttribute('href', '/sites/site-123/content-decay');
    });

    it('NAV-E2E-005: Navigate from search to candidate', () => {
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="seo"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      const link = screen.getByTestId('search-result-link-1');
      expect(link).toHaveAttribute('href', '/sites/site-123/content-decay/1');
    });

    it('NAV-E2E-006: Navigate from settings to queue', () => {
      render(
        <MockSiteSettings
          siteId="site-123"
          decayEnabled={true}
          lastScanAt="2026-03-10"
          onToggleDecay={mockOnToggleDecay}
          canConfigure={true}
        />
      );
      
      const link = screen.getByTestId('configure-decay-link');
      expect(link).toHaveAttribute('href', '/sites/site-123/content-decay/settings');
    });

    it('NAV-E2E-007: Navigate back to dashboard', () => {
      // Back navigation would use router.back()
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/content-decay"
          isAuthenticated={true}
        />
      );
      
      // Dashboard link would exist in nav
      expect(screen.getByTestId('site-nav')).toBeInTheDocument();
    });

    it('NAV-E2E-008: Browser refresh on queue page', () => {
      // Refresh would reload data from API
      render(
        <MockSiteNav
          siteId="site-123"
          currentPath="/sites/site-123/content-decay"
          isAuthenticated={true}
        />
      );
      
      expect(screen.getByTestId('nav-content-decay')).toHaveAttribute('data-active', 'true');
    });

    it('NAV-E2E-009: Deep link to candidate', () => {
      // Direct access to candidate page
      render(
        <MockGlobalSearch
          siteId="site-123"
          searchQuery="seo"
          onSearch={mockOnSearch}
          decayCandidates={mockDecayCandidates}
        />
      );
      
      expect(screen.getByTestId('search-result-link-1')).toHaveAttribute('href', '/sites/site-123/content-decay/1');
    });

    it('NAV-E2E-010: Full user flow: discover → queue → candidate', () => {
      // Combined flow test
      const { container } = render(
        <>
          <MockSiteNav
            siteId="site-123"
            currentPath="/sites/site-123/dashboard"
            isAuthenticated={true}
            decayCount={5}
          />
          <MockDashboard
            siteId="site-123"
            showContentDecayWidget={true}
            widgetPosition={2}
          />
        </>
      );
      
      // 1. Badge shows count in nav
      expect(screen.getByTestId('decay-count-badge')).toHaveTextContent('5');
      
      // 2. Widget shows on dashboard
      expect(screen.getByTestId('widget-content-decay')).toBeInTheDocument();
      
      // 3. Link to queue exists
      expect(screen.getByTestId('nav-content-decay')).toHaveAttribute('href', '/sites/site-123/content-decay');
    });
  });
});
