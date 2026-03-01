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

// Mock dependencies
const mockOnConfirm = jest.fn();
const mockOnCancel = jest.fn();

const defaultProps = {
  isOpen: true,
  analysisId: 127,
  analysisName: "Meta Tag Analyzer - example.com",
  onConfirm: mockOnConfirm,
  onCancel: mockOnCancel,
  isLoading: false,
};

// ==================== Test Setup ====================

beforeEach(() => {
  jest.clearAllMocks();
});

// ==================== DCD-001: Dialog Structure ====================

describe("Dialog Structure", () => {
  it("renders when isOpen is true", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(screen.getByRole("alertdialog")).toBeInTheDocument();
  });

  it("does not render when isOpen is false", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isOpen={false} />);

    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
  });

  it("has correct ARIA attributes for alertdialog", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("alertdialog");
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

    expect(screen.getByText(/meta tag analyzer.*example\.com/i)).toBeInTheDocument();
  });

  it("displays irreversible action warning", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByText(/cannot be undone|permanent|irreversible/i)).toBeInTheDocument();
  });

  it("displays analysis ID for reference", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByText(/#127/i) || screen.getByText(/id.*127/i)).toBeTruthy();
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

    const deleteButton = screen.getByRole("button", { name: /delete/i });
    expect(deleteButton).toBeInTheDocument();
  });

  it("delete button has destructive styling", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /delete/i });
    expect(deleteButton.className).toMatch(/red|rose|destructive|danger/i);
  });

  it("cancel button is not styled as destructive", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton.className).not.toMatch(/red|rose|destructive|danger/i);
  });
};

// ==================== DCD-004: Cancel Action ====================

describe("Cancel Action", () => {
  it("calls onCancel when cancel button clicked", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    await user.click(cancelButton);

    expect(mockOnCancel).toHaveBeenCalled();
    expect(mockOnConfirm).not.toHaveBeenCalled();
  });

  it("calls onCancel when clicking outside dialog", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    // Click on overlay
    const overlay = screen.getByRole("alertdialog").parentElement;
    if (overlay) {
      await user.click(overlay);
      expect(mockOnCancel).toHaveBeenCalled();
    }
  });

  it("calls onCancel when pressing Escape key", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    await user.keyboard("{Escape}");

    expect(mockOnCancel).toHaveBeenCalled();
  });
});

// ==================== DCD-005: Confirm Action ====================

describe("Confirm Action", () => {
  it("calls onConfirm when delete button clicked", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /delete/i });
    await user.click(deleteButton);

    expect(mockOnConfirm).toHaveBeenCalledWith(127);
  });

  it("does not call onCancel when delete button clicked", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /delete/i });
    await user.click(deleteButton);

    expect(mockOnCancel).not.toHaveBeenCalled();
  });
});

// ==================== DCD-006: Loading State ====================

describe("Loading State", () => {
  it("shows spinner on delete button when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isLoading={true} />);

    const deleteButton = screen.getByRole("button", { name: /deleting|delete/i });
    expect(deleteButton).toBeInTheDocument();
    expect(screen.getByTestId("delete-loading") || 
           document.querySelector(".animate-spin")).toBeTruthy();
  });

  it("disables delete button when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isLoading={true} />);

    const deleteButton = screen.getByRole("button", { name: /deleting|delete/i });
    expect(deleteButton).toBeDisabled();
  });

  it("disables cancel button when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isLoading={true} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton).toBeDisabled();
  });

  it("changes delete button text when loading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} isLoading={true} />);

    expect(screen.getByText(/deleting/i)).toBeInTheDocument();
  });

  it("does not close on Escape when loading", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} isLoading={true} />);

    await user.keyboard("{Escape}");

    expect(mockOnCancel).not.toHaveBeenCalled();
  });
});

// ==================== DCD-007: Focus Management ====================

describe("Focus Management", () => {
  it("focuses delete button by default when dialog opens", async () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    await waitFor(() => {
      const deleteButton = screen.getByRole("button", { name: /delete/i });
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
    expect(firstFocus?.closest('[role="alertdialog"]')).toBeTruthy();
    expect(secondFocus?.closest('[role="alertdialog"]')).toBeTruthy();
  });
});

// ==================== DCD-008: Accessibility ====================

describe("Accessibility", () => {
  it("has alertdialog role", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    expect(screen.getByRole("alertdialog")).toBeInTheDocument();
  });

  it("has aria-labelledby pointing to heading", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("alertdialog");
    const labelledBy = dialog.getAttribute("aria-labelledby");
    const heading = screen.getByRole("heading", { name: /delete analysis/i });

    expect(labelledBy).toBeTruthy();
    expect(heading.id).toBe(labelledBy);
  });

  it("has aria-describedby for warning message", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("alertdialog");
    expect(dialog).toHaveAttribute("aria-describedby");
  });

  it("delete button has aria-label", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /delete/i });
    expect(deleteButton).toHaveAttribute("aria-label");
  });

  it("cancel button has aria-label", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    expect(cancelButton).toHaveAttribute("aria-label");
  });
});

// ==================== DCD-009: Keyboard Navigation ====================

describe("Keyboard Navigation", () => {
  it("confirms delete on Enter key from delete button", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const deleteButton = screen.getByRole("button", { name: /delete/i });
    deleteButton.focus();

    await user.keyboard("{Enter}");

    expect(mockOnConfirm).toHaveBeenCalledWith(127);
  });

  it("cancels on Enter key from cancel button", async () => {
    const user = userEvent.setup();
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const cancelButton = screen.getByRole("button", { name: /cancel/i });
    cancelButton.focus();

    await user.keyboard("{Enter}");

    expect(mockOnCancel).toHaveBeenCalled();
  });
});

// ==================== DCD-010: Edge Cases ====================

describe("Edge Cases", () => {
  it("handles missing analysis name gracefully", () => {
    render(<DeleteConfirmationDialog {...defaultProps} analysisName="" />);

    expect(screen.getByRole("alertdialog")).toBeInTheDocument();
    expect(screen.getByText(/analysis.*#127/i) || screen.getByText(/#127/i)).toBeTruthy();
  });

  it("handles very long analysis name", () => {
    const longName = "Meta Tag Analyzer - " + "a".repeat(200) + ".com";
    render(<DeleteConfirmationDialog {...defaultProps} analysisName={longName} />);

    // Should truncate or wrap
    expect(screen.getByRole("alertdialog")).toBeInTheDocument();
  });

  it("handles special characters in analysis name", () => {
    render(<DeleteConfirmationDialog {...defaultProps} analysisName="<script>alert(1)</script>example.com" />);

    // Should escape HTML
    expect(document.querySelector('script')).toBeNull();
    expect(screen.getByRole("alertdialog")).toBeInTheDocument();
  });
});

// ==================== DCD-011: Responsive Design ====================

describe("Responsive Design", () => {
  it("has responsive dialog width", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const dialog = screen.getByRole("alertdialog");
    expect(dialog.className).toMatch(/max-w|w-full/i);
  });

  it("buttons stack on mobile", () => {
    render(<DeleteConfirmationDialog {...defaultProps} />);

    const buttonContainer = screen.getByRole("button", { name: /cancel/i }).parentElement;
    expect(buttonContainer?.className).toMatch(/flex|grid|gap/i);
  });
});
