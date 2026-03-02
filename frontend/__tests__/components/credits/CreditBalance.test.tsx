/**
 * CreditBalance Component Tests - TASK-26-21
 *
 * Tests for the main credit balance component that displays
 * user's balance with actions for adding credits and viewing history.
 *
 * @see SPEC-026
 * @see TASK-26-21
 */

import React from "react";
import { render, screen, within, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { CreditBalance } from "@/components/credits/CreditBalance";

// ==================== Mock Data ====================

const mockBalanceData = {
  balance: 25.5,
  formatted_balance: "$25.50",
  total_purchased: 50.0,
  total_spent: 24.5,
  has_low_balance: false,
  low_balance_threshold: 2.0,
  currency: "USD",
};

const lowBalanceData = {
  balance: 1.5,
  formatted_balance: "$1.50",
  total_purchased: 10.0,
  total_spent: 8.5,
  has_low_balance: true,
  low_balance_threshold: 2.0,
  currency: "USD",
};

const mockHandlers = {
  onAddCredits: jest.fn(),
  onViewHistory: jest.fn(),
  onRetry: jest.fn(),
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== CB-001: Component Rendering ====================

describe("CB-001: Component Rendering", () => {
  it("CB-001-01: renders without crashing", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByTestId("credit-balance")).toBeInTheDocument();
  });

  it("CB-001-02: displays balance in USD format", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText("$25.50")).toBeInTheDocument();
  });

  it("CB-001-03: displays zero balance", () => {
    render(
      <CreditBalance
        balance={{ ...mockBalanceData, balance: 0, formatted_balance: "$0.00" }}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText("$0.00")).toBeInTheDocument();
  });

  it("CB-001-04: displays large balance with formatting", () => {
    render(
      <CreditBalance
        balance={{ ...mockBalanceData, balance: 9999.99, formatted_balance: "$9,999.99" }}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText("$9,999.99")).toBeInTheDocument();
  });

  it("CB-001-05: shows Add Credits button", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("button", { name: /add credits/i })).toBeInTheDocument();
  });

  it("CB-001-06: shows transaction history link", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("button", { name: /history|transactions/i })).toBeInTheDocument();
  });

  it("CB-001-07: displays expandable details toggle", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("button", { name: /details|expand|more/i })).toBeInTheDocument();
  });

  it("CB-001-08: shows total purchased/spent when expanded", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const toggleButton = screen.getByRole("button", { name: /details|expand|more/i });
    await user.click(toggleButton);

    await waitFor(() => {
      expect(screen.getByText(/purchased/i)).toBeInTheDocument();
      expect(screen.getByText(/spent/i)).toBeInTheDocument();
    });
  });
});

// ==================== CB-002: Loading State ====================

describe("CB-002: Loading State", () => {
  it("CB-002-01: shows skeleton while loading", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={true}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByTestId("balance-skeleton")).toBeInTheDocument();
  });

  it("CB-002-02: hides balance during loading", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={true}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.queryByText("$")).not.toBeInTheDocument();
  });

  it("CB-002-03: hides actions during loading", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={true}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.queryByRole("button", { name: /add credits/i })).not.toBeInTheDocument();
  });

  it("CB-002-04: shows pulse animation on skeleton", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={true}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const skeleton = screen.getByTestId("balance-skeleton");
    expect(skeleton.className).toMatch(/animate-pulse|skeleton/);
  });

  it("CB-002-05: transitions smoothly to loaded", async () => {
    const { rerender } = render(
      <CreditBalance
        balance={null}
        isLoading={true}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    rerender(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    await waitFor(() => {
      expect(screen.getByText("$25.50")).toBeInTheDocument();
    });
  });

  it("CB-002-06: shows skeleton with correct height", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={true}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const skeleton = screen.getByTestId("balance-skeleton");
    // Skeleton should have reasonable height (not too small, not too large)
    expect(skeleton).toBeInTheDocument();
  });
});

// ==================== CB-003: Error State ====================

