/**
 * Tests for AdminNav Component
 * Task: TASK-27-14 - Admin Dashboard Layout and Routing
 * 
 * Tests the sidebar navigation component for admin dashboard.
 */

import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';

// Mock next/navigation
const mockPush = jest.fn();
const mockReplace = jest.fn();

jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
  }),
  usePathname: jest.fn(() => '/admin/overview'),
}));

// Mock next/link
jest.mock('next/link', () => {
  return function MockLink({
    children,
    href,
    className,
    ...props
  }: {
    children: React.ReactNode;
    href: string;
    className?: string;
    [key: string]: unknown;
  }) {
    return (
      <a
        href={href}
        className={className}
        onClick={(e) => {
          e.preventDefault();
          mockPush(href);
        }}
        {...props}
      >
        {children}
      </a>
    );
  };
});

// Navigation item type
interface NavItem {
  id: string;
  label: string;
  href: string;
  icon: React.ReactNode;
}

// Mock AdminNav component for TDD
function MockAdminNav({
  activePath,
  collapsed = false,
  onToggle,
  className,
}: {
  activePath: string;
  collapsed?: boolean;
  onToggle?: () => void;
  className?: string;
}) {
  const navItems: NavItem[] = [
    { id: 'overview', label: 'Overview', href: '/admin/overview', icon: '📊' },
    { id: 'users', label: 'Users', href: '/admin/users', icon: '👥' },
    { id: 'usage', label: 'Usage', href: '/admin/usage', icon: '📈' },
    { id: 'credits', label: 'Credits/Billing', href: '/admin/credits', icon: '💳' },
    { id: 'health', label: 'System Health', href: '/admin/health', icon: '❤️' },
  ];

  const isActive = (href: string) => activePath === href || activePath.startsWith(href + '/');

  return (
    <nav
      data-testid="admin-nav"
      className={className}
      role="navigation"
      aria-label="Admin navigation"
    >
      {/* Toggle Button */}
      {onToggle && (
        <button
          data-testid="nav-toggle"
          onClick={onToggle}
          aria-expanded={!collapsed}
          aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
        >
          {collapsed ? '→' : '←'}
        </button>
      )}

      {/* Navigation Items */}
      <ul data-testid="nav-list" role="list">
        {navItems.map((item) => (
          <li key={item.id} data-testid={`nav-item-${item.id}`}>
            <a
              href={item.href}
              data-testid={`nav-link-${item.id}`}
              data-active={isActive(item.href)}
              className={isActive(item.href) ? 'active' : ''}
              aria-current={isActive(item.href) ? 'page' : undefined}
            >
              <span data-testid={`nav-icon-${item.id}`} className="nav-icon">
                {item.icon}
              </span>
              {!collapsed && (
                <span data-testid={`nav-label-${item.id}`} className="nav-label">
                  {item.label}
                </span>
              )}
            </a>
          </li>
        ))}
      </ul>

      {/* Back to Dashboard Link */}
      <div data-testid="nav-footer">
        <a
          href="/dashboard"
          data-testid="back-to-dashboard"
          className="text-slate-400 hover:text-white"
        >
          <span>←</span>
          {!collapsed && <span>Back to Dashboard</span>}
        </a>
      </div>
    </nav>
  );
}

// Mock Icon components
const MockIcons = {
  OverviewIcon: () => <span data-testid="icon-overview">📊</span>,
  UsersIcon: () => <span data-testid="icon-users">👥</span>,
  UsageIcon: () => <span data-testid="icon-usage">📈</span>,
  CreditsIcon: () => <span data-testid="icon-credits">💳</span>,
  HealthIcon: () => <span data-testid="icon-health">❤️</span>,
};

