/**
 * Keywords Page Route - Server Component Wrapper
 * 
 * This server component exports generateStaticParams for static export compatibility.
 * The actual client-side logic is in KeywordsClient.tsx
 */

import KeywordsClient from './KeywordsClient';

// Disable dynamic params for static export compatibility
export const dynamicParams = false;

/**
 * Generate static params for static export compatibility.
 * Returns empty array - all routes handled client-side.
 */
export function generateStaticParams() {
  return [];
}

interface PageProps {
  params: Promise<{
    siteId: string;
  }>;
}

export default async function KeywordsPage({ params }: PageProps) {
  const { siteId } = await params;
  return <KeywordsClient siteId={siteId} />;
}
