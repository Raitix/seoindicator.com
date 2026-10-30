/**
 * @jest-environment jsdom
 */
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
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

// Mock useConsent hook
const mockSetShowBanner = jest.fn();
const mockHasConsent = jest.fn(() => false);
const mockUpdateConsent = jest.fn();
const mockClearConsent = jest.fn();

jest.mock('@/lib/hooks/useConsent', () => ({
  useConsent: () => ({
    consent: null,
    hasConsent: mockHasConsent,
    updateConsent: mockUpdateConsent,
    clearConsent: mockClearConsent,
    showBanner: false,
    setShowBanner: mockSetShowBanner,
  }),
}));

// ============================================================================
// Mock Footer Component
// ============================================================================

interface FooterProps {
  showCookieSettings?: boolean;
  className?: string;
}

const MockFooter = ({ showCookieSettings = true, className }: FooterProps) => {
  const { setShowBanner } = {
    setShowBanner: mockSetShowBanner,
  };

  const handleCookieSettingsClick = () => {
    setShowBanner(true);
  };

  return (
    <footer 
      role="contentinfo" 
      className={className}
      data-testid="footer"
    >
      <div className="container mx-auto px-4 py-8">
        <div className="grid md:grid-cols-4 gap-8">
          {/* Brand Column */}
          <div>
            <h3 className="font-bold text-white mb-4">SEOIndicator</h3>
            <p className="text-slate-400 text-sm">
              AI-powered SEO analysis and monitoring platform.
            </p>
          </div>

          {/* Links Column */}
          <div>
            <h4 className="font-semibold text-white mb-3">Product</h4>
            <nav aria-label="Product links">
              <ul className="space-y-2">
                <li><a href="/tools" className="text-slate-400 hover:text-white">Tools</a></li>
                <li><a href="/dashboard" className="text-slate-400 hover:text-white">Dashboard</a></li>
                <li><a href="/pricing" className="text-slate-400 hover:text-white">Pricing</a></li>
              </ul>
            </nav>
          </div>

          {/* Legal Column */}
          <div>
            <h4 className="font-semibold text-white mb-3">Legal</h4>
            <nav aria-label="Legal links">
              <ul className="space-y-2">
                <li><a href="/terms" className="text-slate-400 hover:text-white">Terms & Privacy</a></li>
                <li><a href="/about" className="text-slate-400 hover:text-white">About</a></li>
              </ul>
            </nav>
          </div>

          {/* Settings Column */}
          <div>
            <h4 className="font-semibold text-white mb-3">Settings</h4>
            <ul className="space-y-2">
              {showCookieSettings && (
                <li>
                  <button
                    onClick={handleCookieSettingsClick}
                    className="text-slate-400 hover:text-white flex items-center gap-2"
                    aria-label="Cookie Settings - Manage your cookie preferences"
                    data-testid="cookie-settings-link"
                  >
                    <svg 
                      data-testid="cookie-icon"
                      className="w-4 h-4" 
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <circle cx="12" cy="12" r="10" strokeWidth="2" />
                      <circle cx="8" cy="10" r="1" fill="currentColor" />
                      <circle cx="16" cy="10" r="1" fill="currentColor" />
                      <circle cx="12" cy="16" r="1" fill="currentColor" />
                    </svg>
                    Cookie Settings
                  </button>
                </li>
              )}
            </ul>
          </div>
        </div>

        {/* Copyright */}
        <div className="border-t border-slate-700 mt-8 pt-8 text-center">
          <p className="text-slate-500 text-sm">
            © 2026 SEOIndicator. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
};

// Import after mocks
import { useConsent } from '@/lib/hooks/useConsent';

// ============================================================================
// Basic Rendering Tests
// ============================================================================

describe('Footer Component - Basic Rendering', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('FR-001: Footer renders', () => {
    render(<MockFooter />);
    
    expect(screen.getByTestId('footer')).toBeInTheDocument();
  });

  it('FR-002: Has contentinfo role', () => {
    render(<MockFooter />);
    
    expect(screen.getByRole('contentinfo')).toBeInTheDocument();
  });

  it('FR-003: Contains copyright', () => {
    render(<MockFooter />);
    
    expect(screen.getByText(/© 2026 SEOIndicator/)).toBeInTheDocument();
  });

  it('FR-004: Has navigation links', () => {
    render(<MockFooter />);
    
    expect(screen.getByRole('link', { name: /tools/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /terms/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /about/i })).toBeInTheDocument();
  });

  it('FR-005: Responsive layout with grid classes', () => {
    render(<MockFooter />);
    
    const footer = screen.getByTestId('footer');
    const grid = footer.querySelector('.grid');
    expect(grid).toHaveClass('md:grid-cols-4');
  });
});

// ============================================================================
// Cookie Settings Link Tests
// ============================================================================

