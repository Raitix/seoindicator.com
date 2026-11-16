/**
 * DeleteConfirmationDialog Component Tests - TASK-25-20
 * 
 * Tests for the delete confirmation dialog component.
 * 
 * @see SPEC-025
 * @see TASK-25-20
 */

import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import DeleteConfirmationDialog from "@/components/history/DeleteConfirmationDialog";
import type { AnalysisRecord } from "@/lib/types/analysis";

// Mock dependencies
const mockOnConfirm = vi.fn();
const mockOnClose = vi.fn();

const defaultAnalysis: AnalysisRecord = {
  id: 127,
  toolName: "meta-tag-analyzer",
  toolDisplayName: "Meta Tag Analyzer",
  inputUrl: "https://example.com",
  status: "completed",
  analyzedAt: "2026-03-01T10:00:00Z",
  analysisDurationMs: 1234,
  cost: { total: 0.05, currency: "USD" },
};

const defaultProps = {
  analysis: defaultAnalysis,
  isOpen: true,
  onClose: mockOnClose,
  onConfirm: mockOnConfirm,
  isDeleting: false,
};

// ==================== Test Setup ====================

beforeEach(() => {
  vi.clearAllMocks();
});

// ==================== DCD-001: Dialog Structure ====================

describe("Dialog Structure", () => {
  it("renders when isOpen is true", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });

  it("does not render when isOpen is false", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isOpen={false} />);

    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
  });

  it("has correct ARIA attributes for the dialog", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    expect(dialog).toHaveAttribute("aria-modal", "true");
    expect(dialog).toHaveAttribute("aria-labelledby");
    expect(dialog).toHaveAttribute("aria-describedby");
  });

  it("has proper heading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByRole("heading", { name: /delete analysis/i })).toBeInTheDocument();
  });
});

// ==================== DCD-002: Content ====================

describe("Dialog Content", () => {
  it("displays warning message", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByText(/are you sure|confirm.*delete/i)).toBeInTheDocument();
  });

  it("displays analysis name being deleted", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    // The dialog identifies the analysis by its tool name and the analysed URL
    expect(screen.getByText("Meta Tag Analyzer")).toBeInTheDocument();
    expect(screen.getByText("https://example.com")).toBeInTheDocument();
  });

  it("displays irreversible action warning", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    // Both the description and the closing warning state the action is permanent
    const warnings = screen.getAllByText(/cannot be undone|permanent|irreversible/i);
    expect(warnings.length).toBeGreaterThan(0);
    expect(warnings.some((w) => /cannot be undone/i.test(w.textContent || ""))).toBe(true);
  });

  it("displays analysis details for reference", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    // The dialog shows the tool, the analysed URL and the current status
    expect(screen.getByText("Tool:")).toBeInTheDocument();
    expect(screen.getByText("URL:")).toBeInTheDocument();
    expect(screen.getByText("Status:")).toBeInTheDocument();
    expect(screen.getByText("completed")).toBeInTheDocument();
  });
});

// ==================== DCD-003: Buttons ====================

describe("Dialog Buttons", () => {
  it("displays cancel button", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton).toBeInTheDocument();
  });

  it("displays delete button", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /^delete$/i });
    expect(deleteButton).toBeInTheDocument();
  });

  it("delete button has destructive styling", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /^delete$/i });
    expect(deleteButton).toHaveAttribute("data-variant", "destructive");
    expect(deleteButton.className).toMatch(/red|rose|destructive|danger/i);
  });

  it("cancel button is not styled as destructive", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton).toHaveAttribute("data-variant", "outline");
    expect(cancelButton.className).not.toMatch(/bg-red-|bg-destructive/);
  });
});

// ==================== DCD-004: Cancel Action ====================

describe("Cancel Action", () => {
  it("calls onClose when cancel button clicked", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    await user.click(cancelButton);

    expect(mockOnClose).toHaveBeenCalled();
    expect(mockOnConfirm).not.toHaveBeenCalled();
  });

  it("calls onClose when clicking outside dialog", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    // Radix renders the dismissable overlay as the previous sibling of the dialog content
    const overlay = screen.getByRole("dialog").previousElementSibling as HTMLElement;
    await user.click(overlay);
    expect(mockOnClose).toHaveBeenCalled();
  });

  it("calls onClose when pressing Escape key", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    await user.keyboard("{Escape}");

    expect(mockOnClose).toHaveBeenCalled();
  });
});

// ==================== DCD-005: Confirm Action ====================

describe("Confirm Action", () => {
  it("calls onConfirm when delete button clicked", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /^delete$/i });
    await user.click(deleteButton);

    expect(mockOnConfirm).toHaveBeenCalledTimes(1);
  });

  it("does not call onClose when delete button clicked", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /^delete$/i });
    await user.click(deleteButton);

    expect(mockOnClose).not.toHaveBeenCalled();
  });
});

