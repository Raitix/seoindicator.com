import { renderHook } from "@testing-library/react";
import { afterAll, beforeEach, describe, expect, it, vi } from "vitest";

vi.mock("swr", () => ({
  __esModule: true,
  default: vi.fn(),
  mutate: vi.fn(),
}));

import useSWR, { mutate as globalMutate } from "swr";

const originalEnv = { ...process.env };
const mockFetch = vi.fn();

describe("useLocationDashboard production routing", () => {
  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    process.env = { ...originalEnv };
    localStorage.clear();
    vi.stubGlobal("fetch", mockFetch);
    (useSWR as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
      data: null,
      error: null,
      isLoading: false,
      mutate: vi.fn(),
    });
    (globalMutate as unknown as ReturnType<typeof vi.fn>).mockResolvedValue(undefined);
  });

  afterAll(() => {
    process.env = originalEnv;
    vi.unstubAllGlobals();
  });

  it("useDashboardLocation builds its SWR key against the configured API origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const dashboardModule = await import("@/lib/hooks/useLocationDashboard");

    dashboardModule.useDashboardLocation("42");

    expect(useSWR).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/locations/42",
      expect.any(Function)
    );
  });

  it("dashboard fetchers include the stored bearer token for protected SPA flows", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";
    localStorage.setItem("seoindicator_token", "spa-fallback-token");

    const dashboardModule = await import("@/lib/hooks/useLocationDashboard");

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          id: 42,
          site_id: 7,
          name: "Riga HQ",
          address: "Brivibas 1, Riga",
          is_gbp_connected: true,
          created_at: "2026-03-10T10:00:00Z",
          updated_at: "2026-03-10T10:00:00Z",
        },
      }),
    });

    dashboardModule.useDashboardLocation("42");

    const swrCalls = (useSWR as unknown as ReturnType<typeof vi.fn>).mock.calls;
    const [, fetcher] = swrCalls.at(-1) as [string, (key: string) => Promise<unknown>];

    await fetcher("https://api.seoindicator.com/api/v1/locations/42");

    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/locations/42",
      expect.objectContaining({
        credentials: "include",
        headers: expect.objectContaining({ Authorization: "Bearer spa-fallback-token" }),
      })
    );
  });

  it("useLocationSync posts to the configured backend origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";
    localStorage.setItem("seoindicator_token", "spa-fallback-token");

    const dashboardModule = await import("@/lib/hooks/useLocationDashboard");

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true }),
    });

    const { result } = renderHook(() => dashboardModule.useLocationSync("42"));

    await result.current.triggerSync();

    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/locations/42/sync",
      expect.objectContaining({
        method: "POST",
        credentials: "include",
        headers: expect.objectContaining({
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: "Bearer spa-fallback-token",
        }),
      })
    );
  });
});
