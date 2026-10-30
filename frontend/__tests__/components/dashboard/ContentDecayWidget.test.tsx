/**
 * ContentDecayWidget Component Tests
 * TASK-36-14: Navigation & Dashboard Integration
 * 
 * @jest-environment jsdom
 */

import React from "react";
import { render, screen, fireEvent, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

import { ContentDecayWidget } from "@/components/dashboard/ContentDecayWidget";
import type { DecaySummaryData, DecayCandidate } from "@/lib/types/content-decay";

// =============================================================================
// Mock Data
// =============================================================================

const mockSummary: DecaySummaryData = {
  totalCandidates: 25,
  criticalCount: 5,
  highCount: 12,
  mediumCount: 8,
  lowCount: 0,
  estimatedLostClicks: 4500,
  recoveryUpside: 1500,
  recoveredCount: 3,
  lastScannedAt: new Date(Date.now() - 30 * 60 * 1000).toISOString(), // 30 minutes ago
};

const mockCandidate: DecayCandidate = {
  id: 1,
  projectId: 1,
  pageUrl: "https://example.com/blog/seo-tips",
  pageTitle: "10 SEO Tips for 2024",
  pageType: "blog",
  severity: "critical",
  decayScore: 85,
  detectionConfidence: 0.95,
  lostClickEstimate: 450,
  recoveryUpsideEstimate: 200,
  primaryReasonCode: "clicks_down",
  reasonCodes: [{ type: "clicks_down", evidence: "Clicks dropped 45%", isPrimary: true }],
  status: "backlog",
  firstDetectedAt: "2026-03-10T10:00:00Z",
  lastDetectedAt: "2026-03-10T14:00:00Z",
  plannedAt: null,
  startedAt: null,
  refreshedAt: null,
  recoveredAt: null,
  ignoredAt: null,
  signals: null,
  trend: null,
};

const mockTopCandidates: DecayCandidate[] = [
  { ...mockCandidate, id: 1, decayScore: 95, severity: "critical" },
  { ...mockCandidate, id: 2, decayScore: 78, severity: "high", pageTitle: "Content Marketing Guide" },
  { ...mockCandidate, id: 3, decayScore: 65, severity: "high", pageTitle: "Keyword Research Tips" },
  { ...mockCandidate, id: 4, decayScore: 52, severity: "medium" },
  { ...mockCandidate, id: 5, decayScore: 40, severity: "medium" },
];

// =============================================================================
// Mock Lucide Icons
// =============================================================================

jest.mock("lucide-react", () => ({
  TrendingDown: () => <span data-testid="trending-down-icon">TrendingDown</span>,
  AlertTriangle: () => <span data-testid="alert-icon">AlertTriangle</span>,
  CheckCircle: () => <span data-testid="check-circle-icon">CheckCircle</span>,
  ArrowRight: () => <span data-testid="arrow-right-icon">ArrowRight</span>,
  Eye: () => <span data-testid="eye-icon">Eye</span>,
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  RefreshCw: () => <span data-testid="refresh-icon">RefreshCw</span>,
  Clock: () => <span data-testid="clock-icon">Clock</span>,
}));

// =============================================================================
// CDW-001: Widget Rendering Tests
// =============================================================================

describe("CDW-001: Widget Rendering", () => {
  test("CDW-001.1: Component renders with required props", () => {
    render(<ContentDecayWidget siteId="1" />);
    expect(screen.getByTestId("content-decay-widget")).toBeInTheDocument();
  });

  test("CDW-001.2: Widget has correct ARIA role", () => {
    render(<ContentDecayWidget siteId="1" />);
    const widget = screen.getByTestId("content-decay-widget");
    expect(widget).toHaveAttribute("role", "region");
  });

  test("CDW-001.3: Widget has correct aria-label", () => {
    render(<ContentDecayWidget siteId="1" />);
    const widget = screen.getByTestId("content-decay-widget");
    expect(widget).toHaveAttribute("aria-label", "Content Decay Summary");
  });

  test("CDW-001.4: Header section renders with title", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByText("Content Decay")).toBeInTheDocument();
  });

  test("CDW-001.5: Summary section renders with data", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("summary-section")).toBeInTheDocument();
  });

  test("CDW-001.6: Top candidates list renders", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    expect(screen.getByTestId("candidates-section")).toBeInTheDocument();
  });

  test("CDW-001.7: View All link renders with correct href", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const link = screen.getByTestId("view-all-link");
    expect(link).toHaveAttribute("href", "/sites/1/content-decay");
  });

  test("CDW-001.8: Custom className applied", () => {
    render(<ContentDecayWidget siteId="1" className="custom-class" />);
    const widget = screen.getByTestId("content-decay-widget");
    expect(widget).toHaveClass("custom-class");
  });

  test("CDW-001.9: Empty data renders empty state", () => {
    const emptySummary = { ...mockSummary, totalCandidates: 0 };
    render(<ContentDecayWidget siteId="1" summary={emptySummary} />);
    expect(screen.getByTestId("empty-state")).toBeInTheDocument();
  });

  test("CDW-001.10: Null summary shows empty content", () => {
    // When summary is null (not undefined), component shows empty card content
    // Use isLoading=true to show loading skeleton
    render(<ContentDecayWidget siteId="1" summary={null} />);
    // Widget renders but with empty content (no skeleton, no error, no data)
    expect(screen.getByTestId("content-decay-widget")).toBeInTheDocument();
    expect(screen.queryByTestId("loading-skeleton")).not.toBeInTheDocument();
    expect(screen.queryByTestId("summary-section")).not.toBeInTheDocument();
  });
});

