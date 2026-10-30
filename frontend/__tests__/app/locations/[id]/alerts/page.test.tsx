import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/components/local/LocalSeoAlertsPage", () => ({
  LocalSeoAlertsPage: ({ locationId, siteId }: { locationId?: string; siteId?: string }) => (
    <div data-testid="local-seo-alerts-props" data-location-id={locationId ?? ""} data-site-id={siteId ?? ""} />
  ),
}));

import StandaloneLocationAlertsRoute from "@/app/locations/[id]/alerts/page";

describe("app/locations/[id]/alerts/page", () => {
  it("passes the route id into LocalSeoAlertsPage as a scoped location filter", async () => {
    const Page = await StandaloneLocationAlertsRoute({ params: Promise.resolve({ id: "123" }) });
    render(Page);

    expect(screen.getByTestId("local-seo-alerts-props")).toHaveAttribute("data-location-id", "123");
    expect(screen.getByTestId("local-seo-alerts-props")).toHaveAttribute("data-site-id", "");
  });
});
