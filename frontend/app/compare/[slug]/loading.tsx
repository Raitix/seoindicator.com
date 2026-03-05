/**
 * Comparison Page Loading State
 * Task: TASK-32-3 - Comparison Page Route with Static Generation
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Loading fallback for comparison pages.
 */

export default function CompareLoading() {
  return (
    <main className="min-h-screen bg-slate-950 text-white" role="main">
      <div className="mx-auto max-w-5xl px-4 py-20">
        {/* Breadcrumb Skeleton */}
        <div className="animate-pulse flex gap-2 mb-8">
          <div className="h-4 w-12 bg-slate-800 rounded"></div>
          <div className="h-4 w-2 bg-slate-800 rounded"></div>
          <div className="h-4 w-16 bg-slate-800 rounded"></div>
          <div className="h-4 w-2 bg-slate-800 rounded"></div>
          <div className="h-4 w-32 bg-slate-800 rounded"></div>
        </div>

        {/* Hero Skeleton */}
        <div className="text-center mb-12 animate-pulse">
          <div className="h-10 w-3/4 mx-auto bg-slate-800 rounded mb-4"></div>
          <div className="h-6 w-2/3 mx-auto bg-slate-800 rounded"></div>
        </div>

        {/* Content Skeleton */}
        <div className="space-y-8 animate-pulse">
          <div className="h-64 bg-slate-800 rounded-xl"></div>
          <div className="h-48 bg-slate-800 rounded-xl"></div>
          <div className="grid grid-cols-2 gap-4">
            <div className="h-32 bg-slate-800 rounded-xl"></div>
            <div className="h-32 bg-slate-800 rounded-xl"></div>
          </div>
        </div>
      </div>
    </main>
  );
}
