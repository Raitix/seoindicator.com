'use client';

/**
 * Audit Page Client Component
 * Uses useSearchParams() - must be wrapped in Suspense boundary
 * @module app/audit/AuditPageClient
 */

import * as React from 'react';
import { useSearchParams } from 'next/navigation';
import { notFound } from 'next/navigation';
import { PublicAuditPage, PublicAuditData } from '@/components/audit/PublicAuditPage';
import { Loader2 } from 'lucide-react';

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
          if (res.status === 404) {
            setData(null);
            setLoading(false);
            return;
          }
          throw new Error(`Failed to fetch audit: ${res.status}`);
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

  if (!hash) {
    return <NoHashState />;
  }

  if (loading) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error} />;
  }

  if (!data) {
    notFound();
  }

  return <PublicAuditPage hash={hash} initialData={data} />;
}
