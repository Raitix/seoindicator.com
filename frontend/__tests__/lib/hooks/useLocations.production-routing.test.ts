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

describe("useLocations production routing", () => {
  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    process.env = { ...originalEnv };
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

  it("useLocations builds its SWR key against the configured API origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const locationsModule = await import("@/lib/hooks/useLocations");

    locationsModule.useLocations("42");

    expect(useSWR).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/sites/42/locations",
      expect.any(Function)
    );
  });

  it("useCreateLocation posts to the configured backend origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const locationsModule = await import("@/lib/hooks/useLocations");

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          id: 7,
          site_id: 42,
          name: "Riga HQ",
          address: "Brivibas 1, Riga",
          is_gbp_connected: false,
          created_at: "2026-03-10T10:00:00Z",
          updated_at: "2026-03-10T10:00:00Z",
        },
      }),
    });

    const { result } = renderHook(() => locationsModule.useCreateLocation("42"));

    await result.current.createLocation({
      name: "Riga HQ",
      address: "Brivibas 1, Riga",
      phone: null,
      website: null,
      category: null,
      latitude: null,
      longitude: null,
    });

    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/sites/42/locations",
      expect.objectContaining({
        method: "POST",
        headers: expect.objectContaining({ Accept: "application/json" }),
      })
    );
  });

  it("useUpdateLocation sends PUT requests to the configured backend origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const locationsModule = await import("@/lib/hooks/useLocations");

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          id: 7,
          site_id: 42,
          name: "Riga HQ Updated",
          address: "Brivibas 1, Riga",
          is_gbp_connected: true,
          created_at: "2026-03-10T10:00:00Z",
          updated_at: "2026-03-10T11:00:00Z",
        },
      }),
    });

    const { result } = renderHook(() => locationsModule.useUpdateLocation());

    await result.current.updateLocation(7, {
      name: "Riga HQ Updated",
      address: "Brivibas 1, Riga",
      phone: null,
      website: null,
      category: null,
      latitude: null,
      longitude: null,
    });

    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/locations/7",
      expect.objectContaining({
        method: "PUT",
        headers: expect.objectContaining({ Accept: "application/json" }),
      })
    );
  });
});
