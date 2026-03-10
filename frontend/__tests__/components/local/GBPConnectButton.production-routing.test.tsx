import * as React from "react";
import { afterAll, beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import "@testing-library/jest-dom";

const originalEnv = { ...process.env };
const mockFetch = vi.fn();
const mockWindowOpen = vi.fn();
const originalWindowOpen = window.open;
const mockOAuthUrl = "https://accounts.google.com/o/oauth2/v2/auth?...";

describe("GBPConnectButton production routing", () => {
  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    process.env = { ...originalEnv };
    vi.stubGlobal("fetch", mockFetch);
    Object.defineProperty(window, "open", {
      configurable: true,
      writable: true,
      value: mockWindowOpen,
    });
    sessionStorage.clear();
  });

  afterAll(() => {
    process.env = originalEnv;
    vi.unstubAllGlobals();
    Object.defineProperty(window, "open", {
      configurable: true,
      writable: true,
      value: originalWindowOpen,
    });
  });

  async function loadComponent() {
    return import("@/components/local/GBPConnectButton");
  }

  it("uses the configured API origin for the initial OAuth bootstrap request", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const { GBPConnectButton } = await loadComponent();

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ oauth_url: mockOAuthUrl }),
    });
    mockWindowOpen.mockReturnValue({ closed: false } as Window);

    render(<GBPConnectButton siteId="site-123" locationId="loc-456" />);

    const user = userEvent.setup();
    await user.click(screen.getByTestId("gbp-connect-button"));

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining("https://api.seoindicator.com/api/v1/locations/loc-456/gbp/oauth-url"),
      expect.objectContaining({ headers: expect.any(Object) })
    );
  });

  it("uses the configured API origin for the connect-gbp fallback contract", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const { GBPConnectButton } = await loadComponent();

    mockFetch
      .mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: async () => ({ message: "Not found" }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ authorization_url: mockOAuthUrl, state: "server-state" }),
      });
    mockWindowOpen.mockReturnValue({ closed: false } as Window);

    render(<GBPConnectButton siteId="site-123" locationId="loc-456" />);

    const user = userEvent.setup();
    await user.click(screen.getByTestId("gbp-connect-button"));

    expect(mockFetch).toHaveBeenNthCalledWith(
      2,
      "https://api.seoindicator.com/api/v1/locations/loc-456/connect-gbp",
      expect.objectContaining({ method: "POST" })
    );
  });
});
