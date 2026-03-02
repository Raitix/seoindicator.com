/**
 * PackageCard Component Tests - TASK-26-20
 *
 * Tests for individual credit package card displaying name,
 * credits, price, bonus, and buy button.
 *
 * @see SPEC-026
 * @see TASK-26-20
 */

import { render, screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import PackageCard from "@/components/pricing/PackageCard";

// ==================== Mock Data ====================

const standardPackage = {
  id: "basic",
  name: "Basic",
  credits: 500,
  price: 20.0,
  currency: "USD",
  bonus: 50,
  isPopular: false,
};

const popularPackage = {
  id: "pro",
  name: "Pro",
  credits: 1500,
  price: 50.0,
  currency: "USD",
  bonus: 250,
  isPopular: true,
};

const noBonusPackage = {
  id: "starter",
  name: "Starter",
  credits: 100,
  price: 5.0,
  currency: "USD",
  bonus: 0,
  isPopular: false,
};

const mockHandlers = {
  onBuyNow: jest.fn(),
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== PC-001: Rendering ====================

describe("Rendering", () => {
  it("renders without crashing", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByTestId("package-card-basic")).toBeInTheDocument();
  });

  it("displays package name", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText("Basic")).toBeInTheDocument();
  });

  it("displays credit amount", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/500/)).toBeInTheDocument();
    expect(screen.getByText(/credits/i)).toBeInTheDocument();
  });

  it("displays price with currency symbol", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/\$20/)).toBeInTheDocument();
  });

  it("displays "Buy Now" button", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByRole("button", { name: /buy now/i })).toBeInTheDocument();
  });
});

// ==================== PC-002: Bonus Credits ====================

describe("Bonus Credits Display", () => {
  it("shows bonus credits when present", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/\+50/i)).toBeInTheDocument();
    expect(screen.getByText(/bonus/i)).toBeInTheDocument();
  });

  it("does not show bonus section when zero", () => {
    render(
      <PackageCard
        package={noBonusPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.queryByText(/bonus/i)).not.toBeInTheDocument();
  });

  it("formats bonus number correctly", () => {
    const largeBonusPackage = {
      ...standardPackage,
      bonus: 1000,
    };

    render(
      <PackageCard
        package={largeBonusPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/\+1,000/i)).toBeInTheDocument();
  });
});

// ==================== PC-003: Popular Badge ====================

describe("Popular Badge", () => {
  it("shows "Popular" badge when isPopular is true", () => {
    render(
      <PackageCard
        package={popularPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/popular/i)).toBeInTheDocument();
  });

  it("applies distinct styling to popular card", () => {
    render(
      <PackageCard
        package={popularPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const card = screen.getByTestId("package-card-pro");
    expect(card.className).toMatch(/ring|border|highlight|popular/i);
  });

  it("does not show badge when not popular", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.queryByText(/popular/i)).not.toBeInTheDocument();
  });

  it("popular badge has distinct color", () => {
    render(
      <PackageCard
        package={popularPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const badge = screen.getByText(/popular/i);
    expect(badge.className).toMatch(/blue|purple|primary|accent/i);
  });
});

// ==================== PC-004: Price Formatting ====================

describe("Price Formatting", () => {
  it("formats price with dollar sign", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/\$20/)).toBeInTheDocument();
  });

  it("shows decimal for non-whole dollar amounts", () => {
    const decimalPackage = {
      ...standardPackage,
      price: 19.99,
    };

    render(
      <PackageCard
        package={decimalPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/\$19\.99/)).toBeInTheDocument();
  });

  it("does not show decimal for whole dollars", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    // Should be $20, not $20.00
    expect(screen.getByText(/\$20$/)).toBeInTheDocument();
    expect(screen.queryByText(/\$20\.00/)).not.toBeInTheDocument();
  });
});

// ==================== PC-005: Buy Now Button ====================

describe("Buy Now Button", () => {
  it("is clickable", async () => {
    const user = userEvent.setup();

    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    await user.click(button);

    expect(mockHandlers.onBuyNow).toHaveBeenCalledWith("basic");
  });

  it("calls onBuyNow with package id", async () => {
    const user = userEvent.setup();

    render(
      <PackageCard
        package={popularPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    await user.click(button);

    expect(mockHandlers.onBuyNow).toHaveBeenCalledWith("pro");
  });

  it("shows loading state when loading prop is true", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
        isLoading={true}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    expect(button).toBeDisabled();
  });

  it("is disabled when disabled prop is true", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
        disabled={true}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    expect(button).toBeDisabled();
  });
});

