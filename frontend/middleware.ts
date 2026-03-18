/**
 * Next.js Middleware for Protected Route Handling
 * Task: TASK-37-10 - Protected Route Middleware
 *
 * This middleware protects routes that require authentication.
 * - Redirects unauthenticated users to /login with their intended destination preserved
 * - Redirects authenticated users away from /login and /register to /dashboard
 * - Runs server-side before page render to prevent flash of protected content
 */

import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// ==================== Route Configuration ====================

/**
 * Routes that require authentication to access.
 * Users without a valid token will be redirected to /login.
 */
const protectedRoutes = [
  '/dashboard',
  '/settings',
  '/projects',
  '/competitors',
  '/comparison',
  '/notifications',
  '/sites',
  '/locations',
  '/ranking-history',
  '/guides',
  '/badges',
  '/history',
  '/audit',
  '/admin',
];

/**
 * Routes that are publicly accessible without authentication.
 * These routes should never redirect to login.
 */
const publicRoutes = [
  '/',
  '/login',
  '/register',
  '/forgot-password',
  '/reset-password',
  '/verify-email',
  '/tools',
  '/seo-guides',
  '/pricing',
  '/terms',
  '/how-it-works',
  '/about',
  '/docs',
  '/compare',
  '/api',
];

// ==================== Helper Functions ====================

/**
 * Check if a pathname matches a protected route.
 * Matches both exact routes and nested routes (e.g., /dashboard/settings).
 */
function isProtectedRoute(pathname: string): boolean {
  return protectedRoutes.some(
    (route) => pathname === route || pathname.startsWith(route + '/')
  );
}

/**
 * Check if a pathname matches a public route.
 * Matches both exact routes and nested routes.
 */
function isPublicRoute(pathname: string): boolean {
  return publicRoutes.some(
    (route) => pathname === route || pathname.startsWith(route + '/')
  );
}

/**
 * Check if the request has a valid authentication token.
 * Note: Middleware only checks token presence. Token validity (expiry)
 * is verified by AuthContext via API calls on the client side.
 */
function hasAuthToken(request: NextRequest): boolean {
  const token = request.cookies.get('seoindicator_token');
  return !!token?.value;
}

// ==================== Middleware ====================

export function middleware(request: NextRequest): NextResponse {
  const { pathname } = request.nextUrl;
  const isAuthenticated = hasAuthToken(request);

  // ==================== Protected Route Check ====================

  // If user is not authenticated and tries to access a protected route,
  // redirect to login with the intended destination stored in the redirect param
  if (!isAuthenticated && isProtectedRoute(pathname)) {
    const loginUrl = new URL('/login', request.url);
    
    // Store the full path (including query params) for post-login redirect
    const fullPath = request.nextUrl.pathname + request.nextUrl.search;
    loginUrl.searchParams.set('redirect', fullPath);

    return NextResponse.redirect(loginUrl);
  }

  // ==================== Auth Page Redirect ====================

  // If user is authenticated and tries to access login or register,
  // redirect them to dashboard (they're already logged in)
  if (isAuthenticated && (pathname === '/login' || pathname === '/register')) {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  // ==================== Allow Request ====================

  // For all other cases, allow the request to proceed
  return NextResponse.next();
}

// ==================== Matcher Configuration ====================

/**
 * Configure which routes the middleware should run on.
 * We exclude static files, API routes (except auth), and public assets.
 */
export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     * - public folder files (images, etc.)
     * - api routes that need to handle their own auth
     */
    '/((?!_next/static|_next/image|favicon.ico|public/|api/public|api/v1/public).*)',
  ],
};
