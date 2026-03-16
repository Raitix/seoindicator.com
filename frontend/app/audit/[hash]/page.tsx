/**
 * Public Audit Page Route - Legacy URL Handler
 * Handles old URL format /audit/{hash}/ (static export compatible)
 * @module app/audit/[hash]/page
 */

import LegacyAuditRedirect from './LegacyAuditRedirect';

// Disable dynamic params for static export compatibility
export const dynamicParams = false;

/**
 * Generate static params for static export compatibility.
 * Returns a placeholder hash to satisfy Next.js static export requirement.
 */
export function generateStaticParams() {
  // Return a placeholder to satisfy Next.js static export
  // Other hashes will get Hostinger 404 (expected with static export)
  // Users should use the new URL format: /audit/?h={hash}
  return [{ hash: '_' }];
}

/**
 * Page Props
 */
interface PageProps {
  params: Promise<{
    hash: string;
  }>;
}

/**
 * Legacy Audit Page Handler
 * Shows a helpful message for the placeholder, redirects to new URL format
 * 
 * For static export, only /audit/_/ is generated.
 * Other hashes will get Hostinger 404, which is expected.
 * Users should use the new URL format: /audit/?h={hash}
 * 
 * @param props - Page props
 * @returns JSX element
 */
export default async function Page({ params }: PageProps) {
  const { hash } = await params;
  
  // Render client-side redirect component
  return <LegacyAuditRedirect hash={hash} />;
}
