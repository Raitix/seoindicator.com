import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi, beforeEach } from "vitest";

import { LocationDashboardPage } from "@/components/local/LocationDashboardPage";

const mocks = vi.hoisted(() => ({
  toastSuccess: vi.fn(),
  toastError: vi.fn(),
  mutateLocation: vi.fn().mockResolvedValue(undefined),
  mutateInsights: vi.fn().mockResolvedValue(undefined),
  mutateReviewStats: vi.fn().mockResolvedValue(undefined),
  mutateReviews: vi.fn().mockResolvedValue(undefined),
  mutateRankings: vi.fn().mockResolvedValue(undefined),
  mutateAlerts: vi.fn().mockResolvedValue(undefined),
  mutateCompetitors: vi.fn().mockResolvedValue(undefined),
  triggerSync: vi.fn().mockResolvedValue({ ok: true }),
}));

vi.mock("next/link", () => ({
  default: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

vi.mock("recharts", () => ({
  ResponsiveContainer: ({ children }: { children: React.ReactNode }) => <div data-testid="sparkline-container">{children}</div>,
  AreaChart: ({ children }: { children: React.ReactNode }) => <div data-testid="sparkline-chart">{children}</div>,
  Area: () => <div data-testid="sparkline-area" />,
  Tooltip: () => null,
}));

vi.mock("sonner", () => ({
  toast: {
    success: mocks.toastSuccess,
    error: mocks.toastError,
  },
}));

vi.mock("@/lib/hooks/useLocationDashboard", () => ({
  useDashboardLocation: () => ({
    location: {
      id: 123,
      siteId: 7,
      name: "Example Business",
      address: "123 Main St, City",
      phone: "+1 (555) 123-4567",
      website: "https://example.com",
      category: "Marketing agency",
      latitude: null,
      longitude: null,
      isGbpConnected: true,
      gbpLocationId: "gbp-123",
      isActive: true,
      createdAt: "2026-03-01T10:00:00Z",
      updatedAt: "2026-03-08T12:00:00Z",
      reviewStats: null,
      insightsSummary: null,
      rating: 4.2,
      reviewCount: 156,
      packPosition: 2,
      lastSyncAt: "2026-03-08T18:00:00Z",
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateLocation,
  }),
  useLocationInsights: () => ({
    insights: {
      views: { total: 1520, trend: [{ label: "Mon", value: 180 }, { label: "Tue", value: 330 }], breakdown: { search: 980, maps: 540 } },
      searches: { total: 845, trend: [{ label: "Mon", value: 100 }, { label: "Tue", value: 145 }], breakdown: { direct: 300, discovery: 545 } },
      actions: { total: 234, trend: [{ label: "Mon", value: 30 }, { label: "Tue", value: 31 }], breakdown: { website: 90, directions: 75, call: 69 } },
      period: "7d",
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateInsights,
  }),
  useLocationReviewStats: () => ({
    stats: {
      average_rating: 4.2,
      total_count: 156,
      rating_distribution: { 1: 5, 2: 8, 3: 22, 4: 48, 5: 73 },
      sentiment: { positive: 112, negative: 18, neutral: 26 },
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateReviewStats,
  }),
  useLocationReviews: () => ({
    reviews: [
      { id: "r1", author: "John D.", rating: 5, text: "Great service.", date: "2026-03-07T12:00:00Z", sentiment: "positive" },
      { id: "r2", author: "Jane S.", rating: 4, text: "Good experience.", date: "2026-03-06T12:00:00Z", sentiment: "positive" },
      { id: "r3", author: "Bob M.", rating: 3, text: "Average.", date: "2026-03-05T12:00:00Z", sentiment: "neutral" },
    ],
    error: null,
    isLoading: false,
    mutate: mocks.mutateReviews,
  }),
  useLocationRankings: () => ({
    rankings: {
      keywords_in_pack: 12,
      average_position: 2.8,
      top_keywords: [
        { keyword: "seo tools", position: 1, change: 0 },
        { keyword: "keyword tracker", position: 2, change: 1 },
        { keyword: "backlink checker", position: 3, change: -1 },
      ],
      dropped_keywords: [{ keyword: "site audit", previous_position: 3, current_position: 0 }],
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateRankings,
  }),
  useLocationAlerts: () => ({
    alerts: {
      unread_count: 3,
      latest: [
        { id: "a1", type: "ranking_drop", message: 'Position dropped for "site audit"', severity: "warning", timestamp: "2026-03-08T18:00:00Z" },
      ],
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateAlerts,
  }),
  useLocationCompetitors: () => ({
    competitors: [
      { name: "Competitor A", rating: 4.5, review_count: 234, position: 1 },
      { name: "Competitor B", rating: 4.1, review_count: 189, position: 2 },
      { name: "Competitor C", rating: 3.9, review_count: 145, position: 3 },
    ],
    error: null,
    isLoading: false,
    mutate: mocks.mutateCompetitors,
  }),
  useLocationSync: () => ({
    triggerSync: mocks.triggerSync,
  }),
}));

describe("LocationDashboardPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders the full dashboard layout and nested links", () => {
    render(<LocationDashboardPage locationId="123" siteId="7" />);

    expect(screen.getByRole("heading", { level: 1, name: "Example Business" })).toBeInTheDocument();
    expect(screen.getByText("Competitor A")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /refresh dashboard/i })).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /view detailed insights/i })).toHaveAttribute("href", "/sites/7/local/123/insights");
    expect(screen.getByRole("link", { name: /view all reviews/i })).toHaveAttribute("href", "/sites/7/local/123/reviews");
    expect(screen.getByRole("link", { name: /view rankings/i })).toHaveAttribute("href", "/sites/7/local/123/rankings");
    expect(screen.getByRole("link", { name: /view all alerts/i })).toHaveAttribute("href", "/sites/7/local/123/alerts");
  });

  it("triggers refresh and revalidates dashboard data", async () => {
    const user = userEvent.setup();

    render(<LocationDashboardPage locationId="123" siteId="7" />);

    await user.click(screen.getByRole("button", { name: /refresh dashboard/i }));

    await waitFor(() => {
      expect(mocks.triggerSync).toHaveBeenCalledTimes(1);
      expect(mocks.mutateLocation).toHaveBeenCalled();
      expect(mocks.mutateInsights).toHaveBeenCalled();
      expect(mocks.mutateReviewStats).toHaveBeenCalled();
      expect(mocks.mutateReviews).toHaveBeenCalled();
      expect(mocks.mutateRankings).toHaveBeenCalled();
      expect(mocks.mutateAlerts).toHaveBeenCalled();
      expect(mocks.mutateCompetitors).toHaveBeenCalled();
      expect(mocks.toastSuccess).toHaveBeenCalledWith("Location refresh started");
    });
  });
});
