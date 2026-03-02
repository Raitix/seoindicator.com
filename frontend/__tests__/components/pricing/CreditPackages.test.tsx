/**
 * CreditPackages Component Tests - TASK-26-20
 *
 * Tests for the credit packages grid section that displays
 * available credit packages for purchase.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import { render, screen, within, fireEvent } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import CreditPackages from "@/components/pricing/CreditPackages";

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

const mockHandlers = {
  onBuyNow: jest.fn(),
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== CP-001: Rendering ====================

describe("Rendering", () => {
  it("renders without crashing", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByTestId("credit-packages-section")).toBeInTheDocument();
  });

  it("displays section heading", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByRole("heading", { level: 2 })).toHaveTextContent(/credit.*package/i);
  });

  it("displays 4 package cards", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const cards = screen.getAllByTestId(/package-card-/);
    expect(cards).toHaveLength(4);
  });

  it("displays all package names", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText("Starter")).toBeInTheDocument();
    expect(screen.getByText("Basic")).toBeInTheDocument();
    expect(screen.getByText("Pro")).toBeInTheDocument();
    expect(screen.getByText("Enterprise")).toBeInTheDocument();
  });
});

// ==================== CP-002: Loading State ====================

describe("Loading State", () => {
  it("shows loading skeleton when isLoading is true", () => {
    render(
      <CreditPackages
        packages={[]}
        isLoading={true}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByTestId("packages-loading-skeleton")).toBeInTheDocument();
  });

  it("shows 4 skeleton placeholders", () => {
    render(
      <CreditPackages
        packages={[]}
        isLoading={true}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const skeletons = screen.getAllByTestId(/skeleton-/);
    expect(skeletons.length).toBeGreaterThanOrEqual(4);
  });

  it("does not show package cards while loading", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={true}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.queryByTestId("package-card-starter")).not.toBeInTheDocument();
  });
});

// ==================== CP-003: Error State ====================

describe("Error State", () => {
  it("shows error message when error is present", () => {
    render(
      <CreditPackages
        packages={[]}
        isLoading={false}
        error={new Error("Failed to fetch packages")}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/failed to load packages/i)).toBeInTheDocument();
  });

  it("shows retry button on error", () => {
    render(
      <CreditPackages
        packages={[]}
        isLoading={false}
        error={new Error("Failed to fetch packages")}
        onBuyNow={mockHandlers.onBuyNow}
        onRetry={jest.fn()}
      />
    );

    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("calls onRetry when retry button clicked", async () => {
    const onRetry = jest.fn();
    const user = userEvent.setup();

    render(
      <CreditPackages
        packages={[]}
        isLoading={false}
        error={new Error("Failed to fetch")}
        onBuyNow={mockHandlers.onBuyNow}
        onRetry={onRetry}
      />
    );

    await user.click(screen.getByRole("button", { name: /retry/i }));
    expect(onRetry).toHaveBeenCalledTimes(1);
  });
});

// ==================== CP-004: Popular Badge ====================

describe("Popular Badge", () => {
  it("shows Popular badge on package with isPopular=true", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const proCard = screen.getByTestId("package-card-pro");
    expect(within(proCard).getByText(/popular/i)).toBeInTheDocument();
  });

  it("applies distinct styling to popular card", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const proCard = screen.getByTestId("package-card-pro");
    expect(proCard.className).toMatch(/ring|border|highlight/i);
  });

  it("does not show Popular badge on other packages", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const starterCard = screen.getByTestId("package-card-starter");
    expect(within(starterCard).queryByText(/popular/i)).not.toBeInTheDocument();
  });
});

// ==================== CP-005: Minimum Purchase ====================

describe("Minimum Purchase Highlight", () => {
  it("shows minimum purchase callout", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
        minimumPurchase={5}
      />
    );

    expect(screen.getByText(/\$5.*minimum/i)).toBeInTheDocument();
  });

  it("shows minimum purchase in starter package description", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
        minimumPurchase={5}
      />
    );

    const starterCard = screen.getByTestId("package-card-starter");
    expect(starterCard).toHaveTextContent(/\$5/);
  });
});

// ==================== CP-006: Buy Now Action ====================

describe("Buy Now Action", () => {
  it("shows Buy Now button for each package", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const buyButtons = screen.getAllByRole("button", { name: /buy now/i });
    expect(buyButtons).toHaveLength(4);
  });

  it("calls onBuyNow with package id when clicked", async () => {
    const user = userEvent.setup();

    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const proCard = screen.getByTestId("package-card-pro");
    const buyButton = within(proCard).getByRole("button", { name: /buy now/i });

    await user.click(buyButton);

    expect(mockHandlers.onBuyNow).toHaveBeenCalledWith("pro");
  });
});

// ==================== CP-007: Responsive Grid ====================

describe("Responsive Grid Layout", () => {
  it("has responsive grid classes", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const grid = screen.getByTestId("packages-grid");
    expect(grid.className).toMatch(/grid/);
  });

  it("has mobile-first responsive columns", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const grid = screen.getByTestId("packages-grid");
    // Should have responsive breakpoints
    expect(grid.className).toMatch(/sm:|md:|lg:/);
  });
});

// ==================== CP-008: Price Sorting ====================

describe("Price Sorting", () => {
  it("displays packages in price ascending order", () => {
    // Shuffle packages to test sorting
    const shuffledPackages = [
      mockPackages[3],
      mockPackages[0],
      mockPackages[2],
      mockPackages[1],
    ];

    render(
      <CreditPackages
        packages={shuffledPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const cards = screen.getAllByTestId(/package-card-/);
    const prices = cards.map((card) => {
      const priceText = within(card).getByText(/^\$\d+/).textContent || "";
      return parseFloat(priceText.replace(/[^0-9.]/g, ""));
    });

    // Verify ascending order
    for (let i = 1; i < prices.length; i++) {
      expect(prices[i]).toBeGreaterThanOrEqual(prices[i - 1]);
    }
  });
});

// ==================== CP-009: Bonus Credits ====================

describe("Bonus Credits Display", () => {
  it("shows bonus credits when available", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    // Pro package has bonus
    const proCard = screen.getByTestId("package-card-pro");
    expect(within(proCard).getByText(/\+250.*bonus/i)).toBeInTheDocument();
  });

  it("does not show bonus for packages without bonus", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    // Starter package has no bonus
    const starterCard = screen.getByTestId("package-card-starter");
    expect(within(starterCard).queryByText(/bonus/i)).not.toBeInTheDocument();
  });
});

// ==================== CP-010: Accessibility ====================

describe("Accessibility", () => {
  it("grid has accessible label", () => {
    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const grid = screen.getByTestId("packages-grid");
    expect(grid).toHaveAttribute("aria-label", "Credit packages");
  });

  it("all buy buttons are keyboard accessible", async () => {
    const user = userEvent.setup();

    render(
      <CreditPackages
        packages={mockPackages}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const buyButtons = screen.getAllByRole("button", { name: /buy now/i });

    for (const button of buyButtons) {
      button.focus();
      expect(button).toHaveFocus();

      await user.keyboard("{Enter}");
    }

    expect(mockHandlers.onBuyNow).toHaveBeenCalledTimes(4);
  });
});

// ==================== CP-011: Empty State ====================

describe("Empty State", () => {
  it("shows empty state when no packages available", () => {
    render(
      <CreditPackages
        packages={[]}
        isLoading={false}
        error={null}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/no packages available/i)).toBeInTheDocument();
  });
});
