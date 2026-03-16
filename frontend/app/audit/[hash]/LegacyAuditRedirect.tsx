'use client';

/**
 * Legacy Audit Redirect Component
 * Handles redirect from old URL format to new query parameter format
 * @module app/audit/[hash]/LegacyAuditRedirect
 */

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Loader2 } from 'lucide-react';

/**
 * Legacy Audit Redirect Props
 */
interface LegacyAuditRedirectProps {
  hash: string;
}

/**
 * Legacy Audit Redirect Component
 * Redirects from /audit/{hash}/ to /audit/?h={hash}
 * Uses client-side navigation for static export compatibility
 */
export default function LegacyAuditRedirect({ hash }: LegacyAuditRedirectProps) {
  const router = useRouter();

  useEffect(() => {
    // Redirect to the new query parameter format
    router.replace(`/audit/?h=${hash}`);
  }, [hash, router]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950">
      <div className="text-center">
        <Loader2 className="mx-auto h-12 w-12 animate-spin text-emerald-500" />
        <p className="mt-4 text-lg text-slate-400">Redirecting...</p>
      </div>
    </div>
  );
}
