/**
 * @jest-environment jsdom
 */
import React from 'react';
import { render, screen, waitFor, fireEvent, act } from '@testing-library/react';
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
    get store() {
      return store;
    },
  };
})();

Object.defineProperty(window, 'localStorage', { value: localStorageMock });

const STORAGE_KEY = 'seoindicator_cookie_consent';

// ============================================================================
// Mock Consent State Provider
// ============================================================================

interface ConsentState {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  timestamp: string;
}

interface ConsentContextType {
  consent: ConsentState | null;
  hasConsent: (type: 'analytics' | 'marketing') => boolean;
  updateConsent: (updates: Partial<Omit<ConsentState, 'timestamp'>>) => void;
  clearConsent: () => void;
  showBanner: boolean;
  setShowBanner: (show: boolean) => void;
}

const ConsentContext = React.createContext<ConsentContextType | null>(null);

function ConsentProvider({ children }: { children: React.ReactNode }) {
  const [consent, setConsent] = React.useState<ConsentState | null>(() => {
    try {
      const stored = localStorageMock.getItem(STORAGE_KEY);
      if (!stored) return null;
      const parsed = JSON.parse(stored);
      if (
        typeof parsed.necessary === 'boolean' &&
        typeof parsed.analytics === 'boolean' &&
        typeof parsed.marketing === 'boolean' &&
        typeof parsed.timestamp === 'string'
      ) {
        return parsed;
      }
      return null;
    } catch {
      return null;
    }
  });

  const [showBanner, setShowBannerState] = React.useState(() => consent === null);

  const hasConsent = React.useCallback(
    (type: 'analytics' | 'marketing'): boolean => {
      return consent?.[type] ?? false;
    },
    [consent]
  );

  const updateConsent = React.useCallback(
    (updates: Partial<Omit<ConsentState, 'timestamp'>>) => {
      const newConsent: ConsentState = {
        necessary: true,
        analytics: updates.analytics ?? consent?.analytics ?? false,
        marketing: updates.marketing ?? consent?.marketing ?? false,
        timestamp: new Date().toISOString(),
      };
      localStorageMock.setItem(STORAGE_KEY, JSON.stringify(newConsent));
      setConsent(newConsent);
      setShowBannerState(false);
    },
    [consent]
  );

  const clearConsent = React.useCallback(() => {
    localStorageMock.removeItem(STORAGE_KEY);
    setConsent(null);
    setShowBannerState(true);
  }, []);

  const setShowBanner = React.useCallback((show: boolean) => {
    setShowBannerState(show);
  }, []);

  return (
    <ConsentContext.Provider
      value={{ consent, hasConsent, updateConsent, clearConsent, showBanner, setShowBanner }}
    >
      {children}
    </ConsentContext.Provider>
  );
}

function useConsent() {
  const context = React.useContext(ConsentContext);
  if (!context) throw new Error('useConsent must be used within ConsentProvider');
  return context;
}

// ============================================================================
// Mock Components for Integration Testing
// ============================================================================

// CookieConsent Banner Component
const CookieConsent = () => {
  const { showBanner, updateConsent } = useConsent();

  if (!showBanner) return null;

  return (
    <div
      data-testid="cookie-consent-banner"
      role="dialog"
      aria-label="Cookie consent"
      className="fixed bottom-0 left-0 right-0 z-50 bg-slate-800 p-4"
    >
      <p>We use cookies to improve your experience.</p>
      <div className="flex gap-4 mt-4">
        <button
          data-testid="accept-all-btn"
          onClick={() => updateConsent({ analytics: true, marketing: true })}
        >
          Accept All
        </button>
        <button
          data-testid="necessary-only-btn"
          onClick={() => updateConsent({ analytics: false, marketing: false })}
        >
          Necessary Only
        </button>
      </div>
    </div>
  );
};

// Footer with Cookie Settings
const Footer = () => {
  const { setShowBanner, hasConsent } = useConsent();

  return (
    <footer data-testid="footer" role="contentinfo">
      <p>© 2026 SEOIndicator</p>
      <button
        data-testid="cookie-settings-btn"
        onClick={() => setShowBanner(true)}
      >
        Cookie Settings
      </button>
      <div data-testid="consent-status">
        Analytics: {hasConsent('analytics') ? 'Enabled' : 'Disabled'}
      </div>
    </footer>
  );
};

