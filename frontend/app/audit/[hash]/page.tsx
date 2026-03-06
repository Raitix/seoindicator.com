/**
 * Public Audit Page Route
 * Next.js App Router route for public SEO audit pages
 * @module app/audit/[hash]/page
 */

import { Metadata } from 'next';
import AuditPageClient from './AuditPageClient';

// ============================================================
// Types
// ============================================================

/**
 * Props for the audit page component
 */
interface PageProps {
  params: Promise<{ hash: string }>;
}

// ============================================================
// Static Export Configuration
// ============================================================

/**
 * Required for static export with dynamic routes
 * Returns empty array - all audit pages are client-side rendered
 */
export function generateStaticParams() {
  return [];
}

// ============================================================
// Metadata (static for all audit pages)
// ============================================================

/**
 * Static metadata for the audit page shell
 */
export const metadata: Metadata = {
  title: 'SEO Audit Report | SEOIndicator',
  description: 'View detailed SEO audit results and recommendations.',
  robots: 'index, follow',
};

// ============================================================
// Page Component
// ============================================================

/**
 * Public Audit Page Component
 * Server component wrapper that renders the client component
 */
export default function Page({ params }: PageProps) {
  // Unwrap params promise
  return <AuditPageClientWrapper params={params} />;
}

/**
 * Async wrapper to unwrap params
 */
async function AuditPageClientWrapper({ params }: { params: Promise<{ hash: string }> }) {
  const { hash } = await params;
  return <AuditPageClient hash={hash} />;
}