// =============================================================================
// CDW-002: Empty State Tests
// =============================================================================

describe("CDW-002: Empty State", () => {
  const emptySummary = { ...mockSummary, totalCandidates: 0 };

  test("CDW-002.1: Empty state displays message", () => {
    render(<ContentDecayWidget siteId="1" summary={emptySummary} />);
    expect(screen.getByTestId("empty-message")).toHaveTextContent("No content decay detected");
  });

  test("CDW-002.2: Empty state has checkmark icon", () => {
    render(<ContentDecayWidget siteId="1" summary={emptySummary} />);
    expect(screen.getByTestId("check-icon")).toBeInTheDocument();
  });

  test("CDW-002.3: Empty state has descriptive text", () => {
    render(<ContentDecayWidget siteId="1" summary={emptySummary} />);
    expect(screen.getByText("Your content is performing well")).toBeInTheDocument();
  });

  test("CDW-002.4: Empty state has Run Scan button", () => {
    const onRefresh = jest.fn();
    render(<ContentDecayWidget siteId="1" summary={emptySummary} onRefresh={onRefresh} />);
    expect(screen.getByTestId("run-scan-btn")).toBeInTheDocument();
  });

  test("CDW-002.5: Empty state button triggers callback", async () => {
    const onRefresh = jest.fn();
    render(<ContentDecayWidget siteId="1" summary={emptySummary} onRefresh={onRefresh} />);
    await userEvent.click(screen.getByTestId("run-scan-btn"));
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  test("CDW-002.6: Empty state accessibility", () => {
    render(<ContentDecayWidget siteId="1" summary={emptySummary} />);
    const emptyState = screen.getByTestId("empty-state");
    expect(emptyState).toHaveAttribute("role", "status");
    expect(emptyState).toHaveAttribute("aria-live", "polite");
  });

  test("CDW-002.7: Empty state with recovered only", () => {
    const recoveredSummary = { ...emptySummary, recoveredCount: 5 };
    render(<ContentDecayWidget siteId="1" summary={recoveredSummary} />);
    expect(screen.getByTestId("recovered-count")).toHaveTextContent("5 pages recovered");
  });
});

// =============================================================================
// CDW-003: Loading State Tests
// =============================================================================

describe("CDW-003: Loading State", () => {
  test("CDW-003.1: Loading state shows skeleton", () => {
    render(<ContentDecayWidget siteId="1" isLoading={true} />);
    expect(screen.getByTestId("loading-skeleton")).toBeInTheDocument();
  });

  test("CDW-003.2: Loading skeleton has correct count", () => {
    render(<ContentDecayWidget siteId="1" isLoading={true} />);
    const skeletons = screen.getAllByTestId("skeleton");
    expect(skeletons).toHaveLength(3);
  });

  test("CDW-003.3: Loading skeleton accessibility", () => {
    render(<ContentDecayWidget siteId="1" isLoading={true} />);
    const skeleton = screen.getByTestId("loading-skeleton");
    expect(skeleton).toHaveAttribute("role", "status");
    expect(skeleton).toHaveAttribute("aria-live", "polite");
  });

  test("CDW-003.4: Loading state hides content", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} isLoading={true} />);
    expect(screen.queryByTestId("summary-section")).not.toBeInTheDocument();
  });

  test("CDW-003.5: Loading transitions to data", () => {
    const { rerender } = render(<ContentDecayWidget siteId="1" isLoading={true} />);
    expect(screen.getByTestId("loading-skeleton")).toBeInTheDocument();
    
    rerender(<ContentDecayWidget siteId="1" summary={mockSummary} isLoading={false} />);
    expect(screen.getByTestId("summary-section")).toBeInTheDocument();
  });

  test("CDW-003.6: Loading takes priority over error", () => {
    // Component shows loading state when both isLoading and error are set
    // Error state is shown only when isLoading=false and error exists
    render(<ContentDecayWidget siteId="1" isLoading={true} error="Failed to load" />);
    expect(screen.getByTestId("loading-skeleton")).toBeInTheDocument();
    expect(screen.queryByTestId("error-state")).not.toBeInTheDocument();
  });
});

