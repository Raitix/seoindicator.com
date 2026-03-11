"use client";

import * as React from "react";
import { useEffect, useState } from "react";
import Link from "next/link";

/**
 * Custom 404 page for static export with dynamic routes.
 * 
 * When the static export server returns 404 for dynamic routes like
 * /locations/123/reviews, this page detects the pattern and redirects
 * to the preview shell (/locations/preview/reviews) which then performs
 * client-side route recovery to extract the ID from the URL.
 */
export default function NotFound() {
  const [redirecting, setRedirecting] = useState(false);
  const [redirectPath, setRedirectPath] = useState<string | null>(null);

  useEffect(() => {
    if (typeof window === "undefined") return;

    const pathname = window.location.pathname;
    const redirect = getPreviewShellRedirect(pathname);

    if (redirect) {
      setRedirectPath(redirect);
      setRedirecting(true);
      // Small delay to show redirect message, then navigate
      const timer = setTimeout(() => {
        window.location.replace(redirect);
      }, 100);
      return () => clearTimeout(timer);
    }
  }, []);

  // Show redirect state
  if (redirecting && redirectPath) {
    return (
      <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-16 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-2xl text-center">
          <div className="animate-pulse">
            <div className="mx-auto mb-4 h-12 w-12 rounded-full bg-blue-500/20 flex items-center justify-center">
              <svg className="h-6 w-6 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
            </div>
            <p className="text-slate-400">Loading page...</p>
          </div>
        </div>
      </main>
    );
  }

  // Standard 404 page for non-dynamic routes
  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900 px-4 py-16 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-2xl text-center">
        <div className="mb-8">
          <span className="text-8xl font-bold bg-gradient-to-r from-blue-500 to-purple-500 bg-clip-text text-transparent">
            404
          </span>
        </div>
        <h1 className="text-2xl font-semibold text-white mb-4">
          Page Not Found
        </h1>
        <p className="text-slate-400 mb-8">
          The page you&apos;re looking for doesn&apos;t exist or has been moved.
        </p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/"
            className="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
          >
            Go Home
          </Link>
          <Link
            href="/dashboard"
            className="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-slate-700 text-slate-300 font-medium hover:bg-slate-800 transition-colors"
          >
            Dashboard
          </Link>
        </div>
      </div>
    </main>
  );
}

/**
 * Patterns for dynamic routes that should redirect to preview shells.
 * Each pattern maps a regex to a preview shell path.
 */
const DYNAMIC_ROUTE_PATTERNS: Array<{
  pattern: RegExp;
  getPreviewPath: (matches: RegExpMatchArray) => string;
}> = [
  // Location dashboard: /locations/{id}/ -> /locations/preview/
  {
    pattern: /^\/locations\/(\d+)\/?$/,
    getPreviewPath: () => "/locations/preview/",
  },
  // Location reviews: /locations/{id}/reviews -> /locations/preview/reviews
  {
    pattern: /^\/locations\/(\d+)\/reviews\/?$/,
    getPreviewPath: () => "/locations/preview/reviews/",
  },
  // Site location dashboard: /sites/{siteId}/local/{locationId}/ -> /sites/preview/local/preview/
  {
    pattern: /^\/sites\/(\d+)\/local\/(\d+)\/?$/,
    getPreviewPath: () => "/sites/preview/local/preview/",
  },
  // Site location reviews: /sites/{siteId}/local/{locationId}/reviews -> /sites/preview/local/preview/reviews
  {
    pattern: /^\/sites\/(\d+)\/local\/(\d+)\/reviews\/?$/,
    getPreviewPath: () => "/sites/preview/local/preview/reviews/",
  },
];

/**
 * Check if the current path should redirect to a preview shell.
 * Returns the preview shell path if matched, null otherwise.
 */
function getPreviewShellRedirect(pathname: string): string | null {
  // Normalize pathname (remove trailing slash for matching, but keep for redirect)
  const normalizedPath = pathname.replace(/\/$/, "") || "/";

  for (const { pattern, getPreviewPath } of DYNAMIC_ROUTE_PATTERNS) {
    const matches = normalizedPath.match(pattern);
    if (matches) {
      return getPreviewPath(matches);
    }
  }

  return null;
}
