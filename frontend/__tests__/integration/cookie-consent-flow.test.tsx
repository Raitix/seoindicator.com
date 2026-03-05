/**
 * Tests for Cookie Consent Integration
 * TASK-30-5: Cookie Consent Integration
 */

import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

// Import components and hooks
import CookieConsent from "@/components/layout/CookieConsent";
import Footer from "@/components/layout/Footer";
import { ConsentProvider, useConsentContext } from "@/lib/contexts/ConsentContext";
import { useConsent } from "@/lib/hooks/useConsent";
import {
  getCookieConsent,
  setCookieConsent,
  hasCookieConsent,
  clearCookieConsent,
} from "@/lib/utils/cookieConsent";

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

Object.defineProperty(window, "localStorage", {
  value: localStorageMock,
});

// Clear localStorage before each test
beforeEach(() => {
  localStorageMock.clear();
  jest.clearAllMocks();
});

// ============================================
// ConsentProvider Tests
// ============================================
describe("ConsentProvider", () => {
  it("provides consent context to children", () => {
    const TestComponent = () => {
      const { hasConsent, showBanner } = useConsentContext();
      return (
        <div>
          <span data-testid="has-consent">{hasConsent.toString()}</span>
          <span data-testid="show-banner">{showBanner.toString()}</span>
        </div>
      );
    };

    render(
      <ConsentProvider>
        <TestComponent />
      </ConsentProvider>
    );

    expect(screen.getByTestId("has-consent")).toHaveTextContent("false");
    expect(screen.getByTestId("show-banner")).toHaveTextContent("true");
  });

  it("shows banner when no consent exists", () => {
    const TestComponent = () => {
      const { showBanner } = useConsentContext();
      return <span data-testid="show-banner">{showBanner.toString()}</span>;
    };

    render(
      <ConsentProvider>
        <TestComponent />
      </ConsentProvider>
    );

    expect(screen.getByTestId("show-banner")).toHaveTextContent("true");
  });

  it("hides banner when consent already exists", () => {
    // Set existing consent
    setCookieConsent({ necessary: true, analytics: true, marketing: false });

    const TestComponent = () => {
      const { showBanner, hasConsent } = useConsentContext();
      return (
        <div>
          <span data-testid="show-banner">{showBanner.toString()}</span>
          <span data-testid="has-consent">{hasConsent.toString()}</span>
        </div>
      );
    };

    render(
      <ConsentProvider>
        <TestComponent />
      </ConsentProvider>
    );

    expect(screen.getByTestId("has-consent")).toHaveTextContent("true");
    expect(screen.getByTestId("show-banner")).toHaveTextContent("false");
  });

  it("updates consent state when updateConsent is called", async () => {
    const TestComponent = () => {
      const { consent, updateConsent, hasConsent } = useConsentContext();
      return (
        <div>
          <span data-testid="has-consent">{hasConsent.toString()}</span>
          <span data-testid="analytics">
            {consent?.analytics?.toString() ?? "null"}
          </span>
          <button onClick={() => updateConsent(true, false)}>Update</button>
        </div>
      );
    };

    render(
      <ConsentProvider>
        <TestComponent />
      </ConsentProvider>
    );

    expect(screen.getByTestId("has-consent")).toHaveTextContent("false");

    fireEvent.click(screen.getByText("Update"));

    await waitFor(() => {
      expect(screen.getByTestId("has-consent")).toHaveTextContent("true");
      expect(screen.getByTestId("analytics")).toHaveTextContent("true");
    });
  });

  it("clears consent and shows banner again", async () => {
    // Set existing consent
    setCookieConsent({ necessary: true, analytics: true, marketing: true });

    const TestComponent = () => {
      const { showBanner, clearConsent, hasConsent } = useConsentContext();
      return (
        <div>
          <span data-testid="show-banner">{showBanner.toString()}</span>
          <span data-testid="has-consent">{hasConsent.toString()}</span>
          <button onClick={clearConsent}>Clear</button>
        </div>
      );
    };

    render(
      <ConsentProvider>
        <TestComponent />
      </ConsentProvider>
    );

    // Initially no banner because consent exists
    expect(screen.getByTestId("show-banner")).toHaveTextContent("false");
    expect(screen.getByTestId("has-consent")).toHaveTextContent("true");

    // Clear consent
    fireEvent.click(screen.getByText("Clear"));

    await waitFor(() => {
      expect(screen.getByTestId("show-banner")).toHaveTextContent("true");
      expect(screen.getByTestId("has-consent")).toHaveTextContent("false");
    });
  });
});

// ============================================
// Footer Component Tests
// ============================================
describe("Footer", () => {
  it("renders the footer with Cookie Settings link", () => {
    const mockShowSettings = jest.fn();

    render(<Footer onShowCookieSettings={mockShowSettings} />);

    expect(screen.getByTestId("site-footer")).toBeInTheDocument();
    expect(screen.getByTestId("cookie-settings-link")).toBeInTheDocument();
  });

  it("calls onShowCookieSettings when Cookie Settings is clicked", async () => {
    const mockShowSettings = jest.fn();

    render(<Footer onShowCookieSettings={mockShowSettings} />);

    const cookieLink = screen.getByTestId("cookie-settings-link");
    await userEvent.click(cookieLink);

    expect(mockShowSettings).toHaveBeenCalled();
  });

  it("renders legal links correctly", () => {
    render(<Footer />);

    expect(screen.getByText("Terms of Service")).toBeInTheDocument();
    expect(screen.getByText("Privacy Policy")).toBeInTheDocument();
    expect(screen.getByText("Cookie Settings")).toBeInTheDocument();
  });

  it("renders tool links correctly", () => {
    render(<Footer />);

    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
    expect(screen.getByText("Keyword Density")).toBeInTheDocument();
  });

  it("has correct accessibility attributes", () => {
    render(<Footer />);

    const footer = screen.getByRole("contentinfo");
    expect(footer).toHaveAttribute("aria-label", "Site footer");
  });
});