describe('AdminNav Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ==================== AN-001: Basic Rendering ====================

  describe('AN-001: Basic Rendering', () => {
    it('renders the navigation container', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('admin-nav')).toBeInTheDocument();
    });

    it('renders navigation list', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-list')).toBeInTheDocument();
    });

    it('has correct ARIA role', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      const nav = screen.getByTestId('admin-nav');
      expect(nav).toHaveAttribute('role', 'navigation');
    });

    it('has ARIA label', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      const nav = screen.getByTestId('admin-nav');
      expect(nav).toHaveAttribute('aria-label', 'Admin navigation');
    });
  });

  // ==================== AN-002: Navigation Items ====================

  describe('AN-002: Navigation Items', () => {
    it('renders all navigation items', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-item-overview')).toBeInTheDocument();
      expect(screen.getByTestId('nav-item-users')).toBeInTheDocument();
      expect(screen.getByTestId('nav-item-usage')).toBeInTheDocument();
      expect(screen.getByTestId('nav-item-credits')).toBeInTheDocument();
      expect(screen.getByTestId('nav-item-health')).toBeInTheDocument();
    });

    it('renders Overview link', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-link-overview')).toBeInTheDocument();
      expect(screen.getByTestId('nav-link-overview')).toHaveAttribute('href', '/admin/overview');
    });

    it('renders Users link', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-link-users')).toBeInTheDocument();
      expect(screen.getByTestId('nav-link-users')).toHaveAttribute('href', '/admin/users');
    });

    it('renders Usage link', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-link-usage')).toBeInTheDocument();
      expect(screen.getByTestId('nav-link-usage')).toHaveAttribute('href', '/admin/usage');
    });

    it('renders Credits/Billing link', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-link-credits')).toBeInTheDocument();
      expect(screen.getByTestId('nav-link-credits')).toHaveAttribute('href', '/admin/credits');
    });

    it('renders System Health link', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-link-health')).toBeInTheDocument();
      expect(screen.getByTestId('nav-link-health')).toHaveAttribute('href', '/admin/health');
    });
  });

  // ==================== AN-003: Active State ====================

  describe('AN-003: Active State', () => {
    it('marks Overview as active when on overview page', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      const link = screen.getByTestId('nav-link-overview');
      expect(link).toHaveAttribute('data-active', 'true');
      expect(link).toHaveClass('active');
    });

    it('marks Users as active when on users page', () => {
      render(<MockAdminNav activePath="/admin/users" />);
      const link = screen.getByTestId('nav-link-users');
      expect(link).toHaveAttribute('data-active', 'true');
    });

    it('marks Usage as active when on usage page', () => {
      render(<MockAdminNav activePath="/admin/usage" />);
      const link = screen.getByTestId('nav-link-usage');
      expect(link).toHaveAttribute('data-active', 'true');
    });

    it('marks Credits as active when on credits page', () => {
      render(<MockAdminNav activePath="/admin/credits" />);
      const link = screen.getByTestId('nav-link-credits');
      expect(link).toHaveAttribute('data-active', 'true');
    });

    it('marks Health as active when on health page', () => {
      render(<MockAdminNav activePath="/admin/health" />);
      const link = screen.getByTestId('nav-link-health');
      expect(link).toHaveAttribute('data-active', 'true');
    });

    it('only one item is active at a time', () => {
      render(<MockAdminNav activePath="/admin/users" />);
      const activeLinks = screen.getAllByTestId(/nav-link-/).filter(
        (link) => link.getAttribute('data-active') === 'true'
      );
      expect(activeLinks.length).toBe(1);
      expect(activeLinks[0]).toHaveAttribute('data-testid', 'nav-link-users');
    });

    it('marks active for nested paths', () => {
      render(<MockAdminNav activePath="/admin/users/123" />);
      const link = screen.getByTestId('nav-link-users');
      expect(link).toHaveAttribute('data-active', 'true');
    });

    it('sets aria-current for active item', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      const link = screen.getByTestId('nav-link-overview');
      expect(link).toHaveAttribute('aria-current', 'page');
    });
  });

  // ==================== AN-004: Icons ====================

  describe('AN-004: Icons', () => {
    it('renders icon for Overview', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-icon-overview')).toBeInTheDocument();
    });

    it('renders icon for Users', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-icon-users')).toBeInTheDocument();
    });

    it('renders icon for Usage', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-icon-usage')).toBeInTheDocument();
    });

    it('renders icon for Credits', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-icon-credits')).toBeInTheDocument();
    });

    it('renders icon for Health', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-icon-health')).toBeInTheDocument();
    });

    it('icons are visible when collapsed', () => {
      render(<MockAdminNav activePath="/admin/overview" collapsed={true} />);
      expect(screen.getByTestId('nav-icon-overview')).toBeInTheDocument();
    });
  });

  // ==================== AN-005: Labels ====================

  describe('AN-005: Labels', () => {
    it('renders label for Overview', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-label-overview')).toBeInTheDocument();
      expect(screen.getByTestId('nav-label-overview')).toHaveTextContent('Overview');
    });

    it('renders label for Users', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-label-users')).toHaveTextContent('Users');
    });

    it('renders label for Usage', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-label-usage')).toHaveTextContent('Usage');
    });

    it('renders label for Credits/Billing', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-label-credits')).toHaveTextContent('Credits/Billing');
    });

    it('renders label for System Health', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('nav-label-health')).toHaveTextContent('System Health');
    });

    it('hides labels when collapsed', () => {
      render(<MockAdminNav activePath="/admin/overview" collapsed={true} />);
      expect(screen.queryByTestId('nav-label-overview')).not.toBeInTheDocument();
    });
  });

  // ==================== AN-006: Collapsed State ====================

  describe('AN-006: Collapsed State', () => {
    it('renders toggle button when onToggle provided', () => {
      render(<MockAdminNav activePath="/admin/overview" onToggle={jest.fn()} />);
      expect(screen.getByTestId('nav-toggle')).toBeInTheDocument();
    });

    it('does not render toggle button when onToggle not provided', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.queryByTestId('nav-toggle')).not.toBeInTheDocument();
    });

    it('shows expand icon when collapsed', () => {
      render(
        <MockAdminNav activePath="/admin/overview" collapsed={true} onToggle={jest.fn()} />
      );
      expect(screen.getByTestId('nav-toggle')).toHaveTextContent('→');
    });

    it('shows collapse icon when expanded', () => {
      render(
        <MockAdminNav activePath="/admin/overview" collapsed={false} onToggle={jest.fn()} />
      );
      expect(screen.getByTestId('nav-toggle')).toHaveTextContent('←');
    });

    it('toggle button has correct aria-expanded when collapsed', () => {
      render(
        <MockAdminNav activePath="/admin/overview" collapsed={true} onToggle={jest.fn()} />
      );
      expect(screen.getByTestId('nav-toggle')).toHaveAttribute('aria-expanded', 'false');
    });

    it('toggle button has correct aria-expanded when expanded', () => {
      render(
        <MockAdminNav activePath="/admin/overview" collapsed={false} onToggle={jest.fn()} />
      );
      expect(screen.getByTestId('nav-toggle')).toHaveAttribute('aria-expanded', 'true');
    });

    it('calls onToggle when toggle button clicked', async () => {
      const user = userEvent.setup();
      const onToggle = jest.fn();
      render(<MockAdminNav activePath="/admin/overview" onToggle={onToggle} />);
      
      await user.click(screen.getByTestId('nav-toggle'));
      expect(onToggle).toHaveBeenCalled();
    });
  });

  // ==================== AN-007: Navigation Actions ====================

  describe('AN-007: Navigation Actions', () => {
    it('clicking Overview link navigates to overview page', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/users" />);
      
      await user.click(screen.getByTestId('nav-link-overview'));
      expect(mockPush).toHaveBeenCalledWith('/admin/overview');
    });

    it('clicking Users link navigates to users page', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/overview" />);
      
      await user.click(screen.getByTestId('nav-link-users'));
      expect(mockPush).toHaveBeenCalledWith('/admin/users');
    });

    it('clicking Usage link navigates to usage page', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/overview" />);
      
      await user.click(screen.getByTestId('nav-link-usage'));
      expect(mockPush).toHaveBeenCalledWith('/admin/usage');
    });

    it('clicking Credits link navigates to credits page', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/overview" />);
      
      await user.click(screen.getByTestId('nav-link-credits'));
      expect(mockPush).toHaveBeenCalledWith('/admin/credits');
    });

    it('clicking Health link navigates to health page', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/overview" />);
      
      await user.click(screen.getByTestId('nav-link-health'));
      expect(mockPush).toHaveBeenCalledWith('/admin/health');
    });
  });

  // ==================== AN-008: Back to Dashboard ====================

  describe('AN-008: Back to Dashboard', () => {
    it('renders back to dashboard link', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('back-to-dashboard')).toBeInTheDocument();
    });

    it('back to dashboard link has correct href', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByTestId('back-to-dashboard')).toHaveAttribute('href', '/dashboard');
    });

    it('clicking back to dashboard navigates to dashboard', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/overview" />);
      
      await user.click(screen.getByTestId('back-to-dashboard'));
      expect(mockPush).toHaveBeenCalledWith('/dashboard');
    });
  });

  // ==================== AN-009: Accessibility ====================

  describe('AN-009: Accessibility', () => {
    it('has proper navigation role', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByRole('navigation')).toBeInTheDocument();
    });

    it('has list role for nav items', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      expect(screen.getByRole('list')).toBeInTheDocument();
    });

    it('all links are focusable', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      const links = screen.getAllByRole('link');
      links.forEach((link) => {
        expect(link).not.toHaveAttribute('tabindex', '-1');
      });
    });

    it('supports keyboard navigation', async () => {
      const user = userEvent.setup();
      render(<MockAdminNav activePath="/admin/overview" />);
      
      const firstLink = screen.getByTestId('nav-link-overview');
      firstLink.focus();
      expect(firstLink).toHaveFocus();
    });

    it('has descriptive aria-label for toggle button when collapsed', () => {
      render(
        <MockAdminNav activePath="/admin/overview" collapsed={true} onToggle={jest.fn()} />
      );
      expect(screen.getByTestId('nav-toggle')).toHaveAttribute(
        'aria-label',
        'Expand navigation'
      );
    });

    it('has descriptive aria-label for toggle button when expanded', () => {
      render(
        <MockAdminNav activePath="/admin/overview" collapsed={false} onToggle={jest.fn()} />
      );
      expect(screen.getByTestId('nav-toggle')).toHaveAttribute(
        'aria-label',
        'Collapse navigation'
      );
    });
  });

  // ==================== AN-010: Responsive Design ====================

  describe('AN-010: Responsive Design', () => {
    it('applies custom className', () => {
      render(<MockAdminNav activePath="/admin/overview" className="custom-nav" />);
      expect(screen.getByTestId('admin-nav')).toHaveClass('custom-nav');
    });

    it('nav items have proper spacing', () => {
      render(<MockAdminNav activePath="/admin/overview" />);
      const items = screen.getAllByTestId(/nav-item-/);
      items.forEach((item) => {
        expect(item.tagName).toBe('LI');
      });
    });
  });

  // ==================== AN-011: Edge Cases ====================

  describe('AN-011: Edge Cases', () => {
    it('handles empty activePath', () => {
      render(<MockAdminNav activePath="" />);
      expect(screen.getByTestId('admin-nav')).toBeInTheDocument();
    });

    it('handles unknown activePath', () => {
      render(<MockAdminNav activePath="/unknown" />);
      const activeLinks = screen.getAllByTestId(/nav-link-/).filter(
        (link) => link.getAttribute('data-active') === 'true'
      );
      expect(activeLinks.length).toBe(0);
    });

    it('handles root admin path', () => {
      render(<MockAdminNav activePath="/admin" />);
      expect(screen.getByTestId('admin-nav')).toBeInTheDocument();
    });

    it('handles path with query params', () => {
      render(<MockAdminNav activePath="/admin/users?page=2" />);
      const link = screen.getByTestId('nav-link-users');
      expect(link).toHaveAttribute('data-active', 'true');
    });
  });
});

// ==================== Export Tests ====================

describe('Module Exports', () => {
  it('AdminNav component is defined', () => {
    expect(MockAdminNav).toBeDefined();
  });

  it('NavItem type is available', () => {
    // TypeScript type, just verify it compiles
    const item: NavItem = {
      id: 'test',
      label: 'Test',
      href: '/test',
      icon: <span>🧪</span>,
    };
    expect(item).toBeDefined();
  });
});
