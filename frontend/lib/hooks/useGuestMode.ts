"use client";

/**
 * Guest-mode state for a page.
 *
 * Several pages need to know whether the visitor is an anonymous guest, so
 * they can treat a 401 as an expected answer instead of a failure. The
 * condition was written out by hand in each of them, which is how the
 * branches drifted apart - one page checked `!isAuthenticated` while the
 * auth context was still loading, so a guest briefly looked like an
 * authenticated user.
 *
 * `isGuest` is only true once the auth state has settled, so it is safe to
 * branch on.
 */

import { useAuth } from "@/lib/contexts/AuthContext";

export interface GuestMode {
  /** True once we know the visitor has no session. */
  readonly isGuest: boolean;
  /** True while the session is still being resolved. */
  readonly isAuthLoading: boolean;
  /** True once the visitor has a session. */
  readonly isAuthenticated: boolean;
}

export function useGuestMode(): GuestMode {
  const { isAuthenticated, isLoading } = useAuth();

  return {
    isGuest: !isLoading && !isAuthenticated,
    isAuthLoading: isLoading,
    isAuthenticated,
  };
}