// ============================================
// CookieConsent with Context Tests
// ============================================
describe("CookieConsent with ConsentProvider", () => {
  it("shows banner when showBanner is true in context", () => {
    render(
      <ConsentProvider>
        <CookieConsent />
      </ConsentProvider>
    );

    expect(screen.getByTestId("cookie-consent-banner")).toBeInTheDocument();
  });

  it("hides banner after user accepts", async () => {
    const TestWrapper = () => {
      return (
        <ConsentProvider>
          <CookieConsent />
        </ConsentProvider>
      );
    };

    render(<TestWrapper />);

    expect(screen.getByTestId("cookie-consent-banner")).toBeInTheDocument();

    const acceptButton = screen.getByRole("button", { name: /accept all/i });
    await userEvent.click(acceptButton);

    await waitFor(() => {
      expect(
        screen.queryByTestId("cookie-consent-banner")
      ).not.toBeInTheDocument();
    });
  });

  it("shows customize panel when showCustomize is true", async () => {
    render(
      <ConsentProvider>
        <CookieConsent showCustomize />
      </ConsentProvider>
    );

    expect(screen.getByText("Customize")).toBeInTheDocument();

    const customizeButton = screen.getByRole("button", { name: /customize/i });
    await userEvent.click(customizeButton);

    expect(screen.getByText("Analytics Cookies")).toBeInTheDocument();
    expect(screen.getByText("Marketing Cookies")).toBeInTheDocument();
  });
});

// ============================================
// useConsent Hook Tests
// ============================================
describe("useConsent hook", () => {
  const TestComponent = () => {
    const {
      consent,
      hasConsent,
      hasConsentFor,
      updateConsent,
      clearConsent,
      showBanner,
      setShowBanner,
    } = useConsent();

    return (
      <div>
        <span data-testid="has-consent">{hasConsent.toString()}</span>
        <span data-testid="show-banner">{showBanner.toString()}</span>
        <span data-testid="analytics-consent">
          {hasConsentFor("analytics").toString()}
        </span>
        <button onClick={() => updateConsent(true, true)}>Accept</button>
        <button onClick={() => updateConsent(false, false)}>Decline</button>
        <button onClick={clearConsent}>Clear</button>
        <button onClick={() => setShowBanner(true)}>Show Banner</button>
      </div>
    );
  };

  it("returns correct initial state when no consent exists", () => {
    render(<TestComponent />);

    expect(screen.getByTestId("has-consent")).toHaveTextContent("false");
    expect(screen.getByTestId("show-banner")).toHaveTextContent("true");
  });

  it("hasConsentFor returns false when no consent exists", () => {
    render(<TestComponent />);

    expect(screen.getByTestId("analytics-consent")).toHaveTextContent("false");
  });

  it("updateConsent updates the consent state", async () => {
    render(<TestComponent />);

    fireEvent.click(screen.getByText("Accept"));

    await waitFor(() => {
      expect(screen.getByTestId("has-consent")).toHaveTextContent("true");
      expect(screen.getByTestId("analytics-consent")).toHaveTextContent("true");
    });
  });

  it("clearConsent resets consent state", async () => {
    // Set initial consent
    setCookieConsent({ necessary: true, analytics: true, marketing: true });

    render(<TestComponent />);

    // Clear it
    fireEvent.click(screen.getByText("Clear"));

    await waitFor(() => {
      expect(screen.getByTestId("has-consent")).toHaveTextContent("false");
      expect(screen.getByTestId("show-banner")).toHaveTextContent("true");
    });
  });

  it("setShowBanner controls banner visibility", async () => {
    // Set initial consent so banner is hidden
    setCookieConsent({ necessary: true, analytics: false, marketing: false });

    render(<TestComponent />);

    // Initially no banner
    expect(screen.getByTestId("show-banner")).toHaveTextContent("false");

    // Show banner
    fireEvent.click(screen.getByText("Show Banner"));

    await waitFor(() => {
      expect(screen.getByTestId("show-banner")).toHaveTextContent("true");
    });
  });
});

// ============================================
// Integration Tests
// ============================================
describe("Cookie Consent Integration", () => {
  it("complete flow: show banner -> accept -> footer link -> show again", async () => {
    const IntegrationTest = () => {
      const { showBanner, setShowBanner } = useConsent();

      return (
        <div>
          <Footer onShowCookieSettings={() => setShowBanner(true)} />
          <CookieConsent visible={showBanner} onVisibleChange={setShowBanner} />
        </div>
      );
    };

    render(
      <ConsentProvider>
        <IntegrationTest />
      </ConsentProvider>
    );

    // Banner shows initially
    expect(screen.getByTestId("cookie-consent-banner")).toBeInTheDocument();

    // Accept all
    const acceptButton = screen.getByRole("button", { name: /accept all/i });
    await userEvent.click(acceptButton);

    // Banner disappears
    await waitFor(() => {
      expect(
        screen.queryByTestId("cookie-consent-banner")
      ).not.toBeInTheDocument();
    });

    // Click Cookie Settings in footer
    const cookieLink = screen.getByTestId("cookie-settings-link");
    await userEvent.click(cookieLink);

    // Banner shows again
    await waitFor(() => {
      expect(screen.getByTestId("cookie-consent-banner")).toBeInTheDocument();
    });
  });
});
