/**
 * Public Audit Page Route
 * Client-side rendered page for public audit viewing
 * @module app/audit/[hash]/page
 */

import AuditPageClient from './AuditPageClient';

// Disable dynamic params for static export compatibility
// The placeholder hash is generated statically, actual routing is client-side
export const dynamicParams = false;

/**
 * Generate static params for static export compatibility.
 * Returns a placeholder hash to satisfy Next.js static export requirement.
 * The actual hash lookup is done client-side via AuditPageClient.
 */
export function generateStaticParams() {
  // Return a placeholder to satisfy Next.js static export
  // The client-side component handles actual hash routing
  return [{ hash: '_' }];
}

/**
 * Public Audit Page
 * Metadata is handled client-side by AuditPageClient
 * @returns JSX element
 */
export default function Page() {
  return <AuditPageClient />;
}
