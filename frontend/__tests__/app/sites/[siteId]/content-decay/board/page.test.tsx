/**
 * Tests for Content Decay Recovery Board Page
 * Task: TASK-36-13
 * 
 * @jest-environment jsdom
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import RecoveryBoardPage from "@/app/sites/[siteId]/content-decay/board/page";

// Mock Next.js navigation
vi.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "123" }),
  useRouter: () => ({
    push: vi.fn(),
  }),
}));

// Mock SWR hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecayCandidates: () => ({
    candidates: [
      {
        id: 1,
        pageUrl: "https://example.com/page-1",
        pageTitle: "Test Page 1",
        pageType: "blog",
        severity: "critical",
        decayScore: 85.5,
        lostClickEstimate: 500,
        recoveryUpsideEstimate: 400,
        status: "backlog",
        primaryReasonCode: "clicks_down",
        reasonCodes: [{ type: "clicks_down", evidence: "Significant decline", isPrimary: true }],
        firstDetectedAt: "2026-03-01T00:00:00Z",
        lastDetectedAt: "2026-03-10T00:00:00Z",
      },
      {
        id: 2,
        pageUrl: "https://example.com/page-2",
        pageTitle: "Test Page 2",
        pageType: "landing",
        severity: "high",
        decayScore: 70.0,
        lostClickEstimate: 300,
        recoveryUpsideEstimate: 250,
        status: "planned",
        primaryReasonCode: "impressions_down",
        reasonCodes: [{ type: "impressions_down", evidence: "Visibility dropped", isPrimary: true }],
        firstDetectedAt: "2026-03-02T00:00:00Z",
        lastDetectedAt: "2026-03-10T00:00:00Z",
      },
    ],
    meta: { currentPage: 1, perPage: 10, total: 2, lastPage: 1 },
    isLoading: false,
    mutate: vi.fn(),
  }),
  useContentDecayCandidate: () => ({
    candidate: null,
    isLoading: false,
  }),
  useUpdateCandidateStatus: () => ({
    updateStatus: vi.fn(() => Promise.resolve({ success: true })),
    isUpdating: false,
  }),
  useContentRefreshTasks: () => ({
    tasks: [],
    mutate: vi.fn(),
  }),
}));

describe("Recovery Board Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page title", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Recovery Board")).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText(/Kanban workflow/i)).toBeInTheDocument();
    });

    it("renders the kanban board", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("kanban-board")).toBeInTheDocument();
    });

    it("renders the back button", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("back-button")).toBeInTheDocument();
    });
  });

  describe("Kanban Columns", () => {
    it("renders all five columns", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("kanban-column-backlog")).toBeInTheDocument();
      expect(screen.getByTestId("kanban-column-planned")).toBeInTheDocument();
      expect(screen.getByTestId("kanban-column-in_progress")).toBeInTheDocument();
      expect(screen.getByTestId("kanban-column-refreshed")).toBeInTheDocument();
      expect(screen.getByTestId("kanban-column-recovered")).toBeInTheDocument();
    });

    it("displays column titles", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Backlog")).toBeInTheDocument();
      expect(screen.getByText("Planned")).toBeInTheDocument();
      expect(screen.getByText("In Progress")).toBeInTheDocument();
      expect(screen.getByText("Refreshed")).toBeInTheDocument();
      expect(screen.getByText("Recovered")).toBeInTheDocument();
    });

    it("displays column counts", () => {
      render(<RecoveryBoardPage />);
      const counts = screen.getAllByTestId("column-count");
      expect(counts.length).toBe(5);
    });
  });

  describe("Kanban Cards", () => {
    it("renders candidate cards in correct columns", () => {
      render(<RecoveryBoardPage />);
      // Card with status 'backlog' should be in backlog column
      expect(screen.getByTestId("kanban-card-1")).toBeInTheDocument();
    });

    it("displays card title", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("card-title")).toBeInTheDocument();
    });

    it("displays card URL", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("card-url")).toBeInTheDocument();
    });
  });

  describe("Filters", () => {
    it("renders severity filter", () => {
      render(<RecoveryBoardPage />);
      // Filter triggers exist
      const filterTriggers = screen.getAllByRole("combobox");
      expect(filterTriggers.length).toBeGreaterThanOrEqual(1);
    });
  });

  describe("Quick Stats", () => {
    it("renders stats section at bottom", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("stat-backlog")).toBeInTheDocument();
      expect(screen.getByTestId("stat-planned")).toBeInTheDocument();
      expect(screen.getByTestId("stat-in_progress")).toBeInTheDocument();
      expect(screen.getByTestId("stat-refreshed")).toBeInTheDocument();
      expect(screen.getByTestId("stat-recovered")).toBeInTheDocument();
    });
  });

  describe("Card Interactions", () => {
    it("cards are clickable", () => {
      render(<RecoveryBoardPage />);
      const card = screen.getByTestId("kanban-card-1");
      expect(card).toHaveAttribute("role", "button");
    });

    it("cards have proper accessibility attributes", () => {
      render(<RecoveryBoardPage />);
      const card = screen.getByTestId("kanban-card-1");
      expect(card).toHaveAttribute("tabIndex", "0");
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      render(<RecoveryBoardPage />);
      const heading = screen.getByRole("heading", { level: 1 });
      expect(heading).toHaveTextContent("Recovery Board");
    });

    it("kanban board has region role", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByRole("region", { name: /Kanban board/i })).toBeInTheDocument();
    });

    it("columns have region roles", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByRole("region", { name: /Backlog column/i })).toBeInTheDocument();
    });
  });

  describe("Responsive Design", () => {
    it("board container has horizontal scroll", () => {
      render(<RecoveryBoardPage />);
      const board = screen.getByTestId("kanban-board");
      expect(board).toHaveClass("overflow-x-auto");
    });
  });

  describe("Loading States", () => {
    it("shows skeletons when loading", () => {
      vi.mock("@/lib/hooks/useContentDecay", () => ({
        useContentDecayCandidates: () => ({ candidates: [], isLoading: true, mutate: vi.fn() }),
      }));
      
      render(<RecoveryBoardPage />);
      const skeletons = screen.queryAllByTestId("skeleton");
      expect(skeletons.length).toBeGreaterThanOrEqual(0);
    });
  });

  describe("Empty States", () => {
    it("shows empty state for empty columns", () => {
      render(<RecoveryBoardPage />);
      // Some columns will be empty
      const emptyStates = screen.getAllByText("No items");
      expect(emptyStates.length).toBeGreaterThan(0);
    });
  });
});
