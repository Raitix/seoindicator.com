import { afterAll, beforeEach, describe, expect, it, vi } from "vitest";

const originalEnv = { ...process.env };
const mockFetch = vi.fn();

describe("auth API production routing", () => {
  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    process.env = { ...originalEnv };
    localStorage.clear();
    vi.stubGlobal("fetch", mockFetch);
  });

  afterAll(() => {
    process.env = originalEnv;
    vi.unstubAllGlobals();
  });

  it("fetchUser requests the configured backend origin instead of the static frontend /api/v1 path", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const auth = await import("@/lib/api/auth");

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        data: {
          user: {
            id: 1,
            name: "Raitis",
            email: "raitis@example.com",
            email_verified: true,
          },
        },
      }),
    });

    await auth.fetchUser();

    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/auth/user",
      expect.objectContaining({
        credentials: "include",
        headers: expect.objectContaining({ Accept: "application/json" }),
      })
    );
  });

  it("login fetches the Sanctum CSRF cookie from the configured backend origin before posting credentials", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const auth = await import("@/lib/api/auth");

    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({}) })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            user: {
              id: 1,
              name: "Raitis",
              email: "raitis@example.com",
              email_verified: true,
            },
          },
        }),
      });

    await auth.login("raitis@example.com", "super-secret", true);

    expect(mockFetch).toHaveBeenNthCalledWith(
      1,
      "https://api.seoindicator.com/sanctum/csrf-cookie",
      expect.objectContaining({ credentials: "include" })
    );

    expect(mockFetch).toHaveBeenNthCalledWith(
      2,
      "https://api.seoindicator.com/api/v1/auth/login",
      expect.objectContaining({
        method: "POST",
        credentials: "include",
      })
    );
  });

  it("register posts to the configured backend origin instead of the static frontend host", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const auth = await import("@/lib/api/auth");

    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({}) })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            user: {
              id: 2,
              name: "New User",
              email: "new@example.com",
              email_verified: false,
            },
          },
        }),
      });

    await auth.register({
      name: "New User",
      email: "new@example.com",
      password: "Password123!",
      password_confirmation: "Password123!",
    });

    expect(mockFetch).toHaveBeenNthCalledWith(
      2,
      "https://api.seoindicator.com/api/v1/auth/register",
      expect.objectContaining({
        method: "POST",
        credentials: "include",
      })
    );
  });

  it("login stores the returned bearer token for SPA fallback auth", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const auth = await import("@/lib/api/auth");

    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({}) })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            user: {
              id: 1,
              name: "Raitis",
              email: "raitis@example.com",
              email_verified: true,
            },
            token: "spa-fallback-token",
          },
        }),
      });

    await auth.login("raitis@example.com", "super-secret", false);

    expect(localStorage.getItem("seoindicator_token")).toBe("spa-fallback-token");
  });

  it("register stores the returned bearer token for SPA fallback auth", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";

    const auth = await import("@/lib/api/auth");

    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({}) })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: {
            user: {
              id: 2,
              name: "New User",
              email: "new@example.com",
              email_verified: false,
            },
            token: "registered-spa-token",
          },
        }),
      });

    await auth.register({
      name: "New User",
      email: "new@example.com",
      password: "Password123!",
      password_confirmation: "Password123!",
    });

    expect(localStorage.getItem("seoindicator_token")).toBe("registered-spa-token");
  });

  it("fetchUser includes the stored bearer token when cookie auth is unavailable", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";
    localStorage.setItem("seoindicator_token", "spa-fallback-token");

    const auth = await import("@/lib/api/auth");

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        data: {
          user: {
            id: 1,
            name: "Raitis",
            email: "raitis@example.com",
            email_verified: true,
          },
        },
      }),
    });

    await auth.fetchUser();

    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.seoindicator.com/api/v1/auth/user",
      expect.objectContaining({
        headers: expect.objectContaining({ Authorization: "Bearer spa-fallback-token" }),
      })
    );
  });

  it("logout posts to the configured backend origin and clears stored SPA fallback auth state", async () => {
    process.env.NEXT_PUBLIC_API_URL = "https://api.seoindicator.com";
    localStorage.setItem("seoindicator_token", "spa-fallback-token");
    localStorage.setItem(
      "seoindicator_user",
      JSON.stringify({
        id: 1,
        name: "Raitis",
        email: "raitis@example.com",
        email_verified: true,
      })
    );

    const auth = await import("@/lib/api/auth");

    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({}) })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true }),
      });

    await auth.logout();

    expect(mockFetch).toHaveBeenNthCalledWith(
      1,
      "https://api.seoindicator.com/sanctum/csrf-cookie",
      expect.objectContaining({ credentials: "include" })
    );

    expect(mockFetch).toHaveBeenNthCalledWith(
      2,
      "https://api.seoindicator.com/api/v1/auth/logout",
      expect.objectContaining({
        method: "POST",
        credentials: "include",
        headers: expect.objectContaining({
          Accept: "application/json",
          Authorization: "Bearer spa-fallback-token",
        }),
      })
    );

    expect(localStorage.getItem("seoindicator_token")).toBeNull();
    expect(localStorage.getItem("seoindicator_user")).toBeNull();
  });
});
