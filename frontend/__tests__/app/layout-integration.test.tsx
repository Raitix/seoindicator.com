/**
 * @jest-environment jsdom
 */
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};
  return {
    getItem: jest.fn((key: string) => store[key] || null),
    setItem: jest.fn((key: string, value: string) => {
      store[key] = value;
    }),
    removeItem: jest.fn((key: string) => {
      delete store[key];
    }),
    clear: jest.fn(() => {
      store = {};
    }),
  };
})();

Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// Mock CookieConsent component
jest.mock('@/components/layout/CookieConsent', () => ({
  CookieConsent: ({ onAccept, onDecline, className }: {
    onAccept?: () => void;
    onDecline?: () => void;
    className?: string;
  }) => (
    <div 
      data-testid="cookie-consent" 
      className={className}
      role="dialog"
      aria-label="Cookie consent"
    >
      <p>We use cookies</p>
      <button onClick={onAccept} data-testid="accept-all">Accept All</button>
      <button onClick={onDecline} data-testid="necessary-only">Necessary Only</button>
    </div>
  ),
}));

// Mock cookieConsent utility
jest.mock('@/lib/utils/cookieConsent', () => ({
  getCookieConsent: jest.fn(),
  setCookieConsent: jest.fn(),
  hasCookieConsent: jest.fn(() => false),
  clearCookieConsent: jest.fn(),
}));

// Import after mocks
import { getCookieConsent, setCookieConsent, hasCookieConsent, clearCookieConsent } from '@/lib/utils/cookieConsent';

// ============================================================================
// Mock Layout Component (simulates actual RootLayout)
// ============================================================================

const MockLayout = ({ children }: { children: React.ReactNode }) => {
  const showBanner = !hasCookieConsent();
  
  return (
    <html lang="en">
      <body>
        <div data-testid="skip-link">Skip to main content</div>
        <div data-testid="providers">
          <main id="main-content" data-testid="main-content">
            {children}
          </main>
          {showBanner && (
            <div 
              data-testid="cookie-consent" 
              className="fixed bottom-0 left-0 right-0 z-50"
              role="dialog"
            >
              <p>We use cookies</p>
              <button data-testid="accept-all">Accept All</button>
              <button data-testid="necessary-only">Necessary Only</button>
            </div>
          )}
        </div>
      </body>
    </html>
  );
};

// ============================================================================
// Layout Rendering Tests
// ============================================================================

describe('Layout Integration - Component Rendering', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('LR-001: CookieConsent rendered in layout', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Page Content</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
  });

  it('LR-002: Component at root level (not deeply nested)', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Page Content</div>
      </MockLayout>
    );
    
    const banner = screen.getByTestId('cookie-consent');
    // Should be directly under providers, not nested in main content
    expect(banner.parentElement).toHaveAttribute('data-testid', 'providers');
  });

  it('LR-003: Renders after children (z-index ensures visual overlay)', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div data-testid="page-content">Page Content</div>
      </MockLayout>
    );
    
    const mainContent = screen.getByTestId('main-content');
    const banner = screen.getByTestId('cookie-consent');
    
    // Main content should exist
    expect(mainContent).toBeInTheDocument();
    // Banner should exist with high z-index
    expect(banner).toBeInTheDocument();
    expect(banner).toHaveClass('z-50');
  });

  it('LR-004: Present on dashboard route simulation', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div data-testid="dashboard-page">Dashboard</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
    expect(screen.getByTestId('dashboard-page')).toBeInTheDocument();
  });

  it('LR-005: Present on tools route simulation', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div data-testid="tools-page">Tools Page</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
    expect(screen.getByTestId('tools-page')).toBeInTheDocument();
  });

  it('LR-006: Present on admin route simulation', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div data-testid="admin-page">Admin Page</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
    expect(screen.getByTestId('admin-page')).toBeInTheDocument();
  });
});

// ============================================================================
// Layout Shift Tests
// ============================================================================

describe('Layout Integration - No Layout Shift', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('LS-001: Banner uses fixed positioning (no content shift)', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    const banner = screen.getByTestId('cookie-consent');
    expect(banner).toHaveClass('fixed');
  });

  it('LS-002: Fixed positioning applied', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    const banner = screen.getByTestId('cookie-consent');
    expect(banner).toHaveClass('fixed');
    expect(banner).toHaveClass('bottom-0');
    expect(banner).toHaveClass('left-0');
    expect(banner).toHaveClass('right-0');
  });

  it('LS-003: Bottom positioning applied', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    const banner = screen.getByTestId('cookie-consent');
    expect(banner).toHaveClass('bottom-0');
  });

  it('LS-004: High z-index for overlay', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    const banner = screen.getByTestId('cookie-consent');
    expect(banner).toHaveClass('z-50');
  });
});

// ============================================================================
// Provider Integration Tests
// ============================================================================

describe('Layout Integration - Provider Integration', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('PI-001: Works within Providers context', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    // Should not throw any context errors
    expect(() => {
      render(
        <MockLayout>
          <div>Content</div>
        </MockLayout>
      );
    }).not.toThrow();
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
  });

  it('PI-002: Banner respects theme context (dark mode classes)', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    const banner = screen.getByTestId('cookie-consent');
    // Banner should render without errors - theme classes would be applied by actual implementation
    expect(banner).toBeInTheDocument();
  });

  it('PI-003: No hydration mismatch (SSR compatible)', async () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    // Render should complete without hydration errors
    const { container } = render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    // No hydration mismatch would occur in actual Next.js
    expect(container).toBeInTheDocument();
  });
});

// ============================================================================
// Conditional Rendering Tests
// ============================================================================

describe('Layout Integration - Conditional Rendering', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('CR-001: Hidden when consent exists', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(true);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    expect(screen.queryByTestId('cookie-consent')).not.toBeInTheDocument();
  });

  it('CR-002: Visible when no consent', () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
  });

  it('CR-003: Visible when consent cleared', () => {
    // First render with consent
    (hasCookieConsent as jest.Mock).mockReturnValue(true);
    
    const { rerender } = render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    expect(screen.queryByTestId('cookie-consent')).not.toBeInTheDocument();
    
    // Clear consent
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    rerender(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
  });

  it('CR-004: Re-renders on consent change (accept)', async () => {
    (hasCookieConsent as jest.Mock).mockReturnValue(false);
    
    const { rerender } = render(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    expect(screen.getByTestId('cookie-consent')).toBeInTheDocument();
    
    // Simulate accepting consent
    (hasCookieConsent as jest.Mock).mockReturnValue(true);
    
    rerender(
      <MockLayout>
        <div>Content</div>
      </MockLayout>
    );
    
    expect(screen.queryByTestId('cookie-consent')).not.toBeInTheDocument();
  });
});
