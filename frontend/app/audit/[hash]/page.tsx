/**
 * Public Audit Page Route
 * Server component that exports generateStaticParams for static export compatibility
 * Renders client component for dynamic audit viewing
 * @module app/audit/[hash]/page
 */

import AuditPageClient from './AuditPageClient';

/**
 * Enable dynamic params for client-side rendering
 * Required for static export with dynamic routes
 */
export const dynamicParams = true;

/**
 * Generate static params - empty array for fully dynamic rendering
 * All pages will be rendered client-side at runtime
 */
export async function generateStaticParams() {
  return [];
}

/**
 * Public Audit Page - Server Component
 * Delegates to client component for dynamic rendering
 * @returns JSX element
 */
export default function Page() {
  return <AuditPageClient />;
}
