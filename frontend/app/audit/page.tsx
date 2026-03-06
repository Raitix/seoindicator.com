import { Suspense } from 'react';
import AuditPageClient from './AuditPageClient';
import { Loader2 } from 'lucide-react';

/**
 * Loading fallback for Suspense boundary
 */
function LoadingFallback() {
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
 * Public Audit Page - Static Export Compatible
 * Uses query parameter for audit hash: /audit/?h=abc123
 *
 * The page wraps the client component in a Suspense boundary
 * because useSearchParams() requires it in Next.js 16+ static exports.
 *
 * @module app/audit/page
 */
export default function AuditPage() {
  return (
    <Suspense fallback={<LoadingFallback />}>
      <AuditPageClient />
    </Suspense>
  );
}
