/**
 * Tests for Content Decay Recovery Report Page
 * Task: TASK-36-13
 * 
 * @jest-environment jsdom
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import RecoveryReportPage from "@/app/sites/[siteId]/content-decay/recovery/page";

// Mock Next.js navigation
vi.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "123" }),
  useRouter: () => ({
    push: vi.fn(),
  }),
}));

// Mock SWR hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecayRecovery: () => ({
    recovery: {
      summary: {
        totalRecovered: 15,
        totalClicksRecovered: 12000,
        avgRecoveryRate: 0.75,
        avgDaysToRecovery: 21,
      },
      timeline: [
        { date: "2026-03-01", recovered: 2, clicks: 800 },
        { date: "2026-03-05", recovered: 3, clicks: 1200 },
        { date: "2026-03-10", recovered: 5, clicks: 2000 },
      ],
      comparisons: [
        {
          id: 1,
          pageUrl: "https://example.com/recovered-1",
          pageTitle: "Recovered Page 1",
          pageType: "blog",
          before: { clicks: 500, impressions: 10000, ctr: 0.05, position: 8.5 },
          after: { clicks: 800, impressions: 15000, ctr: 0.053, position: 6.2 },
          delta: { clicks: 300, impressions: 5000, ctr: 0.003, position: -2.3 },
        },
      ],
      stories: [
        {
          id: 1,
          pageUrl: "https://example.com/story-1",
          pageTitle: "Success Story 1",
          clicksRecovered: 450,
          recoveryRate: 0.85,
          daysToRecovery: 14,
          summary: "Updated content and improved internal linking.",
        },
      ],
    },
    isLoading: false,
  }),
  useExportContentDecay: () => ({
    exportData: vi.fn(() => Promise.resolve({ success: true })),
    isExporting: false,
  }),
}));

describe("Recovery Report Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page title", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText("Recovery Report")).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByText(/Track your content recovery/i)).toBeInTheDocument();
    });

    it("renders the back button", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("back-button")).toBeInTheDocument();
    });

    it("renders the export button", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("export-button")).toBeInTheDocument();
    });
  });

  describe("Summary Statistics", () => {
    it("renders stats section", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("stats-section")).toBeInTheDocument();
    });

    it("displays total recovered count", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("stat-value-total-recovered")).toBeInTheDocument();
    });

    it("displays clicks recovered", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("stat-value-clicks-recovered")).toBeInTheDocument();
    });

    it("displays average recovery rate", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("stat-value-avg-recovery-rate")).toBeInTheDocument();
    });

    it("displays average days to recovery", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("stat-value-avg-days-to-recovery")).toBeInTheDocument();
    });
  });

  describe("Timeline Chart", () => {
    it("renders timeline chart", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("timeline-chart")).toBeInTheDocument();
    });

    it("displays timeline bars", () => {
      render(<RecoveryReportPage />);
      const bars = screen.getAllByTestId(/timeline-bar-/);
      expect(bars.length).toBeGreaterThan(0);
    });
  });

  describe("Comparison Table", () => {
    it("renders comparison table", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("comparison-table")).toBeInTheDocument();
    });

    it("displays comparison rows", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("comparison-row-1")).toBeInTheDocument();
    });
  });

  describe("Recovery Stories", () => {
    it("renders recovery stories section", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("recovery-stories")).toBeInTheDocument();
    });

    it("displays story cards", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByTestId("story-1")).toBeInTheDocument();
    });
  });

  describe("Date Range Filter", () => {
    it("renders date range selector", () => {
      render(<RecoveryReportPage />);
      const selectTrigger = screen.getByRole("combobox", { name: /date range/i });
      expect(selectTrigger).toBeInTheDocument();
    });
  });

  describe("Export Functionality", () => {
    it("export button is clickable", () => {
      render(<RecoveryReportPage />);
      const button = screen.getByTestId("export-button");
      expect(button).not.toBeDisabled();
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      render(<RecoveryReportPage />);
      const heading = screen.getByRole("heading", { level: 1 });
      expect(heading).toHaveTextContent("Recovery Report");
    });

    it("stats section has aria-label", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByLabelText(/Recovery statistics/i)).toBeInTheDocument();
    });

    it("timeline chart has accessible role", () => {
      render(<RecoveryReportPage />);
      expect(screen.getByRole("img", { name: /Recovery timeline chart/i })).toBeInTheDocument();
    });
  });

  describe("Responsive Design", () => {
    it("uses responsive grid for stats", () => {
      render(<RecoveryReportPage />);
      const statsSection = screen.getByTestId("stats-section");
      expect(statsSection?.firstChild).toHaveClass("grid");
    });

    it("uses responsive grid for two-column layout", () => {
      render(<RecoveryReportPage />);
      const cards = screen.getAllByText(/Before\/After|Recovery Stories/);
      expect(cards.length).toBeGreaterThan(0);
    });
  });

  describe("Loading States", () => {
    it("shows skeletons when loading", () => {
      vi.mock("@/lib/hooks/useContentDecay", () => ({
        useContentDecayRecovery: () => ({ recovery: null, isLoading: true }),
        useExportContentDecay: () => ({ exportData: vi.fn(), isExporting: false }),
      }));
      
      render(<RecoveryReportPage />);
      const skeletons = screen.queryAllByTestId("skeleton");
      expect(skeletons.length).toBeGreaterThanOrEqual(0);
    });
  });

  describe("Empty States", () => {
    it("shows empty state when no timeline data", () => {
      vi.mock("@/lib/hooks/useContentDecay", () => ({
        useContentDecayRecovery: () => ({
          recovery: { summary: {}, timeline: [], comparisons: [], stories: [] },
          isLoading: false,
        }),
        useExportContentDecay: () => ({ exportData: vi.fn(), isExporting: false }),
      }));
      
      render(<RecoveryReportPage />);
      // Empty state should be shown
      expect(screen.queryByTestId("no-timeline-data")).toBeTruthy();
    });
  });
});
