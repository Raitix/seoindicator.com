import React from "react";
import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";

const mockUseParams = vi.fn(() => ({ id: "123" }));

vi.mock("next/navigation", () => ({
  useParams: () => mockUseParams(),
}));

vi.mock("@/components/local/LocationReviewsPage", () => ({
  LocationReviewsPage: ({ locationId, siteId }: { locationId: string; siteId?: string }) => (
    <div data-testid="reviews-props" data-location-id={locationId} data-site-id={siteId ?? ""} />
  ),
}));

import StandaloneLocationReviewsPage from "@/app/locations/[id]/reviews/page";
import { generateStaticParams } from "@/app/locations/[id]/layout";

describe("app/locations/[id]/reviews/page", () => {
  beforeEach(() => {
    mockUseParams.mockReturnValue({ id: "123" });
    window.history.replaceState({}, "", "/locations/123/reviews/");
  });

  it("uses the route param when a real location id is already available", async () => {
    render(<StandaloneLocationReviewsPage />);

    expect(await screen.findByTestId("reviews-props")).toHaveAttribute("data-location-id", "123");
    expect(screen.getByTestId("reviews-props")).toHaveAttribute("data-site-id", "");
  });

  it("recovers the numeric location id from the browser pathname when hydration keeps the preview param", async () => {
    mockUseParams.mockReturnValue({ id: "preview" });
    window.history.replaceState({}, "", "/locations/456/reviews/");

    render(<StandaloneLocationReviewsPage />);

    expect(await screen.findByTestId("reviews-props")).toHaveAttribute("data-location-id", "456");
    expect(screen.getByTestId("reviews-props")).toHaveAttribute("data-site-id", "");
  });

  it("exports a preview shell for static export rewrites", () => {
    expect(generateStaticParams()).toEqual([{ id: "preview" }]);
  });
});
