import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { LocationReviewsPage } from "@/components/local/LocationReviewsPage";

const mocks = vi.hoisted(() => ({
  toastSuccess: vi.fn(),
  toastError: vi.fn(),
  mutateLocation: vi.fn().mockResolvedValue(undefined),
  mutateStats: vi.fn().mockResolvedValue(undefined),
  mutateReviews: vi.fn().mockResolvedValue(undefined),
  triggerSync: vi.fn().mockResolvedValue({ job_id: "job-123" }),
  submitResponse: vi.fn().mockResolvedValue({ response_id: "resp-123" }),
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
      rating: 4.4,
      reviewCount: 156,
      packPosition: 2,
      lastSyncAt: "2026-03-08T18:00:00Z",
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateLocation,
  }),
}));

vi.mock("@/lib/hooks/useLocationReviewsPage", () => ({
  useLocationReviewStatsDetailed: () => ({
    stats: {
      averageRating: 4.4,
      previousRating: 4.2,
      totalCount: 156,
      previousCount: 148,
      ratingDistribution: { 1: 4, 2: 8, 3: 19, 4: 44, 5: 81 },
      sentimentBreakdown: { positive: 120, neutral: 22, negative: 14 },
      responseRate: 63.5,
      averageResponseTimeHours: 18,
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateStats,
  }),
  useLocationReviewPage: () => ({
    reviews: [
      {
        id: "review-1",
        numericId: 1,
        gbpReviewId: "gbp-review-1",
        author: "John Doe",
        avatarUrl: null,
        rating: 5,
        text: "Excellent service and friendly staff.",
        sentiment: "positive",
        reviewedAt: "2026-03-07T12:00:00Z",
        hasResponse: false,
        response: null,
        googleUrl: "https://example.com/review/1",
        isLocalGuide: true,
        reviewCount: 24,
      },
    ],
    meta: {
      currentPage: 1,
      perPage: 20,
      total: 1,
      lastPage: 1,
    },
    error: null,
    isLoading: false,
    mutate: mocks.mutateReviews,
  }),
  useSubmitLocationReviewResponse: () => ({
    submitResponse: mocks.submitResponse,
  }),
  useSyncLocationReviews: () => ({
    triggerSync: mocks.triggerSync,
  }),
}));

describe("LocationReviewsPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders the reviews workspace with breadcrumb and review actions", () => {
    render(<LocationReviewsPage locationId="123" siteId="7" />);

    expect(screen.getByRole("heading", { level: 1, name: /reviews management/i })).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /back to dashboard/i })).toHaveAttribute("href", "/sites/7/local/123");
    expect(screen.getByText("John Doe")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /sync reviews/i })).toBeInTheDocument();
  });

  it("opens the response modal and submits a review response", async () => {
    const user = userEvent.setup();
    render(<LocationReviewsPage locationId="123" siteId="7" />);

    await user.click(screen.getByRole("button", { name: /^respond$/i }));
    expect(screen.getByRole("dialog", { name: /respond to review/i })).toBeInTheDocument();

    await user.type(screen.getByLabelText(/your response/i), "Thanks for the feedback!");
    await user.click(screen.getByRole("button", { name: /post response/i }));

    await waitFor(() => {
      expect(mocks.submitResponse).toHaveBeenCalledWith(1, "Thanks for the feedback!");
      expect(mocks.toastSuccess).toHaveBeenCalledWith("Review response posted");
    });
  });
});
