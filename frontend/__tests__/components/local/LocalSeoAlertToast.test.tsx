import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";
import { afterEach, describe, expect, it, vi } from "vitest";

import { LocalSeoAlertToast } from "@/components/local/LocalSeoAlertToast";
import type { LocalSeoAlert } from "@/lib/types/location-alerts";

const alert: LocalSeoAlert = {
  id: "toast-1",
  numericId: 7,
  type: "negative_review",
  severity: "warning",
  title: "Negative review received",
  message: "A 1-star review needs attention.",
  isRead: false,
  readAt: null,
  createdAt: "2026-03-10T12:00:00Z",
  location: { id: "77", name: "West Side Clinic" },
  relatedData: [],
  quickAction: null,
};

describe("LocalSeoAlertToast", () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it("renders an assertive notification with alert content", () => {
    render(<LocalSeoAlertToast alert={alert} onOpen={vi.fn()} onDismiss={vi.fn()} />);

    expect(screen.getByRole("status")).toHaveTextContent(alert.title);
    expect(screen.getByRole("status")).toHaveTextContent(alert.message);
  });

  it("opens the alert detail when the CTA is clicked", async () => {
    const user = userEvent.setup();
    const onOpen = vi.fn();

    render(<LocalSeoAlertToast alert={alert} onOpen={onOpen} onDismiss={vi.fn()} />);

    await user.click(screen.getByRole("button", { name: /view details/i }));

    expect(onOpen).toHaveBeenCalledWith(alert);
  });

  it("auto-dismisses after five seconds by default", () => {
    vi.useFakeTimers();
    const onDismiss = vi.fn();

    render(<LocalSeoAlertToast alert={alert} onOpen={vi.fn()} onDismiss={onDismiss} />);

    vi.advanceTimersByTime(5000);

    expect(onDismiss).toHaveBeenCalledWith(alert.id);
  });
});
