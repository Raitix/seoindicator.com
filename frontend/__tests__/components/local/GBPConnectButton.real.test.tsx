import * as React from "react";
import { beforeEach, afterAll, describe, expect, it, vi } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

import {
  GBPConnectButton,
  GBP_OAUTH_STATE_KEY,
  type ConnectedGBP,
} from "@/components/local/GBPConnectButton";

const mockFetch = vi.fn();
const mockWindowOpen = vi.fn();
const originalWindowOpen = window.open;

const mockOAuthUrl = "https://accounts.google.com/o/oauth2/v2/auth?...";
const mockAccounts = [
  {
    id: "acc-1",
    name: "My Business",
    locationCount: 3,
    profileImage: "https://example.com/account.png",
  },
  {
    id: "acc-2",
    name: "Another Business",
    locationCount: 1,
  },
];
const mockLocations = [
  {
    id: "gbp-loc-1",
    name: "Main Location",
    address: "123 Main St, City, ST 12345",
    accountId: "acc-1",
  },
  {
    id: "gbp-loc-2",
    name: "Branch Office",
    address: "456 Oak Ave, Town, ST 67890",
    accountId: "acc-1",
  },
];
const connectedGBP: ConnectedGBP = {
  businessName: "Main Location",
  locationId: "gbp-loc-1",
  profileImage: "https://example.com/location.png",
};

function renderComponent(props: Partial<React.ComponentProps<typeof GBPConnectButton>> = {}) {
  return render(
    <GBPConnectButton
      siteId="site-123"
      locationId="loc-456"
      {...props}
    />
  );
}

function jsonResponse(payload: unknown, ok = true) {
  return Promise.resolve({
    ok,
    json: async () => payload,
  });
}

async function startOAuth(user = userEvent.setup()) {
  mockFetch.mockResolvedValueOnce(await jsonResponse({ oauth_url: mockOAuthUrl }));
  mockWindowOpen.mockReturnValue({ closed: false } as Window);

  renderComponent();
  await user.click(screen.getByTestId("gbp-connect-button"));
  return user;
}

function dispatchOAuthCallback(data: Record<string, unknown>) {
  window.dispatchEvent(
    new MessageEvent("message", {
      data: {
        type: "gbp_oauth_callback",
        ...data,
      },
    })
  );
}

