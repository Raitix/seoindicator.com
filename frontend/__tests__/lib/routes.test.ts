/**
 * Route access list regression tests.
 *
 * Guards the bug where `frontend/lib/contexts/AuthContext.tsx` and
 * `frontend/middleware.ts` each declared their own list of public routes.
 * The middleware listed `/pricing`, `/terms`, `/how-it-works`, `/about`,
 * `/docs` and `/compare` as public; AuthContext did not. Because the
 * production build is a static export (where Next.js middleware does not
 * run), the client-side list decided the outcome - so anonymous visitors
 * were redirected to `/login` on six public marketing pages that are also
 * listed in the sitemap.
 *
 * Both files now import from `@/lib/routes`, and this test asserts that the
 * shared lists contain every route that must stay public.
 */

import {
  PROTECTED_ROUTES,
  PUBLIC_ROUTES,
  GUEST_VIEWABLE_ROUTES,
  isProtectedRoute,
  isPublicRoute,
  isGuestViewableRoute,
} from '@/lib/routes';

describe('route access lists', () => {
  describe('public routes', () => {
    // These pages are marketing/content pages linked from the footer and
    // included in sitemap.xml. They must never redirect to /login.
    const mustBePublic = [
      '/',
      '/pricing',
      '/terms',
      '/how-it-works',
      '/about',
      '/docs',
      '/compare',
      '/tools',
      '/seo-guides',
    ];

    it.each(mustBePublic)('treats %s as public', (route) => {
      expect(isPublicRoute(route)).toBe(true);
      expect(PUBLIC_ROUTES).toContain(route);
    });

    it.each(mustBePublic)('treats %s and its children as public', (route) => {
      expect(isPublicRoute(`${route}/some-sub-page`)).toBe(true);
    });
  });

  describe('protected routes', () => {
    // These routes show account data, so an anonymous visitor is
    // redirected to /login.
    const mustBeProtected = [
      '/settings',
      '/sites',
      '/locations',
      '/admin',
      '/ai-visibility',
    ];

    it.each(mustBeProtected)('treats %s as protected', (route) => {
      expect(isProtectedRoute(route)).toBe(true);
      expect(PROTECTED_ROUTES).toContain(route);
    });

    it.each(mustBeProtected)('treats %s and its children as protected', (route) => {
      expect(isProtectedRoute(`${route}/sub`)).toBe(true);
    });

    it('keeps /comparison guest-viewable while /compare stays public', () => {
      expect(isProtectedRoute('/comparison')).toBe(false);
      expect(isPublicRoute('/compare')).toBe(true);
      expect(isPublicRoute('/comparison')).toBe(false);
    });
  });

  describe('guest-viewable routes', () => {
    // These routes render in "guest preview" mode: the layout and the
    // explanatory copy are visible, and any account data is replaced by
    // a prompt to sign in. They are neither public nor protected.
    const mustBeGuestViewable = [
      '/dashboard',
      '/projects',
      '/competitors',
      '/comparison',
      '/notifications',
      '/ranking-history',
      '/audit',
      '/guides',
    ];

    it.each(mustBeGuestViewable)('treats %s as guest-viewable', (route) => {
      expect(isGuestViewableRoute(route)).toBe(true);
      expect(GUEST_VIEWABLE_ROUTES).toContain(route);
    });

    it.each(mustBeGuestViewable)('treats %s and its children as guest-viewable', (route) => {
      expect(isGuestViewableRoute(`${route}/sub`)).toBe(true);
    });

    it('does not treat a guest-viewable route as protected or public', (route) => {
      for (const r of mustBeGuestViewable) {
        expect(isProtectedRoute(r)).toBe(false);
        expect(isPublicRoute(r)).toBe(false);
      }
    });
  });

  describe('list consistency', () => {
    it('has no route in both public and protected lists', () => {
      const overlap = PUBLIC_ROUTES.filter((route) =>
        PROTECTED_ROUTES.includes(route)
      );
      expect(overlap).toEqual([]);
    });

    it('has no route in both public and guest-viewable lists', () => {
      const overlap = PUBLIC_ROUTES.filter((route) =>
        GUEST_VIEWABLE_ROUTES.includes(route)
      );
      expect(overlap).toEqual([]);
    });

    it('has no route in both protected and guest-viewable lists', () => {
      const overlap = PROTECTED_ROUTES.filter((route) =>
        GUEST_VIEWABLE_ROUTES.includes(route)
      );
      expect(overlap).toEqual([]);
    });

    it('contains no duplicates', () => {
      expect(new Set(PUBLIC_ROUTES).size).toBe(PUBLIC_ROUTES.length);
      expect(new Set(PROTECTED_ROUTES).size).toBe(PROTECTED_ROUTES.length);
      expect(new Set(GUEST_VIEWABLE_ROUTES).size).toBe(GUEST_VIEWABLE_ROUTES.length);
    });
  });
});