// =============================================================================
// CDW-004: Summary Card Tests
// =============================================================================

describe("CDW-004: Summary Card", () => {
  test("CDW-004.1: Critical count badge", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("critical-badge")).toHaveTextContent("5 Critical");
  });

  test("CDW-004.2: Critical badge color", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const badge = screen.getByTestId("critical-badge");
    expect(badge).toHaveAttribute("data-severity", "critical");
  });

  test("CDW-004.3: High count badge", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("high-badge")).toHaveTextContent("12 High");
  });

  test("CDW-004.4: High badge color", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const badge = screen.getByTestId("high-badge");
    expect(badge).toHaveAttribute("data-severity", "high");
  });

  test("CDW-004.5: Medium count badge", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("medium-badge")).toHaveTextContent("8 Medium");
  });

  test("CDW-004.6: Medium badge color", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const badge = screen.getByTestId("medium-badge");
    expect(badge).toHaveAttribute("data-severity", "medium");
  });

  test("CDW-004.7: Total candidates count", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("total-candidates")).toHaveTextContent("25 pages need attention");
  });

  test("CDW-004.8: Recovery upside display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("recovery-upside")).toHaveTextContent("1.5K potential clicks");
  });

  test("CDW-004.9: Zero upside formatting", () => {
    const zeroSummary = { ...mockSummary, recoveryUpside: 0 };
    render(<ContentDecayWidget siteId="1" summary={zeroSummary} />);
    // No upside should be shown when zero
    expect(screen.queryByTestId("recovery-upside")).not.toBeInTheDocument();
  });

  test("CDW-004.10: Large number formatting", () => {
    const largeSummary = { ...mockSummary, recoveryUpside: 2500000 };
    render(<ContentDecayWidget siteId="1" summary={largeSummary} />);
    expect(screen.getByTestId("recovery-upside")).toHaveTextContent("2.5M potential clicks");
  });

  test("CDW-004.11: Recovered count display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("recovered-info")).toHaveTextContent("3 pages recovered");
  });
});

// =============================================================================
// CDW-005: Top Candidates List Tests
// =============================================================================

