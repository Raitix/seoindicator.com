import React from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

import AlertsWidget from "@/components/local/AlertsWidget";
import QuickStatsCards from "@/components/local/QuickStatsCards";
import RankingsPreviewSection from "@/components/local/RankingsPreviewSection";
import ReviewsPreviewSection from "@/components/local/ReviewsPreviewSection";
import SyncStatus from "@/components/local/SyncStatus";

vi.mock("next/link", () => ({
  default: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

describe("Location dashboard components", () => {
  it("renders quick stats cards with formatted values", () => {
    render(
      <QuickStatsCards
        stats={{
          views: 1520,
          searches: 845,
          actions: 234,
          rating: 4.2,
          reviewCount: 156,
          keywordsInPack: 12,
          viewTrend: 10,
          searchTrend: -5,
          actionsTrend: 0,
        }}
      />
    );

    expect(screen.getByText("Views")).toBeInTheDocument();
    expect(screen.getByText("Searches")).toBeInTheDocument();
    expect(screen.getByText("Actions")).toBeInTheDocument();
    expect(screen.getByText("Rating")).toBeInTheDocument();
    expect(screen.getByText("Reviews")).toBeInTheDocument();
    expect(screen.getByText("In Pack")).toBeInTheDocument();
    expect(screen.getByText("1.5K")).toBeInTheDocument();
    expect(screen.getByText("4.2 ★")).toBeInTheDocument();
    expect(screen.getByText("12")).toBeInTheDocument();
  });

  it("renders the reviews preview with rating, sentiment, and latest reviews", () => {
    render(
      <ReviewsPreviewSection
        locationId="123"
        stats={{
          average_rating: 4.2,
          total_count: 156,
          rating_distribution: { 1: 5, 2: 8, 3: 22, 4: 48, 5: 73 },
          sentiment: { positive: 112, negative: 18, neutral: 26 },
        }}
        reviews={[
          {
            id: "r1",
            author: "John D.",
            rating: 5,
            text: "Great service.",
            date: "2026-03-07T12:00:00Z",
            sentiment: "positive",
          },
          {
            id: "r2",
            author: "Jane S.",
            rating: 4,
            text: "Good experience.",
            date: "2026-03-06T12:00:00Z",
            sentiment: "positive",
          },
          {
            id: "r3",
            author: "Bob M.",
            rating: 3,
            text: "Average.",
            date: "2026-03-05T12:00:00Z",
            sentiment: "neutral",
          },
        ]}
        linkHref="/locations/123/reviews"
      />
    );

    expect(screen.getByText("4.2")).toBeInTheDocument();
    expect(screen.getByText("156 reviews")).toBeInTheDocument();
    expect(screen.getByText("Positive sentiment")).toBeInTheDocument();
    expect(screen.getByText("John D.")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /view all reviews/i })).toHaveAttribute("href", "/locations/123/reviews");
  });

  it("renders rankings preview with top keywords and dropped keywords", () => {
    render(
      <RankingsPreviewSection
        locationId="123"
        rankings={{
          keywords_in_pack: 12,
          average_position: 2.8,
          top_keywords: [
            { keyword: "seo tools", position: 1, change: 0 },
            { keyword: "keyword tracker", position: 2, change: 1 },
            { keyword: "backlink checker", position: 3, change: -1 },
          ],
          dropped_keywords: [
            { keyword: "site audit", previous_position: 3, current_position: 0 },
          ],
        }}
        linkHref="/locations/123/rankings"
      />
    );

    expect(screen.getByText("Top performing keywords")).toBeInTheDocument();
    expect(screen.getByText("seo tools")).toBeInTheDocument();
    expect(screen.getByText("site audit")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /view rankings/i })).toHaveAttribute("href", "/locations/123/rankings");
  });

  it("renders alerts widget with unread count and latest alerts", () => {
    render(
      <AlertsWidget
        locationId="123"
        alerts={{
          unread_count: 3,
          latest: [
            {
              id: "a1",
              type: "ranking_drop",
              message: 'Position dropped for "site audit"',
              severity: "warning",
              timestamp: "2026-03-08T18:00:00Z",
            },
            {
              id: "a2",
              type: "new_review",
              message: "New 1-star review received",
              severity: "critical",
              timestamp: "2026-03-08T15:00:00Z",
            },
          ],
        }}
        linkHref="/locations/123/alerts"
      />
    );

    expect(screen.getByText("3")).toBeInTheDocument();
    expect(screen.getByText('Position dropped for "site audit"')).toBeInTheDocument();
    expect(screen.getByText("critical")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /view all alerts/i })).toHaveAttribute("href", "/locations/123/alerts");
  });

  it("renders sync status and triggers refresh", () => {
    const onRefresh = vi.fn().mockResolvedValue(undefined);

    render(
      <SyncStatus
        locationId="123"
        lastSyncAt="2026-03-08T18:00:00Z"
        onRefresh={onRefresh}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: /refresh location data/i }));
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });
});
