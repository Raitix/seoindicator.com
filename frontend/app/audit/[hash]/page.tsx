/**
 * Public Audit Page Route
 * Client-side rendered page for public audit viewing
 * @module app/audit/[hash]/page
 */

import AuditPageClient from './AuditPageClient';

/**
 * Generate static params - single fallback for all audit hashes
 * Returns empty array since all audits are dynamically rendered client-side
 */
export function generateStaticParams() {
  return [];
}

/**
 * Public Audit Page
 * Metadata is handled client-side by AuditPageClient
 * @returns JSX element
 */
export default function Page() {
  return <AuditPageClient />;
}
