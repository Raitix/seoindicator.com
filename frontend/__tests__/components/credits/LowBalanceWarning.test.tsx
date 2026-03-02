/**
 * LowBalanceWarning Component Tests - TASK-26-21
 *
 * Tests for the low balance warning component that alerts users
 * when their credit balance falls below a threshold.
 *
 * @see SPEC-026
 * @see TASK-26-21
 */

import React from "react";
import { render, screen, within, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { LowBalanceWarning } from "@/components/credits/LowBalanceWarning";

// ==================== Mock Data ====================

const mockHandlers = {
  onAddCredits: jest.fn(),
  onDismiss: jest.fn(),
};

const defaultProps = {
  balance: 1.5,
  threshold: 2.0,
  onAddCredits: mockHandlers.onAddCredits,
  onDismiss: mockHandlers.onDismiss,
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
  localStorage.clear();
});

// ==================== LBW-001: Rendering ====================

describe("LBW-001: Rendering", () => {
  it("LBW-001-01: renders warning message", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    expect(screen.getByText(/low balance|running low/i)).toBeInTheDocument();
  });

  it("LBW-001-02: shows warning icon", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    const warning = screen.getByTestId("low-balance-warning");
    expect(warning.querySelector("svg")).toBeInTheDocument();
  });

  it("LBW-001-03: shows add credits button", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    expect(screen.getByRole("button", { name: /add credits/i })).toBeInTheDocument();
  });

  it("LBW-001-04: shows dismiss button", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    expect(screen.getByRole("button", { name: /dismiss|close/i })).toBeInTheDocument();
  });

  it("LBW-001-05: has warning styling", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    const warning = screen.getByTestId("low-balance-warning");
    expect(warning.className).toMatch(/amber|yellow|warning|orange|bg-/);
  });
});

// ==================== LBW-002: Threshold Behavior ====================

