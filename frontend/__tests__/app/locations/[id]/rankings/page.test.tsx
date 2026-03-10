import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/components/local/LocationRankingsPage", () => ({
  LocationRankingsPage: ({ locationId, siteId }: { locationId: string; siteId?: string }) => (
    <div data-testid="rankings-props" data-location-id={locationId} data-site-id={siteId ?? ""} />
  ),
}));

import StandaloneLocationRankingsRoute from "@/app/locations/[id]/rankings/page";

describe("app/locations/[id]/rankings/page", () => {
  it("passes the route id into LocationRankingsPage", async () => {
    const Page = await StandaloneLocationRankingsRoute({ params: Promise.resolve({ id: "123" }) });
    render(Page);

    expect(screen.getByTestId("rankings-props")).toHaveAttribute("data-location-id", "123");
    expect(screen.getByTestId("rankings-props")).toHaveAttribute("data-site-id", "");
  });
});
