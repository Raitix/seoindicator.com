import React from "react";
import { render, screen } from "@testing-library/react";
import "@testing-library/jest-dom";
import { describe, expect, it } from "vitest";

import { LocalSeoUnreadCountBadge } from "@/components/local/LocalSeoUnreadCountBadge";

describe("LocalSeoUnreadCountBadge", () => {
  it("hides itself when there are no unread alerts by default", () => {
    render(
      <LocalSeoUnreadCountBadge
        unreadCount={{ total: 0, bySeverity: { info: 0, warning: 0, critical: 0 } }}
      />
    );

    expect(screen.queryByTestId("local-seo-unread-badge")).not.toBeInTheDocument();
  });

  it("renders an aria-live status for unread alerts", () => {
    render(
      <LocalSeoUnreadCountBadge
        unreadCount={{ total: 4, bySeverity: { info: 1, warning: 2, critical: 1 } }}
      />
    );

    expect(screen.getByRole("status")).toHaveTextContent("4");
    expect(screen.getByRole("status")).toHaveTextContent(/unread alerts/i);
  });

  it("uses the critical tone when any critical alerts are unread", () => {
    render(
      <LocalSeoUnreadCountBadge
        unreadCount={{ total: 1, bySeverity: { info: 0, warning: 0, critical: 1 } }}
        hideWhenZero={false}
      />
    );

    expect(screen.getByTestId("local-seo-unread-badge").className).toContain("red");
  });
});