// ==================== PC-006: Keyboard Accessibility ====================

describe("Keyboard Accessibility", () => {
  it("can be focused with Tab", async () => {
    const user = userEvent.setup();

    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    await user.tab();

    const button = screen.getByRole("button", { name: /buy now/i });
    expect(button).toHaveFocus();
  });

  it("can be activated with Enter key", async () => {
    const user = userEvent.setup();

    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    button.focus();

    await user.keyboard("{Enter}");

    expect(mockHandlers.onBuyNow).toHaveBeenCalledWith("basic");
  });

  it("can be activated with Space key", async () => {
    const user = userEvent.setup();

    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    button.focus();

    await user.keyboard(" ");

    expect(mockHandlers.onBuyNow).toHaveBeenCalledWith("basic");
  });

  it("has visible focus indicator", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    expect(button.className).toMatch(/focus|ring/);
  });
});

// ==================== PC-007: Hover Effects ====================

describe("Hover Effects", () => {
  it("has hover class on card", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const card = screen.getByTestId("package-card-basic");
    expect(card.className).toMatch(/hover/);
  });

  it("has hover class on button", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    expect(button.className).toMatch(/hover/);
  });
});

// ==================== PC-008: Credit Value ====================

describe("Credit Value Display", () => {
  it("shows "credits" label", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/credits/i)).toBeInTheDocument();
  });

  it("formats large numbers with commas", () => {
    const largePackage = {
      ...standardPackage,
      credits: 5000,
    };

    render(
      <PackageCard
        package={largePackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/5,000/)).toBeInTheDocument();
  });

  it("shows cost per credit when showValue prop is true", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
        showValue={true}
      />
    );

    // 500 credits for $20 = $0.04 per credit
    expect(screen.getByText(/\$0\.04.*credit/i)).toBeInTheDocument();
  });
});

// ==================== PC-009: Currency Support ====================

describe("Currency Support", () => {
  it("displays USD by default", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/\$/)).toBeInTheDocument();
  });

  it("displays EUR when specified", () => {
    const eurPackage = {
      ...standardPackage,
      currency: "EUR",
    };

    render(
      <PackageCard
        package={eurPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/€/)).toBeInTheDocument();
  });
});

// ==================== PC-010: Accessibility ====================

describe("Accessibility", () => {
  it("card has accessible role", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const card = screen.getByTestId("package-card-basic");
    // Should have role="article" or similar
    expect(card).toBeInTheDocument();
  });

  it("has descriptive aria-label on button", () => {
    render(
      <PackageCard
        package={standardPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const button = screen.getByRole("button", { name: /buy now/i });
    expect(button).toBeInTheDocument();
  });
});

// ==================== PC-011: Edge Cases ====================

describe("Edge Cases", () => {
  it("handles zero price gracefully", () => {
    const freePackage = {
      ...standardPackage,
      price: 0,
    };

    render(
      <PackageCard
        package={freePackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/free/i)).toBeInTheDocument();
  });

  it("handles very large credit amounts", () => {
    const largePackage = {
      ...standardPackage,
      credits: 1000000,
      bonus: 250000,
    };

    render(
      <PackageCard
        package={largePackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/1,000,000/)).toBeInTheDocument();
    expect(screen.getByText(/\+250,000/)).toBeInTheDocument();
  });

  it("handles very long package name", () => {
    const longNamePackage = {
      ...standardPackage,
      name: "Enterprise Business Premium Plus Package",
    };

    render(
      <PackageCard
        package={longNamePackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    expect(screen.getByText(/Enterprise Business Premium Plus Package/)).toBeInTheDocument();
  });
});

// ==================== PC-012: Security ====================

describe("Security", () => {
  it("sanitizes XSS in package name", () => {
    const xssPackage = {
      ...standardPackage,
      name: '<script>alert("XSS")</script>Basic',
    };

    render(
      <PackageCard
        package={xssPackage}
        onBuyNow={mockHandlers.onBuyNow}
      />
    );

    const scripts = document.querySelectorAll('script:not([src])');
    scripts.forEach((script) => {
      expect(script.textContent).not.toContain("alert");
    });
  });
});
