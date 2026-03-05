/**
 * Public Audit Not Found Page
 * 404 page for invalid, private, or non-existent audit hashes
 * @module app/audit/[hash]/not-found
 */

import Link from 'next/link';
import { FileQuestion, Home, Search } from 'lucide-react';

/**
 * Not Found Component
 * Renders a user-friendly 404 page for public audits
 * @returns JSX element
 */
export default function NotFound(): JSX.Element {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <div className="text-center max-w-md">
        {/* Icon */}
        <div className="mb-6">
          <FileQuestion className="w-24 h-24 mx-auto text-gray-400" />
        </div>

        {/* Title */}
        <h1 className="text-4xl font-bold text-gray-900 mb-4">
          Audit Not Found
        </h1>

        {/* Description */}
        <p className="text-gray-600 mb-8">
          This audit is either private, has expired, or doesn&apos;t exist. 
          The audit may have been unpublished by the owner or the link may be incorrect.
        </p>

        {/* Actions */}
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/"
            className="inline-flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            <Home className="w-4 h-4" />
            Go Home
          </Link>
          
          <Link
            href="/tools"
            className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Search className="w-4 h-4" />
            Get Your Free Audit
          </Link>
        </div>

        {/* Help text */}
        <p className="mt-8 text-sm text-gray-500">
          If you believe this is an error, please contact the site owner.
        </p>
      </div>
    </div>
  );
}
