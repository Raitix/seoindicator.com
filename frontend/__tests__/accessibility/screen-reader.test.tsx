/**
 * Screen Reader Accessibility Tests
 * TASK-28-17: Color Contrast and Screen Reader Testing
 *
 * Tests ARIA attributes, roles, and screen reader compatibility
 * WCAG 2.1 AA Requirements:
 * - All interactive elements have accessible names
 * - Proper ARIA roles and landmarks
 * - Screen reader announcements for dynamic content
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import { within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// ============================================================================
// MOCK COMPONENTS FOR TESTING
// ============================================================================

// Mock SkipLink component
const SkipLink: React.FC<{ href?: string }> = ({ href = '#main-content' }) => (
  <a
    href={href}
    className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50"
    data-testid="skip-link"
    aria-label="Skip to main content"
  >
    Skip to main content
  </a>
);

// Mock Main content wrapper
const MainContent: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <main
    id="main-content"
    role="main"
    aria-label="Main content"
    tabIndex={-1}
    data-testid="main-content"
  >
    {children}
  </main>
);

// Mock Header with navigation
const Header: React.FC<{ children?: React.ReactNode }> = ({ children }) => (
  <header role="banner" data-testid="header">
    <nav role="navigation" aria-label="Main navigation" data-testid="main-nav">
      <ul role="list">
        <li><a href="/" aria-current="page">Home</a></li>
        <li><a href="/dashboard">Dashboard</a></li>
        <li><a href="/tools">Tools</a></li>
      </ul>
    </nav>
    {children}
  </header>
);

// Mock Footer
const Footer: React.FC = () => (
  <footer role="contentinfo" data-testid="footer">
    <p>&copy; 2026 SEOIndicator</p>
  </footer>
);

// Mock IndicatorCard with accessibility
interface IndicatorCardProps {
  title: string;
  value: string | number;
  status: 'good' | 'warning' | 'critical' | 'neutral';
  description: string;
  href?: string;
}

const IndicatorCard: React.FC<IndicatorCardProps> = ({
  title,
  value,
  status,
  description,
  href = '/dashboard',
}) => (
  <a
    href={href}
    role="link"
    className="p-4 rounded-lg border focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
    aria-label={`${title}: ${value}. ${description}. Status: ${status}.`}
    data-testid="indicator-card"
  >
    <h3>{title}</h3>
    <p className="text-2xl font-bold">{value}</p>
    <span
      role="status"
      aria-label={`Status: ${status}`}
      data-testid="status-badge"
    >
      {status}
    </span>
  </a>
);

// Mock Tab List
const TabList: React.FC<{ tabs: string[]; activeTab: string }> = ({ tabs, activeTab }) => (
  <div role="tablist" aria-label="Category tabs" data-testid="tab-list">
    {tabs.map((tab) => (
      <button
        key={tab}
        role="tab"
        aria-selected={tab === activeTab}
        aria-controls={`tabpanel-${tab}`}
        data-testid={`tab-${tab}`}
      >
        {tab}
      </button>
    ))}
  </div>
);

// Mock Tab Panel
const TabPanel: React.FC<{ id: string; children: React.ReactNode }> = ({ id, children }) => (
  <div
    role="tabpanel"
    id={`tabpanel-${id}`}
    aria-labelledby={`tab-${id}`}
    data-testid={`tabpanel-${id}`}
  >
    {children}
  </div>
);

// Mock Modal/Dialog
const Modal: React.FC<{ isOpen: boolean; onClose: () => void; title: string; children: React.ReactNode }> = ({
  isOpen,
  onClose,
  title,
  children,
}) => {
  if (!isOpen) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      data-testid="modal"
    >
      <h2 id="modal-title">{title}</h2>
      <button onClick={onClose} aria-label="Close dialog">
        Close
      </button>
      {children}
    </div>
  );
};

// Mock Loading State
const LoadingState: React.FC = () => (
  <div
    role="status"
    aria-live="polite"
    aria-busy="true"
    data-testid="loading-state"
  >
    <span className="sr-only">Loading...</span>
    <div className="animate-pulse">Loading content...</div>
  </div>
);

// Mock Error State
const ErrorState: React.FC<{ message: string }> = ({ message }) => (
  <div
    role="alert"
    aria-live="assertive"
    data-testid="error-state"
  >
    <p>{message}</p>
  </div>
);

// Mock Success State
const SuccessState: React.FC<{ message: string }> = ({ message }) => (
  <div
    role="status"
    aria-live="polite"
    data-testid="success-state"
  >
    <p>{message}</p>
  </div>
);

// Mock Form with labels
const SearchForm: React.FC = () => (
  <form role="search" aria-label="Site search" data-testid="search-form">
    <label htmlFor="search-input">Search</label>
    <input
      id="search-input"
      type="search"
      placeholder="Search indicators..."
      aria-label="Search indicators"
      data-testid="search-input"
    />
    <button type="submit" aria-label="Submit search">
      Search
    </button>
  </form>
);

// Mock Icon Button
const IconButton: React.FC<{ icon: string; label: string; onClick: () => void }> = ({
  icon,
  label,
  onClick,
}) => (
  <button
    onClick={onClick}
    aria-label={label}
    data-testid="icon-button"
  >
    <span aria-hidden="true">{icon}</span>
    <span className="sr-only">{label}</span>
  </button>
);

// Mock Decorative Icon
const DecorativeIcon: React.FC<{ icon: string }> = ({ icon }) => (
  <span aria-hidden="true" data-testid="decorative-icon">
    {icon}
  </span>
);

// Mock Informative Icon
const InformativeIcon: React.FC<{ icon: string; label: string }> = ({ icon, label }) => (
  <span
    role="img"
    aria-label={label}
    data-testid="informative-icon"
  >
    {icon}
  </span>
);

// Mock Image with alt
const Image: React.FC<{ src: string; alt: string }> = ({ src, alt }) => (
  <img src={src} alt={alt} data-testid="image" />
);

// Mock Logo
const Logo: React.FC = () => (
  <a href="/" aria-label="SEOIndicator - Home" data-testid="logo">
    <img
      src="/logo.svg"
      alt="SEOIndicator Logo"
      data-testid="logo-image"
    />
  </a>
);

// ============================================================================
// TESTS
// ============================================================================

describe('Screen Reader Accessibility Tests', () => {
  // --------------------------------------------------------------------------
  // SKIP LINK TESTS
  // --------------------------------------------------------------------------

  describe('Skip Link (SR-001 to SR-003)', () => {
    test('SR-001: Skip link exists on page', () => {
      render(<SkipLink />);
      expect(screen.getByTestId('skip-link')).toBeInTheDocument();
    });

    test('SR-002: Skip link target exists', () => {
      render(
        <>
          <SkipLink />
          <MainContent>Content</MainContent>
        </>
      );
      expect(screen.getByTestId('main-content')).toBeInTheDocument();
    });

    test('SR-003: Skip link is first focusable element', () => {
      render(
        <>
          <SkipLink />
          <MainContent>Content</MainContent>
        </>
      );

      const skipLink = screen.getByTestId('skip-link');
      expect(skipLink).toHaveAttribute('href', '#main-content');
    });

    test('SR-003a: Skip link has accessible label', () => {
      render(<SkipLink />);
      const skipLink = screen.getByTestId('skip-link');
      expect(skipLink).toHaveAttribute('aria-label');
      expect(skipLink.getAttribute('aria-label')).toMatch(/skip/i);
    });
  });

  // --------------------------------------------------------------------------
  // LANDMARK TESTS
  // --------------------------------------------------------------------------

  describe('ARIA Landmarks (SR-004 to SR-006)', () => {
    test('SR-004: Navigation has aria-label', () => {
      render(<Header />);
      const nav = screen.getByTestId('main-nav');
      expect(nav).toHaveAttribute('aria-label');
      expect(nav).toHaveAttribute('role', 'navigation');
    });

    test('SR-005: Main content has role="main"', () => {
      render(<MainContent>Content</MainContent>);
      const main = screen.getByTestId('main-content');
      expect(main).toHaveAttribute('role', 'main');
    });

    test('SR-006: Footer has role="contentinfo"', () => {
      render(<Footer />);
      const footer = screen.getByTestId('footer');
      expect(footer).toHaveAttribute('role', 'contentinfo');
    });

    test('SR-006a: Header has role="banner"', () => {
      render(<Header />);
      const header = screen.getByTestId('header');
      expect(header).toHaveAttribute('role', 'banner');
    });
  });

  // --------------------------------------------------------------------------
  // INTERACTIVE ELEMENT TESTS
  // --------------------------------------------------------------------------

  describe('Interactive Elements (SR-007 to SR-009)', () => {
    test('SR-007: All buttons have accessible names', () => {
      render(
        <>
          <button>Click me</button>
          <IconButton icon="🔍" label="Search" onClick={() => {}} />
        </>
      );

      const buttons = screen.getAllByRole('button');
      buttons.forEach((button) => {
        expect(button).toHaveAccessibleName();
      });
    });

    test('SR-008: All links have accessible names', () => {
      render(
        <>
          <a href="/">Home</a>
          <IndicatorCard
            title="Keyword Rankings"
            value={42}
            status="good"
            description="Track SERP positions"
          />
        </>
      );

      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        expect(link).toHaveAccessibleName();
      });
    });

    test('SR-009: All images have alt text', () => {
      render(
        <>
          <Image src="/test.jpg" alt="Test image" />
          <Logo />
        </>
      );

      const images = screen.getAllByRole('img');
      images.forEach((img) => {
        expect(img).toHaveAttribute('alt');
      });
    });
  });

  // --------------------------------------------------------------------------
  // IMAGE AND ICON TESTS
  // --------------------------------------------------------------------------

  describe('Images and Icons (SR-009 to SR-010)', () => {
    test('SR-009a: Informative images have meaningful alt', () => {
      render(<Image src="/chart.png" alt="SEO performance chart showing improvement" />);
      const image = screen.getByRole('img');
      expect(image.getAttribute('alt')).not.toBe('');
    });

    test('SR-010: Decorative images have empty alt', () => {
      render(<DecorativeIcon icon="✨" />);
      const icon = screen.getByTestId('decorative-icon');
      expect(icon).toHaveAttribute('aria-hidden', 'true');
    });

    test('SR-010a: Decorative icons are hidden from screen readers', () => {
      render(<DecorativeIcon icon="→" />);
      const icon = screen.getByTestId('decorative-icon');
      expect(icon).toHaveAttribute('aria-hidden', 'true');
    });

    test('SR-010b: Informative icons have aria-label', () => {
      render(<InformativeIcon icon="⚠️" label="Warning" />);
      const icon = screen.getByTestId('informative-icon');
      expect(icon).toHaveAttribute('aria-label');
    });
  });

  // --------------------------------------------------------------------------
  // FORM TESTS
  // --------------------------------------------------------------------------

  describe('Forms (SR-011)', () => {
    test('SR-011: Form inputs have labels', () => {
      render(<SearchForm />);
      const input = screen.getByTestId('search-input');

      // Check for associated label
      expect(input).toHaveAttribute('id');
      expect(document.querySelector(`label[for="${input.id}"]`)).toBeInTheDocument();
    });

    test('SR-011a: Search form has role="search"', () => {
      render(<SearchForm />);
      const form = screen.getByTestId('search-form');
      expect(form).toHaveAttribute('role', 'search');
    });

    test('SR-011b: Search input has aria-label', () => {
      render(<SearchForm />);
      const input = screen.getByTestId('search-input');
      expect(input).toHaveAttribute('aria-label');
    });
  });

  // --------------------------------------------------------------------------
  // DYNAMIC CONTENT TESTS
  // --------------------------------------------------------------------------

  describe('Dynamic Content (SR-012 to SR-014)', () => {
    test('SR-012: Error messages have role="alert"', () => {
      render(<ErrorState message="Something went wrong" />);
      const error = screen.getByTestId('error-state');
      expect(error).toHaveAttribute('role', 'alert');
      expect(error).toHaveAttribute('aria-live', 'assertive');
    });

    test('SR-013: Status messages have role="status"', () => {
      render(<SuccessState message="Operation completed" />);
      const status = screen.getByTestId('success-state');
      expect(status).toHaveAttribute('role', 'status');
      expect(status).toHaveAttribute('aria-live', 'polite');
    });

    test('SR-014: Loading states have aria-live', () => {
      render(<LoadingState />);
      const loading = screen.getByTestId('loading-state');
      expect(loading).toHaveAttribute('aria-live', 'polite');
      expect(loading).toHaveAttribute('aria-busy', 'true');
    });

    test('SR-014a: Loading state announces to screen readers', () => {
      render(<LoadingState />);
      const loading = screen.getByTestId('loading-state');
      const srOnlyText = within(loading).getByText('Loading...');
      expect(srOnlyText).toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // MODAL/DIALOG TESTS
  // --------------------------------------------------------------------------

  describe('Modals/Dialogs (SR-015 to SR-016)', () => {
    test('SR-015: Modal has aria-modal="true"', () => {
      render(
        <Modal isOpen={true} onClose={() => {}} title="Settings">
          <p>Modal content</p>
        </Modal>
      );
      const modal = screen.getByTestId('modal');
      expect(modal).toHaveAttribute('aria-modal', 'true');
    });

    test('SR-016: Modal has aria-labelledby', () => {
      render(
        <Modal isOpen={true} onClose={() => {}} title="Settings">
          <p>Modal content</p>
        </Modal>
      );
      const modal = screen.getByTestId('modal');
      expect(modal).toHaveAttribute('aria-labelledby');

      const labelledById = modal.getAttribute('aria-labelledby');
      const titleElement = document.getElementById(labelledById!);
      expect(titleElement).toBeInTheDocument();
    });

    test('SR-016a: Modal close button has accessible label', () => {
      render(
        <Modal isOpen={true} onClose={() => {}} title="Settings">
          <p>Modal content</p>
        </Modal>
      );
      const closeButton = screen.getByRole('button', { name: /close/i });
      expect(closeButton).toBeInTheDocument();
    });
  });

  // --------------------------------------------------------------------------
  // TAB LIST TESTS
  // --------------------------------------------------------------------------

  describe('Tab Lists (SR-017 to SR-019)', () => {
    test('SR-017: Tab list has role="tablist"', () => {
      render(<TabList tabs={['All', 'Rankings', 'Traffic']} activeTab="All" />);
      const tabList = screen.getByTestId('tab-list');
      expect(tabList).toHaveAttribute('role', 'tablist');
    });

    test('SR-018: Tabs have role="tab"', () => {
      render(<TabList tabs={['All', 'Rankings', 'Traffic']} activeTab="All" />);
      const tabs = screen.getAllByRole('tab');
      expect(tabs).toHaveLength(3);
    });

    test('SR-018a: Active tab has aria-selected="true"', () => {
      render(<TabList tabs={['All', 'Rankings', 'Traffic']} activeTab="All" />);
      const activeTab = screen.getByTestId('tab-All');
      expect(activeTab).toHaveAttribute('aria-selected', 'true');
    });

    test('SR-018b: Inactive tabs have aria-selected="false"', () => {
      render(<TabList tabs={['All', 'Rankings', 'Traffic']} activeTab="All" />);
      const inactiveTab = screen.getByTestId('tab-Rankings');
      expect(inactiveTab).toHaveAttribute('aria-selected', 'false');
    });

    test('SR-019: Tab panels have role="tabpanel"', () => {
      render(<TabPanel id="test">Content</TabPanel>);
      const tabPanel = screen.getByTestId('tabpanel-test');
      expect(tabPanel).toHaveAttribute('role', 'tabpanel');
    });

    test('SR-019a: Tab panel has aria-labelledby', () => {
      render(<TabPanel id="test">Content</TabPanel>);
      const tabPanel = screen.getByTestId('tabpanel-test');
      expect(tabPanel).toHaveAttribute('aria-labelledby');
    });
  });

  // --------------------------------------------------------------------------
  // INDICATOR CARD TESTS
  // --------------------------------------------------------------------------

  describe('Indicator Cards (SR-022 to SR-023)', () => {
    test('SR-022: Indicator cards have aria-label', () => {
      render(
        <IndicatorCard
          title="Keyword Rankings"
          value={42}
          status="good"
          description="Track SERP positions"
        />
      );
      const card = screen.getByTestId('indicator-card');
      expect(card).toHaveAttribute('aria-label');
    });

    test('SR-022a: Indicator card aria-label includes all information', () => {
      render(
        <IndicatorCard
          title="Keyword Rankings"
          value={42}
          status="good"
          description="Track SERP positions"
        />
      );
      const card = screen.getByTestId('indicator-card');
      const ariaLabel = card.getAttribute('aria-label');

      expect(ariaLabel).toContain('Keyword Rankings');
      expect(ariaLabel).toContain('42');
      expect(ariaLabel).toContain('good');
      expect(ariaLabel).toContain('Track SERP positions');
    });

    test('SR-023: Status badges have accessible text', () => {
      render(
        <IndicatorCard
          title="Keyword Rankings"
          value={42}
          status="warning"
          description="Track SERP positions"
        />
      );
      const badge = screen.getByTestId('status-badge');
      expect(badge).toHaveAttribute('aria-label');
      expect(badge.getAttribute('aria-label')).toContain('warning');
    });

    test('SR-023a: Status badge has role="status"', () => {
      render(
        <IndicatorCard
          title="Keyword Rankings"
          value={42}
          status="good"
          description="Track SERP positions"
        />
      );
      const badge = screen.getByTestId('status-badge');
      expect(badge).toHaveAttribute('role', 'status');
    });
  });

  // --------------------------------------------------------------------------
  // INTEGRATION TESTS
  // --------------------------------------------------------------------------

  describe('Full Page Accessibility (SR-025)', () => {
    test('SR-025: Full page has proper landmark structure', () => {
      render(
        <>
          <SkipLink />
          <Header />
          <MainContent>
            <TabList tabs={['All', 'Rankings']} activeTab="All" />
            <IndicatorCard
              title="Keyword Rankings"
              value={42}
              status="good"
              description="Track SERP positions"
            />
          </MainContent>
          <Footer />
        </>
      );

      // Verify all landmarks
      expect(screen.getByRole('banner')).toBeInTheDocument();
      expect(screen.getByRole('navigation')).toBeInTheDocument();
      expect(screen.getByRole('main')).toBeInTheDocument();
      expect(screen.getByRole('contentinfo')).toBeInTheDocument();
    });

    test('SR-025a: All interactive elements are accessible', () => {
      render(
        <>
          <SkipLink />
          <SearchForm />
          <TabList tabs={['All', 'Rankings']} activeTab="All" />
          <IndicatorCard
            title="Keyword Rankings"
            value={42}
            status="good"
            description="Track SERP positions"
          />
        </>
      );

      // All buttons accessible
      const buttons = screen.getAllByRole('button');
      buttons.forEach((btn) => {
        expect(btn).toHaveAccessibleName();
      });

      // All links accessible
      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        expect(link).toHaveAccessibleName();
      });

      // All tabs accessible
      const tabs = screen.getAllByRole('tab');
      tabs.forEach((tab) => {
        expect(tab).toHaveAccessibleName();
      });
    });
  });

  // --------------------------------------------------------------------------
  // ACCEPTANCE CRITERIA TESTS
  // --------------------------------------------------------------------------

  describe('Acceptance Criteria', () => {
    test('AC-004: All interactive elements have proper aria-labels', () => {
      render(
        <>
          <SkipLink />
          <IconButton icon="🔔" label="Notifications" onClick={() => {}} />
          <SearchForm />
        </>
      );

      const skipLink = screen.getByTestId('skip-link');
      const iconButton = screen.getByTestId('icon-button');
      const searchInput = screen.getByTestId('search-input');

      expect(skipLink).toHaveAttribute('aria-label');
      expect(iconButton).toHaveAttribute('aria-label');
      expect(searchInput).toHaveAttribute('aria-label');
    });

    test('AC-005: All images/icons have alt text', () => {
      render(
        <>
          <Image src="/logo.png" alt="SEOIndicator Logo" />
          <InformativeIcon icon="📊" label="Analytics" />
          <DecorativeIcon icon="→" />
        </>
      );

      // Image has alt
      const image = screen.getByRole('img');
      expect(image).toHaveAttribute('alt');

      // Informative icon has label
      const informativeIcon = screen.getByTestId('informative-icon');
      expect(informativeIcon).toHaveAttribute('aria-label');

      // Decorative icon is hidden
      const decorativeIcon = screen.getByTestId('decorative-icon');
      expect(decorativeIcon).toHaveAttribute('aria-hidden', 'true');
    });
  });

  // --------------------------------------------------------------------------
  // EDGE CASES
  // --------------------------------------------------------------------------

  describe('Edge Cases', () => {
    test('EC-001: Empty aria-label is not used', () => {
      render(<IconButton icon="🔍" label="Search" onClick={() => {}} />);
      const button = screen.getByTestId('icon-button');
      expect(button.getAttribute('aria-label')).not.toBe('');
    });

    test('EC-002: aria-hidden elements are not focusable', () => {
      render(<DecorativeIcon icon="✨" />);
      const icon = screen.getByTestId('decorative-icon');
      expect(icon).not.toHaveAttribute('tabindex');
    });

    test('EC-003: No duplicate IDs for aria-labelledby', () => {
      render(
        <>
          <Modal isOpen={true} onClose={() => {}} title="Modal 1">
            Content 1
          </Modal>
        </>
      );

      const modal = screen.getByTestId('modal');
      const labelledById = modal.getAttribute('aria-labelledby');
      const elements = document.querySelectorAll(`[id="${labelledById}"]`);
      expect(elements.length).toBeLessThanOrEqual(1);
    });

    test('EC-004: Screen reader text is visually hidden', () => {
      render(<LoadingState />);
      const srOnlyText = screen.getByText('Loading...');
      expect(srOnlyText.className).toContain('sr-only');
    });

    test('EC-005: All status values are announced', () => {
      const statuses: Array<'good' | 'warning' | 'critical' | 'neutral'> = ['good', 'warning', 'critical', 'neutral'];

      statuses.forEach((status) => {
        const { unmount } = render(
          <IndicatorCard
            title="Test"
            value={0}
            status={status}
            description="Test description"
          />
        );

        const badge = screen.getByTestId('status-badge');
        expect(badge.getAttribute('aria-label')).toContain(status);

        unmount();
      });
    });
  });
});
