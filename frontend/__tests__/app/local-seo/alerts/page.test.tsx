import React from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

vi.mock("@/components/local/LocalSeoAlertsPage", () => ({
  LocalSeoAlertsPage: ({ locationId, siteId }: { locationId?: string; siteId?: string }) => (
    <div data-testid="local-seo-alerts-props" data-location-id={locationId ?? ""} data-site-id={siteId ?? ""} />
  ),
}));

import LocalSeoAlertsRoute from "@/app/local-seo/alerts/page";

describe("app/local-seo/alerts/page", () => {
  it("renders the all-locations alerts workspace without scoped route ids", () => {
    render(<LocalSeoAlertsRoute />);

    expect(screen.getByTestId("local-seo-alerts-props")).toHaveAttribute("data-location-id", "");
    expect(screen.getByTestId("local-seo-alerts-props")).toHaveAttribute("data-site-id", "");
  });
});