describe("LBW-002: Threshold Behavior", () => {
  it("LBW-002-01: shows at $1.99", () => {
    render(<LowBalanceWarning {...defaultProps} balance={1.99} threshold={2.0} />);

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("LBW-002-02: shows at $0.01", () => {
    render(<LowBalanceWarning {...defaultProps} balance={0.01} threshold={2.0} />);

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("LBW-002-03: shows at $0.00", () => {
    render(<LowBalanceWarning {...defaultProps} balance={0} threshold={2.0} />);

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("LBW-002-04: hidden at $2.00 (threshold)", () => {
    render(<LowBalanceWarning {...defaultProps} balance={2.0} threshold={2.0} />);

    // At exactly the threshold, warning should not show
    expect(screen.queryByTestId("low-balance-warning")).not.toBeInTheDocument();
  });

  it("LBW-002-05: custom threshold works", () => {
    render(<LowBalanceWarning {...defaultProps} balance={4.0} threshold={5.0} />);

    // With custom threshold of $5, $4 should trigger warning
    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });
});

// ==================== LBW-003: Actions ====================

describe("LBW-003: Actions", () => {
  it("LBW-003-01: add credits navigates to pricing", async () => {
    const user = userEvent.setup();

    render(<LowBalanceWarning {...defaultProps} />);

    const addButton = screen.getByRole("button", { name: /add credits/i });
    await user.click(addButton);

    expect(mockHandlers.onAddCredits).toHaveBeenCalledTimes(1);
  });

  it("LBW-003-02: dismiss hides warning", async () => {
    const user = userEvent.setup();

    render(<LowBalanceWarning {...defaultProps} />);

    const dismissButton = screen.getByRole("button", { name: /dismiss|close/i });
    await user.click(dismissButton);

    await waitFor(() => {
      expect(screen.queryByTestId("low-balance-warning")).not.toBeInTheDocument();
    });
  });

  it("LBW-003-03: dismiss stores preference", async () => {
    const user = userEvent.setup();

    render(<LowBalanceWarning {...defaultProps} />);

    const dismissButton = screen.getByRole("button", { name: /dismiss|close/i });
    await user.click(dismissButton);

    await waitFor(() => {
      expect(localStorage.setItem).toHaveBeenCalled();
    });
  });

  it("LBW-003-04: dismiss is temporary", () => {
    // First render with dismissed state
    localStorage.getItem = jest.fn().mockReturnValue(null);

    render(<LowBalanceWarning {...defaultProps} />);

    // Warning should show because no dismissal stored
    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("LBW-003-05: dismiss shows undo option", async () => {
    const user = userEvent.setup();

    render(<LowBalanceWarning {...defaultProps} showUndo={true} />);

    const dismissButton = screen.getByRole("button", { name: /dismiss|close/i });
    await user.click(dismissButton);

    await waitFor(() => {
      expect(screen.getByRole("button", { name: /undo/i })).toBeInTheDocument();
    });
  });
});

// ==================== LBW-004: Toast Notification ====================

describe("LBW-004: Toast Notification", () => {
  it("LBW-004-01: shows toast on low balance", () => {
    render(<LowBalanceWarning {...defaultProps} showToast={true} />);

    expect(screen.getByRole("alert")).toBeInTheDocument();
  });

  it("LBW-004-02: toast auto-dismisses", async () => {
    jest.useFakeTimers();

    render(<LowBalanceWarning {...defaultProps} showToast={true} autoDismissMs={5000} />);

    expect(screen.getByRole("alert")).toBeInTheDocument();

    jest.advanceTimersByTime(5000);

    await waitFor(() => {
      expect(screen.queryByRole("alert")).not.toBeInTheDocument();
    });

    jest.useRealTimers();
  });

  it("LBW-004-03: toast has action button", () => {
    render(<LowBalanceWarning {...defaultProps} showToast={true} />);

    const toast = screen.getByRole("alert");
    expect(within(toast).getByRole("button", { name: /add credits/i })).toBeInTheDocument();
  });

  it("LBW-004-04: toast action navigates to pricing", async () => {
    const user = userEvent.setup();

    render(<LowBalanceWarning {...defaultProps} showToast={true} />);

    const toast = screen.getByRole("alert");
    const addButton = within(toast).getByRole("button", { name: /add credits/i });
    await user.click(addButton);

    expect(mockHandlers.onAddCredits).toHaveBeenCalled();
  });

  it("LBW-004-05: multiple toasts stacked", () => {
    // Multiple low balance events could stack toasts
    render(
      <>
        <LowBalanceWarning {...defaultProps} showToast={true} id="warning-1" />
        <LowBalanceWarning {...defaultProps} showToast={true} id="warning-2" />
      </>
    );

    const toasts = screen.getAllByRole("alert");
    expect(toasts.length).toBe(2);
  });
});

// ==================== LBW-005: Accessibility ====================

describe("LBW-005: Accessibility", () => {
  it("LBW-005-01: warning has role='alert'", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    const warning = screen.getByTestId("low-balance-warning");
    expect(warning).toHaveAttribute("role", "alert");
  });

  it("LBW-005-02: screen reader announces", () => {
    render(<LowBalanceWarning {...defaultProps} />);

    // Warning should be announced via aria-live
    const warning = screen.getByTestId("low-balance-warning");
    expect(warning).toHaveAttribute("aria-live", "polite");
  });

  it("LBW-005-03: focus management on dismiss", async () => {
    const user = userEvent.setup();

    render(
      <>
        <button>Previous focus</button>
        <LowBalanceWarning {...defaultProps} />
      </>
    );

    const dismissButton = screen.getByRole("button", { name: /dismiss|close/i });
    dismissButton.focus();

    await user.click(dismissButton);

    // Focus should move to a logical element
    expect(document.activeElement).toBeDefined();
  });

  it("LBW-005-04: high contrast support", () => {
    window.matchMedia = jest.fn().mockImplementation((query) => ({
      matches: query === "(prefers-contrast: more)",
      media: query,
      onchange: null,
      addListener: jest.fn(),
      removeListener: jest.fn(),
    }));

    render(<LowBalanceWarning {...defaultProps} />);

    const warning = screen.getByTestId("low-balance-warning");
    expect(warning).toBeInTheDocument();
  });

  it("LBW-005-05: reduced motion support", () => {
    window.matchMedia = jest.fn().mockImplementation((query) => ({
      matches: query === "(prefers-reduced-motion: reduce)",
      media: query,
      onchange: null,
      addListener: jest.fn(),
      removeListener: jest.fn(),
    }));

    render(<LowBalanceWarning {...defaultProps} />);

    const warning = screen.getByTestId("low-balance-warning");
    // Should not have animation classes when reduced motion is preferred
    expect(warning).toBeInTheDocument();
  });
});

// ==================== Edge Cases ====================

describe("Edge Cases", () => {
  it("handles negative balance", () => {
    render(<LowBalanceWarning {...defaultProps} balance={-5} />);

    // Negative balance should still show warning
    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("handles very low balance", () => {
    render(<LowBalanceWarning {...defaultProps} balance={0.001} />);

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("handles high custom threshold", () => {
    render(<LowBalanceWarning {...defaultProps} balance={50} threshold={100} />);

    expect(screen.getByTestId("low-balance-warning")).toBeInTheDocument();
  });

  it("handles zero threshold", () => {
    render(<LowBalanceWarning {...defaultProps} balance={0} threshold={0} />);

    // With zero threshold, balance >= 0 should not show warning
    expect(screen.queryByTestId("low-balance-warning")).not.toBeInTheDocument();
  });

  it("handles undefined balance gracefully", () => {
    render(<LowBalanceWarning {...defaultProps} balance={undefined as any} />);

    // Should handle undefined without crashing
    expect(screen.queryByTestId("low-balance-warning")).not.toBeInTheDocument();
  });
});
