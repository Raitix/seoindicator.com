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
  isProtectedRoute,
  isPublicRoute,
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
    const mustBeProtected = [
      '/dashboard',
      '/settings',
      '/projects',
      '/competitors',
      '/comparison',
      '/notifications',
      '/sites',
      '/locations',
      '/ranking-history',
      '/admin',
      '/audit',
    ];

    it.each(mustBeProtected)('treats %s as protected', (route) => {
      expect(isProtectedRoute(route)).toBe(true);
      expect(PROTECTED_ROUTES).toContain(route);
    });

    it.each(mustBeProtected)('treats %s and its children as protected', (route) => {
      expect(isProtectedRoute(`${route}/sub`)).toBe(true);
    });

    it('keeps /comparison protected while /compare stays public', () => {
      expect(isProtectedRoute('/comparison')).toBe(true);
      expect(isPublicRoute('/compare')).toBe(true);
      expect(isPublicRoute('/comparison')).toBe(false);
    });
  });

  describe('list consistency', () => {
    it('has no route in both public and protected lists', () => {
      const overlap = PUBLIC_ROUTES.filter((route) =>
        PROTECTED_ROUTES.includes(route)
      );
      expect(overlap).toEqual([]);
    });

    it('contains no duplicates', () => {
      expect(new Set(PUBLIC_ROUTES).size).toBe(PUBLIC_ROUTES.length);
      expect(new Set(PROTECTED_ROUTES).size).toBe(PROTECTED_ROUTES.length);
    });
  });
});
