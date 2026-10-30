import React from "react";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import { LocationRankingsPage } from "@/components/local/LocationRankingsPage";

const mocks = vi.hoisted(() => ({
  toastSuccess: vi.fn(),
  toastError: vi.fn(),
  mutateLocation: vi.fn().mockResolvedValue(undefined),
  mutateRankings: vi.fn().mockResolvedValue(undefined),
  mutateHistory: vi.fn().mockResolvedValue(undefined),
  mutateQuota: vi.fn().mockResolvedValue(undefined),
  mutateCompetitors: vi.fn().mockResolvedValue(undefined),
  checkAll: vi.fn().mockResolvedValue({ data: { keywords_queued: 2 } }),
  checkKeyword: vi.fn().mockResolvedValue({ data: { keywords_queued: 1 } }),
  createCompetitor: vi.fn().mockResolvedValue({ success: true }),
  deleteCompetitor: vi.fn().mockResolvedValue(true),
}));

vi.mock("next/link", () => ({
  default: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

vi.mock("sonner", () => ({
  toast: {
    success: mocks.toastSuccess,
    error: mocks.toastError,
  },
}));

vi.mock("@/components/charts/RankingHistoryChart", () => ({
  __esModule: true,
  default: ({ keywordName, data }: { keywordName: string; data: Array<{ date: string; position: number | null }> }) => (
    <div data-testid="ranking-history-chart" data-keyword={keywordName} data-points={data.length} />
  ),
}));

vi.mock("@/lib/hooks/useLocationDashboard", () => ({
  useDashboardLocation: () => ({
    location: {
      id: 123,
      siteId: 7,
      name: "Example Business",
      address: "123 Main St",
      phone: null,
      website: null,
      category: "Pizza restaurant",
      latitude: null,
      longitude: null,
      isGbpConnected: true,
      gbpLocationId: "gbp-123",
      isActive: true,
      createdAt: "2026-03-01T10:00:00Z",
      updatedAt: "2026-03-08T12:00:00Z",
      reviewStats: null,
      insightsSummary: null,
      rating: 4.6,
      reviewCount: 187,
      packPosition: 2,
      lastSyncAt: "2026-03-10T11:00:00Z",
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateLocation,
  }),
}));

vi.mock("@/lib/hooks/useLocationRankingsPage", () => ({
  useLocationRankingsData: () => ({
    rankings: [
      {
        keywordId: 1,
        keyword: "best pizza near me",
        localPackPosition: 2,
        localOrganicPosition: 5,
        lastChecked: "2026-03-10T10:00:00Z",
        competitorsInPack: [
          { name: "Competitor Alpha", position: 1, rating: 4.8, reviewCount: 240 },
          { name: "Example Business", position: 2, rating: 4.6, reviewCount: 187 },
          { name: "Competitor Beta", position: 3, rating: 4.4, reviewCount: 120 },
        ],
      },
      {
        keywordId: 2,
        keyword: "pizza delivery",
        localPackPosition: null,
        localOrganicPosition: 12,
        lastChecked: "2026-03-09T10:00:00Z",
        competitorsInPack: [],
      },
    ],
    error: null,
    isLoading: false,
    mutate: mocks.mutateRankings,
  }),
  useLocationRankingHistory: () => ({
    history: [
      {
        keywordId: 1,
        keyword: "best pizza near me",
        snapshots: [
          { date: "2026-03-08", localPackPosition: 5, localOrganicPosition: 8, competitorsInPack: [] },
          { date: "2026-03-09", localPackPosition: 4, localOrganicPosition: 7, competitorsInPack: [] },
          { date: "2026-03-10", localPackPosition: 2, localOrganicPosition: 5, competitorsInPack: [] },
        ],
      },
      {
        keywordId: 2,
        keyword: "pizza delivery",
        snapshots: [
          { date: "2026-03-09", localPackPosition: 7, localOrganicPosition: 10, competitorsInPack: [] },
          { date: "2026-03-10", localPackPosition: null, localOrganicPosition: 12, competitorsInPack: [] },
        ],
      },
    ],
    error: null,
    isLoading: false,
    mutate: mocks.mutateHistory,
  }),
  useLocationRankingQuota: () => ({
    quota: {
      monthlyLimit: 50,
      usedThisMonth: 12,
      remaining: 38,
      resetDate: "2026-04-01T00:00:00Z",
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateQuota,
  }),
  useLocationTrackedCompetitors: () => ({
    competitors: [
      { id: 7, name: "Competitor Alpha", googlePlaceId: "place-1", address: null, rating: 4.8, reviewCount: 240 },
      { id: 8, name: "Competitor Beta", googlePlaceId: "place-2", address: null, rating: 4.4, reviewCount: 120 },
    ],
    error: null,
    isLoading: false,
    mutate: mocks.mutateCompetitors,
  }),
  useCheckAllLocationRankings: () => ({
    checkAll: mocks.checkAll,
  }),
  useCheckSingleLocationRanking: () => ({
    checkKeyword: mocks.checkKeyword,
  }),
  useCreateTrackedCompetitor: () => ({
    createCompetitor: mocks.createCompetitor,
  }),
  useDeleteTrackedCompetitor: () => ({
    deleteCompetitor: mocks.deleteCompetitor,
  }),
}));

describe("LocationRankingsPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.checkAll.mockResolvedValue({ data: { keywords_queued: 2 } });
    mocks.createCompetitor.mockResolvedValue({ success: true });

    Object.defineProperty(URL, "createObjectURL", {
      value: vi.fn(() => "blob:rankings"),
      writable: true,
    });
    Object.defineProperty(URL, "revokeObjectURL", {
      value: vi.fn(() => undefined),
      writable: true,
    });
    Object.defineProperty(HTMLAnchorElement.prototype, "click", {
      value: vi.fn(),
      writable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("renders the rankings workspace with quota, alerts, table, and competitors", () => {
    render(<LocationRankingsPage locationId="123" siteId="7" />);

    const table = screen.getByRole("table");

    expect(screen.getByRole("heading", { level: 1, name: /local rankings tracker/i })).toBeInTheDocument();
    expect(screen.getByText("12 / 50")).toBeInTheDocument();
    expect(within(table).getByText("best pizza near me")).toBeInTheDocument();
    expect(screen.getAllByText("Competitor Alpha").length).toBeGreaterThan(0);
    expect(screen.getByTestId("ranking-history-chart")).toHaveAttribute("data-keyword", "best pizza near me");
    expect(screen.getByRole("link", { name: /back to dashboard/i })).toHaveAttribute("href", "/sites/7/local/123");
  });

  it("queues a full ranking check", async () => {
    const user = userEvent.setup();
    render(<LocationRankingsPage locationId="123" siteId="7" />);

    await user.click(screen.getByRole("button", { name: /check all rankings/i }));

    await waitFor(() => {
      expect(mocks.checkAll).toHaveBeenCalledTimes(1);
      expect(mocks.mutateQuota).toHaveBeenCalled();
      expect(mocks.mutateRankings).toHaveBeenCalled();
      expect(mocks.mutateHistory).toHaveBeenCalled();
      expect(mocks.toastSuccess).toHaveBeenCalledWith("Ranking check queued for 2 keyword(s)");
    });
  });

  it("filters rows and exports the filtered result to CSV", async () => {
    const user = userEvent.setup();
    render(<LocationRankingsPage locationId="123" siteId="7" />);

    await user.type(screen.getByRole("textbox", { name: /search tracked keywords/i }), "delivery");

    const table = screen.getByRole("table");
    expect(within(table).queryByText("best pizza near me")).not.toBeInTheDocument();
    expect(within(table).getByText("pizza delivery")).toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: /export csv/i }));

    expect(URL.createObjectURL).toHaveBeenCalled();
    expect(mocks.toastSuccess).toHaveBeenCalledWith("Rankings exported as CSV");
  });

  it("adds a competitor from the dialog", async () => {
    const user = userEvent.setup();
    render(<LocationRankingsPage locationId="123" siteId="7" />);

    await user.click(screen.getByRole("button", { name: /^add competitor$/i }));

    const dialog = await screen.findByRole("dialog");
    await user.type(within(dialog).getByLabelText(/competitor name/i), "Competitor Gamma");
    await user.type(within(dialog).getByLabelText(/google place id/i), "place-3");
    await user.click(within(dialog).getByRole("button", { name: /^add competitor$/i }));

    await waitFor(() => {
      expect(mocks.createCompetitor).toHaveBeenCalledWith({
        name: "Competitor Gamma",
        googlePlaceId: "place-3",
        notes: "",
      });
      expect(mocks.toastSuccess).toHaveBeenCalledWith("Competitor added to tracking");
    });
  });
});
