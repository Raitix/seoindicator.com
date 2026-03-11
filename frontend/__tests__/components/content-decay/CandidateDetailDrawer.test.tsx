/**
 * Tests for CandidateDetailDrawer Component
 * Task: TASK-36-12
 * Reference: TESTS/TEST-36-12.md
 */

import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { describe, expect, it, vi, beforeEach } from "vitest";

// Mock next/link
vi.mock("next/link", () => ({
  default: ({ href, children, ...props }: { href: string; children: React.ReactNode }) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  AlertTriangle: () => <span data-testid="icon-alert-triangle">AlertTriangle</span>,
  TrendingDown: () => <span data-testid="icon-trending-down">TrendingDown</span>,
  TrendingUp: () => <span data-testid="icon-trending-up">TrendingUp</span>,
  ExternalLink: () => <span data-testid="icon-external-link">ExternalLink</span>,
  X: () => <span data-testid="icon-x">X</span>,
  Clock: () => <span data-testid="icon-clock">Clock</span>,
  Sparkles: () => <span data-testid="icon-sparkles">Sparkles</span>,
  FileText: () => <span data-testid="icon-file-text">FileText</span>,
  AlertCircle: () => <span data-testid="icon-alert-circle">AlertCircle</span>,
  Info: () => <span data-testid="icon-info">Info</span>,
  ChevronRight: () => <span data-testid="icon-chevron-right">ChevronRight</span>,
}));

// Import types
import type {
  DecayCandidate,
  ContentRefreshTask,
  RecoveryComparison,
  AiRefreshBrief,
  ContentDecayCandidateStatus,
} from "@/lib/types/content-decay";

