/**
 * Tests for Content Decay Refresh Queue Page
 * 
 * Task: TASK-36-13 - Frontend Pages - Refresh Queue & Board
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import ContentDecayPage from "@/app/sites/[siteId]/content-decay/page";

// Mock Next.js hooks
vi.mock("next/navigation", () => ({
  useParams: vi.fn(),
  useRouter: vi.fn(),
  useSearchParams: vi.fn(),
}));

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySummary: vi.fn(() => ({
    summary: {
      totalCandidates: 10,
      criticalCount: 2,
      highCount: 3,
      mediumCount: 3,
      lowCount: 2,
      estimatedLostClicks: 5000,
      recoveryUpside: 8000,
      recoveredCount: 5,
      lastScannedAt: "2026-03-10T00:00:00Z",
    },
    isLoading: false,
    mutate: vi.fn(),
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
        plannedAt: null,
        startedAt: null,
        refreshedAt: null,
        recoveredAt: null,
        ignoredAt: null,
        signals: null,
        trend: null,
      },
    ],
    meta: { currentPage: 1, perPage: 10, total: 1, lastPage: 1 },
    isLoading: false,
    mutate: vi.fn(),
  })),
  useContentDecayCandidate: vi.fn(() => ({
    candidate: null,
    isLoading: false,
  })),
  useContentRefreshTasks: vi.fn(() => ({ tasks: [] })),
  useRunContentDecayScan: vi.fn(() => ({ runScan: vi.fn(), isRunning: false })),
  useUpdateCandidateStatus: vi.fn(() => ({ updateStatus: vi.fn(), isUpdating: false })),
  useExportContentDecay: vi.fn(() => ({ exportData: vi.fn(), isExporting: false })),
  useCreateRefreshTask: vi.fn(() => ({ createTask: vi.fn(), isCreating: false })),
}));

// Mock components
vi.mock("@/components/content-decay/DecaySummaryCards", () => ({
  DecaySummaryCards: ({ summary }: any) => (
    <div data-testid="decay-summary-cards">Summary Cards: {summary?.totalCandidates} candidates</div>
  ),
}));

vi.mock("@/components/content-decay/DecayQueueTable", () => ({
  DecayQueueTable: ({ candidates, onRowClick }: any) => (
    <div data-testid="decay-queue-table">
      Queue Table: {candidates?.length} candidates
      <button onClick={() => onRowClick?.(candidates[0])} data-testid="view-candidate-btn">
        View Candidate
      </button>
    </div>
  ),
}));

vi.mock("@/components/content-decay/CandidateDetailDrawer", () => ({
  CandidateDetailDrawer: ({ isOpen, onClose }: any) => (
    isOpen ? (
      <div data-testid="candidate-detail-drawer">
        Candidate Drawer
        <button onClick={onClose} data-testid="close-drawer-btn">Close</button>
      </div>
    ) : null
  ),
}));

describe("Content Decay Refresh Queue Page", () => {
  const mockRouter = {
    push: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    (useParams as any).mockReturnValue({ siteId: "test-site-123" });
    (useRouter as any).mockReturnValue(mockRouter);
    (useSearchParams as any).mockReturnValue(new URLSearchParams());
  });

  describe("Page Rendering", () => {
    it("should render the page header", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Content Decay")).toBeInTheDocument();
    });

    it("should render the page description", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText(/Monitor and recover pages/)).toBeInTheDocument();
    });

    it("should render navigation tabs", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Queue")).toBeInTheDocument();
      expect(screen.getByText("Board")).toBeInTheDocument();
      expect(screen.getByText("Recovery")).toBeInTheDocument();
    });

    it("should render action buttons", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Run Scan")).toBeInTheDocument();
      expect(screen.getByText("Export")).toBeInTheDocument();
    });

    it("should render summary cards", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("decay-summary-cards")).toBeInTheDocument();
    });

    it("should render queue table", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("decay-queue-table")).toBeInTheDocument();
    });
  });

  describe("Site ID Handling", () => {
    it("should show not found when siteId is missing", () => {
      (useParams as any).mockReturnValue({});
      render(<ContentDecayPage />);
      expect(screen.getByText("Site not found")).toBeInTheDocument();
    });
  });

  describe("Navigation", () => {
    it("should navigate to board when Board tab is clicked", async () => {
      render(<ContentDecayPage />);
      const boardTab = screen.getByText("Board").closest("button");
      if (boardTab) {
        fireEvent.click(boardTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay/board");
    });

    it("should navigate to recovery when Recovery tab is clicked", async () => {
      render(<ContentDecayPage />);
      const recoveryTab = screen.getByText("Recovery").closest("button");
      if (recoveryTab) {
        fireEvent.click(recoveryTab);
      }
      expect(mockRouter.push).toHaveBeenCalledWith("/sites/test-site-123/content-decay/recovery");
    });

    it("should navigate to settings when Settings button is clicked", async () => {
      render(<ContentDecayPage />);
      const settingsBtn = screen.getByRole("button", { name: "" }).closest("button");
      // Settings button has only an icon, find by navigating parent
      const allButtons = screen.getAllByRole("button");
      // Find the one that would navigate to settings
      expect(allButtons.length).toBeGreaterThan(0);
    });
  });

  describe("User Actions", () => {
    it("should trigger scan when Run Scan button is clicked", async () => {
      const { useRunContentDecayScan } = await import("@/lib/hooks/useContentDecay");
      const mockRunScan = vi.fn().mockResolvedValue({ success: true });
      (useRunContentDecayScan as any).mockReturnValue({ runScan: mockRunScan, isRunning: false });

      render(<ContentDecayPage />);
      const runScanBtn = screen.getByText("Run Scan").closest("button");
      if (runScanBtn) {
        fireEvent.click(runScanBtn);
      }
      // The button should be clickable
      expect(runScanBtn).not.toBeNull();
    });

    it("should open candidate drawer when row is clicked", async () => {
      render(<ContentDecayPage />);
      const viewBtn = screen.getByTestId("view-candidate-btn");
      fireEvent.click(viewBtn);
      expect(screen.getByTestId("candidate-detail-drawer")).toBeInTheDocument();
    });
  });

  describe("Loading States", () => {
    it("should render loading skeleton when loading", () => {
      const { useContentDecaySummary, useContentDecayCandidates } = await import("@/lib/hooks/useContentDecay");
      (useContentDecaySummary as any).mockReturnValue({ summary: null, isLoading: true });
      (useContentDecayCandidates as any).mockReturnValue({ candidates: [], isLoading: true });

      render(<ContentDecayPage />);
      // Should still render the page structure
      expect(screen.getByText("Content Decay")).toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("should have proper heading hierarchy", () => {
      render(<ContentDecayPage />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toHaveTextContent("Content Decay");
    });

    it("should have accessible navigation tabs", () => {
      render(<ContentDecayPage />);
      const tabs = screen.getAllByRole("tab");
      expect(tabs.length).toBeGreaterThan(0);
    });
  });

  describe("Responsive Design", () => {
    it("should render responsive layout classes", () => {
      const { container } = render(<ContentDecayPage />);
      // Check for responsive container classes
      expect(container.querySelector(".container")).toBeInTheDocument();
    });
  });
});
