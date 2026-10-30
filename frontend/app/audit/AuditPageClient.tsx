'use client';

/**
 * Audit Page Client Component
 * Uses useSearchParams() - must be wrapped in Suspense boundary
 * @module app/audit/AuditPageClient
 */

import * as React from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { PublicAuditPage, PublicAuditData } from '@/components/audit/PublicAuditPage';
import { Loader2, FileQuestion, Home, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { generateJsonLdSchema } from '@/lib/audit/json-ld-schema';

// ============================================================
// Data Fetching Hook
// ============================================================

/**
 * Hook to fetch audit data from the public API
 */
function useAuditData(hash: string | null) {
  const [data, setData] = React.useState<PublicAuditData | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!hash) {
      setLoading(false);
      return;
    }

    let mounted = true;

    async function fetchData() {
      try {
        const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.seoindicator.com';

        const res = await fetch(
          `${apiUrl}/api/v1/public-audit/${hash}`,
          {
            headers: {
              'Accept': 'application/json',
            },
          }
        );

        if (!mounted) return;

        if (!res.ok) {
          // Any error status (400, 404, 500, etc.) means the audit is not accessible
          // Show "Not Found" UI instead of technical error messages
          setData(null);
          setLoading(false);
          return;
        }

        const json = await res.json();

        if (!json.success || !json.data) {
          setData(null);
          setLoading(false);
          return;
        }

        setData(json.data as PublicAuditData);
        setLoading(false);
      } catch (err) {
        if (!mounted) return;
        setError(err instanceof Error ? err.message : 'Failed to load audit');
        setLoading(false);
      }
    }

    fetchData();

    return () => {
      mounted = false;
    };
  }, [hash]);

  return { data, loading, error };
}

// ============================================================
// JSON-LD Structured Data Injection
// ============================================================

/**
 * Inject JSON-LD structured data for SEO rich snippets
 * Creates a script tag with application/ld+json type in document head
 */
function injectJsonLd(audit: PublicAuditData | null, hash: string | null) {
  if (typeof document === 'undefined') {
    return;
  }

  // Remove any existing JSON-LD script tag
  const existingScript = document.querySelector('script[type="application/ld+json"][data-json-ld="audit"]');
  if (existingScript) {
    existingScript.remove();
  }

  if (!audit || !hash) {
    return;
  }

  // Build JSON-LD schema with the hash from URL params
  const jsonLd = generateJsonLdSchema({
    ...audit,
    publicHash: audit.publicHash || hash,
  });

  if (!jsonLd) {
    return;
  }

  const script = document.createElement('script');
  script.type = 'application/ld+json';
  script.setAttribute('data-json-ld', 'audit');
  script.textContent = JSON.stringify(jsonLd);
  document.head.appendChild(script);
}

// ============================================================
// Components
// ============================================================

/**
 * Loading Component
 */
function LoadingState() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950">
      <div className="text-center">
        <Loader2 className="mx-auto h-12 w-12 animate-spin text-emerald-500" />
        <p className="mt-4 text-lg text-slate-400">Loading audit...</p>
      </div>
    </div>
  );
}

/**
 * Error Component
 */
function ErrorState({ message }: { message: string }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950">
      <div className="text-center">
        <h1 className="text-2xl font-bold text-red-400">Error</h1>
        <p className="mt-2 text-slate-400">{message}</p>
      </div>
    </div>
  );
}

/**
 * No Hash State
 */
function NoHashState() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950">
      <div className="text-center">
        <h1 className="text-2xl font-bold text-slate-200">No Audit Specified</h1>
        <p className="mt-2 text-slate-400">Please provide a valid audit hash in the URL.</p>
        <Link href="/tools" className="mt-4 inline-block">
          <Button>
            <Search className="mr-2 h-4 w-4" />
            Get Your Free Audit
          </Button>
        </Link>
      </div>
    </div>
  );
}

/**
 * Not Found State - Client-side fallback
 * Displayed when audit is not found or is private
 */
function NotFoundState() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-16">
      <div className="max-w-md text-center">
        {/* Icon */}
        <div className="mb-6 flex justify-center">
          <div className="rounded-full bg-slate-800 p-6">
            <FileQuestion
              className="h-16 w-16 text-slate-400"
              data-testid="file-question-icon"
              aria-hidden="true"
            />
          </div>
        </div>

        {/* Title */}
        <h1 className="mb-4 text-4xl font-bold text-white">
          Audit Not Found
        </h1>

        {/* Description */}
        <p className="mb-8 text-lg text-slate-400">
          This audit is either private or doesn&apos;t exist.
          The audit may have been removed or the link is incorrect.
        </p>

        {/* Actions */}
        <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
          <Link href="/">
            <Button variant="outline" className="w-full sm:w-auto">
              <Home className="mr-2 h-4 w-4" aria-hidden="true" />
              Go Home
            </Button>
          </Link>
          <Link href="/tools">
            <Button className="w-full sm:w-auto">
              <Search className="mr-2 h-4 w-4" aria-hidden="true" />
              Get Your Free Audit
            </Button>
          </Link>
        </div>

        {/* Additional Help */}
        <p className="mt-8 text-sm text-slate-500">
          If you believe this is an error, please{' '}
          <Link
            href="/contact"
            className="text-emerald-400 hover:text-emerald-300 hover:underline"
          >
            contact support
          </Link>
          .
        </p>
      </div>
    </div>
  );
}

// ============================================================
// Main Component
// ============================================================

/**
 * Audit Page Client Component
 * Uses useSearchParams() - requires Suspense boundary in parent
 * URL format: /audit/?h=abc123
 */
export default function AuditPageClient() {
  const searchParams = useSearchParams();
  const hash = searchParams.get('h');
  const { data, loading, error } = useAuditData(hash);

  // Inject JSON-LD structured data when audit data is loaded
  React.useEffect(() => {
    injectJsonLd(data, hash);

    // Cleanup: remove JSON-LD on unmount
    return () => {
      if (typeof document !== 'undefined') {
        const existingScript = document.querySelector('script[type="application/ld+json"][data-json-ld="audit"]');
        if (existingScript) {
          existingScript.remove();
        }
      }
    };
  }, [data, hash]);

  // No hash provided
  if (!hash) {
    return <NoHashState />;
  }

  // Loading state
  if (loading) {
    return <LoadingState />;
  }

  // Error state
  if (error) {
    return <ErrorState message={error} />;
  }

  // Not found state - show proper UI instead of calling notFound()
  // This works with static export where notFound() doesn't function
  if (!data) {
    return <NotFoundState />;
  }

  // Render audit page
  return <PublicAuditPage hash={hash} initialData={data} />;
}