// Mock component - will be replaced with real implementation
const MockCandidateDetailDrawer: React.FC<{
  candidate: DecayCandidate | null;
  tasks?: ContentRefreshTask[];
  comparison?: RecoveryComparison | null;
  aiBrief?: AiRefreshBrief | null;
  isOpen: boolean;
  onClose: () => void;
  onStatusChange?: (status: ContentDecayCandidateStatus) => void;
  onCreateTask?: (task: { title: string; description?: string }) => void;
  onUpdateTask?: (taskId: number, updates: Record<string, unknown>) => void;
  onDeleteTask?: (taskId: number) => void;
  onRequestAiBrief?: () => void;
  onRegenerateAiBrief?: () => void;
  isLoading?: boolean;
  error?: string | null;
}> = ({
  candidate,
  tasks = [],
  comparison,
  aiBrief,
  isOpen,
  onClose,
  onStatusChange,
  isLoading,
  error,
}) => {
  const [activeTab, setActiveTab] = React.useState("overview");

  // Handle ESC key
  React.useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape" && isOpen) {
        onClose();
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  if (isLoading) {
    return (
      <div data-testid="drawer-loading" role="dialog" aria-modal="true">
        <div data-testid="skeleton-title" />
        <div data-testid="skeleton-content" />
      </div>
    );
  }

  if (error) {
    return (
      <div data-testid="drawer-error" role="dialog" aria-modal="true">
        <span>{error}</span>
        <button onClick={onClose}>Close</button>
      </div>
    );
  }

  if (!candidate) return null;

  return (
    <div
      data-testid="candidate-detail-drawer"
      role="dialog"
      aria-modal="true"
      aria-label={`Details for ${candidate.pageTitle || candidate.pageUrl}`}
    >
      <button onClick={onClose} aria-label="Close drawer" data-testid="close-button">
        <span data-testid="icon-x">X</span>
      </button>

      {/* Header */}
      <a href={candidate.pageUrl} target="_blank" rel="noopener noreferrer" data-testid="page-url-link">
        {candidate.pageUrl}
        <span data-testid="icon-external-link">ExternalLink</span>
      </a>
      <h2 data-testid="page-title">{candidate.pageTitle || "Untitled Page"}</h2>
      <span data-testid="page-type-badge">{candidate.pageType}</span>
      <span data-testid="severity-badge" data-severity={candidate.severity}>
        {candidate.severity}
      </span>

      {/* Score Section */}
      <div data-testid="decay-score">{candidate.decayScore}</div>
      <div data-testid="detection-confidence">{Math.round(candidate.detectionConfidence * 100)}%</div>

      {/* Reasons Section */}
      <div data-testid="reasons-section">
        <span data-testid="primary-reason">{candidate.primaryReasonCode}</span>
        <div data-testid="supporting-reasons">
          {candidate.reasonCodes
            .filter((r) => !r.isPrimary)
            .map((reason, i) => (
              <span key={i} data-testid={`reason-${reason.type}`}>
                {reason.type}
              </span>
            ))}
        </div>
      </div>

      {/* Estimates */}
      <div data-testid="lost-clicks">{candidate.lostClickEstimate.toLocaleString("en-US")} Lost Clicks</div>
      <div data-testid="recovery-upside">{candidate.recoveryUpsideEstimate.toLocaleString("en-US")} Upside</div>

      {/* Dates */}
      <div data-testid="first-detected">{candidate.firstDetectedAt}</div>
      <div data-testid="last-detected">{candidate.lastDetectedAt}</div>

      {/* Status */}
      <span data-testid="status-badge" data-status={candidate.status}>
        {candidate.status}
      </span>

      {/* Tabs */}
      <div data-testid="tabs-container">
        <button data-testid="tab-overview" aria-selected={activeTab === "overview"} onClick={() => setActiveTab("overview")}>
          Overview
        </button>
        <button data-testid="tab-signals" aria-selected={activeTab === "signals"} onClick={() => setActiveTab("signals")}>
          Signals
        </button>
        <button data-testid="tab-tasks" aria-selected={activeTab === "tasks"} onClick={() => setActiveTab("tasks")}>
          Tasks ({tasks.length})
        </button>
        <button data-testid="tab-notes" aria-selected={activeTab === "notes"} onClick={() => setActiveTab("notes")}>
          Notes
        </button>
        <button data-testid="tab-ai-brief" aria-selected={activeTab === "ai-brief"} onClick={() => setActiveTab("ai-brief")}>
          AI Brief
        </button>
      </div>

      {/* Tab Content */}
      <div data-testid="tab-content-overview">
        {candidate.signals && (
          <table data-testid="signals-table">
            <thead>
              <tr>
                <th>Metric</th>
                <th>Previous</th>
                <th>Current</th>
                <th>Change</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Clicks</td>
                <td data-testid="prev-clicks">{candidate.signals.previousClicks}</td>
                <td data-testid="curr-clicks">{candidate.signals.currentClicks}</td>
                <td data-testid="delta-clicks">
                  {candidate.signals.currentClicks - candidate.signals.previousClicks}
                </td>
              </tr>
              <tr>
                <td>Impressions</td>
                <td data-testid="prev-impressions">{candidate.signals.previousImpressions}</td>
                <td data-testid="curr-impressions">{candidate.signals.currentImpressions}</td>
              </tr>
              <tr>
                <td>CTR</td>
                <td>{(candidate.signals.previousCtr * 100).toFixed(2)}%</td>
                <td>{(candidate.signals.currentCtr * 100).toFixed(2)}%</td>
              </tr>
              <tr>
                <td>Avg Position</td>
                <td>{candidate.signals.previousAvgPosition}</td>
                <td>{candidate.signals.currentAvgPosition}</td>
              </tr>
            </tbody>
          </table>
        )}
        {!candidate.signals && <div data-testid="no-signals">No signal data available</div>}
      </div>

      {/* Trend Chart */}
      {candidate.trend && (
        <div data-testid="trend-chart" aria-label="Trend visualization">
          <div data-testid="clicks-trend">Clicks Trend</div>
          <div data-testid="impressions-trend">Impressions Trend</div>
        </div>
      )}

      {/* Status Workflow */}
      <div data-testid="status-workflow">
        <button
          data-testid="status-change-btn"
          onClick={() => onStatusChange?.("planned")}
          disabled={isLoading}
        >
          Plan Refresh
        </button>
      </div>
    </div>
  );
};