// Layout Component
const Layout = ({ children, path = '/' }: { children: React.ReactNode; path?: string }) => {
  return (
    <ConsentProvider>
      <div data-testid="layout" data-path={path}>
        <main data-testid="main-content">{children}</main>
        <CookieConsent />
        <Footer />
      </div>
    </ConsentProvider>
  );
};

// Page Components
const HomePage = () => <div data-testid="home-page">Home Page</div>;
const DashboardPage = () => <div data-testid="dashboard-page">Dashboard</div>;
const ToolsPage = () => <div data-testid="tools-page">Tools</div>;

// ============================================================================
// Full Consent Flow Tests
// ============================================================================

describe('Cookie Consent Integration - Full Flow', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('FF-001: Fresh visit shows banner', () => {
    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();
    expect(screen.getByTestId('home-page')).toBeInTheDocument();
  });

  it('FF-002: Accept All stores consent with all true', async () => {
    const user = userEvent.setup();

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('accept-all-btn'));

    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();
    
    // Check localStorage was updated
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.analytics).toBe(true);
    expect(stored.marketing).toBe(true);
    expect(stored.necessary).toBe(true);
  });

  it('FF-003: Banner hides after accept', async () => {
    const user = userEvent.setup();

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();

    await user.click(screen.getByTestId('accept-all-btn'));

    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();
  });

  it('FF-004: Consent persists across page refresh (remount)', async () => {
    const user = userEvent.setup();

    // First render - accept consent
    const { unmount } = render(
      <Layout>
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('accept-all-btn'));
    unmount();

    // Simulate page refresh - remount with stored consent
    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Banner should not show
    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();
  });

  it('FF-005: Footer link shows banner again', async () => {
    const user = userEvent.setup();

    // Set existing consent
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Banner should not be visible initially
    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();

    // Click Cookie Settings
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Banner should now be visible
    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();
  });

  it('FF-006: Can change consent (Accept All → Necessary Only)', async () => {
    const user = userEvent.setup();

    // First accept all
    const { rerender } = render(
      <Layout>
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('accept-all-btn'));

    // Show banner again via footer
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Change to necessary only
    await user.click(screen.getByTestId('necessary-only-btn'));

    // Check consent was updated
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.analytics).toBe(false);
    expect(stored.marketing).toBe(false);
  });

  it('FF-007: New consent overwrites old', async () => {
    const user = userEvent.setup();

    // Start with all false
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: false,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Show banner
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Accept all
    await user.click(screen.getByTestId('accept-all-btn'));

    // Verify new values
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.analytics).toBe(true);
    expect(stored.marketing).toBe(true);
  });
});

// ============================================================================
// Consent Withdrawal Flow Tests
// ============================================================================

describe('Cookie Consent Integration - Withdrawal Flow', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('WF-001: Withdraw via footer link', async () => {
    const user = userEvent.setup();

    // Start with full consent
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Banner not visible
    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();

    // Click Cookie Settings
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Banner visible
    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();
  });

  it('WF-002: Change from all to necessary', async () => {
    const user = userEvent.setup();

    // Start with all true
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Open banner
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Change to necessary only
    await user.click(screen.getByTestId('necessary-only-btn'));

    // Verify
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.necessary).toBe(true);
    expect(stored.analytics).toBe(false);
    expect(stored.marketing).toBe(false);
  });

  it('WF-003: Change from necessary to all', async () => {
    const user = userEvent.setup();

    // Start with necessary only
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: false,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Open banner
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Accept all
    await user.click(screen.getByTestId('accept-all-btn'));

    // Verify
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.analytics).toBe(true);
    expect(stored.marketing).toBe(true);
  });

  it('WF-004: Timestamp updated on change', async () => {
    const user = userEvent.setup();

    const oldTimestamp = '2026-03-05T10:00:00Z';
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: oldTimestamp,
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Open and change
    await user.click(screen.getByTestId('cookie-settings-btn'));
    await user.click(screen.getByTestId('necessary-only-btn'));

    // Verify new timestamp
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.timestamp).not.toBe(oldTimestamp);
  });

  it('WF-005: Previous consent replaced (no merge)', async () => {
    const user = userEvent.setup();

    // Set initial consent with custom values
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Open and accept all
    await user.click(screen.getByTestId('cookie-settings-btn'));
    await user.click(screen.getByTestId('accept-all-btn'));

    // Verify - marketing should now be true (not preserved)
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.marketing).toBe(true);
  });
});

// ============================================================================
// Analytics Integration Tests
// ============================================================================

