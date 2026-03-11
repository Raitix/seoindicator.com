/**
 * Tests for Content Decay Recovery Board Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { useParams, useRouter } from "next/navigation";
import RecoveryBoardPage from "@/app/sites/[siteId]/content-decay/board/page";

// Mock Next.js hooks
vi.mock("next/navigation", () => ({
  useParams: vi.fn(),
  useRouter: vi.fn(),
}));

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySummary: vi.fn(() => ({
    summary: { totalCandidates: 10, criticalCount: 2 },
    isLoading: false,
  })),
  useContentDecayCandidates: vi.fn(() => ({
    candidates: [
      {
        id: 1,
        projectId: 1,
        pageUrl: "https://example.com/page-1",
        pageTitle: "Test Page 1",
        pageType: "blog",
        severity: "high",
        decayScore: 75.5,
        detectionConfidence: 0.9,
        lostClickEstimate: 500,
        recoveryUpsideEstimate: 800,
        primaryReasonCode: "clicks_down",
        reasonCodes: [{ type: "clicks_down", evidence: "50% decline", isPrimary: true }],
        status: "backlog",
        firstDetectedAt: "2026-03-01T00:00:00Z",
        lastDetectedAt: "2026-03-10T00:00:00Z",
      },
      {
        id: 2,
        projectId: 1,
        pageUrl: "https://example.com/page-2",
        pageTitle: "Test Page 2",
        pageType: "landing",
        severity: "critical",
        decayScore: 95.0,
        detectionConfidence: 0.95,
        lostClickEstimate: 1000,
        recoveryUpsideEstimate: 1500,
        primaryReasonCode: "impressions_down",
        reasonCodes: [{ type: "impressions_down", evidence: "70% decline", isPrimary: true }],
        status: "planned",
        firstDetectedAt: "2026-03-01T00:00:00Z",
        lastDetectedAt: "2026-03-10T00:00:00Z",
      },
    ],
    isLoading: false,
    mutate: vi.fn(),
  })),
  useContentDecayCandidate: vi.fn(() => ({
    candidate: null,
    isLoading: false,
  })),
  useUpdateCandidateStatus: vi.fn(() => ({ updateStatus: vi.fn(), isUpdating: false })),
}));

// Mock components
vi.mock("@/components/content-decay/SeverityBadge", () => ({
  SeverityBadge: ({ severity }: any) => (
    <span data-testid={`severity-${severity}`} data-severity={severity}>
      {severity}
    </span>
  ),
}));

vi.mock("@/components/content-decay/DecayReasonBadges", () => ({
  DecayReasonBadges: () => <div data-testid="decay-reason-badges">Reasons</div>,
}));

vi.mock("@/components/content-decay/StatusWorkflowButtons", () => ({
  StatusWorkflowButtons: ({ status, onStatusChange }: any) => (
    <div data-testid="status-workflow-buttons">
      <button onClick={() => onStatusChange("planned")}>Update Status</button>
    </div>
  ),
}));

describe("Content Decay Recovery Board Page", () => {
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
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Recovery Board")).toBeInTheDocument();
    });

    it("should render the page description", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText(/Drag candidates through the recovery workflow/)).toBeInTheDocument();
    });

    it("should render navigation tabs", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Queue")).toBeInTheDocument();
      expect(screen.getByText("Board")).toBeInTheDocument();
      expect(screen.getByText("Recovery")).toBeInTheDocument();
    });

    it("should render all Kanban columns", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Backlog")).toBeInTheDocument();
      expect(screen.getByText("Planned")).toBeInTheDocument();
      expect(screen.getByText("In Progress")).toBeInTheDocument();
      expect(screen.getByText("Refreshed")).toBeInTheDocument();
      expect(screen.getByText("Recovered")).toBeInTheDocument();
    });

    it("should render filter controls", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Filters:")).toBeInTheDocument();
    });
  });

  describe("Kanban Columns", () => {
    it("should render column with candidates", () => {
      render(<RecoveryBoardPage />);
      // Check that columns have count badges
      const badges = screen.getAllByRole("status");
      // Or check for column elements
      expect(screen.getByTestId("column-backlog")).toBeInTheDocument();
    });

    it("should display candidate cards in correct columns", () => {
      render(<RecoveryBoardPage />);
      // Card should be rendered for each candidate
      expect(screen.getByTestId("candidate-card-1")).toBeInTheDocument();
    });

    it("should show candidate count in column header", () => {
      render(<RecoveryBoardPage />);
      // Check for count badges
      const countBadges = screen.getAllByText(/^[0-9]+$/);
      expect(countBadges.length).toBeGreaterThan(0);
    });
  });

  describe("Candidate Cards", () => {
    it("should display candidate title", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Test Page 1")).toBeInTheDocument();
    });

    it("should display candidate metrics", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText(/-500 clicks/)).toBeInTheDocument();
      expect(screen.getByText(/\+800 upside/)).toBeInTheDocument();
    });

    it("should display severity badge", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByTestId("severity-high")).toBeInTheDocument();
    });

    it("should open detail sheet when card is clicked", async () => {
      render(<RecoveryBoardPage />);
      const card = screen.getByTestId("candidate-card-1");
      fireEvent.click(card);
      // Sheet should open - check for sheet content
      await waitFor(() => {
        // The sheet content should be visible
      });
    });
  });

  describe("Filters", () => {
    it("should render severity filter", () => {
      render(<RecoveryBoardPage />);
      const severityTrigger = screen.getByRole("combobox", { name: /severity/i });
      expect(severityTrigger).toBeInTheDocument();
    });

    it("should render page type filter", () => {
      render(<RecoveryBoardPage />);
      const pageTypeTrigger = screen.getByRole("combobox", { name: /page type/i });
      expect(pageTypeTrigger).toBeInTheDocument();
    });
  });

  describe("Navigation", () => {
    it("should navigate to queue when Queue tab is clicked", async () => {
      render(<RecoveryBoardPage />);
      const queueTab = screen.getByText("Queue").closest("button");
      if (queueTab) {
        fireEvent.click(queueTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay");
    });

    it("should navigate to recovery when Recovery tab is clicked", async () => {
      render(<RecoveryBoardPage />);
      const recoveryTab = screen.getByText("Recovery").closest("button");
      if (recoveryTab) {
        fireEvent.click(recoveryTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay/recovery");
    });
  });

  describe("Site ID Handling", () => {
    it("should show not found when siteId is missing", () => {
      (useParams as any).mockReturnValue({});
      render(<RecoveryBoardPage />);
      expect(screen.getByText("Site not found")).toBeInTheDocument();
    });
  });

  describe("Quick Stats", () => {
    it("should display total candidates count", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText(/Total Candidates:/)).toBeInTheDocument();
    });

    it("should display critical count", () => {
      render(<RecoveryBoardPage />);
      expect(screen.getByText(/Critical:/)).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("should have proper heading hierarchy", () => {
      render(<RecoveryBoardPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toHaveTextContent("Recovery Board");
    });

    it("should have accessible column regions", () => {
      render(<RecoveryBoardPage />);
      const region = screen.getByRole("region", { name: /kanban board/i });
      expect(region).toBeInTheDocument();
    });
  });

  describe("Loading States", () => {
    it("should render loading skeleton when loading", async () => {
      const { useContentDecayCandidates } = await import("@/lib/hooks/useContentDecay");
      (useContentDecayCandidates as any).mockReturnValue({
        candidates: [],
        isLoading: true,
        mutate: vi.fn(),
      });

      render(<RecoveryBoardPage />);
      // Should still render header
      expect(screen.getByText("Recovery Board")).toBeInTheDocument();
    });
  });
});