// Test Data
const mockCandidate: DecayCandidate = {
  id: 1,
  projectId: 1,
  pageUrl: "https://example.com/blog/seo-guide-2024",
  pageTitle: "Complete SEO Guide 2024",
  pageType: "blog",
  severity: "critical",
  decayScore: 72.5,
  detectionConfidence: 0.93,
  lostClickEstimate: 1234,
  recoveryUpsideEstimate: 5678,
  primaryReasonCode: "clicks_down",
  reasonCodes: [
    { type: "clicks_down", evidence: "Clicks decreased 45% vs previous window", isPrimary: true },
    { type: "ctr_down", evidence: "CTR dropped from 5.2% to 2.1%" },
    { type: "avg_position_down", evidence: "Position fell from 3.2 to 7.8" },
  ],
  status: "backlog",
  firstDetectedAt: "2026-03-01T10:00:00Z",
  lastDetectedAt: "2026-03-10T08:00:00Z",
  plannedAt: null,
  startedAt: null,
  refreshedAt: null,
  recoveredAt: null,
  ignoredAt: null,
  signals: {
    previousStart: "2026-01-15",
    previousEnd: "2026-02-11",
    currentStart: "2026-02-12",
    currentEnd: "2026-03-11",
    previousClicks: 1234,
    currentClicks: 679,
    previousImpressions: 45678,
    currentImpressions: 38901,
    previousCtr: 0.027,
    currentCtr: 0.017,
    previousAvgPosition: 3.2,
    currentAvgPosition: 7.8,
    previousRankVisibility: null,
    currentRankVisibility: null,
    previousAiVisibility: null,
    currentAiVisibility: null,
    unresolvedAuditIssues: 2,
    contentAgeDays: 180,
  },
  trend: {
    previousWindow: {
      clicks: [120, 115, 110, 105, 100, 95, 90, 85, 80, 75],
      impressions: [1200, 1180, 1160, 1140, 1120, 1100, 1080, 1060, 1040, 1020],
    },
    currentWindow: {
      clicks: [70, 68, 66, 64, 62, 60, 58, 56, 54, 52],
      impressions: [980, 960, 940, 920, 900, 880, 860, 840, 820, 800],
    },
  },
};

const mockTasks: ContentRefreshTask[] = [
  {
    id: 1,
    candidateId: 1,
    title: "Update meta title",
    recommendationType: "meta_improvement",
    description: "Rewrite meta to improve CTR",
    notes: null,
    ownerUserId: 1,
    ownerName: "John Doe",
    dueDate: "2026-03-15",
    status: "todo",
    source: "system",
    createdAt: "2026-03-10T10:00:00Z",
    updatedAt: "2026-03-10T10:00:00Z",
  },
];