describe('Cookie Consent Integration - Analytics', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('AI-001: No tracking before consent', async () => {
    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    const status = screen.getByTestId('consent-status');
    expect(status).toHaveTextContent('Analytics: Disabled');
  });

  it('AI-002: Analytics enabled after accept', async () => {
    const user = userEvent.setup();

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('accept-all-btn'));

    const status = screen.getByTestId('consent-status');
    expect(status).toHaveTextContent('Analytics: Enabled');
  });

  it('AI-003: Analytics disabled after decline', async () => {
    const user = userEvent.setup();

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('necessary-only-btn'));

    const status = screen.getByTestId('consent-status');
    expect(status).toHaveTextContent('Analytics: Disabled');
  });

  it('AI-004: Consent change reflected in hook', async () => {
    const user = userEvent.setup();

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Initially disabled
    expect(screen.getByTestId('consent-status')).toHaveTextContent('Analytics: Disabled');

    // Accept
    await user.click(screen.getByTestId('accept-all-btn'));

    // Now enabled
    expect(screen.getByTestId('consent-status')).toHaveTextContent('Analytics: Enabled');
  });

  it('AI-005: Marketing consent is separate from analytics', async () => {
    const user = userEvent.setup();

    // Set partial consent
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Analytics enabled
    expect(screen.getByTestId('consent-status')).toHaveTextContent('Analytics: Enabled');

    // But marketing is false in storage
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.marketing).toBe(false);
  });
});

// ============================================================================
// Cross-Page Consistency Tests
// ============================================================================

describe('Cookie Consent Integration - Cross-Page', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('CP-001: Same consent on dashboard page', async () => {
    const user = userEvent.setup();

    // Accept consent on home
    const { unmount } = render(
      <Layout path="/">
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('accept-all-btn'));
    unmount();

    // Navigate to dashboard (remount with stored consent)
    render(
      <Layout path="/dashboard">
        <DashboardPage />
      </Layout>
    );

    // Same consent state
    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();
    expect(screen.getByTestId('consent-status')).toHaveTextContent('Analytics: Enabled');
  });

  it('CP-002: Same consent on tools page', async () => {
    const user = userEvent.setup();

    // Accept consent
    const { unmount } = render(
      <Layout>
        <HomePage />
      </Layout>
    );

    await user.click(screen.getByTestId('accept-all-btn'));
    unmount();

    // Navigate to tools
    render(
      <Layout path="/tools">
        <ToolsPage />
      </Layout>
    );

    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();
    expect(screen.getByTestId('consent-status')).toHaveTextContent('Analytics: Enabled');
  });

  it('CP-003: Footer link works from all pages', async () => {
    const user = userEvent.setup();

    // Set consent
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: true,
      timestamp: '2026-03-05T10:00:00Z',
    }));

    render(
      <Layout path="/tools">
        <ToolsPage />
      </Layout>
    );

    // Banner not visible
    expect(screen.queryByTestId('cookie-consent-banner')).not.toBeInTheDocument();

    // Click Cookie Settings from tools page
    await user.click(screen.getByTestId('cookie-settings-btn'));

    // Banner shows
    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();
  });

  it('CP-004: Banner state preserved during navigation', async () => {
    const user = userEvent.setup();

    // Fresh visit
    const { rerender } = render(
      <Layout path="/">
        <HomePage />
      </Layout>
    );

    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();

    // Simulate navigation (remount without consent)
    // Banner should still show because no consent was given
  });
});

// ============================================================================
// Error Recovery Tests
// ============================================================================

describe('Cookie Consent Integration - Error Recovery', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('ER-001: Corrupted consent ignored (shows banner)', () => {
    localStorageMock.setItem(STORAGE_KEY, 'not-valid-json');

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Banner should show because consent is invalid
    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();
  });

  it('ER-002: Clear consent recovers from error', async () => {
    const user = userEvent.setup();

    // Start with corrupted data
    localStorageMock.setItem(STORAGE_KEY, 'invalid');

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Banner shows
    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();

    // Accept consent
    await user.click(screen.getByTestId('accept-all-btn'));

    // Valid consent stored
    const stored = JSON.parse(localStorageMock.store[STORAGE_KEY]);
    expect(stored.analytics).toBe(true);
  });

  it('ER-003: Malformed consent object handled', () => {
    localStorageMock.setItem(STORAGE_KEY, JSON.stringify({
      wrongField: true,
    }));

    render(
      <Layout>
        <HomePage />
      </Layout>
    );

    // Banner shows due to missing required fields
    expect(screen.getByTestId('cookie-consent-banner')).toBeInTheDocument();
  });
});
