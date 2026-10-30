/**
 * 404 Not Found Page for SEO Guides
 * Task: TASK-20-4 - Set Up Routing with Static Params Generation
 * 
 * Displayed when a user tries to access a non-existent guide slug.
 */

import Link from 'next/link';

export default function GuideNotFound() {
  return (
    <main className="min-h-screen bg-slate-950 text-white flex items-center justify-center px-4">
      <div className="text-center max-w-md">
        <div className="text-6xl mb-6">🔍</div>
        <h1 className="text-3xl font-bold text-white mb-4">
          Guide Not Found
        </h1>
        <p className="text-slate-400 mb-8">
          The SEO guide you&apos;re looking for doesn&apos;t exist or has been moved.
        </p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/seo-guides/"
            className="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-6 py-3 font-medium text-white hover:bg-emerald-600 transition-colors"
          >
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Browse All Guides
          </Link>
          <Link
            href="/"
            className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-700 px-6 py-3 font-medium text-slate-300 hover:border-emerald-500 hover:text-emerald-400 transition-colors"
          >
            Go Home
          </Link>
        </div>
      </div>
    </main>
  );
}
