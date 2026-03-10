import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { LocalSeoAlertsPage } from "@/components/local/LocalSeoAlertsPage";
import type { LocalSeoAlert } from "@/lib/types/location-alerts";

const mocks = vi.hoisted(() => ({
  useLocalSeoAlertsPage: vi.fn(),
  mutateAlerts: vi.fn().mockResolvedValue(undefined),
  mutateUnreadCount: vi.fn().mockResolvedValue(undefined),
  markAlertRead: vi.fn().mockResolvedValue({ success: true }),
  markAllRead: vi.fn().mockResolvedValue({ success: true, count: 2 }),
  toastSuccess: vi.fn(),
  toastError: vi.fn(),
}));

vi.mock("next/link", () => ({
  default: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

vi.mock("sonner", () => ({
  toast: {
    success: mocks.toastSuccess,
    error: mocks.toastError,
  },
}));

vi.mock("@/lib/hooks/useLocalSeoAlertsPage", () => ({
  useLocalSeoAlertsPage: (...args: unknown[]) => mocks.useLocalSeoAlertsPage(...args),
}));

const alerts: LocalSeoAlert[] = [
  {
    id: "alert-1",
    numericId: 1,
    type: "ranking_dropped",
    severity: "warning",
    title: "Ranking dropped for pizza delivery",
    message: "Position fell from #2 to #5.",
    isRead: false,
    readAt: null,
    createdAt: "2026-03-10T12:00:00Z",
    location: { id: "123", name: "Example Business" },
    relatedData: [
      { label: "Previous position", value: "2" },
      { label: "Current position", value: "5" },
    ],
    quickAction: { label: "View rankings", href: "/local-seo/rankings?location_id=123" },
  },
  {
    id: "alert-2",
    numericId: 2,
    type: "negative_review",
    severity: "critical",
    title: "Negative review received",
    message: "A 1-star review needs a response.",
    isRead: false,
    readAt: null,
    createdAt: "2026-03-10T13:00:00Z",
    location: { id: "456", name: "Second Location" },
    relatedData: [{ label: "Rating", value: "1" }],
    quickAction: { label: "View reviews", href: "/local-seo/reviews?location_id=456" },
  },
];

describe("LocalSeoAlertsPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.useLocalSeoAlertsPage.mockImplementation(() => ({
      alerts,
      meta: { currentPage: 1, perPage: 15, total: 2, lastPage: 1 },
      unreadCount: { total: 2, bySeverity: { info: 0, warning: 1, critical: 1 } },
      isLoading: false,
      error: null,
      mutateAlerts: mocks.mutateAlerts,
      mutateUnreadCount: mocks.mutateUnreadCount,
      markAlertRead: mocks.markAlertRead,
      markAllRead: mocks.markAllRead,
      deleteAlert: vi.fn(),
    }));
  });

  it("renders the alerts workspace with filters, unread badge, and alert cards", () => {
    render(<LocalSeoAlertsPage />);

    expect(screen.getByRole("heading", { level: 1, name: /local seo alerts/i })).toBeInTheDocument();
    expect(screen.getByRole("status")).toHaveTextContent(/2 unread alerts/i);
    expect(screen.getByRole("combobox", { name: /filter alerts by severity/i })).toBeInTheDocument();
    expect(screen.getByText("Ranking dropped for pizza delivery")).toBeInTheDocument();
    expect(screen.getByText("Negative review received")).toBeInTheDocument();
  });

  it("shows the scoped back link when opened from a location dashboard route", () => {
    render(<LocalSeoAlertsPage locationId="123" siteId="7" />);

    expect(screen.getByRole("link", { name: /back to dashboard/i })).toHaveAttribute(
      "href",
      "/sites/7/local/123"
    );
  });

  it("updates filter state through the hook inputs", async () => {
    const user = userEvent.setup();

    render(<LocalSeoAlertsPage />);

    await user.selectOptions(screen.getByRole("combobox", { name: /filter alerts by severity/i }), "critical");

    await waitFor(() => {
      expect(mocks.useLocalSeoAlertsPage).toHaveBeenLastCalledWith(
        expect.objectContaining({ severity: "critical" }),
        1
      );
    });
  });

  it("exposes a location filter on the all-locations workspace", () => {
    render(<LocalSeoAlertsPage />);

    expect(screen.getByRole("combobox", { name: /filter alerts by location/i })).toBeInTheDocument();
  });

  it("opens the detail modal when an alert card is selected", async () => {
    const user = userEvent.setup();

    render(<LocalSeoAlertsPage />);

    await user.click(screen.getByRole("button", { name: /ranking dropped for pizza delivery/i }));

    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(screen.getByText(/position fell from #2 to #5/i)).toBeInTheDocument();
  });

  it("marks a single alert as read and shows success feedback", async () => {
    const user = userEvent.setup();

    render(<LocalSeoAlertsPage />);

    await user.click(screen.getAllByRole("button", { name: /mark as read/i })[0]);

    await waitFor(() => {
      expect(mocks.markAlertRead).toHaveBeenCalledWith("alert-1");
      expect(mocks.toastSuccess).toHaveBeenCalledWith("Alert marked as read");
    });
  });

  it("asks for confirmation before marking all alerts as read", async () => {
    const user = userEvent.setup();

    render(<LocalSeoAlertsPage />);

    await user.click(screen.getByRole("button", { name: /mark all as read/i }));

    expect(screen.getByText(/this will mark all currently filtered alerts as read/i)).toBeInTheDocument();
    expect(mocks.markAllRead).not.toHaveBeenCalled();
  });

  it("marks all alerts as read from the header action", async () => {
    const user = userEvent.setup();

    render(<LocalSeoAlertsPage />);

    await user.click(screen.getByRole("button", { name: /mark all as read/i }));
    await user.click(screen.getByRole("button", { name: /confirm mark all as read/i }));

    await waitFor(() => {
      expect(mocks.markAllRead).toHaveBeenCalled();
      expect(mocks.toastSuccess).toHaveBeenCalledWith("Marked 2 alert(s) as read");
    });
  });
});