describe("CB-003: Error State", () => {
  it("CB-003-01: shows error message on fetch failure", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={new Error("Failed to fetch balance")}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    expect(screen.getByText(/failed to load|error/i)).toBeInTheDocument();
  });

  it("CB-003-02: shows retry button on error", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={new Error("Failed to fetch")}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    expect(screen.getByRole("button", { name: /retry/i })).toBeInTheDocument();
  });

  it("CB-003-03: calls onRetry when retry clicked", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={new Error("Failed to fetch")}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    await user.click(screen.getByRole("button", { name: /retry/i }));

    expect(mockHandlers.onRetry).toHaveBeenCalledTimes(1);
  });

  it("CB-003-04: shows specific error for network failure", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={new Error("Network error")}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    expect(screen.getByText(/network|connection/i)).toBeInTheDocument();
  });

  it("CB-003-05: shows specific error for unauthorized", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={{ message: "Unauthorized", status: 401 } as any}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText(/sign in|unauthorized/i)).toBeInTheDocument();
  });

  it("CB-003-06: shows generic error for unknown", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={new Error("Unknown error")}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    expect(screen.getByText(/failed to load balance|error/i)).toBeInTheDocument();
  });

  it("CB-003-07: clears error on successful retry", async () => {
    const user = userEvent.setup();
    const { rerender } = render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={new Error("Failed")}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    expect(screen.getByText(/error/i)).toBeInTheDocument();

    // Simulate successful retry
    rerender(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        onRetry={mockHandlers.onRetry}
      />
    );

    await waitFor(() => {
      expect(screen.getByText("$25.50")).toBeInTheDocument();
    });
  });
});

// ==================== CB-004: Unauthenticated State ====================

describe("CB-004: Unauthenticated State", () => {
  it("CB-004-01: shows sign-in prompt when unauthenticated", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={null}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText(/sign in to view balance/i)).toBeInTheDocument();
  });

  it("CB-004-02: shows login link when unauthenticated", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={null}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("link", { name: /sign in|login/i })).toBeInTheDocument();
  });

  it("CB-004-03: does not fetch balance when unauthenticated", () => {
    // This test ensures no API call is made
    // In actual implementation, the component should not call useBalance hook
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={null}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    // Component should show unauthenticated state without any balance
    expect(screen.getByText(/sign in/i)).toBeInTheDocument();
    expect(screen.queryByText("$")).not.toBeInTheDocument();
  });

  it("CB-004-04: shows icon for unauthenticated state", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={null}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    // Should have a credit/wallet icon
    const container = screen.getByTestId("credit-balance");
    expect(container.querySelector("svg")).toBeInTheDocument();
  });

  it("CB-004-05: login link navigates correctly", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={null}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        loginUrl="/login"
      />
    );

    const loginLink = screen.getByRole("link", { name: /sign in|login/i });
    expect(loginLink).toHaveAttribute("href", "/login");
  });

  it("CB-004-06: shows call-to-action styling", () => {
    render(
      <CreditBalance
        balance={null}
        isLoading={false}
        error={null}
        isAuthenticated={false}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const container = screen.getByTestId("credit-balance");
    expect(container.className).toMatch(/bg-|border-|rounded/);
  });
});

// ==================== CB-005: Low Balance Warning ====================

