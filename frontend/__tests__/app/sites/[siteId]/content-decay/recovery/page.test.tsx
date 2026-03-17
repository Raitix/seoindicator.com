/**
 * Unit tests for Content Decay Recovery Report Page
 * Route: /sites/[siteId]/content-decay/recovery
 *
 * @see TASK-36-13
 * @see SPEC-036
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { BrowserRouter } from "react-router-dom";
import RecoveryReportPage from "@/app/sites/[siteId]/content-decay/recovery/page";

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecayRecovery: vi.fn(() => ({
    recovery: {
      summary: {
        totalRecovered: 45,
        totalClicksRecovered: 2500,
        avgRecoveryRate: 72.5,
        avgDaysToRecovery: 14,
      },
      timeline: [
        { date: "2026-03-01", recovered: 5, clicks: 250 },
        { date: "2026-03-02", recovered: 8, clicks: 400 },
        { date: "2026-03-03", recovered: 6, clicks: 320 },
      ],
      comparisons: [
        {
          id: 1,
          pageUrl: "https://example.com/page-1",
          pageTitle: "Recovered Page 1",
          pageType: "blog",
          before: { clicks: 100, impressions: 5000, ctr: 2.0, position: 15 },
          after: { clicks: 250, impressions: 8000, ctr: 3.1, position: 8 },
          delta: { clicks: 150, impressions: 3000, ctr: 1.1, position: -7 },
        },
        {
          id: 2,
          pageUrl: "https://example.com/page-2",
          pageTitle: "Recovered Page 2",
          pageType: "product",
          before: { clicks: 50, impressions: 2000, ctr: 2.5, position: 20 },
          after: { clicks: 120, impressions: 4000, ctr: 3.0, position: 12 },
          delta: { clicks: 70, impressions: 2000, ctr: 0.5, position: -8 },
        },
      ],
      stories: [
        {
          id: 1,
          pageUrl: "https://example.com/story-1",
          pageTitle: "Success Story 1",
          clicksRecovered: 150,
          recoveryRate: 85,
          daysToRecovery: 10,
          summary: "Updated content with fresh keywords and improved internal linking.",
        },
      ],
    },
    isLoading: false,
    error: null,
  })),
  useExportContentDecay: vi.fn(() => ({
    trigger: vi.fn(),
    isMutating: false,
  })),
}));

// Mock next/navigation
vi.mock("next/navigation", () => ({
  useParams: vi.fn(() => ({ siteId: "1" })),
  useRouter: vi.fn(() => ({
    push: vi.fn(),
  })),
  useSearchParams: vi.fn(() => ({
    get: vi.fn(() => "30d"),
    toString: () => "range=30d",
  })),
}));

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  LayoutDashboard: () => <span data-testid="layout-icon">LayoutDashboard</span>,
  Columns: () => <span data-testid="columns-icon">Columns</span>,
  BarChart3: () => <span data-testid="chart-icon">BarChart3</span>,
  Download: () => <span data-testid="download-icon">Download</span>,
  TrendingUp: () => <span data-testid="trending-icon">TrendingUp</span>,
  Calendar: () => <span data-testid="calendar-icon">Calendar</span>,
  Target: () => <span data-testid="target-icon">Target</span>,
}));

const renderWithRouter = (component: React.ReactElement) => {
  return render(<BrowserRouter>{component}</BrowserRouter>);
};

describe("Recovery Report Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page container", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByTestId("recovery-report-page")).toBeInTheDocument();
    });

    it("renders the page title", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("heading", { name: /recovery report/i })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText(/track content refresh outcomes/i)).toBeInTheDocument();
    });

    it("renders navigation tabs", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("link", { name: /queue/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /board/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /recovery/i })).toBeInTheDocument();
    });

    it("renders export PDF button", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("button", { name: /export pdf/i })).toBeInTheDocument();
    });
  });

  describe("Summary Statistics", () => {
    it("renders summary section", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("region", { name: /recovery summary/i })).toBeInTheDocument();
    });

    it("renders total recovered card", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText(/total recovered/i)).toBeInTheDocument();
    });

    it("renders clicks recovered card", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText(/clicks recovered/i)).toBeInTheDocument();
    });

    it("renders avg recovery rate card", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText(/avg recovery rate/i)).toBeInTheDocument();
    });

    it("renders avg days to recovery card", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText(/avg days to recovery/i)).toBeInTheDocument();
    });
  });

  describe("Timeline Chart", () => {
    it("renders timeline section", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("region", { name: /recovery timeline/i })).toBeInTheDocument();
    });

    it("renders timeline chart heading", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("heading", { name: /recovery timeline/i })).toBeInTheDocument();
    });

    it("renders timeline chart", () => {
      renderWithRouter(<RecoveryReportPage />);
      const chart = screen.getByRole("img", { name: /recovery timeline chart/i });
      expect(chart).toBeInTheDocument();
    });
  });

  describe("Comparison Table", () => {
    it("renders comparison section", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("region", { name: /before and after comparison/i })).toBeInTheDocument();
    });

    it("renders comparison table heading", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("heading", { name: /before & after comparison/i })).toBeInTheDocument();
    });

    it("renders table headers", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("columnheader", { name: /page/i })).toBeInTheDocument();
      expect(screen.getByRole("columnheader", { name: /before clicks/i })).toBeInTheDocument();
      expect(screen.getByRole("columnheader", { name: /after clicks/i })).toBeInTheDocument();
      expect(screen.getByRole("columnheader", { name: /recovery/i })).toBeInTheDocument();
    });

    it("renders comparison data", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText("Recovered Page 1")).toBeInTheDocument();
      expect(screen.getByText("Recovered Page 2")).toBeInTheDocument();
    });
  });

  describe("Recovery Stories", () => {
    it("renders stories section", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("region", { name: /recovery stories/i })).toBeInTheDocument();
    });

    it("renders stories heading", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("heading", { name: /recovery stories/i })).toBeInTheDocument();
    });

    it("renders story cards", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText("Success Story 1")).toBeInTheDocument();
    });
  });

  describe("Date Range Filter", () => {
    it("renders date range dropdown", () => {
      renderWithRouter(<RecoveryReportPage />);
      // The select is rendered with proper accessibility
      expect(screen.getByLabelText(/date range/i)).toBeInTheDocument();
    });

    it("has date range options", () => {
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByRole("option", { name: /last 7 days/i })).toBeInTheDocument();
      expect(screen.getByRole("option", { name: /last 30 days/i })).toBeInTheDocument();
      expect(screen.getByRole("option", { name: /last 90 days/i })).toBeInTheDocument();
      expect(screen.getByRole("option", { name: /all time/i })).toBeInTheDocument();
    });
  });

  describe("Loading States", () => {
    it("shows loading state when data is loading", () => {
      vi.mocked(require("@/lib/hooks/useContentDecay").useContentDecayRecovery).mockReturnValue({
        recovery: null,
        isLoading: true,
        error: null,
      });
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByTestId("recovery-report-page")).toBeInTheDocument();
    });
  });

  describe("Empty States", () => {
    it("shows empty state for stories when none exist", () => {
      vi.mocked(require("@/lib/hooks/useContentDecay").useContentDecayRecovery).mockReturnValue({
        recovery: {
          summary: { totalRecovered: 0, totalClicksRecovered: 0, avgRecoveryRate: 0, avgDaysToRecovery: 0 },
          timeline: [],
          comparisons: [],
          stories: [],
        },
        isLoading: false,
        error: null,
      });
      renderWithRouter(<RecoveryReportPage />);
      expect(screen.getByText(/no recovery stories yet/i)).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      renderWithRouter(<RecoveryReportPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it("has accessible navigation", () => {
      renderWithRouter(<RecoveryReportPage />);
      const nav = screen.getByRole("navigation", { name: /content decay views/i });
      expect(nav).toBeInTheDocument();
    });

    it("has accessible table", () => {
      renderWithRouter(<RecoveryReportPage />);
      const table = screen.getByRole("table");
      expect(table).toBeInTheDocument();
    });
  });
});