describe("CDW-005: Top Candidates List", () => {
  test("CDW-005.1: Renders top 3 candidates", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    // Should render first 3 candidates
    expect(screen.getByTestId("candidate-1")).toBeInTheDocument();
    expect(screen.getByTestId("candidate-2")).toBeInTheDocument();
    expect(screen.getByTestId("candidate-3")).toBeInTheDocument();
    expect(screen.queryByTestId("candidate-4")).not.toBeInTheDocument();
  });

  test("CDW-005.2: Candidate URL display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    // Scope to first candidate to avoid multiple URL elements
    const firstCandidate = screen.getByTestId("candidate-1");
    const urlElement = within(firstCandidate).getByTestId("candidate-url");
    expect(urlElement).toBeInTheDocument();
    // URL is shown in the element (truncation depends on CSS, not JS text manipulation)
    expect(urlElement.textContent).toBeTruthy();
  });

  test("CDW-005.3: Candidate title display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    expect(screen.getByText("10 SEO Tips for 2024")).toBeInTheDocument();
  });

  test("CDW-005.4: Decay score display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    const score = screen.getAllByTestId("decay-score")[0];
    expect(score).toHaveTextContent("Score: 95");
  });

  test("CDW-005.5: Severity indicator", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    const candidate = screen.getByTestId("candidate-1");
    expect(candidate).toHaveAttribute("data-severity", "critical");
  });

  test("CDW-005.6: Primary reason display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    // Scope to first candidate to avoid multiple elements with same text
    const firstCandidate = screen.getByTestId("candidate-1");
    expect(within(firstCandidate).getByTestId("primary-reason")).toHaveTextContent("Clicks Down");
  });

  test("CDW-005.7: Lost clicks display", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    // Scope to first candidate to avoid multiple elements
    const firstCandidate = screen.getByTestId("candidate-1");
    expect(within(firstCandidate).getByTestId("lost-clicks")).toHaveTextContent(/450 clicks/);
  });

  test("CDW-005.8: View action button triggers callback", async () => {
    const onView = jest.fn();
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} onViewCandidate={onView} />);
    const viewBtn = within(screen.getByTestId("candidate-1")).getByTestId("view-btn");
    await userEvent.click(viewBtn);
    expect(onView).toHaveBeenCalledWith(1);
  });

  test("CDW-005.9: Plan action button triggers callback", async () => {
    const onPlan = jest.fn();
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} onMarkPlanned={onPlan} />);
    const planBtn = within(screen.getByTestId("candidate-1")).getByTestId("plan-btn");
    await userEvent.click(planBtn);
    expect(onPlan).toHaveBeenCalledWith(1);
  });

  test("CDW-005.10: Empty candidates list", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={[]} />);
    // With empty candidates but has summary (totalCandidates > 0), candidates section should not be shown
    // because there are no candidates to display
    expect(screen.queryByTestId("candidates-section")).not.toBeInTheDocument();
  });

  test("CDW-005.11: List item accessibility", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    const candidate = screen.getByTestId("candidate-1");
    expect(candidate).toHaveAttribute("role", "listitem");
  });

  test("CDW-005.12: Keyboard navigation - buttons focusable", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    const viewBtn = within(screen.getByTestId("candidate-1")).getByTestId("view-btn");
    viewBtn.focus();
    expect(viewBtn).toHaveFocus();
  });
});

// =============================================================================
// CDW-006: Timestamp Display Tests
// =============================================================================

describe("CDW-006: Timestamp Display", () => {
  test("CDW-006.1: Recent scan timestamp - minutes ago", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    expect(screen.getByTestId("last-scanned")).toHaveTextContent("30 minutes ago");
  });

  test("CDW-006.2: Hours ago", () => {
    const hoursAgo = { ...mockSummary, lastScannedAt: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() };
    render(<ContentDecayWidget siteId="1" summary={hoursAgo} topCandidates={mockTopCandidates} />);
    expect(screen.getByTestId("last-scanned")).toHaveTextContent("2 hours ago");
  });

  test("CDW-006.3: Yesterday", () => {
    const yesterday = { ...mockSummary, lastScannedAt: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString() };
    render(<ContentDecayWidget siteId="1" summary={yesterday} topCandidates={mockTopCandidates} />);
    expect(screen.getByTestId("last-scanned")).toHaveTextContent("Yesterday");
  });

  test("CDW-006.4: Days ago", () => {
    const daysAgo = { ...mockSummary, lastScannedAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString() };
    render(<ContentDecayWidget siteId="1" summary={daysAgo} topCandidates={mockTopCandidates} />);
    expect(screen.getByTestId("last-scanned")).toHaveTextContent("5 days ago");
  });

  test("CDW-006.5: Null timestamp", () => {
    const noScan = { ...mockSummary, lastScannedAt: null };
    render(<ContentDecayWidget siteId="1" summary={noScan} topCandidates={mockTopCandidates} />);
    expect(screen.getByTestId("last-scanned")).toHaveTextContent("Never scanned");
  });
});

// =============================================================================
// CDW-007: Navigation Links Tests
// =============================================================================

