/**
 * Unit tests for Content Decay Refresh Queue Page
 * Route: /sites/[siteId]/content-decay
 *
 * The route module (app/sites/[siteId]/content-decay/page.tsx) is a thin
 * async server-component wrapper. The markup under test lives in the
 * ContentDecayClient component it renders, so the client component is
 * rendered directly.
 *
 * @see TASK-36-13
 * @see SPEC-036
 */

import React from "react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ContentDecayClient from "@/app/sites/[siteId]/content-decay/ContentDecayClient";
import {
  useContentDecayCandidates,
  useContentDecaySummary,
  useExportContentDecay,
  useRunContentDecayScan,
} from "@/lib/hooks/useContentDecay";

const runScanSpy = vi.fn();
const exportDataSpy = vi.fn();
const mutateCandidatesSpy = vi.fn();

const mockSummary = {
  totalCandidates: 25,
  criticalCount: 5,
  highCount: 10,
  mediumCount: 6,
  lowCount: 4,
  estimatedLostClicks: 1500,
  recoveryUpside: 3200,
  recoveredCount: 3,
  lastScannedAt: "2026-03-10T10:00:00Z",
};

const mockCandidates = [
  {
    id: 1,
    projectId: 1,
    pageUrl: "https://example.com/page-1",
    pageTitle: "Test Page 1",
    pageType: "blog" as const,
    severity: "critical" as const,
    decayScore: 85.5,
    detectionConfidence: 0.94,
    lostClickEstimate: 150,
    recoveryUpsideEstimate: 400,
    primaryReasonCode: "clicks_down" as const,
    reasonCodes: [{ type: "clicks_down" as const, evidence: "Clicks fell 40%" }],
    status: "backlog" as const,
    firstDetectedAt: "2026-03-01T10:00:00Z",
    lastDetectedAt: "2026-03-10T10:00:00Z",
    plannedAt: null,
    startedAt: null,
    refreshedAt: null,
    recoveredAt: null,
    ignoredAt: null,
    signals: null,
    trend: null,
  },
  {
    id: 2,
    projectId: 1,
    pageUrl: "https://example.com/page-2",
    pageTitle: "Test Page 2",
    pageType: "product" as const,
    severity: "high" as const,
    decayScore: 65,
    detectionConfidence: 0.88,
    lostClickEstimate: 75,
    recoveryUpsideEstimate: 220,
    primaryReasonCode: "avg_position_down" as const,
    reasonCodes: [{ type: "avg_position_down" as const, evidence: "Position fell" }],
    status: "planned" as const,
    firstDetectedAt: "2026-03-02T10:00:00Z",
    lastDetectedAt: "2026-03-10T10:00:00Z",
    plannedAt: null,
    startedAt: null,
    refreshedAt: null,
    recoveredAt: null,
    ignoredAt: null,
    signals: null,
    trend: null,
  },
];

const mockMeta = {
  currentPage: 1,
  perPage: 25,
  total: 25,
  lastPage: 1,
};

// Mock hooks
vi.mock("@/lib/hooks/useContentDecay", () => ({
  useContentDecaySummary: vi.fn(),
  useContentDecayCandidates: vi.fn(),
  useRunContentDecayScan: vi.fn(),
  useExportContentDecay: vi.fn(),
}));

// The candidate drawer is a Radix sheet with its own test file; stub it so
// this suite stays focused on the page shell.
vi.mock("@/components/content-decay/CandidateDetailDrawer", () => ({
  CandidateDetailDrawer: () => null,
}));

