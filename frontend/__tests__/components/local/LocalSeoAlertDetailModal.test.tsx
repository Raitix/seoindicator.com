import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { describe, expect, it, vi } from "vitest";

vi.mock("next/link", () => ({
  default: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

import { LocalSeoAlertDetailModal } from "@/components/local/LocalSeoAlertDetailModal";
import type { LocalSeoAlert } from "@/lib/types/location-alerts";

const alert: LocalSeoAlert = {
  id: "alert-2",
  numericId: 2,
  type: "competitor_overtake",
  severity: "critical",
  title: "Competitor overtook your listing",
  message: "A nearby competitor is now above you in the local pack.",
  isRead: false,
  readAt: null,
  createdAt: "2026-03-10T12:00:00Z",
  location: {
    id: "321",
    name: "Downtown Office",
  },
  relatedData: [
    { label: "Competitor", value: "Acme SEO" },
    { label: "New position", value: "#1" },
  ],
  quickAction: {
    label: "View rankings",
    href: "/local-seo/rankings?location_id=321",
  },
};

describe("LocalSeoAlertDetailModal", () => {
  it("renders the full alert information and related data when open", () => {
    render(<LocalSeoAlertDetailModal alert={alert} isOpen onClose={vi.fn()} />);

    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(screen.getByText(alert.title)).toBeInTheDocument();
    expect(screen.getByText(alert.message)).toBeInTheDocument();
    expect(screen.getByText(/downtown office/i)).toBeInTheDocument();
    expect(screen.getByTestId("local-seo-alert-related-data")).toHaveTextContent("Competitor");
    expect(screen.getByRole("link", { name: /view rankings/i })).toHaveAttribute(
      "href",
      "/local-seo/rankings?location_id=321"
    );
  });

  it("closes via the close button", async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    render(<LocalSeoAlertDetailModal alert={alert} isOpen onClose={onClose} />);

    await user.click(screen.getByRole("button", { name: /close/i }));

    expect(onClose).toHaveBeenCalled();
  });

  it("can mark the alert as read from the modal", async () => {
    const user = userEvent.setup();
    const onMarkRead = vi.fn();

    render(<LocalSeoAlertDetailModal alert={alert} isOpen onClose={vi.fn()} onMarkRead={onMarkRead} />);

    await user.click(screen.getByRole("button", { name: /mark as read/i }));

    expect(onMarkRead).toHaveBeenCalledWith(alert);
  });
});
