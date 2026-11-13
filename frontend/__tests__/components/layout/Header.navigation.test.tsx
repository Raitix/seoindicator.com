/**
 * Header Navigation Tests - TASK-37-2
 * Tests for header navigation behavior, auth-based rendering, and link functionality
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock Next.js navigation
const mockPush = vi.fn();
const mockPathname = vi.fn(() => '/');

vi.mock('next/navigation', () => ({
  usePathname: () => mockPathname(),
  useRouter: () => ({
    push: mockPush,
    refresh: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
  }),
}));

// Mock Next.js Link
vi.mock('next/link', () => {
  const MockLink = ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
    <a href={href} {...props}>
      {children}
    </a>
  );
  MockLink.displayName = 'MockLink';
  return { default: MockLink };
});

// Mock notification hooks
vi.mock('@/lib/api/notifications', () => ({
  useUnreadCount: () => ({ count: 0, mutate: vi.fn() }),
}));

vi.mock('@/hooks/useAIVisibilityUnreadCount', () => ({
  useAIVisibilityUnreadCount: () => ({ count: 0, mutate: vi.fn() }),
}));

// Mock Lucide icons - render empty spans to avoid text content issues
vi.mock('lucide-react', () => ({
  LayoutDashboard: () => <span data-testid="dashboard-icon" aria-hidden="true" />,
  FolderKanban: () => <span data-testid="competitors-icon" aria-hidden="true" />,
  Key: () => <span data-testid="keywords-icon" aria-hidden="true" />,
  FileBarChart: () => <span data-testid="reports-icon" aria-hidden="true" />,
  Settings: () => <span data-testid="settings-icon" aria-hidden="true" />,
  Bell: () => <span data-testid="bell-icon" aria-hidden="true" />,
  User: () => <span data-testid="user-icon" aria-hidden="true" />,
  LogOut: () => <span data-testid="logout-icon" aria-hidden="true" />,
  ChevronDown: () => <span data-testid="chevron-icon" aria-hidden="true" />,
  Eye: () => <span data-testid="eye-icon" aria-hidden="true" />,
  Menu: () => <span data-testid="menu-icon" aria-hidden="true" />,
  X: () => <span data-testid="x-icon" aria-hidden="true" />,
}));

// Mock dropdown menu
vi.mock('@/components/ui/dropdown-menu', () => ({
  DropdownMenu: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuItem: ({ children, onClick }: { children: React.ReactNode; onClick?: () => void }) => (
    <button onClick={onClick}>{children}</button>
  ),
  DropdownMenuLabel: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  DropdownMenuSeparator: () => <hr />,
}));

// Mock MobileNav component
vi.mock('./MobileNav', () => ({
  MobileNav: () => <div data-testid="mobile-nav">Mobile Nav</div>,
  NavItem: {},
  UserInfo: {},
}));

// Import Header after mocks
import Header from '@/components/layout/Header';

describe('Header Navigation - TASK-37-2', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockPathname.mockReturnValue('/');
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  // ============================================================
  // NAV-001: Sign In Button Navigation (Guest)
  // ============================================================
  describe('NAV-001: Sign In Button for Guests', () => {
    it('NAV-001a: should show Sign In link for guests', () => {
      render(<Header />);
      
      const signInLink = screen.queryByRole('link', { name: /sign in/i });
      expect(signInLink).toBeInTheDocument();
    });

    it('NAV-001b: should have correct href for Sign In link', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink).toHaveAttribute('href', '/login');
    });

    it('NAV-001c: should not show Sign In for authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const signInLink = screen.queryByRole('link', { name: /sign in/i });
      expect(signInLink).not.toBeInTheDocument();
    });

    it('NAV-001d: should have cursor-pointer class on Sign In link', () => {
      render(<Header />);
      
      const signInLink = screen.getByRole('link', { name: /sign in/i });
      expect(signInLink.className).toMatch(/cursor-pointer/);
    });
  });

  // ============================================================
  // NAV-002: Indicators Button Navigation
  // ============================================================
  describe('NAV-002: Indicators Button', () => {
    it('NAV-002a: should show Indicators link for guests', () => {
      render(<Header />);
      
      const indicatorsLink = screen.queryByRole('link', { name: /indicators/i });
      expect(indicatorsLink).toBeInTheDocument();
    });

    it('NAV-002b: should have correct href for Indicators link', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink).toHaveAttribute('href', '/dashboard');
    });

    it('NAV-002c: should not show Indicators for authenticated users (Dashboard instead)', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const indicatorsLink = screen.queryByRole('link', { name: /indicators/i });
      expect(indicatorsLink).not.toBeInTheDocument();
    });

    it('NAV-002d: should have cursor-pointer class on Indicators link', () => {
      render(<Header />);
      
      const indicatorsLink = screen.getByRole('link', { name: /indicators/i });
      expect(indicatorsLink.className).toMatch(/cursor-pointer/);
    });
  });

  // ============================================================
  // NAV-003: Protected Nav Items Hidden from Guests
  // ============================================================
  describe('NAV-003: Protected Nav Items Hidden from Guests', () => {
    it('NAV-003a: should hide Dashboard from guests', () => {
      render(<Header />);
      
      const dashboardLink = screen.queryByRole('link', { name: /dashboard/i });
      expect(dashboardLink).not.toBeInTheDocument();
    });

    it('NAV-003b: should hide Competitors from guests', () => {
      render(<Header />);
      
      const competitorsLink = screen.queryByRole('link', { name: /competitors/i });
      expect(competitorsLink).not.toBeInTheDocument();
    });

    it('NAV-003c: should hide Keywords from guests', () => {
      render(<Header />);
      
      const keywordsLink = screen.queryByRole('link', { name: /keywords/i });
      expect(keywordsLink).not.toBeInTheDocument();
    });

    it('NAV-003d: should hide Reports from guests', () => {
      render(<Header />);
      
      const reportsLink = screen.queryByRole('link', { name: /reports/i });
      expect(reportsLink).not.toBeInTheDocument();
    });

    it('NAV-003e: should hide AI Visibility from guests', () => {
      render(<Header />);
      
      const aiVisibilityLink = screen.queryByRole('link', { name: /ai visibility/i });
      expect(aiVisibilityLink).not.toBeInTheDocument();
    });

    it('NAV-003f: should hide Settings from guests (desktop nav)', () => {
      render(<Header />);
      
      // Settings link in desktop nav should be hidden
      const settingsLinks = screen.queryAllByRole('link', { name: /settings/i });
      // Settings should only appear in mobile nav, not desktop
      expect(settingsLinks.filter(link => link.className.includes('hidden md:flex')).length).toBe(0);
    });
  });

  // ============================================================
  // NAV-004: Protected Nav Items Visible to Authenticated Users
  // ============================================================
  describe('NAV-004: Protected Nav Items Visible to Authenticated Users', () => {
    it('NAV-004a: should show Dashboard to authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink).toBeInTheDocument();
    });

    it('NAV-004b: should show Competitors to authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const competitorsLink = screen.getByRole('link', { name: /competitors/i });
      expect(competitorsLink).toBeInTheDocument();
    });

    it('NAV-004c: should show Keywords to authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const keywordsLink = screen.getByRole('link', { name: /keywords/i });
      expect(keywordsLink).toBeInTheDocument();
    });

    it('NAV-004d: should show Reports to authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const reportsLink = screen.getByRole('link', { name: /reports/i });
      expect(reportsLink).toBeInTheDocument();
    });

    it('NAV-004e: should show AI Visibility to authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const aiVisibilityLink = screen.getByRole('link', { name: /ai visibility/i });
      expect(aiVisibilityLink).toBeInTheDocument();
    });

    it('NAV-004f: should show Settings to authenticated users', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const settingsLink = screen.getByRole('link', { name: /settings/i });
      expect(settingsLink).toBeInTheDocument();
    });
  });

  // ============================================================
  // NAV-005: Navigation Links Functionality
  // ============================================================
  describe('NAV-005: Navigation Links Functionality', () => {
    it('NAV-005a: Dashboard link should have correct href', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink).toHaveAttribute('href', '/dashboard');
    });

    it('NAV-005b: Keywords link should have correct href', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const keywordsLink = screen.getByRole('link', { name: /keywords/i });
      expect(keywordsLink).toHaveAttribute('href', '/keywords');
    });

    it('NAV-005c: Competitors link should have correct href', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const competitorsLink = screen.getByRole('link', { name: /competitors/i });
      expect(competitorsLink).toHaveAttribute('href', '/competitors');
    });

    it('NAV-005d: Reports link should have correct href', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const reportsLink = screen.getByRole('link', { name: /reports/i });
      expect(reportsLink).toHaveAttribute('href', '/reports');
    });

    it('NAV-005e: Settings link should have correct href', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const settingsLink = screen.getByRole('link', { name: /settings/i });
      expect(settingsLink).toHaveAttribute('href', '/settings');
    });
  });

  // ============================================================
  // ACC-001: Accessibility
  // ============================================================
  describe('ACC-001: Accessibility', () => {
    it('ACC-001a: should have navigation role', () => {
      render(<Header />);
      
      const nav = screen.getByRole('navigation', { name: /main navigation/i });
      expect(nav).toBeInTheDocument();
    });

    it('ACC-001b: should have aria-label on navigation', () => {
      render(<Header />);
      
      const nav = screen.getByRole('navigation');
      expect(nav).toHaveAttribute('aria-label', 'Main navigation');
    });

    it('ACC-001c: should have aria-label on logo link', () => {
      render(<Header />);
      
      const logoLink = screen.getByRole('link', { name: /seoindicator home/i });
      expect(logoLink).toBeInTheDocument();
    });

    it('ACC-001d: should have focus-visible styles', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink.className).toMatch(/focus-visible:/);
    });
  });

  // ============================================================
  // SEC-001: Security
  // ============================================================
  describe('SEC-001: Security', () => {
    it('SEC-001a: should not expose sensitive data in nav', () => {
      render(<Header userName="John Doe" userEmail="secret@example.com" />);
      
      const navContent = screen.getByRole('navigation').textContent;
      expect(navContent).not.toMatch(/token|password|secret|api.key/i);
    });

    it('SEC-001b: should handle XSS in userName safely', () => {
      // The component should render the userName but not execute scripts
      render(<Header userName='<script>alert("xss")</script>' userEmail="test@example.com" />);
      
      // Check that no script elements are rendered
      const scripts = document.querySelectorAll('script:not([src])');
      expect(scripts.length).toBe(0);
    });

    it('SEC-001c: should handle special characters in userName', () => {
      render(<Header userName='Test & "Quote" <Script>' userEmail="test@example.com" />);
      
      // Should render without crashing
      expect(screen.getByRole('navigation')).toBeInTheDocument();
    });
  });

  // ============================================================
  // Edge Cases
  // ============================================================
  describe('Edge Cases', () => {
    it('should handle undefined userName gracefully', () => {
      render(<Header userName={undefined} userEmail={undefined} />);
      
      // Should show guest navigation
      expect(screen.getByRole('link', { name: /sign in/i })).toBeInTheDocument();
    });

    it('should handle null userName gracefully', () => {
      render(<Header userName={null as any} userEmail={null as any} />);
      
      // Should show guest navigation
      expect(screen.getByRole('link', { name: /sign in/i })).toBeInTheDocument();
    });

    it('should handle empty string userName as guest', () => {
      render(<Header userName="" userEmail="" />);
      
      // Empty string is falsy, should show guest navigation
      expect(screen.getByRole('link', { name: /sign in/i })).toBeInTheDocument();
    });

    it('should handle very long userName', () => {
      const longName = 'A'.repeat(100);
      render(<Header userName={longName} userEmail="test@example.com" />);
      
      // Should render without crashing
      expect(screen.getByRole('navigation')).toBeInTheDocument();
    });

    it('should handle unicode in userName', () => {
      render(<Header userName="用户名 🎉" userEmail="test@example.com" />);
      
      // Should render without crashing
      expect(screen.getByRole('navigation')).toBeInTheDocument();
    });
  });

  // ============================================================
  // Active State Tests
  // ============================================================
  describe('Active State Indication', () => {
    it('should mark Dashboard as active when on /dashboard', () => {
      mockPathname.mockReturnValue('/dashboard');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
      expect(dashboardLink).toHaveAttribute('aria-current', 'page');
    });

    it('should mark Keywords as active when on /keywords', () => {
      mockPathname.mockReturnValue('/keywords');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const keywordsLink = screen.getByRole('link', { name: /keywords/i });
      expect(keywordsLink).toHaveAttribute('aria-current', 'page');
    });

    it('should mark Competitors as active when on /competitors', () => {
      mockPathname.mockReturnValue('/competitors');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const competitorsLink = screen.getByRole('link', { name: /competitors/i });
      expect(competitorsLink).toHaveAttribute('aria-current', 'page');
    });

    it('should mark Reports as active when on /reports', () => {
      mockPathname.mockReturnValue('/reports');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const reportsLink = screen.getByRole('link', { name: /reports/i });
      expect(reportsLink).toHaveAttribute('aria-current', 'page');
    });

    it('should mark Settings as active when on /settings', () => {
      mockPathname.mockReturnValue('/settings');
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const settingsLink = screen.getByRole('link', { name: /settings/i });
      expect(settingsLink).toHaveAttribute('aria-current', 'page');
    });
  });

  // ============================================================
  // Logo Navigation
  // ============================================================
  describe('Logo Navigation', () => {
    it('should have logo link pointing to /dashboard', () => {
      render(<Header userName="John Doe" userEmail="john@example.com" />);
      
      const logoLink = screen.getByRole('link', { name: /seoindicator home/i });
      expect(logoLink).toHaveAttribute('href', '/dashboard');
    });

    it('should have logo link pointing to / for guests', () => {
      render(<Header />);
      
      const logoLink = screen.getByRole('link', { name: /seoindicator home/i });
      expect(logoLink).toHaveAttribute('href', '/');
    });
  });

  // ============================================================
  // Guest Navigation Structure
  // ============================================================
  describe('Guest Navigation Structure', () => {
    it('should only show Indicators and Sign In for guests', () => {
      render(<Header />);
      
      // Guest should see limited nav
      const navLinks = screen.getAllByRole('link').filter(link => 
        link.closest('nav') !== null
      );
      
      // Should not include protected items
      expect(navLinks.filter(link => link.textContent?.match(/dashboard/i)).length).toBe(0);
      expect(navLinks.filter(link => link.textContent?.match(/competitors/i)).length).toBe(0);
      expect(navLinks.filter(link => link.textContent?.match(/keywords/i)).length).toBe(0);
      expect(navLinks.filter(link => link.textContent?.match(/reports/i)).length).toBe(0);
    });
  });
});
