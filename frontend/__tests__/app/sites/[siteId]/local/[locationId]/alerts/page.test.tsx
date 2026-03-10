import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/components/local/LocalSeoAlertsPage", () => ({
  LocalSeoAlertsPage: ({ locationId, siteId }: { locationId?: string; siteId?: string }) => (
    <div data-testid="local-seo-alerts-props" data-location-id={locationId ?? ""} data-site-id={siteId ?? ""} />
  ),
}));

import SiteLocationAlertsRoute from "@/app/sites/[siteId]/local/[locationId]/alerts/page";

describe("app/sites/[siteId]/local/[locationId]/alerts/page", () => {
  it("passes both route ids into LocalSeoAlertsPage", async () => {
    const Page = await SiteLocationAlertsRoute({
      params: Promise.resolve({ siteId: "7", locationId: "123" }),
    });
    render(Page);

    expect(screen.getByTestId("local-seo-alerts-props")).toHaveAttribute("data-site-id", "7");
    expect(screen.getByTestId("local-seo-alerts-props")).toHaveAttribute("data-location-id", "123");
  });
});
