import React from "react";
import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";

const mockUseParams = vi.fn(() => ({ siteId: "7", locationId: "123" }));

vi.mock("next/navigation", () => ({
  useParams: () => mockUseParams(),
}));

vi.mock("@/components/local/LocationRankingsPage", () => ({
  LocationRankingsPage: ({ locationId, siteId }: { locationId: string; siteId?: string }) => (
    <div data-testid="rankings-props" data-location-id={locationId} data-site-id={siteId ?? ""} />
  ),
}));

import SiteLocationRankingsPage from "@/app/sites/[siteId]/local/[locationId]/rankings/page";
import { generateStaticParams } from "@/app/sites/[siteId]/local/[locationId]/layout";

describe("app/sites/[siteId]/local/[locationId]/rankings/page", () => {
  beforeEach(() => {
    mockUseParams.mockReturnValue({ siteId: "7", locationId: "123" });
    window.history.replaceState({}, "", "/sites/7/local/123/rankings/");
  });

  it("uses the route params when real ids are already available", async () => {
    render(<SiteLocationRankingsPage />);

    expect(await screen.findByTestId("rankings-props")).toHaveAttribute("data-site-id", "7");
    expect(screen.getByTestId("rankings-props")).toHaveAttribute("data-location-id", "123");
  });

  it("recovers numeric site and location ids from the browser pathname when hydration keeps preview params", async () => {
    mockUseParams.mockReturnValue({ siteId: "preview", locationId: "preview" });
    window.history.replaceState({}, "", "/sites/8/local/456/rankings/");

    render(<SiteLocationRankingsPage />);

    expect(await screen.findByTestId("rankings-props")).toHaveAttribute("data-site-id", "8");
    expect(screen.getByTestId("rankings-props")).toHaveAttribute("data-location-id", "456");
  });

  it("exports a preview shell for static export rewrites", () => {
    expect(generateStaticParams()).toEqual([{ siteId: "preview", locationId: "preview" }]);
  });
});
