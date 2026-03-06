/**
 * Audit Detail Page Route
 * 
 * Task: TASK-14-16 - Create Audit Detail Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Route: /sites/[siteId]/audits/[auditId]
 */

import { Suspense } from 'react';
import { AuditDetailPage } from '@/components/audit/AuditDetailPage';
import { Skeleton } from '@/components/ui/skeleton';

function AuditDetailSkeleton() {
  return (
    <div className="min-h-screen bg-slate-950 p-4 sm:p-6 lg:p-8">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Summary skeleton */}
        <div className="space-y-4">
          <Skeleton className="h-6 w-32 bg-slate-800" />
          <Skeleton className="h-8 w-48 bg-slate-800" />
          <Skeleton className="h-4 w-64 bg-slate-800" />
          <div className="flex gap-2">
            {[1, 2, 3, 4].map((i) => (
              <Skeleton key={i} className="h-20 w-20 bg-slate-800 rounded-lg" />
            ))}
          </div>
        </div>

        {/* Filters skeleton */}
        <div className="flex gap-2 flex-wrap">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-9 w-36 bg-slate-800 rounded-md" />
          ))}
          <Skeleton className="h-9 w-64 bg-slate-800 rounded-md" />
        </div>

        {/* Issues skeleton */}
        <div className="space-y-3">
          {[1, 2, 3, 4, 5].map((i) => (
            <div key={i} className="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
              <div className="flex items-start gap-3">
                <Skeleton className="h-4 w-4 rounded bg-slate-800" />
                <div className="flex-1 space-y-2">
                  <div className="flex items-center gap-2">
                    <Skeleton className="h-5 w-16 rounded bg-slate-800" />
                    <Skeleton className="h-3 w-20 rounded bg-slate-800" />
                  </div>
                  <Skeleton className="h-4 w-48 rounded bg-slate-800" />
                  <Skeleton className="h-3 w-64 rounded bg-slate-800" />
                  <Skeleton className="h-3 w-96 rounded bg-slate-800" />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function Page() {
  return (
    <Suspense fallback={<AuditDetailSkeleton />}>
      <AuditDetailPage />
    </Suspense>
  );
}