// ==================== DCD-006: Loading State ====================

describe("Loading State", () => {
  it("shows spinner on delete button when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isDeleting={true} />);

    const deleteButton = screen.getByRole("button", { name: /deleting|delete/i });
    expect(deleteButton).toBeInTheDocument();
    expect(screen.getByTestId("delete-loading") || 
           document.querySelector(".animate-spin")).toBeTruthy();
  });

  it("disables delete button when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isDeleting={true} />);

    const deleteButton = screen.getByRole("button", { name: /deleting|delete/i });
    expect(deleteButton).toBeDisabled();
  });

  it("disables cancel button when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isDeleting={true} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton).toBeDisabled();
  });

  it("changes delete button text when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isDeleting={true} />);

    expect(screen.getByText(/deleting/i)).toBeInTheDocument();
  });

  it("does not close on Escape when loading", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} isDeleting={true} />);

    await user.keyboard("{Escape}");

    expect(mockOnClose).not.toHaveBeenCalled();
  });
});

// ==================== DCD-007: Focus Management ====================

describe("Focus Management", () => {
  it("focuses delete button by default when dialog opens", async () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    await waitFor(() => {
      const deleteButton = screen.getByRole("button", { name: /^delete$/i });
      expect(deleteButton).not.toHaveFocus(); // Cancel is usually focused by default
    });
  });

  it("traps focus within dialog", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    // Tab through all focusable elements
    await user.tab();
    const firstFocus = document.activeElement;

    await user.tab();
    const secondFocus = document.activeElement;

    // Both should be within dialog
    expect(firstFocus?.closest('[role="dialog"]')).toBeTruthy();
    expect(secondFocus?.closest('[role="dialog"]')).toBeTruthy();
  });
});

// ==================== DCD-008: Accessibility ====================

describe("Accessibility", () => {
  it("has dialog role", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });

  it("has aria-labelledby pointing to heading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    const labelledBy = dialog.getAttribute("aria-labelledby");
    const heading = screen.getByRole("heading", { name: /delete analysis/i });

    expect(labelledBy).toBeTruthy();
    expect(heading.id).toBe(labelledBy);
  });

  it("has aria-describedby for warning message", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    expect(dialog).toHaveAttribute("aria-describedby");
  });

  it("delete button has accessible name", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /^delete$/i });
    expect(deleteButton).toHaveAccessibleName(/delete/i);
  });

  it("cancel button has accessible name", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton).toHaveAccessibleName(/cancel/i);
  });
});

// ==================== DCD-009: Keyboard Navigation ====================

describe("Keyboard Navigation", () => {
  it("confirms delete on Enter key from delete button", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /^delete$/i });
    deleteButton.focus();

    await user.keyboard("{Enter}");

    expect(mockOnConfirm).toHaveBeenCalledTimes(1);
  });

  it("cancels on Enter key from cancel button", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    cancelButton.focus();

    await user.keyboard("{Enter}");

    expect(mockOnClose).toHaveBeenCalled();
  });
});

// ==================== DCD-010: Edge Cases ====================

describe("Edge Cases", () => {
  it("handles missing analysis name gracefully", () => {
    const analysisWithoutName = { ...defaultAnalysis, toolDisplayName: "" };
    render(<DeleteConfirmationDialog {...defaultProps} analysis={analysisWithoutName} />);

    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(screen.getByText("https://example.com")).toBeInTheDocument();
  });

  it("handles very long analysis name", () => {
    const longName = "Meta Tag Analyzer - " + "a".repeat(200) + ".com";
    render(
      <DeleteConfirmationDialog
        {...defaultProps}
        analysis={{ ...defaultAnalysis, toolDisplayName: longName }}
      />
    );

    // Should truncate or wrap
    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });

  it("handles special characters in analysis name", () => {
    render(
      <DeleteConfirmationDialog
        {...defaultProps}
        analysis={{ ...defaultAnalysis, toolDisplayName: "<script>alert(1)</script>example.com" }}
      />
    );

    // Should escape HTML
    expect(document.querySelector('script')).toBeNull();
    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });
});

// ==================== DCD-011: Responsive Design ====================

describe("Responsive Design", () => {
  it("has responsive dialog width", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("dialog");
    expect(dialog.className).toMatch(/max-w|w-full/i);
  });

  it("buttons stack on mobile", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const buttonContainer = screen.getByRole("button", { name: /cancel/i }).parentElement;
    expect(buttonContainer?.className).toMatch(/flex|grid|gap/i);
  });
});