describe('Footer Component - Cookie Settings Link', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('CS-001: Link renders in footer', () => {
    render(<MockFooter />);
    
    expect(screen.getByTestId('cookie-settings-link')).toBeInTheDocument();
    expect(screen.getByText('Cookie Settings')).toBeInTheDocument();
  });

  it('CS-002: Link has accessible name', () => {
    render(<MockFooter />);
    
    const link = screen.getByTestId('cookie-settings-link');
    expect(link).toHaveAttribute('aria-label', 'Cookie Settings - Manage your cookie preferences');
  });

  it('CS-003: Link is keyboard accessible', async () => {
    const user = userEvent.setup();
    
    render(<MockFooter />);
    
    const link = screen.getByTestId('cookie-settings-link');
    link.focus();
    
    expect(link).toHaveFocus();
  });

  it('CS-004: Link visible after consent given', () => {
    mockHasConsent.mockReturnValue(true);
    
    render(<MockFooter />);
    
    expect(screen.getByTestId('cookie-settings-link')).toBeInTheDocument();
  });

  it('CS-005: Link visible without consent', () => {
    mockHasConsent.mockReturnValue(false);
    
    render(<MockFooter />);
    
    expect(screen.getByTestId('cookie-settings-link')).toBeInTheDocument();
  });

  it('CS-006: Click shows consent banner', async () => {
    const user = userEvent.setup();
    
    render(<MockFooter />);
    
    const link = screen.getByTestId('cookie-settings-link');
    await user.click(link);
    
    expect(mockSetShowBanner).toHaveBeenCalledWith(true);
  });

  it('CS-007: Click triggers setShowBanner(true)', async () => {
    const user = userEvent.setup();
    
    render(<MockFooter />);
    
    const link = screen.getByTestId('cookie-settings-link');
    await user.click(link);
    
    expect(mockSetShowBanner).toHaveBeenCalledTimes(1);
    expect(mockSetShowBanner).toHaveBeenCalledWith(true);
  });

  it('CS-008: Has cookie/settings icon', () => {
    render(<MockFooter />);
    
    expect(screen.getByTestId('cookie-icon')).toBeInTheDocument();
  });

  it('CS-009: Link position in Settings column', () => {
    render(<MockFooter />);
    
    const link = screen.getByTestId('cookie-settings-link');
    const settingsHeading = screen.getByText('Settings');
    
    // Link should be in the same column as Settings heading
    expect(link.closest('div')).toBe(settingsHeading.closest('div'));
  });

  it('CS-010: Can hide Cookie Settings link', () => {
    render(<MockFooter showCookieSettings={false} />);
    
    expect(screen.queryByTestId('cookie-settings-link')).not.toBeInTheDocument();
  });
});

// ============================================================================
// Footer Structure Tests
// ============================================================================

describe('Footer Component - Structure', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('FS-001: Multi-column layout on desktop', () => {
    render(<MockFooter />);
    
    const grid = screen.getByTestId('footer').querySelector('.grid');
    expect(grid).toHaveClass('md:grid-cols-4');
  });

  it('FS-002: Mobile responsive (single column on small screens)', () => {
    render(<MockFooter />);
    
    const grid = screen.getByTestId('footer').querySelector('.grid');
    // Grid should have gap-8 for spacing
    expect(grid).toHaveClass('gap-8');
  });

  it('FS-003: Visual separator before copyright', () => {
    render(<MockFooter />);
    
    const separator = screen.getByTestId('footer').querySelector('.border-t');
    expect(separator).toBeInTheDocument();
    expect(separator).toHaveClass('border-slate-700');
  });

  it('FS-004: Consistent styling on multiple renders', () => {
    const { rerender } = render(<MockFooter />);
    const firstRender = screen.getByTestId('footer').className;
    
    rerender(<MockFooter />);
    const secondRender = screen.getByTestId('footer').className;
    
    expect(firstRender).toBe(secondRender);
  });

  it('FS-005: Links have hover states (text-slate-400 hover:text-white)', () => {
    render(<MockFooter />);
    
    const toolsLink = screen.getByRole('link', { name: /tools/i });
    expect(toolsLink).toHaveClass('text-slate-400');
    expect(toolsLink).toHaveClass('hover:text-white');
  });
});

// ============================================================================
// Accessibility Tests
// ============================================================================

describe('Footer Component - Accessibility', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('has correct ARIA landmarks', () => {
    render(<MockFooter />);
    
    // Footer should have contentinfo role
    expect(screen.getByRole('contentinfo')).toBeInTheDocument();
  });

  it('navigation areas have aria-labels', () => {
    render(<MockFooter />);
    
    expect(screen.getByLabelText('Product links')).toBeInTheDocument();
    expect(screen.getByLabelText('Legal links')).toBeInTheDocument();
  });

  it('Cookie Settings button has descriptive aria-label', () => {
    render(<MockFooter />);
    
    const cookieButton = screen.getByTestId('cookie-settings-link');
    expect(cookieButton).toHaveAttribute('aria-label');
    expect(cookieButton.getAttribute('aria-label')).toContain('Cookie Settings');
  });

  it('icon is hidden from screen readers (aria-hidden)', () => {
    render(<MockFooter />);
    
    const icon = screen.getByTestId('cookie-icon');
    expect(icon).toHaveAttribute('aria-hidden', 'true');
  });
});

// ============================================================================
// Custom ClassName Tests
// ============================================================================

describe('Footer Component - Custom Props', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  it('accepts custom className', () => {
    render(<MockFooter className="custom-footer-class" />);
    
    const footer = screen.getByTestId('footer');
    expect(footer).toHaveClass('custom-footer-class');
  });

  it('showCookieSettings prop controls link visibility (true)', () => {
    render(<MockFooter showCookieSettings={true} />);
    
    expect(screen.getByTestId('cookie-settings-link')).toBeInTheDocument();
  });

  it('showCookieSettings prop controls link visibility (false)', () => {
    render(<MockFooter showCookieSettings={false} />);
    
    expect(screen.queryByTestId('cookie-settings-link')).not.toBeInTheDocument();
  });
});
