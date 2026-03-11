/**
 * Tests for Content Decay Recovery Report Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { useParams, useRouter } from "next/navigation";
import RecoveryReportPage from "@/app/sites/[siteId]/content-decay/recovery/page";

// Mock Next.js hooks
vi.mock("next/navigation", () => ({
  useParams: vi.fn(),
  useRouter: vi.fn(),
}));

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecayRecovery: vi.fn(() => ({
    recovery: {
      summary: {
        totalRecovered: 15,
        totalClicksRecovered: 3500,
        avgRecoveryRate: 0.72,
        avgDaysToRecovery: 14.5,
      },
      timeline: [
        { date: "2026-03-01", recovered: 2, clicks: 500 },
        { date: "2026-03-02", recovered: 3, clicks: 700 },
        { date: "2026-03-03", recovered: 1, clicks: 200 },
      ],
      comparisons: [
        {
          id: 1,
          pageUrl: "https://example.com/page-1",
          pageTitle: "Recovered Page 1",
          pageType: "blog",
          before: { clicks: 100, impressions: 1000, ctr: 0.1, position: 10 },
          after: { clicks: 250, impressions: 2000, ctr: 0.125, position: 5 },
          delta: { clicks: 150, impressions: 1000, ctr: 0.025, position: -5 },
        },
      ],
      stories: [
        {
          id: 1,
          pageUrl: "https://example.com/page-1",
          pageTitle: "Recovered Page 1",
          clicksRecovered: 150,
          recoveryRate: 0.75,
          daysToRecovery: 12,
          summary: "Updated content and improved rankings",
        },
      ],
    },
    isLoading: false,
  })),
}));

// Mock components
vi.mock("@/components/content-decay/RecoveryComparisonCard", () => ({
  RecoveryComparisonCard: ({ comparison }: any) => (
    <div data-testid="recovery-comparison-card">
      Comparison Card: {comparison ? "Has Data" : "No Data"}
    </div>
  ),
}));

describe("Content Decay Recovery Report Page", () => {
  const mockRouter = {
    push: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    (useParams as any).mockReturnValue({ siteId: "test-site-123" });
    (useRouter as any).mockReturnValue(mockRouter);
  });

  describe("Page Rendering", () => {
    it("should render the page header", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Recovery Report")).toBeInTheDocument();
    });

    it("should render the page description", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText(/Track content recovery progress/)).toBeInTheDocument();
    });

    it("should render navigation tabs", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Queue")).toBeInTheDocument();
      expect(screen.getByText("Board")).toBeInTheDocument();
      expect(screen.getByText("Recovery")).toBeInTheDocument();
    });

    it("should render date range filter", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Last 30 days")).toBeInTheDocument();
    });

    it("should render export button", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Export PDF")).toBeInTheDocument();
    });
  });

  describe("Summary Statistics", () => {
    it("should display total recovered count", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Total Recovered")).toBeInTheDocument();
    });

    it("should display clicks recovered", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Clicks Recovered")).toBeInTheDocument();
    });

    it("should display average recovery rate", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Avg Recovery Rate")).toBeInTheDocument();
    });

    it("should display average days to recovery", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Avg Days to Recovery")).toBeInTheDocument();
    });

    it("should show summary stats section", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("summary-stats")).toBeInTheDocument();
    });
  });

  describe("Timeline Chart", () => {
    it("should render timeline chart section", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Recovery Timeline")).toBeInTheDocument();
    });

    it("should render chart bars", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("timeline-chart")).toBeInTheDocument();
    });
  });

  describe("Comparison Table", () => {
    it("should render comparison table section", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Before/After Comparison")).toBeInTheDocument();
    });

    it("should display recovered page titles", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Recovered Page 1")).toBeInTheDocument();
    });

    it("should display before/after click values", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("100")).toBeInTheDocument();
      expect(screen.getByText("250")).toBeInTheDocument();
    });
  });

  describe("Recovery Stories", () => {
    it("should render recovery stories section", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Recovery Stories")).toBeInTheDocument();
    });

    it("should display story cards", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("recovery-stories")).toBeInTheDocument();
    });

    it("should display clicks recovered in story", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText(/\+150/)).toBeInTheDocument();
    });
  });

  describe("Date Range Filter", () => {
    it("should have 7 days option", async () => {
      render(<RecoveryReportPage />);
      const trigger = screen.getByRole("combobox", { name: /date range/i });
      fireEvent.click(trigger);
      await waitFor(() => {
        expect(screen.getByText("Last 7 days")).toBeInTheDocument();
      });
    });

    it("should have 90 days option", async () => {
      render(<RecoveryReportPage />);
      const trigger = screen.getByRole("combobox", { name: /date range/i });
      fireEvent.click(trigger);
      await waitFor(() => {
        expect(screen.getByText("Last 90 days")).toBeInTheDocument();
      });
    });

    it("should have all time option", async () => {
      render(<RecoveryReportPage />);
      const trigger = screen.getByRole("combobox", { name: /date range/i });
      fireEvent.click(trigger);
      await waitFor(() => {
        expect(screen.getByText("All time")).toBeInTheDocument();
      });
    });
  });

  describe("Navigation", () => {
    it("should navigate to queue when Queue tab is clicked", async () => {
      render(<RecoveryReportPage />);
      const queueTab = screen.getByText("Queue").closest("button");
      if (queueTab) {
        fireEvent.click(queueTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay");
    });

    it("should navigate to board when Board tab is clicked", async () => {
      render(<RecoveryReportPage />);
      const boardTab = screen.getByText("Board").closest("button");
      if (boardTab) {
        fireEvent.click(boardTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay/board");
    });
  });

  describe("Site ID Handling", () => {
    it("should show not found when siteId is missing", () => {
      (useParams as any).mockReturnValue({});
      render(<RecoveryReportPage />);
      expect(screen.getByText("Site not found")).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("should have proper heading hierarchy", () => {
      render(<RecoveryReportPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toHaveTextContent("Recovery Report");
    });

    it("should have accessible table headers", () => {
      render(<RecoveryReportPage />);
      const tableHeaders = screen.getAllByRole("columnheader");
      expect(tableHeaders.length).toBeGreaterThan(0);
    });
  });

  describe("Loading States", () => {
    it("should render loading skeleton when loading", async () => {
      const { useContentDecayRecovery } = await import("@/lib/hooks/useContentDecay");
      (useContentDecayRecovery as any).mockReturnValue({
        recovery: null,
        isLoading: true,
      });

      render(<RecoveryReportPage />);
      expect(screen.getByTestId("page-skeleton")).toBeInTheDocument();
    });
  });

  describe("Empty States", () => {
    it("should handle empty timeline data", async () => {
      const { useContentDecayRecovery } = await import("@/lib/hooks/useContentDecay");
      (useContentDecayRecovery as any).mockReturnValue({
        recovery: {
          summary: { totalRecovered: 0, totalClicksRecovered: 0, avgRecoveryRate: 0, avgDaysToRecovery: 0 },
          timeline: [],
          comparisons: [],
          stories: [],
        },
        isLoading: false,
      });

      render(<RecoveryReportPage />);
      expect(screen.getByText(/No timeline data available/)).toBeInTheDocument();
    });

    it("should handle empty comparisons", async () => {
      const { useContentDecayRecovery } = await import("@/lib/hooks/useContentDecay");
      (useContentDecayRecovery as any).mockReturnValue({
        recovery: {
          summary: { totalRecovered: 0, totalClicksRecovered: 0, avgRecoveryRate: 0, avgDaysToRecovery: 0 },
          timeline: [],
          comparisons: [],
          stories: [],
        },
        isLoading: false,
      });

      render(<RecoveryReportPage />);
      expect(screen.getByText(/No recovery comparisons available/)).toBeInTheDocument();
    });

    it("should handle empty stories", async () => {
      const { useContentDecayRecovery } = await import("@/lib/hooks/useContentDecay");
      (useContentDecayRecovery as any).mockReturnValue({
        recovery: {
          summary: { totalRecovered: 0, totalClicksRecovered: 0, avgRecoveryRate: 0, avgDaysToRecovery: 0 },
          timeline: [],
          comparisons: [],
          stories: [],
        },
        isLoading: false,
      });

      render(<RecoveryReportPage />);
      expect(screen.getByText(/No recovery stories available/)).toBeInTheDocument();
    });
  });
});