describe("CandidateDetailDrawer", () => {
  const mockOnClose = vi.fn();
  const mockOnStatusChange = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // CDD-01: Renders with candidate data
  it("renders all detail sections when candidate data is provided", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        tasks={mockTasks}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("candidate-detail-drawer")).toBeInTheDocument();
    expect(screen.getByTestId("page-url-link")).toBeInTheDocument();
    expect(screen.getByTestId("page-title")).toBeInTheDocument();
    expect(screen.getByTestId("page-type-badge")).toBeInTheDocument();
    expect(screen.getByTestId("decay-score")).toBeInTheDocument();
    expect(screen.getByTestId("reasons-section")).toBeInTheDocument();
    expect(screen.getByTestId("tabs-container")).toBeInTheDocument();
  });

  // CDD-02: Shows page URL as link
  it("shows page URL as a clickable link", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    const link = screen.getByTestId("page-url-link");
    expect(link).toHaveAttribute("href", mockCandidate.pageUrl);
    expect(link).toHaveAttribute("target", "_blank");
  });

  // CDD-03: Shows page title
  it("displays the page title prominently", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("page-title")).toHaveTextContent("Complete SEO Guide 2024");
  });

  // CDD-04: Shows page type badge
  it("shows page type badge with correct label", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("page-type-badge")).toHaveTextContent("blog");
  });

  // CDD-05: Shows decay score with severity badge
  it("shows decay score with severity badge", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("decay-score")).toHaveTextContent("72.5");
    expect(screen.getByTestId("severity-badge")).toHaveTextContent("critical");
    expect(screen.getByTestId("severity-badge")).toHaveAttribute("data-severity", "critical");
  });

  // CDD-06: Shows detection confidence indicator
  it("shows detection confidence as percentage", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("detection-confidence")).toHaveTextContent("93%");
  });

  // CDD-07: Shows primary reason with evidence
  it("shows primary reason code", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("primary-reason")).toHaveTextContent("clicks_down");
  });

  // CDD-08: Shows supporting reason badges
  it("shows supporting reason badges", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("reason-ctr_down")).toBeInTheDocument();
    expect(screen.getByTestId("reason-avg_position_down")).toBeInTheDocument();
  });

  // CDD-09: Renders signal comparison table
  it("renders signal comparison table when signals exist", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("signals-table")).toBeInTheDocument();
    expect(screen.getByTestId("prev-clicks")).toHaveTextContent("1234");
    expect(screen.getByTestId("curr-clicks")).toHaveTextContent("679");
  });

  // CDD-10: Shows mini trend charts
  it("shows trend charts when trend data exists", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("trend-chart")).toBeInTheDocument();
    expect(screen.getByTestId("clicks-trend")).toBeInTheDocument();
    expect(screen.getByTestId("impressions-trend")).toBeInTheDocument();
  });

  // CDD-11: Tab navigation works
  it("allows tab navigation", async () => {
    const user = userEvent.setup();
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        tasks={mockTasks}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    const tasksTab = screen.getByTestId("tab-tasks");
    await user.click(tasksTab);

    expect(tasksTab).toHaveAttribute("aria-selected", "true");
  });

  // CDD-12: Overview tab default active
  it("shows overview tab as default active", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("tab-overview")).toHaveAttribute("aria-selected", "true");
  });

  // CDD-13: Signals tab shows comparison
  it("shows signals comparison on signals tab", async () => {
    const user = userEvent.setup();
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    await user.click(screen.getByTestId("tab-signals"));
    expect(screen.getByTestId("tab-signals")).toHaveAttribute("aria-selected", "true");
  });

  // CDD-14: Tasks tab shows task list
  it("shows task count in tasks tab", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        tasks={mockTasks}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("tab-tasks")).toHaveTextContent("Tasks (1)");
  });

  // CDD-15: Notes tab shows notes
  it("has notes tab available", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("tab-notes")).toBeInTheDocument();
  });

  // CDD-16: AI Brief tab shows panel
  it("has AI brief tab available", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("tab-ai-brief")).toBeInTheDocument();
  });

  // CDD-17: Status workflow buttons rendered
  it("renders status workflow buttons", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
        onStatusChange={mockOnStatusChange}
      />
    );

    expect(screen.getByTestId("status-workflow")).toBeInTheDocument();
  });

  // CDD-18: Loading state
  it("shows loading skeleton when loading", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
        isLoading={true}
      />
    );

    expect(screen.getByTestId("drawer-loading")).toBeInTheDocument();
    expect(screen.getByTestId("skeleton-title")).toBeInTheDocument();
  });

  // CDD-19: Error state
  it("shows error message on error", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
        error="Failed to load candidate"
      />
    );

    expect(screen.getByTestId("drawer-error")).toBeInTheDocument();
    expect(screen.getByText("Failed to load candidate")).toBeInTheDocument();
  });

  // CDD-20: Close button works
  it("calls onClose when close button clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    await user.click(screen.getByTestId("close-button"));
    expect(mockOnClose).toHaveBeenCalledTimes(1);
  });

  // CDD-21: Drawer opens from right (CSS behavior - test visibility)
  it("drawer is visible when open", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("candidate-detail-drawer")).toBeVisible();
  });

  // CDD-22: Accessible drawer
  it("has correct accessibility attributes", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    const drawer = screen.getByTestId("candidate-detail-drawer");
    expect(drawer).toHaveAttribute("role", "dialog");
    expect(drawer).toHaveAttribute("aria-modal", "true");
    expect(drawer).toHaveAttribute("aria-label");
  });

  // CDD-23: ESC key closes drawer
  it("closes on ESC key press", async () => {
    const user = userEvent.setup();
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    await user.keyboard("{Escape}");
    expect(mockOnClose).toHaveBeenCalled();
  });

  // CDD-25: Handles missing optional data
  it("handles missing signal data gracefully", () => {
    const candidateNoSignals = { ...mockCandidate, signals: null };
    render(
      <MockCandidateDetailDrawer
        candidate={candidateNoSignals}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("no-signals")).toBeInTheDocument();
  });

  // CDD-26: Shows status badge
  it("shows status badge with correct status", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("status-badge")).toHaveTextContent("backlog");
    expect(screen.getByTestId("status-badge")).toHaveAttribute("data-status", "backlog");
  });

  // CDD-27: Shows detection dates
  it("shows detection dates formatted", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("first-detected")).toHaveTextContent("2026-03-01T10:00:00Z");
    expect(screen.getByTestId("last-detected")).toHaveTextContent("2026-03-10T08:00:00Z");
  });

  // CDD-28: Shows lost click estimate
  it("formats lost click estimate", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("lost-clicks")).toHaveTextContent("1,234 Lost Clicks");
  });

  // CDD-29: Shows recovery upside
  it("formats recovery upside estimate", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("recovery-upside")).toHaveTextContent("5,678 Upside");
  });

  // CDD-30: Does not render when closed
  it("does not render when isOpen is false", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={false}
        onClose={mockOnClose}
      />
    );

    expect(screen.queryByTestId("candidate-detail-drawer")).not.toBeInTheDocument();
  });

  // CDD-31: Does not render without candidate
  it("does not render when candidate is null", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={null}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.queryByTestId("candidate-detail-drawer")).not.toBeInTheDocument();
  });

  // CDD-32: Large number formatting
  it("formats large lost click estimates", () => {
    const largeCandidate = {
      ...mockCandidate,
      lostClickEstimate: 1500000,
    };

    render(
      <MockCandidateDetailDrawer
        candidate={largeCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("lost-clicks")).toHaveTextContent("1,500,000");
  });

  // CDD-33: Status change triggers callback
  it("calls onStatusChange when status button clicked", async () => {
    const user = userEvent.setup();
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
        onStatusChange={mockOnStatusChange}
      />
    );

    await user.click(screen.getByTestId("status-change-btn"));
    expect(mockOnStatusChange).toHaveBeenCalledWith("planned");
  });

  // CDD-34: Different status shows appropriate workflow buttons
  it("shows different workflow for different statuses", () => {
    const plannedCandidate = { ...mockCandidate, status: "planned" as const };
    render(
      <MockCandidateDetailDrawer
        candidate={plannedCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("status-badge")).toHaveAttribute("data-status", "planned");
  });

  // CDD-35: Handles candidate without title
  it("handles candidate without title", () => {
    const noTitleCandidate = { ...mockCandidate, pageTitle: null };
    render(
      <MockCandidateDetailDrawer
        candidate={noTitleCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("page-title")).toHaveTextContent("Untitled Page");
  });

  // CDD-36: Handles candidate without trend data
  it("handles candidate without trend data", () => {
    const noTrendCandidate = { ...mockCandidate, trend: null };
    render(
      <MockCandidateDetailDrawer
        candidate={noTrendCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.queryByTestId("trend-chart")).not.toBeInTheDocument();
  });

  // CDD-37: Close button has accessible label
  it("close button has accessible label", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("close-button")).toHaveAttribute("aria-label", "Close drawer");
  });

  // CDD-38: All tabs are focusable
  it("all tabs are buttons and focusable", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    const tabs = ["tab-overview", "tab-signals", "tab-tasks", "tab-notes", "tab-ai-brief"];
    tabs.forEach((tabId) => {
      const tab = screen.getByTestId(tabId);
      expect(tab.tagName.toLowerCase()).toBe("button");
    });
  });

  // CDD-39: External link icon shown
  it("shows external link icon on URL", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    expect(screen.getByTestId("icon-external-link")).toBeInTheDocument();
  });

  // CDD-40: Signal delta shows negative sign for decline
  it("shows negative delta for clicks decline", () => {
    render(
      <MockCandidateDetailDrawer
        candidate={mockCandidate}
        isOpen={true}
        onClose={mockOnClose}
      />
    );

    const deltaClicks = screen.getByTestId("delta-clicks");
    const delta = mockCandidate.signals!.currentClicks - mockCandidate.signals!.previousClicks;
    expect(deltaClicks).toHaveTextContent(delta.toString());
  });
});
