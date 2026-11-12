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
 ** Returns empty array - all routes handled client-side.
 *
 * Next.js refuses to export a dynamic route whose
 * generateStaticParams must not return an empty array or the export
 * fails. The values match the parent layout, so the exported path
 * is the same one the site already serves. The client recovers the
 * real id from the browser URL during hydration.
 */
export function generateStaticParams() {
  return [{ siteId: '1' }];
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
