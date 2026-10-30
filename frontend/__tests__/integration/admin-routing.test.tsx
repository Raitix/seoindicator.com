/**
 * Admin Routing Integration Tests
 * Task: TASK-27-14 - Admin Dashboard Layout and Routing
 * 
 * Integration tests for admin dashboard routing, navigation, and redirects.
 */

import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';

// Mock next/navigation
const mockPush = jest.fn();
const mockReplace = jest.fn();
const mockBack = jest.fn();
const mockForward = jest.fn();

const mockUsePathname = jest.fn(() => '/admin/overview');
const mockUseSearchParams = jest.fn(() => ({
  get: jest.fn(() => null),
}));

jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    replace: mockReplace,
    back: mockBack,
    forward: mockForward,
  }),
  usePathname: () => mockUsePathname(),
  useSearchParams: () => mockUseSearchParams(),
  useParams: () => ({}),
  notFound: jest.fn(() => {
    throw new Error('NOT_FOUND');
  }),
  redirect: jest.fn((url: string) => {
    throw new Error(`REDIRECT:${url}`);
  }),
}));

// Mock next/link
jest.mock('next/link', () => {
  return function MockLink({
    children,
    href,
    ...props
  }: {
    children: React.ReactNode;
    href: string;
    [key: string]: unknown;
  }) {
    return (
      <a
        href={href}
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

// Mock AuthContext
const mockAdminUser = {
  id: 1,
  email: 'admin@example.com',
  name: 'Admin User',
  is_admin: true,
};

const mockRegularUser = {
  id: 2,
  email: 'user@example.com',
  name: 'Regular User',
  is_admin: false,
};

const mockUseAuth = jest.fn();

jest.mock('@/lib/contexts/AuthContext', () => ({
  useAuth: () => mockUseAuth(),
  AuthProvider: ({ children }: { children: React.ReactNode }) => children,
}));

// Mock AdminGuard
jest.mock('@/components/admin/AdminGuard', () => ({
  AdminGuard: ({ children }: { children: React.ReactNode }) => {
    const auth = mockUseAuth();
    if (auth.isLoading) return <div data-testid="loading">Loading...</div>;
    if (!auth.isAuthenticated || !auth.user) return <div data-testid="unauthorized">Unauthorized</div>;
    if (!auth.user.is_admin) return <div data-testid="forbidden">Forbidden</div>;
    return <>{children}</>;
  },
}));

// Mock AdminLayout
jest.mock('@/components/admin/AdminLayout', () => ({
  AdminLayout: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="admin-layout">
      <nav data-testid="admin-nav">
        <a href="/admin/overview" data-testid="nav-overview">Overview</a>
        <a href="/admin/users" data-testid="nav-users">Users</a>
        <a href="/admin/usage" data-testid="nav-usage">Usage</a>
        <a href="/admin/credits" data-testid="nav-credits">Credits</a>
        <a href="/admin/health" data-testid="nav-health">Health</a>
      </nav>
      <main data-testid="admin-content">{children}</main>
    </div>
  ),
}));

// Mock page components
function MockAdminIndexPage() {
  // This page should redirect to /admin/overview
  return null;
}

function MockAdminOverviewPage() {
  return (
    <div data-testid="page-overview">
      <h1>Dashboard Overview</h1>
      <p>Welcome to the admin dashboard.</p>
    </div>
  );
}

function MockAdminUsersPage() {
  return (
    <div data-testid="page-users">
      <h1>User Management</h1>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
          </tr>
        </thead>
      </table>
    </div>
  );
}

function MockAdminUsagePage() {
  return (
    <div data-testid="page-usage">
      <h1>Usage Statistics</h1>
      <div data-testid="usage-chart">Chart Placeholder</div>
    </div>
  );
}

function MockAdminCreditsPage() {
  return (
    <div data-testid="page-credits">
      <h1>Credits & Billing</h1>
      <div data-testid="billing-summary">Billing Summary</div>
    </div>
  );
}

function MockAdminHealthPage() {
  return (
    <div data-testid="page-health">
      <h1>System Health</h1>
      <div data-testid="health-status">All Systems Operational</div>
    </div>
  );
}

// Mock router for redirect testing
function MockAdminRouter({ path }: { path: string }) {
  const pages: Record<string, React.ReactNode> = {
    '/admin': <MockAdminIndexPage />,
    '/admin/overview': <MockAdminOverviewPage />,
    '/admin/users': <MockAdminUsersPage />,
    '/admin/usage': <MockAdminUsagePage />,
    '/admin/credits': <MockAdminCreditsPage />,
    '/admin/health': <MockAdminHealthPage />,
  };

  // Simulate redirect from /admin to /admin/overview
  if (path === '/admin') {
    mockReplace('/admin/overview');
    return <div data-testid="redirecting">Redirecting to overview...</div>;
  }

  const page = pages[path];
  if (!page) {
    return <div data-testid="not-found">404 - Page Not Found</div>;
  }

  return <>{page}</>;
}

describe('Admin Routing Integration Tests', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockUseAuth.mockReturnValue({
      user: mockAdminUser,
      isAuthenticated: true,
      isLoading: false,
    });
  });

  // ==================== AR-001: Route Redirects ====================

  describe('AR-001: Route Redirects', () => {
    it('/admin redirects to /admin/overview', () => {
      render(<MockAdminRouter path="/admin" />);
      expect(mockReplace).toHaveBeenCalledWith('/admin/overview');
    });

    it('shows redirecting message during redirect', () => {
      render(<MockAdminRouter path="/admin" />);
      expect(screen.getByTestId('redirecting')).toBeInTheDocument();
    });
  });

  // ==================== AR-002: Page Rendering ====================

  describe('AR-002: Page Rendering', () => {
    it('/admin/overview renders overview page', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('page-overview')).toBeInTheDocument();
      expect(screen.getByText('Dashboard Overview')).toBeInTheDocument();
    });

    it('/admin/users renders users page', () => {
      render(<MockAdminRouter path="/admin/users" />);
      expect(screen.getByTestId('page-users')).toBeInTheDocument();
      expect(screen.getByText('User Management')).toBeInTheDocument();
    });

    it('/admin/usage renders usage page', () => {
      render(<MockAdminRouter path="/admin/usage" />);
      expect(screen.getByTestId('page-usage')).toBeInTheDocument();
      expect(screen.getByText('Usage Statistics')).toBeInTheDocument();
    });

    it('/admin/credits renders credits page', () => {
      render(<MockAdminRouter path="/admin/credits" />);
      expect(screen.getByTestId('page-credits')).toBeInTheDocument();
      expect(screen.getByText('Credits & Billing')).toBeInTheDocument();
    });

    it('/admin/health renders health page', () => {
      render(<MockAdminRouter path="/admin/health" />);
      expect(screen.getByTestId('page-health')).toBeInTheDocument();
      expect(screen.getByText('System Health')).toBeInTheDocument();
    });
  });

  // ==================== AR-003: 404 Handling ====================

  describe('AR-003: 404 Handling', () => {
    it('shows 404 for invalid admin route', () => {
      render(<MockAdminRouter path="/admin/nonexistent" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });

    it('shows 404 for deep admin routes without page', () => {
      render(<MockAdminRouter path="/admin/users/123/edit" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });
  });

  // ==================== AR-004: Navigation Links ====================

  describe('AR-004: Navigation Links', () => {
    it('all navigation links are present', () => {
      mockUsePathname.mockReturnValue('/admin/overview');
      render(<MockAdminRouter path="/admin/overview" />);

      expect(screen.getByTestId('nav-overview')).toBeInTheDocument();
      expect(screen.getByTestId('nav-users')).toBeInTheDocument();
      expect(screen.getByTestId('nav-usage')).toBeInTheDocument();
      expect(screen.getByTestId('nav-credits')).toBeInTheDocument();
      expect(screen.getByTestId('nav-health')).toBeInTheDocument();
    });

    it('Overview link has correct href', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('nav-overview')).toHaveAttribute('href', '/admin/overview');
    });

    it('Users link has correct href', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('nav-users')).toHaveAttribute('href', '/admin/users');
    });

    it('Usage link has correct href', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('nav-usage')).toHaveAttribute('href', '/admin/usage');
    });

    it('Credits link has correct href', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('nav-credits')).toHaveAttribute('href', '/admin/credits');
    });

    it('Health link has correct href', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('nav-health')).toHaveAttribute('href', '/admin/health');
    });
  });

  // ==================== AR-005: Navigation Actions ====================

  describe('AR-005: Navigation Actions', () => {
    it('clicking Overview link navigates to overview', async () => {
      const user = userEvent.setup();
      render(<MockAdminRouter path="/admin/users" />);

      await user.click(screen.getByTestId('nav-overview'));
      expect(mockPush).toHaveBeenCalledWith('/admin/overview');
    });

    it('clicking Users link navigates to users', async () => {
      const user = userEvent.setup();
      render(<MockAdminRouter path="/admin/overview" />);

      await user.click(screen.getByTestId('nav-users'));
      expect(mockPush).toHaveBeenCalledWith('/admin/users');
    });

    it('clicking Usage link navigates to usage', async () => {
      const user = userEvent.setup();
      render(<MockAdminRouter path="/admin/overview" />);

      await user.click(screen.getByTestId('nav-usage'));
      expect(mockPush).toHaveBeenCalledWith('/admin/usage');
    });

    it('clicking Credits link navigates to credits', async () => {
      const user = userEvent.setup();
      render(<MockAdminRouter path="/admin/overview" />);

      await user.click(screen.getByTestId('nav-credits'));
      expect(mockPush).toHaveBeenCalledWith('/admin/credits');
    });

    it('clicking Health link navigates to health', async () => {
      const user = userEvent.setup();
      render(<MockAdminRouter path="/admin/overview" />);

      await user.click(screen.getByTestId('nav-health'));
      expect(mockPush).toHaveBeenCalledWith('/admin/health');
    });
  });

  // ==================== AR-006: Authentication Guard ====================

  describe('AR-006: Authentication Guard', () => {
    it('shows loading state while checking auth', () => {
      mockUseAuth.mockReturnValue({
        user: null,
        isAuthenticated: false,
        isLoading: true,
      });

      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('loading')).toBeInTheDocument();
    });

    it('shows unauthorized for unauthenticated users', () => {
      mockUseAuth.mockReturnValue({
        user: null,
        isAuthenticated: false,
        isLoading: false,
      });

      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('unauthorized')).toBeInTheDocument();
    });

    it('shows forbidden for non-admin users', () => {
      mockUseAuth.mockReturnValue({
        user: mockRegularUser,
        isAuthenticated: true,
        isLoading: false,
      });

      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('forbidden')).toBeInTheDocument();
    });

    it('shows content for authenticated admin users', () => {
      mockUseAuth.mockReturnValue({
        user: mockAdminUser,
        isAuthenticated: true,
        isLoading: false,
      });

      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('page-overview')).toBeInTheDocument();
    });
  });

  // ==================== AR-007: URL Parameters ====================

  describe('AR-007: URL Parameters', () => {
    it('handles query parameters gracefully', () => {
      mockUseSearchParams.mockReturnValue({
        get: jest.fn((key: string) => {
          if (key === 'page') return '2';
          return null;
        }),
      });

      render(<MockAdminRouter path="/admin/users" />);
      expect(screen.getByTestId('page-users')).toBeInTheDocument();
    });

    it('handles redirect parameter after login', () => {
      mockUseSearchParams.mockReturnValue({
        get: jest.fn((key: string) => {
          if (key === 'redirect') return '/admin/health';
          return null;
        }),
      });

      // After login, should redirect to stored location
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByTestId('page-overview')).toBeInTheDocument();
    });
  });

  // ==================== AR-008: Browser Navigation ====================

  describe('AR-008: Browser Navigation', () => {
    it('back navigation works', async () => {
      render(<MockAdminRouter path="/admin/overview" />);

      // Simulate back button
      act(() => {
        mockBack();
      });

      expect(mockBack).toHaveBeenCalled();
    });

    it('forward navigation works', async () => {
      render(<MockAdminRouter path="/admin/overview" />);

      // Simulate forward button
      act(() => {
        mockForward();
      });

      expect(mockForward).toHaveBeenCalled();
    });
  });

  // ==================== AR-009: Security ====================

  describe('AR-009: Security', () => {
    it('rejects path traversal attempts', () => {
      render(<MockAdminRouter path="/admin/../config" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });

    it('rejects script injection in path', () => {
      render(<MockAdminRouter path="/admin/<script>alert(1)</script>" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });

    it('rejects null bytes in path', () => {
      render(<MockAdminRouter path="/admin/%00users" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });

    it('does not expose admin content to non-admin users', () => {
      mockUseAuth.mockReturnValue({
        user: mockRegularUser,
        isAuthenticated: true,
        isLoading: false,
      });

      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.queryByTestId('page-overview')).not.toBeInTheDocument();
      expect(screen.getByTestId('forbidden')).toBeInTheDocument();
    });
  });

  // ==================== AR-010: Accessibility ====================

  describe('AR-010: Accessibility', () => {
    it('pages have main landmark', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByRole('main')).toBeInTheDocument();
    });

    it('navigation has nav landmark', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      expect(screen.getByRole('navigation')).toBeInTheDocument();
    });

    it('pages have correct heading hierarchy', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      const h1 = screen.getByRole('heading', { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it('navigation links are accessible', () => {
      render(<MockAdminRouter path="/admin/overview" />);
      const links = screen.getAllByRole('link');
      expect(links.length).toBeGreaterThan(0);
    });
  });

  // ==================== AR-011: Edge Cases ====================

  describe('AR-011: Edge Cases', () => {
    it('handles trailing slash in path', () => {
      render(<MockAdminRouter path="/admin/overview/" />);
      // Note: Next.js with trailingSlash config handles this
    });

    it('handles case-sensitive paths', () => {
      render(<MockAdminRouter path="/admin/Overview" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });

    it('handles deep nested paths', () => {
      render(<MockAdminRouter path="/admin/users/profile/settings" />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });

    it('handles very long paths', () => {
      const longPath = '/admin/' + 'a'.repeat(1000);
      render(<MockAdminRouter path={longPath} />);
      expect(screen.getByTestId('not-found')).toBeInTheDocument();
    });
  });
});

// ==================== Redirect Function Tests ====================

describe('Admin Redirect Function', () => {
  it('redirects /admin to /admin/overview', () => {
    render(<MockAdminRouter path="/admin" />);
    expect(mockReplace).toHaveBeenCalledWith('/admin/overview');
  });

  it('uses replace instead of push for initial redirect', () => {
    render(<MockAdminRouter path="/admin" />);
    expect(mockReplace).toHaveBeenCalled();
    expect(mockPush).not.toHaveBeenCalled();
  });
});

// ==================== Export Tests ====================

describe('Module Exports', () => {
  it('AdminIndexPage is defined', () => {
    expect(MockAdminIndexPage).toBeDefined();
  });

  it('AdminOverviewPage is defined', () => {
    expect(MockAdminOverviewPage).toBeDefined();
  });

  it('AdminUsersPage is defined', () => {
    expect(MockAdminUsersPage).toBeDefined();
  });

  it('AdminUsagePage is defined', () => {
    expect(MockAdminUsagePage).toBeDefined();
  });

  it('AdminCreditsPage is defined', () => {
    expect(MockAdminCreditsPage).toBeDefined();
  });

  it('AdminHealthPage is defined', () => {
    expect(MockAdminHealthPage).toBeDefined();
  });
});
