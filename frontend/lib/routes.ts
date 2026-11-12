/**
 * Route access rules.
 *
 * Single source of truth for which routes require authentication.
 *
 * Both the Next.js middleware (`frontend/middleware.ts`) and the client-side
 * auth provider (`frontend/lib/contexts/AuthContext.tsx`) must use these
 * lists. The production build is a static export, where Next.js middleware
 * does not execute - so the client-side check in `AuthContext` is what
 * actually decides whether a visitor is redirected to `/login`.
 *
 * Keeping two hand-maintained copies of this list previously caused a bug:
 * `/pricing`, `/terms`, `/how-it-works`, `/about`, `/docs` and `/compare` were
 * listed as public in the middleware but missing from `AuthContext`, so those
 * pages redirected anonymous visitors to the login screen even though they are
 * public marketing pages included in the sitemap.
 *
 * Import from here instead of re-declaring a local copy.
 */

/**
 * Routes that require authentication. A visitor without a valid session is
 * redirected to `/login` with their intended destination preserved.
 *
 * These pages show or change data that belongs to a specific account, so
 * there is nothing meaningful to show without a session.
 */
export const PROTECTED_ROUTES: readonly string[] = [
  "/settings",
  "/sites",
  "/locations",
  "/guides",
  "/badges",
  "/history",
  "/admin",
];

/**
 * Routes that an anonymous visitor may view.
 *
 * These pages render for a signed-out visitor in "guest preview" mode: the
 * layout, the explanatory copy and the structure are all visible, and any
 * personal data is replaced by a prompt to sign in. That keeps the whole
 * site explorable - which matters for a product demo - without pretending
 * the visitor has an account.
 *
 * A route belongs here when the page still makes sense without any of the
 * visitor's own data.
 */
export const GUEST_VIEWABLE_ROUTES: readonly string[] = [
  "/dashboard",
  "/projects",
  "/competitors",
  "/comparison",
  "/notifications",
  "/ranking-history",
  "/audit",
];

/**
 * Routes that are publicly accessible without authentication. These must
 * never redirect to the login page.
 */
export const PUBLIC_ROUTES: readonly string[] = [
  "/",
  "/login",
  "/register",
  "/forgot-password",
  "/reset-password",
  "/verify-email",
  "/tools",
  "/seo-guides",
  "/pricing",
  "/terms",
  "/how-it-works",
  "/about",
  "/docs",
  "/compare",
  "/api",
];

/**
 * True when the path matches a protected route.
 * Matches exact routes and nested routes (e.g. `/dashboard/settings`).
 */
export function isProtectedRoute(pathname: string): boolean {
  return PROTECTED_ROUTES.some(
    (route) => pathname === route || pathname.startsWith(route + "/")
  );
}

/**
 * True when the path is one an anonymous visitor may view in preview mode.
 */
export function isGuestViewableRoute(pathname: string): boolean {
  return GUEST_VIEWABLE_ROUTES.some(
    (route) => pathname === route || pathname.startsWith(route + "/")
  );
}

/**
 * True when the path matches a public route.
 * Matches exact routes and nested routes.
 */
export function isPublicRoute(pathname: string): boolean {
  return PUBLIC_ROUTES.some(
    (route) => pathname === route || pathname.startsWith(route + "/")
  );
}
