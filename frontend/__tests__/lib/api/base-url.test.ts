import { afterAll, beforeEach, describe, expect, it, vi } from "vitest";

const originalEnv = { ...process.env };

describe("API base URL helpers", () => {
  beforeEach(() => {
    vi.resetModules();
    process.env = { ...originalEnv };
    delete process.env.NEXT_PUBLIC_API_URL;
  });

  afterAll(() => {
    process.env = originalEnv;
  });

  it("defaults to same-origin /api/v1 when NEXT_PUBLIC_API_URL is not provided", async () => {
    const { getApiBaseUrl } = await import("@/lib/api/base-url");

    expect(getApiBaseUrl()).toBe("/api/v1");
  });

  it("appends /api/v1 when NEXT_PUBLIC_API_URL is configured as a bare origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const { getApiBaseUrl } = await import("@/lib/api/base-url");

    expect(getApiBaseUrl()).toBe("https://api.seoindicator.com/api/v1");
  });

  it("normalizes a trailing slash on NEXT_PUBLIC_API_URL before appending /api/v1", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com/";

    const { getApiBaseUrl } = await import("@/lib/api/base-url");

    expect(getApiBaseUrl()).toBe("https://api.seoindicator.com/api/v1");
  });

  it("does not duplicate /api/v1 when NEXT_PUBLIC_API_URL already includes the versioned base path", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com/api/v1";

    const { getApiBaseUrl } = await import("@/lib/api/base-url");

    expect(getApiBaseUrl()).toBe("https://api.seoindicator.com/api/v1");
  });

  it("buildApiUrl joins endpoint paths against the normalized API base URL", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const { buildApiUrl } = await import("@/lib/api/base-url");

    expect(buildApiUrl("auth/login")).toBe("https://api.seoindicator.com/api/v1/auth/login");
    expect(buildApiUrl("/sites/123/locations")).toBe("https://api.seoindicator.com/api/v1/sites/123/locations");
  });

  it("buildSanctumUrl targets the configured API origin instead of the static frontend origin", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const { buildSanctumUrl } = await import("@/lib/api/base-url");

    expect(buildSanctumUrl()).toBe("https://api.seoindicator.com/sanctum/csrf-cookie");
  });
});
