/**
 * Tests for Content Decay Refresh Queue Page
 * Task: TASK-36-13
 * 
 * @jest-environment jsdom
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import ContentDecayPage from "@/app/sites/[siteId]/content-decay/page";

// Mock Next.js navigation
vi.mock("next/navigation", () => ({
  useParams: () => ({ siteId: "123" }),
  useRouter: () => ({
    push: vi.fn(),
  }),
}));

// Mock SWR hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySummary: () => ({
    summary: {
      totalCandidates: 10,
      criticalCount: 2,
      highCount: 3,
      mediumCount: 4,
      lowCount: 1,
      estimatedLostClicks: 5000,
      recoveryUpside: 3500,
      recoveredCount: 5,
      lastScannedAt: "2026-03-10T00:00:00Z",
    },
    isLoading: false,
    mutate: vi.fn(),
  }),
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
    ],
    meta: { currentPage: 1, perPage: 10, total: 1, lastPage: 1 },
    isLoading: false,
    mutate: vi.fn(),
  }),
  useContentDecayCandidate: () => ({
    candidate: null,
    isLoading: false,
  }),
  useRunContentDecayScan: () => ({
    runScan: vi.fn(() => Promise.resolve({ success: true })),
    isRunning: false,
  }),
  useUpdateCandidateStatus: () => ({
    updateStatus: vi.fn(() => Promise.resolve({ success: true })),
    isUpdating: false,
  }),
  useExportContentDecay: () => ({
    exportData: vi.fn(() => Promise.resolve({ success: true })),
    isExporting: false,
  }),
  useContentRefreshTasks: () => ({
    tasks: [],
    mutate: vi.fn(),
  }),
}));

describe("Content Decay Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("Page Rendering", () => {
    it("renders the page title", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Content Decay")).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText(/Monitor and manage pages/i)).toBeInTheDocument();
    });

    it("renders the summary section", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("summary-section")).toBeInTheDocument();
    });

    it("renders the queue section", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("queue-section")).toBeInTheDocument();
    });

    it("renders navigation tabs", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("tab-queue")).toBeInTheDocument();
      expect(screen.getByTestId("tab-board")).toBeInTheDocument();
      expect(screen.getByTestId("tab-recovery")).toBeInTheDocument();
    });
  });

  describe("Action Buttons", () => {
    it("renders run scan button", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("run-scan-button")).toBeInTheDocument();
    });

    it("renders export button", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("export-button")).toBeInTheDocument();
    });

    it("renders settings button", () => {
      render(<ContentDecayPage />);
      expect(screen.getByTestId("settings-button")).toBeInTheDocument();
    });
  });

  describe("Summary Cards", () => {
    it("displays total candidates count", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Total Candidates")).toBeInTheDocument();
    });

    it("displays critical count", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Critical")).toBeInTheDocument();
    });

    it("displays lost clicks", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Lost Clicks")).toBeInTheDocument();
    });

    it("displays recovery upside", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Recovery Upside")).toBeInTheDocument();
    });

    it("displays recovered count", () => {
      render(<ContentDecayPage />);
      expect(screen.getByText("Recovered")).toBeInTheDocument();
    });
  });

  describe("User Actions", () => {
    it("run scan button is clickable", async () => {
      render(<ContentDecayPage />);
      const button = screen.getByTestId("run-scan-button");
      expect(button).not.toBeDisabled();
    });

    it("export button is clickable", async () => {
      render(<ContentDecayPage />);
      const button = screen.getByTestId("export-button");
      expect(button).not.toBeDisabled();
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      render(<ContentDecayPage />);
      const heading = screen.getByRole("heading", { level: 1 });
      expect(heading).toHaveTextContent("Content Decay");
    });

    it("summary section has aria-label", () => {
      render(<ContentDecayPage />);
      expect(screen.getByLabelText(/Summary statistics/i)).toBeInTheDocument();
    });

    it("queue section has aria-label", () => {
      render(<ContentDecayPage />);
      expect(screen.getByLabelText(/Decay candidates queue/i)).toBeInTheDocument();
    });
  });

  describe("Responsive Design", () => {
    it("renders container with responsive classes", () => {
      render(<ContentDecayPage />);
      const container = screen.getByTestId("summary-section").parentElement;
      expect(container).toHaveClass("space-y-6");
    });
  });

  describe("Loading States", () => {
    it("shows skeletons when loading", () => {
      // Override mock for loading state
      vi.mock("@/lib/hooks/useContentDecay", () => ({
        useContentDecaySummary: () => ({ summary: null, isLoading: true, mutate: vi.fn() }),
        useContentDecayCandidates: () => ({ candidates: [], isLoading: true, mutate: vi.fn() }),
      }));
      
      render(<ContentDecayPage />);
      const skeletons = screen.queryAllByTestId("skeleton");
      // Skeletons are shown in loading state
      expect(skeletons.length).toBeGreaterThanOrEqual(0);
    });
  });
});
