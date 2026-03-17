/**
 * Unit tests for Content Decay Refresh Queue Page
 * Route: /sites/[siteId]/content-decay
 *
 * @see TASK-36-13
 * @see SPEC-036
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ContentDecayPage from "@/app/sites/[siteId]/content-decay/page";

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySummary: vi.fn(() => ({
    summary: {
      totalCandidates: 25,
      criticalCount: 5,
      highCount: 10,
      totalClicksLost: 1500,
      avgDecayScore: 45.5,
    },
    isLoading: false,
    error: null,
  })),
  useContentDecayCandidates: vi.fn(() => ({
    candidates: [
      {
        id: 1,
        pageUrl: "https://example.com/page-1",
        pageTitle: "Test Page 1",
        pageType: "blog",
        severity: "critical",
        status: "backlog",
        clicksLost: 150,
        impressionsLost: 5000,
        decayScore: 85.5,
        decayReasons: ["traffic_drop"],
      },
      {
        id: 2,
        pageUrl: "https://example.com/page-2",
        pageTitle: "Test Page 2",
        pageType: "product",
        severity: "high",
        status: "planned",
        clicksLost: 75,
        impressionsLost: 2500,
        decayScore: 65.0,
        decayReasons: ["position_drop"],
      },
    ],
    meta: {
      currentPage: 1,
      perPage: 25,
      total: 25,
      totalPages: 1,
    },
    isLoading: false,
    error: null,
  })),
  useContentDecayCandidate: vi.fn(() => ({
    candidate: null,
    isLoading: false,
    error: null,
  })),
  useRunContentDecayScan: vi.fn(() => ({
    trigger: vi.fn(),
    isMutating: false,
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
    get: vi.fn(),
  })),
}));

// Mock Lucide icons - comprehensive mock for all used icons
vi.mock("lucide-react", () => {
  const createIcon = (name: string) => () => <span data-testid={`${name.toLowerCase()}-icon`}>{name}</span>;
  return {
    RefreshCw: createIcon("RefreshCw"),
    Download: createIcon("Download"),
    Settings: createIcon("Settings"),
    LayoutDashboard: createIcon("LayoutDashboard"),
    Kanban: createIcon("Kanban"),
    TrendingUp: createIcon("TrendingUp"),
    TrendingDown: createIcon("TrendingDown"),
    AlertTriangle: createIcon("AlertTriangle"),
    AlertCircle: createIcon("AlertCircle"),
    Info: createIcon("Info"),
    Minus: createIcon("Minus"),
    FileText: createIcon("FileText"),
    ChevronDown: createIcon("ChevronDown"),
    ChevronRight: createIcon("ChevronRight"),
    ExternalLink: createIcon("ExternalLink"),
    Clock: createIcon("Clock"),
    X: createIcon("X"),
    Filter: createIcon("Filter"),
    Search: createIcon("Search"),
    ArrowUpDown: createIcon("ArrowUpDown"),
    Calendar: createIcon("Calendar"),
    Eye: createIcon("Eye"),
    MousePointer: createIcon("MousePointer"),
  };
});

describe("Content Decay Refresh Queue Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page container", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("content-decay-page")).toBeInTheDocument();
    });

    it("renders the page title", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("heading", { name: /content decay/i })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText(/identify and refresh underperforming content/i)).toBeInTheDocument();
    });

    it("renders navigation tabs", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("link", { name: /queue/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /board/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /recovery/i })).toBeInTheDocument();
    });

    it("renders action buttons", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("button", { name: /run scan/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /export/i })).toBeInTheDocument();
    });

    it("renders settings link", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("link", { name: /settings/i })).toBeInTheDocument();
    });
  });

  describe("Summary Section", () => {
    it("renders summary section with aria-label", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("region", { name: /summary statistics/i })).toBeInTheDocument();
    });
  });

  describe("Queue Table Section", () => {
    it("renders queue section with aria-label", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("region", { name: /decay candidates queue/i })).toBeInTheDocument();
    });
  });

  describe("User Actions", () => {
    it("run scan button is clickable", async () => {
      const user = userEvent.setup();
      render(<ContentDecayPage />);
      const runScanButton = screen.getByRole("button", { name: /run scan/i });
      await user.click(runScanButton);
      expect(runScanButton).toBeInTheDocument();
    });

    it("export button is clickable", async () => {
      const user = userEvent.setup();
      render(<ContentDecayPage />);
      const exportButton = screen.getByRole("button", { name: /export/i });
      await user.click(exportButton);
      expect(exportButton).toBeInTheDocument();
    });
  });

  describe("Loading States", () => {
    it("shows loading fallback when data is loading", () => {
      vi.mocked(require("@/lib/hooks/useContentDecay").useContentDecaySummary).mockReturnValue({
        summary: null,
        isLoading: true,
        error: null,
      });
      render(<ContentDecayPage />);
      expect(screen.getByTestId("content-decay-page")).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      render(<ContentDecayPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it("has accessible navigation", () => {
      render(<ContentDecayPage />);
      const nav = screen.getByRole("navigation", { name: /content decay views/i });
      expect(nav).toBeInTheDocument();
    });

    it("has accessible button labels", () => {
      render(<ContentDecayPage />);
      expect(screen.getByRole("button", { name: /run manual scan/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /export to csv/i })).toBeInTheDocument();
    });
  });
});
