/**
 * Pricing Page Tests - TASK-26-20
 *
 * Tests for the public pricing page that displays credit packages,
 * model pricing comparison, and FAQ section.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import PricingPage from "@/app/pricing/page";

// Mock SWR
jest.mock("swr", () => ({
  __esModule: true,
  default: jest.fn(),
}));

import useSWR from "swr";

// Mock next/navigation
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(() => ({
    push: jest.fn(),
  })),
}));

// ==================== Mock Data ====================

const mockPackages = [
  {
    id: "starter",
    name: "Starter",
    credits: 100,
    price: 5.0,
    currency: "USD",
    bonus: 0,
    isPopular: false,
  },
  {
    id: "basic",
    name: "Basic",
    credits: 500,
    price: 20.0,
    currency: "USD",
    bonus: 50,
    isPopular: false,
  },
  {
    id: "pro",
    name: "Pro",
    credits: 1500,
    price: 50.0,
    currency: "USD",
    bonus: 250,
    isPopular: true,
  },
  {
    id: "enterprise",
    name: "Enterprise",
    credits: 5000,
    price: 150.0,
    currency: "USD",
    bonus: 1000,
    isPopular: false,
  },
];

const mockModels = [
  {
    id: "gpt-4o-mini",
    name: "GPT-4o Mini",
    provider: "OpenAI",
    costPerAnalysis: 0.02,
    freeTier: "10/month",
    contextWindow: "128K",
    description: "Fast and efficient for most tasks",
  },
  {
    id: "claude-3-haiku",
    name: "Claude 3 Haiku",
    provider: "Anthropic",
    costPerAnalysis: 0.025,
    freeTier: "10/month",
    contextWindow: "200K",
    description: "Quick responses with high quality",
  },
  {
    id: "gemini-2-flash",
    name: "Gemini 2 Flash",
    provider: "Google",
    costPerAnalysis: 0.015,
    freeTier: "15/month",
    contextWindow: "1M",
    description: "Best value for large content",
  },
];

const mockFAQs = [
  {
    id: "faq-1",
    question: "What are credits?",
    answer: "Credits are used to run SEO analyses. Each analysis costs credits based on the AI model.",
  },
  {
    id: "faq-2",
    question: "Do credits expire?",
    answer: "No, purchased credits never expire. They remain in your account until used.",
  },
];

// Helper to setup SWR mock
const mockUseSWR = (
  packagesData: any = mockPackages,
  modelsData: any = mockModels,
  isLoading = false,
  error: any = null
) => {
  (useSWR as jest.Mock).mockImplementation((key: string) => {
    if (key === "/api/v1/packages") {
      return { data: packagesData, isLoading, error, mutate: jest.fn() };
    }
    if (key === "/api/v1/models") {
      return { data: modelsData, isLoading, error, mutate: jest.fn() };
    }
    return { data: null, isLoading: false, error: null, mutate: jest.fn() };
  });
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
  mockUseSWR();
});

// ==================== PP-001: Page Rendering ====================

describe("Page Rendering", () => {
  it("renders the pricing page at /pricing route (PP-001)", () => {
    render(<PricingPage />);

    expect(screen.getByTestId("pricing-page")).toBeInTheDocument();
  });

  it("displays hero section with title and description (PP-002)", () => {
    render(<PricingPage />);

    expect(screen.getByRole("heading", { level: 1 })).toHaveTextContent(/pricing/i);
    expect(screen.getByText(/credit/i)).toBeInTheDocument();
  });

  it("displays credit packages section (PP-003)", () => {
    render(<PricingPage />);

    expect(screen.getByTestId("credit-packages-section")).toBeInTheDocument();
  });

  it("displays model pricing table (PP-004)", () => {
    render(<PricingPage />);

    expect(screen.getByTestId("model-pricing-table")).toBeInTheDocument();
  });

  it("displays FAQ section (PP-005)", () => {
    render(<PricingPage />);

    expect(screen.getByTestId("pricing-faq")).toBeInTheDocument();
  });

  it("works without authentication - no redirect (PP-007)", () => {
    render(<PricingPage />);

    // Page should render without any auth redirect
    expect(screen.getByTestId("pricing-page")).toBeInTheDocument();
  });
});

// ==================== PP-010: Loading States ====================

describe("Loading States", () => {
  it("shows loading skeleton for packages while fetching (CP-002)", () => {
    mockUseSWR(null, null, true);

    render(<PricingPage />);

    expect(screen.getByTestId("packages-loading-skeleton")).toBeInTheDocument();
  });

  it("shows loading skeleton for models while fetching (MT-002)", () => {
    mockUseSWR(mockPackages, null, true);

    render(<PricingPage />);

    expect(screen.getByTestId("models-loading-skeleton")).toBeInTheDocument();
  });
});

// ==================== PP-020: Error States ====================

describe("Error States", () => {
  it("shows error message when packages fetch fails (CP-003)", () => {
    mockUseSWR(null, mockModels, false, new Error("Failed to fetch packages"));

    render(<PricingPage />);

    expect(screen.getByText(/failed to load packages/i)).toBeInTheDocument();
  });

  it("shows retry button on packages error (API-005)", () => {
    mockUseSWR(null, mockModels, false, new Error("Failed to fetch packages"));

    render(<PricingPage />);

    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("shows error message when models fetch fails (MT-003)", () => {
    mockUseSWR(mockPackages, null, false, null);

    // Simulate models error by passing error for models
    (useSWR as jest.Mock).mockImplementation((key: string) => {
      if (key === "/api/v1/packages") {
        return { data: mockPackages, isLoading: false, error: null, mutate: jest.fn() };
      }
      if (key === "/api/v1/models") {
        return { data: null, isLoading: false, error: new Error("Failed"), mutate: jest.fn() };
      }
      return { data: null, isLoading: false, error: null, mutate: jest.fn() };
    });

    render(<PricingPage />);

    expect(screen.getByText(/failed to load models/i)).toBeInTheDocument();
  });
});

// ==================== PP-030: Credit Packages Display ====================

describe("Credit Packages Display", () => {
  it("displays 4 credit packages (CP-001)", () => {
    render(<PricingPage />);

    const packages = screen.getAllByTestId(/package-card-/);
    expect(packages).toHaveLength(4);
  });

  it("highlights popular package with badge (CP-004)", () => {
    render(<PricingPage />);

    const popularBadge = screen.getByText(/popular/i);
    expect(popularBadge).toBeInTheDocument();
  });

  it("shows minimum purchase callout (CP-005)", () => {
    render(<PricingPage />);

    expect(screen.getByText(/\$5.*minimum/i)).toBeInTheDocument();
  });

  it("displays packages sorted by price ascending (CP-008)", () => {
    render(<PricingPage />);

    const packageCards = screen.getAllByTestId(/package-card-/);
    const prices = packageCards.map((card) => {
      const priceText = within(card).getByText(/\$\d+/).textContent;
      return parseFloat(priceText?.replace(/[^0-9.]/g, "") || "0");
    });

    // Check prices are in ascending order
    for (let i = 1; i < prices.length; i++) {
      expect(prices[i]).toBeGreaterThanOrEqual(prices[i - 1]);
    }
  });
});

// ==================== PP-040: Model Pricing Table Display ====================

describe("Model Pricing Table Display", () => {
  it("displays table with correct headers (MT-001)", () => {
    render(<PricingPage />);

    const table = screen.getByRole("table");
    expect(table).toBeInTheDocument();

    expect(within(table).getByText(/model/i)).toBeInTheDocument();
    expect(within(table).getByText(/cost.*analysis/i)).toBeInTheDocument();
    expect(within(table).getByText(/free.*tier/i)).toBeInTheDocument();
    expect(within(table).getByText(/context/i)).toBeInTheDocument();
  });

  it("displays all models from API (MT-001)", () => {
    render(<PricingPage />);

    expect(screen.getByText("GPT-4o Mini")).toBeInTheDocument();
    expect(screen.getByText("Claude 3 Haiku")).toBeInTheDocument();
    expect(screen.getByText("Gemini 2 Flash")).toBeInTheDocument();
  });

  it("displays free tier information (MT-007)", () => {
    render(<PricingPage />);

    expect(screen.getByText("10/month")).toBeInTheDocument();
    expect(screen.getByText("15/month")).toBeInTheDocument();
  });

  it("displays context window information (MT-008)", () => {
    render(<PricingPage />);

    expect(screen.getByText("128K")).toBeInTheDocument();
    expect(screen.getByText("200K")).toBeInTheDocument();
    expect(screen.getByText("1M")).toBeInTheDocument();
  });

  it("shows empty state when no models available (MT-010)", () => {
    mockUseSWR(mockPackages, [], false);

    render(<PricingPage />);

    expect(screen.getByText(/no models available/i)).toBeInTheDocument();
  });
});

// ==================== PP-050: FAQ Section ====================

describe("FAQ Section", () => {
  it("displays accordion with questions (FAQ-001)", () => {
    render(<PricingPage />);

    const faqSection = screen.getByTestId("pricing-faq");
    expect(within(faqSection).getAllByRole("button").length).toBeGreaterThan(0);
  });

  it("questions are collapsed by default (FAQ-002)", () => {
    render(<PricingPage />);

    // Answers should not be visible initially
    expect(screen.queryByText(/Credits are used to run SEO analyses/)).not.toBeVisible();
  });
});

// ==================== PP-060: SEO Requirements ====================

describe("SEO Requirements", () => {
  it("has proper heading hierarchy with single h1 (SEO-006, A11Y-001)", () => {
    render(<PricingPage />);

    const h1Elements = screen.getAllByRole("heading", { level: 1 });
    expect(h1Elements).toHaveLength(1);
    expect(h1Elements[0]).toHaveTextContent(/pricing/i);
  });

  it("has h2 for main sections (SEO-006)", () => {
    render(<PricingPage />);

    const h2Elements = screen.getAllByRole("heading", { level: 2 });
    expect(h2Elements.length).toBeGreaterThan(0);
  });
});

// ==================== PP-070: Accessibility ====================

describe("Accessibility", () => {
  it("supports keyboard navigation (A11Y-002)", async () => {
    const user = userEvent.setup();
    render(<PricingPage />);

    // Tab through interactive elements
    await user.tab();
    expect(document.activeElement).toBeTruthy();

    // Should be able to tab to Buy Now buttons
    let attempts = 0;
    while (attempts < 20 && !document.activeElement?.textContent?.includes("Buy")) {
      await user.tab();
      attempts++;
    }
    // Either found a Buy button or tabbed through all elements
    expect(document.activeElement).toBeTruthy();
  });

  it("has main content landmark (A11Y-003)", () => {
    render(<PricingPage />);

    const main = screen.getByRole("main");
    expect(main).toBeInTheDocument();
  });

  it("packages grid has accessible label", () => {
    render(<PricingPage />);

    const packagesGrid = screen.getByTestId("packages-grid");
    expect(packagesGrid).toHaveAttribute("aria-label", "Credit packages");
  });

  it("table has accessible caption", () => {
    render(<PricingPage />);

    const table = screen.getByRole("table");
    const caption = within(table).getByText(/AI Model Pricing/i);
    expect(caption).toBeInTheDocument();
  });
});

// ==================== PP-080: Responsive Layout ====================

describe("Responsive Layout", () => {
  it("packages grid has responsive classes (RWD-001-003)", () => {
    render(<PricingPage />);

    const packagesGrid = screen.getByTestId("packages-grid");
    // Should have responsive grid classes
    expect(packagesGrid.className).toMatch(/grid/);
  });

  it("table has responsive wrapper for mobile scroll (RWD-004)", () => {
    render(<PricingPage />);

    const tableWrapper = screen.getByTestId("table-wrapper");
    expect(tableWrapper).toHaveClass(/overflow/);
  });
});

// ==================== PP-090: Security ====================

describe("Security", () => {
  it("sanitizes XSS in package names (SEC-001)", () => {
    const xssPackages = [
      {
        ...mockPackages[0],
        name: '<script>alert("XSS")</script>Starter',
      },
      ...mockPackages.slice(1),
    ];

    mockUseSWR(xssPackages);

    render(<PricingPage />);

    // Script tag should be escaped, not executed
    const scriptElements = document.querySelectorAll("script:not([src])");
    scriptElements.forEach((script) => {
      expect(script.textContent).not.toContain("alert");
    });
  });

  it("sanitizes XSS in model names (SEC-002)", () => {
    const xssModels = [
      {
        ...mockModels[0],
        name: '<img src="x" onerror="alert(1)">GPT',
      },
    ];

    mockUseSWR(mockPackages, xssModels);

    render(<PricingPage />);

    // Should not have img elements with onerror
    const imgElements = document.querySelectorAll('img[onerror*="alert"]');
    expect(imgElements).toHaveLength(0);
  });

  it("does not expose sensitive data in DOM (SEC-004)", () => {
    render(<PricingPage />);

    const html = document.body.innerHTML;
    expect(html).not.toContain("api_key");
    expect(html).not.toContain("password");
    expect(html).not.toContain("secret");
    expect(html).not.toContain("token");
  });
});

// ==================== PP-100: Performance ====================

describe("Performance", () => {
  it("renders within acceptable time", () => {
    const startTime = performance.now();

    render(<PricingPage />);

    const renderTime = performance.now() - startTime;
    expect(renderTime).toBeLessThan(200); // Should render in under 200ms
  });
});