describe("GBPConnectButton (real)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    Object.defineProperty(window, "open", {
      configurable: true,
      writable: true,
      value: mockWindowOpen,
    });
    vi.stubGlobal("fetch", mockFetch);
    sessionStorage.clear();
  });

  afterAll(() => {
    Object.defineProperty(window, "open", {
      configurable: true,
      writable: true,
      value: originalWindowOpen,
    });
  });

  it("renders the Google-branded connect button", () => {
    renderComponent();

    const button = screen.getByTestId("gbp-connect-button");
    expect(button).toBeInTheDocument();
    expect(button).toHaveTextContent("Connect Google Business Profile");
    expect(button).toHaveClass("google-branding");
    expect(button).toHaveAttribute("aria-label", "Connect Google Business Profile");
    expect(screen.getByTestId("google-logo")).toBeInTheDocument();
  });

  it("starts OAuth, stores state in sessionStorage, and opens the popup", async () => {
    const user = userEvent.setup();
    await startOAuth(user);

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining("/api/v1/locations/loc-456/gbp/oauth-url"),
      expect.objectContaining({ headers: expect.any(Object) })
    );
    expect(sessionStorage.getItem(GBP_OAUTH_STATE_KEY)).toContain("site-123");
    expect(sessionStorage.getItem(GBP_OAUTH_STATE_KEY)).toContain("loc-456");
    expect(mockWindowOpen).toHaveBeenCalledWith(
      mockOAuthUrl,
      "GBP OAuth",
      expect.stringContaining("width=600")
    );
  });

  it("shows a retryable error when the popup is blocked", async () => {
    mockFetch.mockResolvedValueOnce(await jsonResponse({ oauth_url: mockOAuthUrl }));
    mockWindowOpen.mockReturnValue(null);

    const user = userEvent.setup();
    renderComponent();
    await user.click(screen.getByTestId("gbp-connect-button"));

    await waitFor(() => {
      expect(screen.getByTestId("gbp-error")).toBeInTheDocument();
      expect(screen.getByText(/popup blocked/i)).toBeInTheDocument();
    });

    await user.click(screen.getByTestId("retry-button"));
    expect(screen.getByTestId("gbp-connect-button")).toBeInTheDocument();
  });

  it("loads available GBP accounts after a successful OAuth callback", async () => {
    const user = await startOAuth();
    mockFetch.mockResolvedValueOnce(await jsonResponse({ accounts: mockAccounts }));

    dispatchOAuthCallback({ success: true });

    await waitFor(() => {
      expect(screen.getByTestId("gbp-selecting")).toBeInTheDocument();
      expect(screen.getByTestId("account-acc-1")).toBeInTheDocument();
      expect(screen.getByText("3 locations")).toBeInTheDocument();
      expect(screen.getByText("1 location")).toBeInTheDocument();
    });

    expect(user).toBeDefined();
  });

  it("handles empty account responses with a user-safe error", async () => {
    await startOAuth();
    mockFetch.mockResolvedValueOnce(await jsonResponse({ accounts: [] }));

    dispatchOAuthCallback({ success: true });

    await waitFor(() => {
      expect(screen.getByTestId("gbp-error")).toBeInTheDocument();
      expect(screen.getByText(/no google business profile accounts found/i)).toBeInTheDocument();
    });
  });

  it("shows account locations and connects the selected GBP location", async () => {
    const onConnectionChange = vi.fn();
    const user = userEvent.setup();

    mockFetch.mockResolvedValueOnce(await jsonResponse({ oauth_url: mockOAuthUrl }));
    mockWindowOpen.mockReturnValue({ closed: false } as Window);
    renderComponent({ onConnectionChange });

    await user.click(screen.getByTestId("gbp-connect-button"));

    mockFetch.mockResolvedValueOnce(await jsonResponse({ accounts: mockAccounts }));
    dispatchOAuthCallback({ success: true });

    await waitFor(() => {
      expect(screen.getByTestId("account-acc-1")).toBeInTheDocument();
    });

    mockFetch.mockResolvedValueOnce(await jsonResponse({ locations: mockLocations }));
    await user.click(screen.getByTestId("account-acc-1"));

    await waitFor(() => {
      expect(screen.getByTestId("location-list")).toBeInTheDocument();
      expect(screen.getByText(mockLocations[0].address)).toBeInTheDocument();
    });

    mockFetch.mockResolvedValueOnce(await jsonResponse({ success: true }));
    await user.click(screen.getByTestId("location-gbp-loc-1"));

    await waitFor(() => {
      expect(screen.getByTestId("gbp-connected")).toBeInTheDocument();
      expect(screen.getByText(/Connected to Main Location/)).toBeInTheDocument();
    });

    expect(onConnectionChange).toHaveBeenCalledWith(
      true,
      expect.objectContaining({ businessName: "Main Location", locationId: "gbp-loc-1" })
    );
  });

  it("renders the connected state and falls back to a placeholder icon when no image is provided", () => {
    renderComponent({
      connectedGBP: {
        businessName: "No Image Business",
        locationId: "gbp-loc-1",
      },
    });

    expect(screen.getByTestId("gbp-connected")).toBeInTheDocument();
    expect(screen.getByText(/Connected to No Image Business/)).toBeInTheDocument();
    expect(screen.getByTestId("building-icon")).toBeInTheDocument();
  });

  it("disconnects a connected profile and returns to the idle state", async () => {
    const onConnectionChange = vi.fn();
    const user = userEvent.setup();

    renderComponent({ connectedGBP, onConnectionChange });
    await user.click(screen.getByTestId("disconnect-button"));

    expect(screen.getByRole("dialog")).toBeInTheDocument();

    mockFetch.mockResolvedValueOnce(await jsonResponse({ success: true }));
    await user.click(screen.getByTestId("confirm-disconnect"));

    await waitFor(() => {
      expect(screen.getByTestId("gbp-connect-button")).toBeInTheDocument();
    });

    expect(onConnectionChange).toHaveBeenCalledWith(false);
  });

  it("keeps the connected state when disconnect fails", async () => {
    const user = userEvent.setup();

    renderComponent({ connectedGBP });
    await user.click(screen.getByTestId("disconnect-button"));

    mockFetch.mockResolvedValueOnce(await jsonResponse({ error: "Disconnect failed" }, false));
    await user.click(screen.getByTestId("confirm-disconnect"));

    await waitFor(() => {
      expect(screen.getByTestId("gbp-connected")).toBeInTheDocument();
      expect(screen.getByText(/disconnect failed/i)).toBeInTheDocument();
    });
  });

  it("does not leak unrelated sensitive fields from backend errors", async () => {
    const user = userEvent.setup();

    mockFetch.mockResolvedValueOnce(
      await jsonResponse(
        {
          error: "Connection failed",
          access_token: "secret-token",
          api_key: "secret-key",
        },
        false
      )
    );

    renderComponent();
    await user.click(screen.getByTestId("gbp-connect-button"));

    await waitFor(() => {
      const text = screen.getByTestId("gbp-error").textContent ?? "";
      expect(text).toContain("Connection failed");
      expect(text).not.toContain("secret-token");
      expect(text).not.toContain("secret-key");
    });
  });
});
