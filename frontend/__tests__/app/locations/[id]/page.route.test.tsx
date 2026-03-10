import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

const mockUseParams = vi.fn(() => ({ id: "123" }));

vi.mock("next/navigation", () => ({
  useParams: () => mockUseParams(),
}));

vi.mock("@/components/local/LocationDashboardPage", () => ({
  LocationDashboardPage: ({ locationId, siteId }: { locationId: string; siteId?: string }) => (
    <div data-testid="dashboard-props" data-location-id={locationId} data-site-id={siteId ?? ""} />
  ),
}));

import StandaloneLocationDashboardPage from "@/app/locations/[id]/page";
import { generateStaticParams } from "@/app/locations/[id]/layout";

describe("app/locations/[id]/page", () => {
  it("reads the runtime location id from useParams", () => {
    render(<StandaloneLocationDashboardPage />);

    expect(screen.getByTestId("dashboard-props")).toHaveAttribute("data-location-id", "123");
    expect(screen.getByTestId("dashboard-props")).toHaveAttribute("data-site-id", "");
  });

  it("exports a preview shell for static export rewrites", () => {
    expect(generateStaticParams()).toEqual([{ id: "preview" }]);
  });
});
