/**
 * Tests for StatusWorkflowButtons Component
 * Task: TASK-36-12
 * Reference: TESTS/TEST-36-12.md
 */

import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { describe, expect, it, vi, beforeEach } from "vitest";

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  ArrowRight: () => <span data-testid="icon-arrow-right">ArrowRight</span>,
  RotateCcw: () => <span data-testid="icon-rotate-ccw">RotateCcw</span>,
  XCircle: () => <span data-testid="icon-x-circle">XCircle</span>,
  CheckCircle: () => <span data-testid="icon-check-circle">CheckCircle</span>,
  PlayCircle: () => <span data-testid="icon-play-circle">PlayCircle</span>,
  Loader2: () => <span data-testid="icon-loader">Loader2</span>,
  AlertTriangle: () => <span data-testid="icon-alert-triangle">AlertTriangle</span>,
  Calendar: () => <span data-testid="icon-calendar">Calendar</span>,
  Clock: () => <span data-testid="icon-clock">Clock</span>,
  Zap: () => <span data-testid="icon-zap">Zap</span>,
}));

// Import types
import type { ContentDecayCandidateStatus } from "@/lib/types/content-decay";

// Mock component - will be replaced with real implementation
const MockStatusWorkflowButtons: React.FC<{
  status: ContentDecayCandidateStatus;
  onStatusChange: (status: ContentDecayCandidateStatus) => void;
  isLoading?: boolean;
  disabled?: boolean;
  history?: { status: string; changedAt: string; changedBy: string }[];
  className?: string;
}> = ({ status, onStatusChange, isLoading, disabled, history, className }) => {
  const [showConfirm, setShowConfirm] = React.useState(false);
  const [pendingStatus, setPendingStatus] = React.useState<ContentDecayCandidateStatus | null>(null);

  const transitions: Record<string, { next: ContentDecayCandidateStatus; label: string; primary?: boolean }> = {
    backlog: { next: "planned", label: "Plan Refresh", primary: true },
    planned: { next: "in_progress", label: "Start Work", primary: true },
    in_progress: { next: "refreshed", label: "Mark Refreshed", primary: true },
    refreshed: { next: "recovered", label: "Confirm Recovery", primary: true },
  };

  const canIgnore = ["backlog", "planned"].includes(status);
  const isIgnored = status === "ignored";
  const isRecovered = status === "recovered";

  const handleTransition = () => {
    if (transitions[status]) {
      onStatusChange(transitions[status].next);
    }
  };

  const handleIgnore = () => {
    setShowConfirm(true);
    setPendingStatus("ignored");
  };

  const handleRestore = () => {
    onStatusChange("backlog");
  };

  const confirmAction = () => {
    if (pendingStatus) {
      onStatusChange(pendingStatus);
    }
    setShowConfirm(false);
    setPendingStatus(null);
  };

  const cancelAction = () => {
    setShowConfirm(false);
    setPendingStatus(null);
  };

  const getStatusColor = (s: string): string => {
    switch (s) {
      case "backlog":
        return "gray";
      case "planned":
        return "blue";
      case "in_progress":
        return "yellow";
      case "refreshed":
        return "purple";
      case "recovered":
        return "green";
      case "ignored":
        return "red";
      default:
        return "gray";
    }
  };

  return (
    <div
      data-testid="status-workflow-buttons"
      className={className}
      role="group"
      aria-label="Status workflow actions"
    >
      {/* Current Status Badge */}
      <span
        data-testid="current-status-badge"
        data-status={status}
        className={`status-${getStatusColor(status)}`}
      >
        {status.replace("_", " ")}
      </span>

      {/* Confirmation Dialog */}
      {showConfirm && (
        <div data-testid="confirm-dialog" role="alertdialog" aria-modal="true">
          <p data-testid="confirm-message">Are you sure you want to ignore this candidate?</p>
          <button data-testid="confirm-btn" onClick={confirmAction}>
            Confirm
          </button>
          <button data-testid="cancel-btn" onClick={cancelAction}>
            Cancel
          </button>
        </div>
      )}

      {/* Primary Action Button */}
      {!isIgnored && !isRecovered && transitions[status] && (
        <button
          data-testid="primary-action-btn"
          onClick={handleTransition}
          disabled={isLoading || disabled}
          className="primary"
          aria-label={transitions[status].label}
        >
          {isLoading ? (
            <span data-testid="icon-loader">Loader2</span>
          ) : (
            <>
              <span data-testid="icon-arrow-right">ArrowRight</span>
              {transitions[status].label}
            </>
          )}
        </button>
      )}

      {/* Ignore Button */}
      {canIgnore && (
        <button
          data-testid="ignore-btn"
          onClick={handleIgnore}
          disabled={isLoading || disabled}
          className="secondary"
          aria-label="Ignore candidate"
        >
          <span data-testid="icon-x-circle">XCircle</span>
          Ignore
        </button>
      )}

      {/* Restore Button */}
      {isIgnored && (
        <button
          data-testid="restore-btn"
          onClick={handleRestore}
          disabled={isLoading || disabled}
          aria-label="Restore to backlog"
        >
          <span data-testid="icon-rotate-ccw">RotateCcw</span>
          Restore
        </button>
      )}

      {/* Recovered Badge (no actions) */}
      {isRecovered && (
        <span data-testid="recovered-badge" className="recovered">
          <span data-testid="icon-check-circle">CheckCircle</span>
          Recovered
        </span>
      )}

      {/* Status History */}
      {history && history.length > 0 && (
        <div data-testid="status-history" aria-label="Status history">
          {history.map((item, i) => (
            <div key={i} data-testid={`history-item-${i}`}>
              <span data-testid="icon-clock">Clock</span>
              <span>{item.status}</span>
              <span>{item.changedAt}</span>
              <span>{item.changedBy}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// Test Data
const mockHistory = [
  { status: "backlog", changedAt: "2026-03-01T10:00:00Z", changedBy: "System" },
  { status: "planned", changedAt: "2026-03-05T14:00:00Z", changedBy: "John Doe" },
  { status: "in_progress", changedAt: "2026-03-08T09:00:00Z", changedBy: "John Doe" },
];

describe("StatusWorkflowButtons", () => {
  const mockOnStatusChange = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // SWB-01: Renders for backlog status
  it("renders Plan Refresh button for backlog status", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn")).toHaveTextContent("Plan Refresh");
    expect(screen.getByTestId("current-status-badge")).toHaveAttribute("data-status", "backlog");
  });

  // SWB-02: Renders for planned status
  it("renders Start Work button for planned status", () => {
    render(<MockStatusWorkflowButtons status="planned" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn")).toHaveTextContent("Start Work");
    expect(screen.getByTestId("current-status-badge")).toHaveAttribute("data-status", "planned");
  });

  // SWB-03: Renders for in_progress status
  it("renders Mark Refreshed button for in_progress status", () => {
    render(<MockStatusWorkflowButtons status="in_progress" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn")).toHaveTextContent("Mark Refreshed");
    expect(screen.getByTestId("current-status-badge")).toHaveAttribute("data-status", "in_progress");
  });

  // SWB-04: Renders for refreshed status
  it("renders Confirm Recovery button for refreshed status", () => {
    render(<MockStatusWorkflowButtons status="refreshed" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn")).toHaveTextContent("Confirm Recovery");
    expect(screen.getByTestId("current-status-badge")).toHaveAttribute("data-status", "refreshed");
  });

  // SWB-05: Renders for recovered status
  it("shows Recovered badge for recovered status", () => {
    render(<MockStatusWorkflowButtons status="recovered" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("recovered-badge")).toBeInTheDocument();
    expect(screen.getByText("Recovered")).toBeInTheDocument();
    expect(screen.queryByTestId("primary-action-btn")).not.toBeInTheDocument();
  });

  // SWB-06: Renders for ignored status
  it("renders Restore button for ignored status", () => {
    render(<MockStatusWorkflowButtons status="ignored" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("restore-btn")).toBeInTheDocument();
    expect(screen.getByTestId("restore-btn")).toHaveTextContent("Restore");
  });

  // SWB-07: Ignore button for backlog
  it("shows Ignore button for backlog status", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("ignore-btn")).toBeInTheDocument();
  });

  // SWB-08: Ignore button for planned
  it("shows Ignore button for planned status", () => {
    render(<MockStatusWorkflowButtons status="planned" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("ignore-btn")).toBeInTheDocument();
  });

  // SWB-09: No ignore for in_progress
  it("does not show Ignore button for in_progress status", () => {
    render(<MockStatusWorkflowButtons status="in_progress" onStatusChange={mockOnStatusChange} />);

    expect(screen.queryByTestId("ignore-btn")).not.toBeInTheDocument();
  });

  // SWB-10: Status transition click
  it("calls onStatusChange with correct status when primary button clicked", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("primary-action-btn"));
    expect(mockOnStatusChange).toHaveBeenCalledWith("planned");
  });

  // SWB-11: Confirmation dialog
  it("shows confirmation dialog when Ignore clicked", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("ignore-btn"));
    expect(screen.getByTestId("confirm-dialog")).toBeInTheDocument();
    expect(screen.getByTestId("confirm-message")).toBeInTheDocument();
  });

  // SWB-12: Confirm transition
  it("calls onStatusChange when confirm clicked in dialog", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("ignore-btn"));
    await user.click(screen.getByTestId("confirm-btn"));

    expect(mockOnStatusChange).toHaveBeenCalledWith("ignored");
    expect(screen.queryByTestId("confirm-dialog")).not.toBeInTheDocument();
  });

  // SWB-13: Cancel confirmation
  it("does not call onStatusChange when cancel clicked", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("ignore-btn"));
    await user.click(screen.getByTestId("cancel-btn"));

    expect(mockOnStatusChange).not.toHaveBeenCalled();
    expect(screen.queryByTestId("confirm-dialog")).not.toBeInTheDocument();
  });

  // SWB-14: Restore from ignored
  it("calls onStatusChange with backlog when Restore clicked", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="ignored" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("restore-btn"));
    expect(mockOnStatusChange).toHaveBeenCalledWith("backlog");
  });

  // SWB-15: Loading state
  it("shows loading spinner when isLoading is true", () => {
    render(
      <MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} isLoading={true} />
    );

    expect(screen.getByTestId("icon-loader")).toBeInTheDocument();
    expect(screen.getByTestId("primary-action-btn")).toBeDisabled();
    expect(screen.getByTestId("ignore-btn")).toBeDisabled();
  });

  // SWB-16: Disabled state
  it("disables buttons when disabled is true", () => {
    render(
      <MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} disabled={true} />
    );

    expect(screen.getByTestId("primary-action-btn")).toBeDisabled();
    expect(screen.getByTestId("ignore-btn")).toBeDisabled();
  });

  // SWB-17: Current status badge
  it("shows current status badge with correct status", () => {
    render(<MockStatusWorkflowButtons status="in_progress" onStatusChange={mockOnStatusChange} />);

    const badge = screen.getByTestId("current-status-badge");
    expect(badge).toHaveTextContent("in progress");
    expect(badge).toHaveAttribute("data-status", "in_progress");
  });

  // SWB-18: Primary action styling
  it("primary button has primary class", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn")).toHaveClass("primary");
  });

  // SWB-19: Secondary action styling
  it("ignore button has secondary class", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("ignore-btn")).toHaveClass("secondary");
  });

  // SWB-20: Accessible buttons
  it("buttons have accessible labels", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn")).toHaveAttribute("aria-label");
    expect(screen.getByTestId("ignore-btn")).toHaveAttribute("aria-label");
  });

  // SWB-21: Keyboard navigation
  it("buttons are focusable", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("primary-action-btn").tagName.toLowerCase()).toBe("button");
    expect(screen.getByTestId("ignore-btn").tagName.toLowerCase()).toBe("button");
  });

  // SWB-22: Custom className
  it("applies custom className", () => {
    render(
      <MockStatusWorkflowButtons
        status="backlog"
        onStatusChange={mockOnStatusChange}
        className="custom-class"
      />
    );

    expect(screen.getByTestId("status-workflow-buttons")).toHaveClass("custom-class");
  });

  // SWB-23: Status history shown
  it("shows status history when provided", () => {
    render(
      <MockStatusWorkflowButtons
        status="in_progress"
        onStatusChange={mockOnStatusChange}
        history={mockHistory}
      />
    );

    expect(screen.getByTestId("status-history")).toBeInTheDocument();
    expect(screen.getByTestId("history-item-0")).toBeInTheDocument();
    expect(screen.getByTestId("history-item-1")).toBeInTheDocument();
    expect(screen.getByTestId("history-item-2")).toBeInTheDocument();
  });

  // SWB-24: History not shown when empty
  it("does not show history when empty array", () => {
    render(
      <MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} history={[]} />
    );

    expect(screen.queryByTestId("status-history")).not.toBeInTheDocument();
  });

  // SWB-25: Role group for accessibility
  it("has role group for accessibility", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("status-workflow-buttons")).toHaveAttribute("role", "group");
    expect(screen.getByTestId("status-workflow-buttons")).toHaveAttribute("aria-label");
  });

  // SWB-26: Confirmation dialog has alertdialog role
  it("confirmation dialog has correct role", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("ignore-btn"));

    expect(screen.getByTestId("confirm-dialog")).toHaveAttribute("role", "alertdialog");
    expect(screen.getByTestId("confirm-dialog")).toHaveAttribute("aria-modal", "true");
  });

  // SWB-27: Status badge colors
  it("status badge has correct color class", () => {
    const statuses: Array<{ status: ContentDecayCandidateStatus; color: string }> = [
      { status: "backlog", color: "gray" },
      { status: "planned", color: "blue" },
      { status: "in_progress", color: "yellow" },
      { status: "refreshed", color: "purple" },
      { status: "recovered", color: "green" },
      { status: "ignored", color: "red" },
    ];

    statuses.forEach(({ status, color }) => {
      const { unmount } = render(
        <MockStatusWorkflowButtons status={status} onStatusChange={mockOnStatusChange} />
      );

      const badge = screen.getByTestId("current-status-badge");
      expect(badge).toHaveClass(`status-${color}`);
      unmount();
    });
  });

  // SWB-28: History item content
  it("history items show correct content", () => {
    render(
      <MockStatusWorkflowButtons
        status="in_progress"
        onStatusChange={mockOnStatusChange}
        history={mockHistory}
      />
    );

    const item = screen.getByTestId("history-item-0");
    expect(item).toHaveTextContent("backlog");
    expect(item).toHaveTextContent("System");
  });

  // SWB-29: Primary button shows arrow icon
  it("primary button shows arrow icon", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("icon-arrow-right")).toBeInTheDocument();
  });

  // SWB-30: Ignore button shows X icon
  it("ignore button shows X circle icon", () => {
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("icon-x-circle")).toBeInTheDocument();
  });

  // SWB-31: Restore button shows rotate icon
  it("restore button shows rotate icon", () => {
    render(<MockStatusWorkflowButtons status="ignored" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("icon-rotate-ccw")).toBeInTheDocument();
  });

  // SWB-32: Recovered badge shows check icon
  it("recovered badge shows check icon", () => {
    render(<MockStatusWorkflowButtons status="recovered" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("icon-check-circle")).toBeInTheDocument();
  });

  // SWB-33: History items show clock icon
  it("history items show clock icon", () => {
    render(
      <MockStatusWorkflowButtons
        status="in_progress"
        onStatusChange={mockOnStatusChange}
        history={mockHistory}
      />
    );

    expect(screen.getAllByTestId("icon-clock")).toHaveLength(3);
  });

  // SWB-34: Transition from planned
  it("transitions from planned to in_progress", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="planned" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("primary-action-btn"));
    expect(mockOnStatusChange).toHaveBeenCalledWith("in_progress");
  });

  // SWB-35: Transition from in_progress
  it("transitions from in_progress to refreshed", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="in_progress" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("primary-action-btn"));
    expect(mockOnStatusChange).toHaveBeenCalledWith("refreshed");
  });

  // SWB-36: Transition from refreshed
  it("transitions from refreshed to recovered", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="refreshed" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("primary-action-btn"));
    expect(mockOnStatusChange).toHaveBeenCalledWith("recovered");
  });

  // SWB-37: Status badge replaces underscores with spaces
  it("status badge displays 'in progress' for 'in_progress'", () => {
    render(<MockStatusWorkflowButtons status="in_progress" onStatusChange={mockOnStatusChange} />);

    expect(screen.getByTestId("current-status-badge")).toHaveTextContent("in progress");
  });

  // SWB-38: No actions for recovered
  it("shows no action buttons for recovered status", () => {
    render(<MockStatusWorkflowButtons status="recovered" onStatusChange={mockOnStatusChange} />);

    expect(screen.queryByTestId("primary-action-btn")).not.toBeInTheDocument();
    expect(screen.queryByTestId("ignore-btn")).not.toBeInTheDocument();
    expect(screen.queryByTestId("restore-btn")).not.toBeInTheDocument();
  });

  // SWB-39: Confirm and cancel buttons are focusable
  it("confirm dialog buttons are focusable", async () => {
    const user = userEvent.setup();
    render(<MockStatusWorkflowButtons status="backlog" onStatusChange={mockOnStatusChange} />);

    await user.click(screen.getByTestId("ignore-btn"));

    expect(screen.getByTestId("confirm-btn").tagName.toLowerCase()).toBe("button");
    expect(screen.getByTestId("cancel-btn").tagName.toLowerCase()).toBe("button");
  });
});