describe("CDW-007: Navigation Links", () => {
  test("CDW-007.1: View All link href correct", () => {
    render(<ContentDecayWidget siteId="123" summary={mockSummary} />);
    const link = screen.getByTestId("view-all-link");
    expect(link).toHaveAttribute("href", "/sites/123/content-decay");
  });

  test("CDW-007.2: View All link has aria-label", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const link = screen.getByTestId("view-all-link");
    expect(link).toHaveAttribute("aria-label", "View all content decay issues");
  });

  test("CDW-007.3: Link hidden when no candidates", () => {
    const emptySummary = { ...mockSummary, totalCandidates: 0 };
    render(<ContentDecayWidget siteId="1" summary={emptySummary} />);
    expect(screen.queryByTestId("view-all-link")).not.toBeInTheDocument();
  });

  test("CDW-007.4: Link visible with candidates", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    expect(screen.getByTestId("view-all-link")).toBeInTheDocument();
  });
});

// =============================================================================
// CDW-008: Quick Actions Tests
// =============================================================================

describe("CDW-008: Quick Actions", () => {
  test("CDW-008.1: View button triggers callback", async () => {
    const onView = jest.fn();
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} onViewCandidate={onView} />);
    const viewBtn = within(screen.getByTestId("candidate-1")).getByTestId("view-btn");
    await userEvent.click(viewBtn);
    expect(onView).toHaveBeenCalledWith(1);
  });

  test("CDW-008.2: Plan button triggers callback", async () => {
    const onPlan = jest.fn();
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} onMarkPlanned={onPlan} />);
    const planBtn = within(screen.getByTestId("candidate-1")).getByTestId("plan-btn");
    await userEvent.click(planBtn);
    expect(onPlan).toHaveBeenCalledWith(1);
  });

  test("CDW-008.3: Buttons have aria-labels", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    const viewBtn = within(screen.getByTestId("candidate-1")).getByTestId("view-btn");
    expect(viewBtn).toHaveAttribute("aria-label");
  });

  test("CDW-008.4: Buttons disabled when loading", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} isLoading={true} />);
    // Loading state should not show action buttons
    expect(screen.queryByTestId("candidate-1")).not.toBeInTheDocument();
  });
});

// =============================================================================
// CDW-009: Error State Tests
// =============================================================================

describe("CDW-009: Error State", () => {
  test("CDW-009.1: Error state displays message", () => {
    render(<ContentDecayWidget siteId="1" error="Failed to load data" />);
    expect(screen.getByTestId("error-message")).toHaveTextContent("Failed to load data");
  });

  test("CDW-009.2: Error has alert role", () => {
    render(<ContentDecayWidget siteId="1" error="Failed" />);
    const errorState = screen.getByTestId("error-state");
    expect(errorState).toHaveAttribute("role", "alert");
  });

  test("CDW-009.3: Error has retry button", () => {
    render(<ContentDecayWidget siteId="1" error="Failed" onRefresh={jest.fn()} />);
    expect(screen.getByTestId("retry-btn")).toBeInTheDocument();
  });

  test("CDW-009.4: Retry button triggers callback", async () => {
    const onRefresh = jest.fn();
    render(<ContentDecayWidget siteId="1" error="Failed" onRefresh={onRefresh} />);
    await userEvent.click(screen.getByTestId("retry-btn"));
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  test("CDW-009.5: Error icon displayed", () => {
    render(<ContentDecayWidget siteId="1" error="Failed" />);
    expect(screen.getByTestId("alert-icon")).toBeInTheDocument();
  });
});

// =============================================================================
// CDW-010: Accessibility Tests
// =============================================================================

describe("CDW-010: Accessibility", () => {
  test("CDW-010.1: Widget has region role", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const widget = screen.getByTestId("content-decay-widget");
    expect(widget).toHaveAttribute("role", "region");
  });

  test("CDW-010.2: Widget has aria-label", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const widget = screen.getByTestId("content-decay-widget");
    expect(widget).toHaveAttribute("aria-label", "Content Decay Summary");
  });

  test("CDW-010.3: Severity badges have aria-labels", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} />);
    const criticalBadge = screen.getByTestId("critical-badge");
    expect(criticalBadge).toHaveAttribute("aria-label", "5 critical issues");
  });

  test("CDW-010.4: Keyboard focus order", () => {
    render(<ContentDecayWidget siteId="1" summary={mockSummary} topCandidates={mockTopCandidates} />);
    // Verify all interactive elements are focusable
    const viewAllLink = screen.getByTestId("view-all-link");
    viewAllLink.focus();
    expect(viewAllLink).toHaveFocus();
  });
});