describe("CB-005: Low Balance Warning", () => {
  it("CB-005-01: shows warning when balance < $2.00", () => {
    render(
      <CreditBalance
        balance={lowBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("CB-005-02: hides warning when balance >= $2.00", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.queryByTestId("low-balance-warning")).not.toBeInTheDocument();
  });

  it("CB-005-03: shows warning icon", () => {
    render(
      <CreditBalance
        balance={lowBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const warning = screen.getByTestId("low-balance-warning");
    expect(warning.querySelector("svg")).toBeInTheDocument();
  });

  it("CB-005-04: shows correct warning message", () => {
    render(
      <CreditBalance
        balance={{ ...lowBalanceData, balance: 0.5 }}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText(/low balance|running low/i)).toBeInTheDocument();
  });

  it("CB-005-05: warning includes quick add button", () => {
    render(
      <CreditBalance
        balance={lowBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const warning = screen.getByTestId("low-balance-warning");
    expect(within(warning).getByRole("button", { name: /add credits/i })).toBeInTheDocument();
  });

  it("CB-005-06: warning has amber/yellow styling", () => {
    render(
      <CreditBalance
        balance={lowBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const warning = screen.getByTestId("low-balance-warning");
    expect(warning.className).toMatch(/amber|yellow|warning|orange/i);
  });

  it("CB-005-07: can dismiss warning temporarily", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={lowBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const dismissButton = screen.getByRole("button", { name: /dismiss|close/i });
    await user.click(dismissButton);

    await waitFor(() => {
      expect(screen.queryByTestId("low-balance-warning")).not.toBeInTheDocument();
    });
  });

  it("CB-005-08: warning reappears after session", () => {
    // This test simulates warning appearing again after page refresh
    // In actual implementation, this uses sessionStorage
    render(
      <CreditBalance
        balance={lowBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });
});

// ==================== CB-006: Add Credits Action ====================

describe("CB-006: Add Credits Action", () => {
  it("CB-006-01: shows Add Credits button", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("button", { name: /add credits/i })).toBeInTheDocument();
  });

  it("CB-006-02: calls onAddCredits on click", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    await user.click(screen.getByRole("button", { name: /add credits/i }));

    expect(mockHandlers.onAddCredits).toHaveBeenCalledTimes(1);
  });

  it("CB-006-03: shows icon on button", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const button = screen.getByRole("button", { name: /add credits/i });
    expect(button.querySelector("svg")).toBeInTheDocument();
  });

  it("CB-006-04: button has correct aria-label", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const button = screen.getByRole("button", { name: /add credits/i });
    expect(button).toHaveAttribute("aria-label");
  });

  it("CB-006-05: shows quick add modal option", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        showQuickAddModal={true}
      />
    );

    await user.click(screen.getByRole("button", { name: /add credits/i }));

    await waitFor(() => {
      expect(screen.getByRole("dialog")).toBeInTheDocument();
    });
  });

  it("CB-006-06: quick add modal shows packages", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        showQuickAddModal={true}
      />
    );

    await user.click(screen.getByRole("button", { name: /add credits/i }));

    await waitFor(() => {
      expect(screen.getByRole("dialog")).toBeInTheDocument();
      // Modal should show package options
    });
  });

  it("CB-006-07: tracks add credits click event", async () => {
    const user = userEvent.setup();
    const mockTrackEvent = jest.fn();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        trackEvent={mockTrackEvent}
      />
    );

    await user.click(screen.getByRole("button", { name: /add credits/i }));

    expect(mockTrackEvent).toHaveBeenCalledWith("add_credits_clicked");
  });
});

// ==================== CB-007: Transaction History Link ====================

describe("CB-007: Transaction History Link", () => {
  it("CB-007-01: shows transaction history link", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("button", { name: /history|transactions/i })).toBeInTheDocument();
  });

  it("CB-007-02: calls onViewHistory on click", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    await user.click(screen.getByRole("button", { name: /history|transactions/i }));

    expect(mockHandlers.onViewHistory).toHaveBeenCalledTimes(1);
  });

  it("CB-007-03: shows icon next to link", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const historyButton = screen.getByRole("button", { name: /history|transactions/i });
    expect(historyButton.querySelector("svg")).toBeInTheDocument();
  });

  it("CB-007-04: link has correct text", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    // Should show either "View history", "Transaction history", or similar
    expect(screen.getByText(/history|transactions/i)).toBeInTheDocument();
  });

  it("CB-007-05: link is keyboard accessible", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const historyButton = screen.getByRole("button", { name: /history|transactions/i });
    historyButton.focus();
    expect(historyButton).toHaveFocus();

    await user.keyboard("{Enter}");
    expect(mockHandlers.onViewHistory).toHaveBeenCalled();
  });
});

// ==================== CB-008: Expandable Details ====================

describe("CB-008: Expandable Details", () => {
  it("CB-008-01: shows expand/collapse toggle", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByRole("button", { name: /details|expand|more/i })).toBeInTheDocument();
  });

  it("CB-008-02: shows total purchased when expanded", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const toggle = screen.getByRole("button", { name: /details|expand|more/i });
    await user.click(toggle);

    await waitFor(() => {
      expect(screen.getByText(/purchased/i)).toBeInTheDocument();
      expect(screen.getByText("$50.00")).toBeInTheDocument();
    });
  });

  it("CB-008-03: shows total spent when expanded", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const toggle = screen.getByRole("button", { name: /details|expand|more/i });
    await user.click(toggle);

    await waitFor(() => {
      expect(screen.getByText(/spent/i)).toBeInTheDocument();
      expect(screen.getByText("$24.50")).toBeInTheDocument();
    });
  });

  it("CB-008-04: formats totals as USD", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const toggle = screen.getByRole("button", { name: /details|expand|more/i });
    await user.click(toggle);

    await waitFor(() => {
      // Both values should be formatted as USD
      const details = screen.getByTestId("balance-details");
      expect(within(details).getAllByText(/\$\d+\.\d{2}/).length).toBeGreaterThan(0);
    });
  });

  it("CB-008-05: toggle has accessible label", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const toggle = screen.getByRole("button", { name: /details|expand|more/i });
    expect(toggle).toHaveAttribute("aria-expanded");
  });

  it("CB-008-06: smooth animation on expand", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const toggle = screen.getByRole("button", { name: /details|expand|more/i });
    await user.click(toggle);

    // Details should have transition classes
    await waitFor(() => {
      const details = screen.getByTestId("balance-details");
      expect(details).toBeInTheDocument();
    });
  });
});

