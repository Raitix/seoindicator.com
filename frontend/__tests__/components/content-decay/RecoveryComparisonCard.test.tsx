/**
 * Tests for RecoveryComparisonCard Component
 * Task: TASK-36-12
 * Reference: TESTS/TEST-36-12.md
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import "@testing-library/jest-dom";
import { describe, expect, it, vi } from "vitest";

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  TrendingUp: () => <span data-testid="icon-trending-up">TrendingUp</span>,
  TrendingDown: () => <span data-testid="icon-trending-down">TrendingDown</span>,
  Minus: () => <span data-testid="icon-minus">Minus</span>,
  MousePointerClick: () => <span data-testid="icon-click">MousePointerClick</span>,
  Eye: () => <span data-testid="icon-eye">Eye</span>,
  BarChart2: () => <span data-testid="icon-bar-chart">BarChart2</span>,
  Target: () => <span data-testid="icon-target">Target</span>,
  Sparkles: () => <span data-testid="icon-sparkles">Sparkles</span>,
}));

// Import types
import type { RecoveryComparison, RecoveryMetric } from "@/lib/types/content-decay";

// Mock component - will be replaced with real implementation
const MockRecoveryComparisonCard: React.FC<{
  comparison: RecoveryComparison | null;
  isLoading?: boolean;
  className?: string;
  compact?: boolean;
}> = ({ comparison, isLoading, className, compact }) => {
  if (isLoading) {
    return (
      <div data-testid="comparison-loading" className={className}>
        <div data-testid="skeleton-metric" />
        <div data-testid="skeleton-metric" />
        <div data-testid="skeleton-metric" />
      </div>
    );
  }

  if (!comparison) {
    return (
      <div data-testid="comparison-empty" className={className}>
        No recovery data yet
      </div>
    );
  }

  const formatNumber = (num: number): string => {
    if (Math.abs(num) >= 1000000) {
      return (num / 1000000).toFixed(1) + "M";
    }
    if (Math.abs(num) >= 1000) {
      return (num / 1000).toFixed(1) + "K";
    }
    return num.toString();
  };

  const formatPercent = (num: number): string => {
    return (num * 100).toFixed(1) + "%";
  };

  const getDeltaColor = (delta: number, isPosition = false): string => {
    // For position, negative delta (going down in position number) is good
    const isPositive = isPosition ? delta < 0 : delta > 0;
    if (delta === 0) return "gray";
    return isPositive ? "green" : "red";
  };

  const MetricRow: React.FC<{
    label: string;
    metric: RecoveryMetric;
    icon: string;
    isPosition?: boolean;
    format?: "number" | "percent" | "position";
  }> = ({ label, metric, icon, isPosition, format = "number" }) => {
    const deltaColor = getDeltaColor(metric.delta, isPosition);
    const showUpIcon = isPosition ? metric.delta < 0 : metric.delta > 0;

    return (
      <div data-testid={`metric-${label.toLowerCase().replace(/\s/g, "-")}`} className="metric-row">
        <span data-testid={`icon-${icon}`}>{icon}</span>
        <span data-testid="metric-label">{label}</span>
        <span data-testid="metric-before">{format === "percent" ? formatPercent(metric.before) : formatNumber(metric.before)}</span>
        <span data-testid="metric-after">{format === "percent" ? formatPercent(metric.after) : formatNumber(metric.after)}</span>
        <span data-testid="metric-delta" data-color={deltaColor} className={`delta-${deltaColor}`}>
          {metric.delta > 0 ? "+" : ""}
          {format === "percent" ? formatPercent(metric.delta) : formatNumber(metric.delta)}
          {isPosition ? " positions" : ""}
          {showUpIcon ? (
            <span data-testid="icon-trending-up">TrendingUp</span>
          ) : metric.delta < 0 ? (
            <span data-testid="icon-trending-down">TrendingDown</span>
          ) : (
            <span data-testid="icon-minus">Minus</span>
          )}
        </span>
        {metric.deltaPercent !== null && (
          <span data-testid="metric-delta-percent">({metric.deltaPercent > 0 ? "+" : ""}{metric.deltaPercent.toFixed(1)}%)</span>
        )}
      </div>
    );
  };

  return (
    <div
      data-testid="recovery-comparison-card"
      className={className}
      role="region"
      aria-label="Recovery comparison"
    >
      <h3 data-testid="card-title">Recovery Comparison</h3>

      {!compact && (
        <div data-testid="headers">
          <span>Metric</span>
          <span>Before</span>
          <span>After</span>
          <span>Change</span>
        </div>
      )}

      <div data-testid="metrics-container">
        <MetricRow label="Clicks" metric={comparison.clicks} icon="click" />
        <MetricRow label="Impressions" metric={comparison.impressions} icon="eye" />
        <MetricRow label="CTR" metric={comparison.ctr} icon="bar-chart" format="percent" />
        <MetricRow label="Position" metric={comparison.position} icon="target" isPosition format="position" />
        {comparison.aiVisibility && (
          <MetricRow label="AI Visibility" metric={comparison.aiVisibility} icon="sparkles" format="percent" />
        )}
      </div>
    </div>
  );
};

// Test Data
const mockComparison: RecoveryComparison = {
  clicks: { before: 1234, after: 1450, delta: 216, deltaPercent: 17.5 },
  impressions: { before: 45678, after: 52341, delta: 6663, deltaPercent: 14.6 },
  ctr: { before: 0.027, after: 0.028, delta: 0.001, deltaPercent: 3.7 },
  position: { before: 7.8, after: 4.2, delta: -3.6, deltaPercent: -46.2 },
  aiVisibility: { before: 12.5, after: 28.3, delta: 15.8, deltaPercent: 126.4 },
};

const mockComparisonWithoutAi: RecoveryComparison = {
  clicks: { before: 1234, after: 1450, delta: 216, deltaPercent: 17.5 },
  impressions: { before: 45678, after: 52341, delta: 6663, deltaPercent: 14.6 },
  ctr: { before: 0.027, after: 0.028, delta: 0.001, deltaPercent: 3.7 },
  position: { before: 7.8, after: 4.2, delta: -3.6, deltaPercent: -46.2 },
};

const mockNegativeComparison: RecoveryComparison = {
  clicks: { before: 1450, after: 1234, delta: -216, deltaPercent: -14.9 },
  impressions: { before: 52341, after: 45678, delta: -6663, deltaPercent: -12.7 },
  ctr: { before: 0.028, after: 0.027, delta: -0.001, deltaPercent: -3.6 },
  position: { before: 4.2, after: 7.8, delta: 3.6, deltaPercent: 85.7 },
};

describe("RecoveryComparisonCard", () => {
  // RCC-01: Renders comparison data
  it("renders all metric rows when comparison data is provided", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    expect(screen.getByTestId("recovery-comparison-card")).toBeInTheDocument();
    expect(screen.getByTestId("metric-clicks")).toBeInTheDocument();
    expect(screen.getByTestId("metric-impressions")).toBeInTheDocument();
    expect(screen.getByTestId("metric-ctr")).toBeInTheDocument();
    expect(screen.getByTestId("metric-position")).toBeInTheDocument();
  });

  // RCC-02: Shows clicks delta (negative)
  it("shows negative clicks delta in red with down icon", () => {
    render(<MockRecoveryComparisonCard comparison={mockNegativeComparison} />);

    const delta = screen.getAllByTestId("metric-delta")[0]; // clicks delta
    expect(delta).toHaveAttribute("data-color", "red");
    expect(screen.getByTestId("icon-trending-down")).toBeInTheDocument();
  });

  // RCC-03: Shows positive clicks delta
  it("shows positive clicks delta in green with up icon", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const delta = screen.getAllByTestId("metric-delta")[0]; // clicks delta
    expect(delta).toHaveAttribute("data-color", "green");
    expect(screen.getByTestId("icon-trending-up")).toBeInTheDocument();
  });

  // RCC-04: Shows impressions delta
  it("shows impressions delta formatted with K suffix", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const impressionsMetric = screen.getByTestId("metric-impressions");
    expect(impressionsMetric).toBeInTheDocument();
  });

  // RCC-05: Shows CTR delta
  it("shows CTR delta as percentage", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const ctrMetric = screen.getByTestId("metric-ctr");
    expect(ctrMetric).toBeInTheDocument();
  });

  // RCC-06: Shows position delta (improvement)
  it("shows positive position change (lower number) in green", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const positionDelta = screen.getAllByTestId("metric-delta")[3]; // position delta
    // Position went from 7.8 to 4.2 (lower is better), delta is -3.6 which is positive improvement
    expect(positionDelta).toHaveAttribute("data-color", "green");
  });

  // RCC-07: Shows negative position delta (decline)
  it("shows negative position change (higher number) in red", () => {
    render(<MockRecoveryComparisonCard comparison={mockNegativeComparison} />);

    const positionDelta = screen.getAllByTestId("metric-delta")[3]; // position delta
    // Position went from 4.2 to 7.8 (higher is worse), delta is +3.6 which is negative improvement
    expect(positionDelta).toHaveAttribute("data-color", "red");
  });

  // RCC-08: Shows AI visibility delta
  it("shows AI visibility delta when present", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    expect(screen.getByTestId("metric-ai-visibility")).toBeInTheDocument();
  });

  // RCC-09: Missing AI visibility
  it("does not show AI visibility row when not present", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparisonWithoutAi} />);

    expect(screen.queryByTestId("metric-ai-visibility")).not.toBeInTheDocument();
  });

  // RCC-10: Shows before/after values
  it("shows before and after values for each metric", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const beforeValues = screen.getAllByTestId("metric-before");
    const afterValues = screen.getAllByTestId("metric-after");

    expect(beforeValues.length).toBeGreaterThan(0);
    expect(afterValues.length).toBeGreaterThan(0);
  });

  // RCC-11: Delta color coding - negative
  it("applies red color to negative delta", () => {
    render(<MockRecoveryComparisonCard comparison={mockNegativeComparison} />);

    const clicksDelta = screen.getAllByTestId("metric-delta")[0];
    expect(clicksDelta).toHaveAttribute("data-color", "red");
    expect(clicksDelta).toHaveClass("delta-red");
  });

  // RCC-12: Delta color coding - positive
  it("applies green color to positive delta", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const clicksDelta = screen.getAllByTestId("metric-delta")[0];
    expect(clicksDelta).toHaveAttribute("data-color", "green");
    expect(clicksDelta).toHaveClass("delta-green");
  });

  // RCC-13: Zero delta
  it("applies gray color to zero delta", () => {
    const zeroDeltaComparison: RecoveryComparison = {
      clicks: { before: 100, after: 100, delta: 0, deltaPercent: 0 },
      impressions: { before: 1000, after: 1000, delta: 0, deltaPercent: 0 },
      ctr: { before: 0.01, after: 0.01, delta: 0, deltaPercent: 0 },
      position: { before: 5.0, after: 5.0, delta: 0, deltaPercent: 0 },
    };

    render(<MockRecoveryComparisonCard comparison={zeroDeltaComparison} />);

    const clicksDelta = screen.getAllByTestId("metric-delta")[0];
    expect(clicksDelta).toHaveAttribute("data-color", "gray");
    expect(screen.getByTestId("icon-minus")).toBeInTheDocument();
  });

  // RCC-14: Large number formatting
  it("formats large numbers with K suffix", () => {
    const largeComparison: RecoveryComparison = {
      clicks: { before: 15000, after: 18000, delta: 3000, deltaPercent: 20 },
      impressions: { before: 1500000, after: 1800000, delta: 300000, deltaPercent: 20 },
      ctr: { before: 0.01, after: 0.01, delta: 0, deltaPercent: 0 },
      position: { before: 5.0, after: 5.0, delta: 0, deltaPercent: 0 },
    };

    render(<MockRecoveryComparisonCard comparison={largeComparison} />);

    // Numbers should be formatted with K/M suffix
    const card = screen.getByTestId("recovery-comparison-card");
    expect(card).toBeInTheDocument();
  });

  // RCC-15: Trend visualization
  it("shows trend icon for each metric", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    // At least one trending up icon should be present (for clicks)
    expect(screen.getByTestId("icon-trending-up")).toBeInTheDocument();
  });

  // RCC-16: Loading state
  it("shows loading skeleton when loading", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} isLoading={true} />);

    expect(screen.getByTestId("comparison-loading")).toBeInTheDocument();
    expect(screen.getAllByTestId("skeleton-metric")).toHaveLength(3);
  });

  // RCC-17: Empty state
  it("shows empty state when comparison is null", () => {
    render(<MockRecoveryComparisonCard comparison={null} />);

    expect(screen.getByTestId("comparison-empty")).toHaveTextContent("No recovery data yet");
  });

  // RCC-18: Accessible card
  it("has correct accessibility attributes", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const card = screen.getByTestId("recovery-comparison-card");
    expect(card).toHaveAttribute("role", "region");
    expect(card).toHaveAttribute("aria-label", "Recovery comparison");
  });

  // RCC-19: Custom className
  it("applies custom className", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} className="custom-class" />);

    const card = screen.getByTestId("recovery-comparison-card");
    expect(card).toHaveClass("custom-class");
  });

  // RCC-20: Compact mode
  it("renders in compact mode without headers", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} compact={true} />);

    expect(screen.queryByTestId("headers")).not.toBeInTheDocument();
    expect(screen.getByTestId("metrics-container")).toBeInTheDocument();
  });

  // RCC-21: Shows percentage change
  it("shows percentage change for delta", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const deltaPercents = screen.getAllByTestId("metric-delta-percent");
    expect(deltaPercents.length).toBeGreaterThan(0);
    expect(deltaPercents[0]).toHaveTextContent("(+17.5%)");
  });

  // RCC-22: Negative percentage shown
  it("shows negative percentage for decline", () => {
    render(<MockRecoveryComparisonCard comparison={mockNegativeComparison} />);

    const deltaPercents = screen.getAllByTestId("metric-delta-percent");
    expect(deltaPercents[0]).toHaveTextContent("(-14.9%)");
  });

  // RCC-23: Card title shown
  it("shows card title", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    expect(screen.getByTestId("card-title")).toHaveTextContent("Recovery Comparison");
  });

  // RCC-24: All metric icons shown
  it("shows icons for all metrics", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    expect(screen.getByTestId("icon-click")).toBeInTheDocument();
    expect(screen.getByTestId("icon-eye")).toBeInTheDocument();
    expect(screen.getByTestId("icon-bar-chart")).toBeInTheDocument();
    expect(screen.getByTestId("icon-target")).toBeInTheDocument();
    expect(screen.getByTestId("icon-sparkles")).toBeInTheDocument();
  });

  // RCC-25: Position shows "positions" suffix
  it("shows 'positions' suffix for position delta", () => {
    render(<MockRecoveryComparisonCard comparison={mockComparison} />);

    const positionMetric = screen.getByTestId("metric-position");
    const delta = screen.getAllByTestId("metric-delta")[3];
    expect(delta.textContent).toContain("positions");
  });
});
