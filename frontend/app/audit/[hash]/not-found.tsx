/**
 * Public Audit Not Found Page
 * 404 page for non-existent or private audits
 * @module app/audit/[hash]/not-found
 */

import Link from 'next/link';
import { FileQuestion, Home, Search } from 'lucide-react';

import { Button } from '@/components/ui/button';

/**
 * Not Found Page Component
 * Displayed when an audit is not found or is private
 * @returns JSX element
 */
export default function NotFound() {
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
