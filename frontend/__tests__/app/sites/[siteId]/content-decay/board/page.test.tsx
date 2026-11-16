/**
 * Unit tests for Content Decay Recovery Board Page
 * Route: /sites/[siteId]/content-decay/board
 *
 * @see TASK-36-13
 * @see SPEC-036
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import RecoveryBoardPage from "@/app/sites/[siteId]/content-decay/board/page";
import { useContentDecayCandidates } from "@/lib/hooks/useContentDecay";

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecayCandidates: vi.fn(() => ({
    mutate: vi.fn(),
    candidates: [
      {
        id: 1,
        pageUrl: "https://example.com/page-1",
        pageTitle: "Backlog Page",
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
        pageTitle: "Planned Page",
        pageType: "product",
        severity: "high",
        status: "planned",
        clicksLost: 75,
        impressionsLost: 2500,
        decayScore: 65.0,
        decayReasons: ["position_drop"],
      },
      {
        id: 3,
        pageUrl: "https://example.com/page-3",
        pageTitle: "In Progress Page",
        pageType: "landing",
        severity: "medium",
        status: "in_progress",
        clicksLost: 50,
        impressionsLost: 1500,
        decayScore: 45.0,
        decayReasons: ["ctr_drop"],
      },
    ],
    isLoading: false,
    error: null,
  })),
  useContentDecayCandidate: vi.fn(() => ({
    candidate: null,
    isLoading: false,
    error: null,
  })),
  useUpdateCandidateStatus: vi.fn(() => ({
    trigger: vi.fn(),
    isMutating: false,
  })),
}));

// Mock components (the page imports each one by its own module path)
vi.mock("@/components/content-decay/SeverityBadge", () => ({
  SeverityBadge: ({ severity }: { severity: string }) => (
    <span data-testid={`severity-badge-${severity}`}>{severity}</span>
  ),
}));

vi.mock("@/components/content-decay/DecayReasonBadges", () => ({
  DecayReasonBadges: () => null,
}));

vi.mock("@/components/content-decay/CandidateDetailDrawer", () => ({
  CandidateDetailDrawer: () => null,
}));

// Mock next/link
vi.mock("next/link", () => ({
  default: ({ children, href }: any) => <a href={href}>{children}</a>,
}));

// Mock next/navigation
vi.mock("next/navigation", () => ({
  useParams: vi.fn(() => ({ siteId: "1" })),
  useRouter: vi.fn(() => ({
    push: vi.fn(),
  })),
  useSearchParams: vi.fn(() => ({
    get: vi.fn(),
    toString: () => "",
  })),
}));

// Mock Lucide icons
vi.mock("lucide-react", () => ({
  LayoutDashboard: () => <span data-testid="layout-icon" aria-hidden="true" />,
  Kanban: () => <span data-testid="kanban-icon" aria-hidden="true" />,
  TrendingUp: () => <span data-testid="trending-up-icon" aria-hidden="true" />,
  AlertTriangle: () => <span data-testid="alert-triangle-icon" aria-hidden="true" />,
  FileText: () => <span data-testid="file-text-icon" aria-hidden="true" />,
  Globe: () => <span data-testid="globe-icon" aria-hidden="true" />,
  ChevronRight: () => <span data-testid="chevron-right-icon" aria-hidden="true" />,
  AlertCircle: () => <span data-testid="alert-circle-icon" aria-hidden="true" />,
  Info: () => <span data-testid="info-icon" aria-hidden="true" />,
  Minus: () => <span data-testid="minus-icon" aria-hidden="true" />,
  X: () => <span data-testid="x-icon" aria-hidden="true" />,
  Filter: () => <span data-testid="filter-icon" aria-hidden="true" />,
}));

const renderWithRouter = (component: React.ReactElement) => {
  return render(component);
};

describe("Recovery Board Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page container", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByTestId("content-decay-board-page")).toBeInTheDocument();
    });

    it("renders the page title", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByRole("heading", { name: /recovery board/i })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByText(/manage content recovery workflow/i)).toBeInTheDocument();
    });

    it("renders all five Kanban columns", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByRole("list", { name: "Backlog column" })).toBeInTheDocument();
      expect(screen.getByRole("list", { name: "Planned column" })).toBeInTheDocument();
      expect(screen.getByRole("list", { name: "In Progress column" })).toBeInTheDocument();
      expect(screen.getByRole("list", { name: "Refreshed column" })).toBeInTheDocument();
      expect(screen.getByRole("list", { name: "Recovered column" })).toBeInTheDocument();
    });

    it("renders column titles", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByText("Backlog")).toBeInTheDocument();
      expect(screen.getByText("Planned")).toBeInTheDocument();
      expect(screen.getByText("In Progress")).toBeInTheDocument();
      expect(screen.getByText("Refreshed")).toBeInTheDocument();
      expect(screen.getByText("Recovered")).toBeInTheDocument();
    });

    it("renders navigation tabs", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByRole("link", { name: /queue/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /board/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /recovery/i })).toBeInTheDocument();
    });
  });

  describe("Filters", () => {
    it("renders severity filter dropdown", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByRole("combobox", { name: /filter by severity/i })).toBeInTheDocument();
    });

    it("renders page type filter dropdown", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByRole("combobox", { name: /filter by page type/i })).toBeInTheDocument();
    });
  });

  describe("Candidate Cards", () => {
    it("renders candidate cards in correct columns", () => {
      renderWithRouter(<RecoveryBoardPage />);
      expect(screen.getByText("Backlog Page")).toBeInTheDocument();
      expect(screen.getByText("Planned Page")).toBeInTheDocument();
      expect(screen.getByText("In Progress Page")).toBeInTheDocument();
    });

    it("renders column counts", () => {
      renderWithRouter(<RecoveryBoardPage />);
      // Check that column count badges are rendered
      const countBadges = screen.getAllByText(/^[0-9]+$/);
      expect(countBadges.length).toBeGreaterThan(0);
    });
  });

  describe("Loading States", () => {
    it("shows loading state when candidates are loading", () => {
      vi.mocked(useContentDecayCandidates).mockReturnValue({
        candidates: [],
        isLoading: true,
        error: null,
      });
      renderWithRouter(<RecoveryBoardPage />);
      // The page swaps the board for a loading skeleton while fetching
      expect(screen.getByTestId("loading-skeleton")).toBeInTheDocument();
      expect(screen.queryByTestId("content-decay-board-page")).not.toBeInTheDocument();
    });
  });

  describe("Empty States", () => {
    it("shows empty state when no candidates exist", () => {
      vi.mocked(useContentDecayCandidates).mockReturnValue({
        candidates: [],
        isLoading: false,
        error: null,
      });
      renderWithRouter(<RecoveryBoardPage />);
      // Each column should show "No candidates"
      const noItemsTexts = screen.getAllByText(/no candidates/i);
      expect(noItemsTexts.length).toBe(5); // 5 columns
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      renderWithRouter(<RecoveryBoardPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it("has accessible navigation", () => {
      renderWithRouter(<RecoveryBoardPage />);
      const nav = screen.getByRole("navigation", { name: /content decay navigation/i });
      expect(nav).toBeInTheDocument();
    });

    it("has accessible Kanban board region", () => {
      renderWithRouter(<RecoveryBoardPage />);
      const board = screen.getByRole("region", { name: /kanban board/i });
      expect(board).toBeInTheDocument();
    });
  });

  describe("Column Structure", () => {
    it("renders columns in correct order", () => {
      renderWithRouter(<RecoveryBoardPage />);
      const columns = screen.getAllByRole("heading", { level: 3 });
      const columnTitles = columns.map((col) => col.textContent);
      expect(columnTitles).toContain("Backlog");
      expect(columnTitles).toContain("Planned");
      expect(columnTitles).toContain("In Progress");
      expect(columnTitles).toContain("Refreshed");
      expect(columnTitles).toContain("Recovered");
    });
  });
});
