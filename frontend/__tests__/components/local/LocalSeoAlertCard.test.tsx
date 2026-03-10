import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { describe, expect, it, vi } from "vitest";

vi.mock("next/link", () => ({
  default: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

import { LocalSeoAlertCard } from "@/components/local/LocalSeoAlertCard";
import type { LocalSeoAlert } from "@/lib/types/location-alerts";

const alert: LocalSeoAlert = {
  id: "alert-1",
  numericId: 1,
  type: "ranking_dropped",
  severity: "warning",
  title: "Ranking dropped for pizza delivery",
  message: "Your local pack ranking moved from #2 to #5.",
  isRead: false,
  readAt: null,
  createdAt: "2026-03-10T12:00:00Z",
  location: {
    id: "123",
    name: "Example Business",
  },
  relatedData: [
    { label: "Previous position", value: "2" },
    { label: "Current position", value: "5" },
  ],
  quickAction: {
    label: "View rankings",
    href: "/local-seo/rankings",
  },
};

describe("LocalSeoAlertCard", () => {
  it("renders the alert summary with severity, location link, and unread state", () => {
    render(<LocalSeoAlertCard alert={alert} />);

    expect(screen.getByText(alert.title)).toBeInTheDocument();
    expect(screen.getByText(alert.message)).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /example business/i })).toHaveAttribute(
      "href",
      "/local-seo/alerts?location_id=123"
    );
    expect(screen.getByTestId("unread-indicator")).toHaveTextContent(/unread/i);
    expect(screen.getByText(/warning/i)).toBeInTheDocument();
  });

  it("opens the alert when the content area is clicked", async () => {
    const user = userEvent.setup();
    const onOpen = vi.fn();

    render(<LocalSeoAlertCard alert={alert} onOpen={onOpen} />);

    await user.click(screen.getByRole("button", { name: /ranking dropped for pizza delivery/i }));

    expect(onOpen).toHaveBeenCalledWith(alert);
  });

  it("calls the mark-as-read callback", async () => {
    const user = userEvent.setup();
    const onMarkRead = vi.fn();

    render(<LocalSeoAlertCard alert={alert} onMarkRead={onMarkRead} />);

    await user.click(screen.getByRole("button", { name: /mark as read/i }));

    expect(onMarkRead).toHaveBeenCalledWith(alert);
  });
});