// Mock next/link
vi.mock("next/link", () => ({
  default: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

// Mock next/navigation
vi.mock("next/navigation", () => ({
  useParams: vi.fn(() => ({ siteId: "1" })),
  useRouter: vi.fn(() => ({
    push: vi.fn(),
  })),
  useSearchParams: vi.fn(() => new URLSearchParams()),
}));

// Mock sonner toasts
vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

describe("Content Decay Refresh Queue Page", () => {
  beforeEach(() => {
    vi.clearAllMocks();

    vi.mocked(useContentDecaySummary).mockReturnValue({
      summary: mockSummary,
      isLoading: false,
      error: null,
      mutate: vi.fn(),
    });

    vi.mocked(useContentDecayCandidates).mockReturnValue({
      candidates: mockCandidates,
      meta: mockMeta,
      isLoading: false,
      error: null,
      mutate: mutateCandidatesSpy,
    });

    vi.mocked(useRunContentDecayScan).mockReturnValue({
      runScan: runScanSpy,
      isRunning: false,
    });

    vi.mocked(useExportContentDecay).mockReturnValue({
      exportData: exportDataSpy,
      isExporting: false,
    });

    runScanSpy.mockResolvedValue({ success: true });
    exportDataSpy.mockResolvedValue({ success: true });
  });

  describe("Page Rendering", () => {
    it("renders the page container", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByTestId("content-decay-page")).toBeInTheDocument();
    });

    it("renders the page title", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("heading", { name: /content decay/i })).toBeInTheDocument();
    });

    it("renders the page description", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByText(/identify and recover declining content/i)).toBeInTheDocument();
    });

    it("renders navigation tabs", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("link", { name: /queue/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /board/i })).toBeInTheDocument();
      expect(screen.getByRole("link", { name: /recovery/i })).toBeInTheDocument();
    });

    it("renders action buttons", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("button", { name: /run manual scan/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /export to csv/i })).toBeInTheDocument();
    });

    it("renders settings link", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("link", { name: /settings/i })).toBeInTheDocument();
    });

    it("points the settings link at the site content decay settings", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("link", { name: /settings/i })).toHaveAttribute(
        "href",
        "/sites/1/content-decay/settings"
      );
    });
  });

  describe("Summary Section", () => {
    it("renders summary section with aria-label", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("region", { name: "Summary statistics" })).toBeInTheDocument();
    });
  });

  describe("Queue Table Section", () => {
    it("renders queue section with aria-label", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("region", { name: /decay candidates queue/i })).toBeInTheDocument();
    });

    it("renders the queued candidates in the queue table", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByText("Test Page 1")).toBeInTheDocument();
      expect(screen.getByText("Test Page 2")).toBeInTheDocument();
    });
  });

  describe("User Actions", () => {
    it("run scan button is clickable", async () => {
      const user = userEvent.setup();
      render(<ContentDecayClient siteId="1" />);
      const runScanButton = screen.getByRole("button", { name: /run manual scan/i });
      await user.click(runScanButton);
      expect(runScanButton).toBeInTheDocument();
    });

    it("run scan button triggers the scan and refreshes the queue", async () => {
      const user = userEvent.setup();
      render(<ContentDecayClient siteId="1" />);
      await user.click(screen.getByRole("button", { name: /run manual scan/i }));
      expect(runScanSpy).toHaveBeenCalled();
      expect(mutateCandidatesSpy).toHaveBeenCalled();
    });

    it("export button is clickable", async () => {
      const user = userEvent.setup();
      render(<ContentDecayClient siteId="1" />);
      const exportButton = screen.getByRole("button", { name: /export to csv/i });
      await user.click(exportButton);
      expect(exportButton).toBeInTheDocument();
    });

    it("export button requests a csv export", async () => {
      const user = userEvent.setup();
      render(<ContentDecayClient siteId="1" />);
      await user.click(screen.getByRole("button", { name: /export to csv/i }));
      expect(exportDataSpy).toHaveBeenCalledWith("csv");
    });
  });

  describe("Loading States", () => {
    it("shows loading fallback when data is loading", () => {
      vi.mocked(useContentDecaySummary).mockReturnValue({
        summary: null,
        isLoading: true,
        error: null,
        mutate: vi.fn(),
      });

      render(<ContentDecayClient siteId="1" />);

      expect(screen.getByTestId("loading-skeleton")).toBeInTheDocument();
      expect(screen.queryByTestId("content-decay-page")).not.toBeInTheDocument();
    });
  });

  describe("Accessibility", () => {
    it("has proper heading hierarchy", () => {
      render(<ContentDecayClient siteId="1" />);
      const h1 = screen.getByRole("heading", { level: 1 });
      expect(h1).toBeInTheDocument();
    });

    it("has accessible navigation", () => {
      render(<ContentDecayClient siteId="1" />);
      const nav = screen.getByRole("navigation", { name: /content decay navigation/i });
      expect(nav).toBeInTheDocument();
    });

    it("has accessible button labels", () => {
      render(<ContentDecayClient siteId="1" />);
      expect(screen.getByRole("button", { name: /run manual scan/i })).toBeInTheDocument();
      expect(screen.getByRole("button", { name: /export to csv/i })).toBeInTheDocument();
    });
  });
});