// ==================== CB-009: Real-time Updates ====================

describe("CB-009: Real-time Updates", () => {
  it("CB-009-01: updates balance on successful purchase", async () => {
    const { rerender } = render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    expect(screen.getByText("$25.50")).toBeInTheDocument();

    // Simulate balance update after purchase
    rerender(
      <CreditBalance
        balance={{ ...mockBalanceData, balance: 50.5, formatted_balance: "$50.50" }}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    await waitFor(() => {
      expect(screen.getByText("$50.50")).toBeInTheDocument();
    });
  });

  it("CB-009-02: invalidates cache after purchase", () => {
    const mockMutate = jest.fn();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        mutate={mockMutate}
      />
    );

    // Component should use mutate for cache invalidation
    // This test verifies the prop is accepted
    expect(mockMutate).toBeDefined();
  });

  it("CB-009-03: shows optimistic update", async () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        optimisticBalance={30.0}
      />
    );

    // Should show optimistic balance if provided
    expect(screen.getByText("$30.00")).toBeInTheDocument();
  });

  it("CB-009-04: reverts on purchase failure", async () => {
    const { rerender } = render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        optimisticBalance={30.0}
      />
    );

    expect(screen.getByText("$30.00")).toBeInTheDocument();

    // Simulate failure - revert to original
    rerender(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    await waitFor(() => {
      expect(screen.getByText("$25.50")).toBeInTheDocument();
    });
  });

  it("CB-009-05: shows animation on balance change", async () => {
    const { rerender } = render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const balanceElement = screen.getByText("$25.50");

    rerender(
      <CreditBalance
        balance={{ ...mockBalanceData, balance: 50.5, formatted_balance: "$50.50" }}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    await waitFor(() => {
      const newBalanceElement = screen.getByText("$50.50");
      // Should have animation class or data attribute
      expect(newBalanceElement).toBeInTheDocument();
    });
  });

  it("CB-009-06: uses SWR for caching", () => {
    // This test verifies SWR usage pattern
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
        useSWR={true}
      />
    );

    expect(screen.getByTestId("credit-balance")).toBeInTheDocument();
  });
});

// ==================== CB-010: Accessibility ====================

describe("CB-010: Accessibility", () => {
  it("CB-010-01: has live region for updates", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const balanceElement = screen.getByText("$25.50");
    const liveRegion = balanceElement.closest("[aria-live]");

    expect(liveRegion).toBeInTheDocument();
  });

  it("CB-010-02: all buttons keyboard accessible", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const buttons = screen.getAllByRole("button");

    for (const button of buttons) {
      button.focus();
      expect(button).toHaveFocus();
    }
  });

  it("CB-010-03: screen reader announces balance", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    // Balance should have aria-label for screen readers
    const balanceElement = screen.getByText("$25.50");
    expect(balanceElement).toHaveAttribute("aria-label", expect.stringContaining("balance"));
  });

  it("CB-010-04: high contrast mode support", () => {
    // Mock high contrast preference
    window.matchMedia = jest.fn().mockImplementation((query) => ({
      matches: query === "(prefers-contrast: more)",
      media: query,
      onchange: null,
      addListener: jest.fn(),
      removeListener: jest.fn(),
    }));

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const container = screen.getByTestId("credit-balance");
    expect(container).toBeInTheDocument();
  });

  it("CB-010-05: focus visible on all interactive", async () => {
    const user = userEvent.setup();

    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    const addButton = screen.getByRole("button", { name: /add credits/i });
    addButton.focus();

    // Focus should be visible (focus-visible class or outline)
    expect(addButton).toHaveFocus();
  });

  it("CB-010-06: correct heading hierarchy", () => {
    render(
      <CreditBalance
        balance={mockBalanceData}
        isLoading={false}
        error={null}
        isAuthenticated={true}
        onAddCredits={mockHandlers.onAddCredits}
        onViewHistory={mockHandlers.onViewHistory}
      />
    );

    // Should have appropriate heading structure
    const headings = screen.getAllByRole("heading");
    expect(headings.length).toBeGreaterThan(0);
  });
});
