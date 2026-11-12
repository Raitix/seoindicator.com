/**
 * Tests for guest preview mode.
 *
 * An anonymous visitor must be able to browse the pages that normally sit
 * behind a login. The route lists live in `@/lib/routes` so the middleware,
 * the auth provider and the token-expiry handler always agree.
 */

import {
  PROTECTED_ROUTES,
  PUBLIC_ROUTES,
  GUEST_VIEWABLE_ROUTES,
  isProtectedRoute,
  isPublicRoute,
  isGuestViewableRoute,
} from "@/lib/routes";

describe("Guest viewable routes", () => {
  it("keeps the guest-viewable list free of protected routes", () => {
    const overlap = GUEST_VIEWABLE_ROUTES.filter((r) =>
      PROTECTED_ROUTES.includes(r)
    );
    expect(overlap).toEqual([]);
  });

  it("keeps the guest-viewable list free of public routes", () => {
    const overlap = GUEST_VIEWABLE_ROUTES.filter((r) =>
      PUBLIC_ROUTES.includes(r)
    );
    expect(overlap).toEqual([]);
  });

  it("identifies guest-viewable routes", () => {
    expect(isGuestViewableRoute("/dashboard")).toBe(true);
    expect(isGuestViewableRoute("/projects")).toBe(true);
    expect(isGuestViewableRoute("/competitors")).toBe(true);
    expect(isGuestViewableRoute("/comparison")).toBe(true);
    expect(isGuestViewableRoute("/notifications")).toBe(true);
    expect(isGuestViewableRoute("/ranking-history")).toBe(true);
    expect(isGuestViewableRoute("/audit")).toBe(true);
  });

  it("does not treat routes that need a session as guest-viewable", () => {
    expect(isGuestViewableRoute("/settings")).toBe(false);
    expect(isGuestViewableRoute("/admin")).toBe(false);
    expect(isGuestViewableRoute("/sites")).toBe(false);
    expect(isGuestViewableRoute("/login")).toBe(false);
  });

  it("matches nested routes under a guest-viewable prefix", () => {
    expect(isGuestViewableRoute("/dashboard/history")).toBe(true);
    expect(isGuestViewableRoute("/dashboard/badges")).toBe(true);
  });

  it("keeps account-specific pages out of guest view", () => {
    // These pages only ever show a visitor's own data, so there is nothing
    // to preview - they still need a session.
    expect(isGuestViewableRoute("/settings/alerts")).toBe(false);
    expect(isGuestViewableRoute("/sites/1/keywords")).toBe(false);
    expect(isGuestViewableRoute("/locations/1/rankings")).toBe(false);
    expect(isGuestViewableRoute("/admin/users")).toBe(false);
  });

  it("does not treat a route that merely starts with the same letters as guest-viewable", () => {
    expect(isGuestViewableRoute("/dashboarding")).toBe(false);
    expect(isProtectedRoute("/settings")).toBe(true);
    expect(isProtectedRoute("/settings/profile")).toBe(true);
  });
});
