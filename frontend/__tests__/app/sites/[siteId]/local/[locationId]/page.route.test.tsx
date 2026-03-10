import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

const mockUseParams = vi.fn(() => ({ siteId: "7", locationId: "123" }));

vi.mock("next/navigation", () => ({
  useParams: () => mockUseParams(),
}));

vi.mock("@/components/local/LocationDashboardPage", () => ({
  LocationDashboardPage: ({ locationId, siteId }: { locationId: string; siteId?: string }) => (
    <div data-testid="dashboard-props" data-location-id={locationId} data-site-id={siteId ?? ""} />
  ),
}));

import SiteLocationDashboardPage from "@/app/sites/[siteId]/local/[locationId]/page";
import { generateStaticParams } from "@/app/sites/[siteId]/local/[locationId]/layout";

describe("app/sites/[siteId]/local/[locationId]/page", () => {
  it("reads the runtime site and location ids from useParams", () => {
    render(<SiteLocationDashboardPage />);

    expect(screen.getByTestId("dashboard-props")).toHaveAttribute("data-site-id", "7");
    expect(screen.getByTestId("dashboard-props")).toHaveAttribute("data-location-id", "123");
  });

  it("exports a preview shell for static export rewrites", () => {
    expect(generateStaticParams()).toEqual([{ siteId: "preview", locationId: "preview" }]);
  });
});
