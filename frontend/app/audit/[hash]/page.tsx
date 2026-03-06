/**
 * Public Audit Page Route
 * Next.js App Router route for public SEO audit pages with ISR
 * @module app/audit/[hash]/page
 */

import { notFound } from 'next/navigation';
import { Metadata } from 'next';
import PublicAuditPage from '@/components/audit/PublicAuditPage';

// ============================================================
// Types
// ============================================================

/**
 * Props for the audit page component
 */
interface PageProps {
  params: Promise<{ hash: string }>;
}

/**
 * Public audit data structure
 */
interface PublicAuditData {
  siteUrl: string;
  auditedAt: string;
  overallScore: number;
  categoryScores: CategoryScore[];
  topIssues: Issue[];
  recommendations: string[];
  isPartial: boolean;
}

/**
 * Category score structure
 */
interface CategoryScore {
  name: string;
  score: number;
  maxScore: number;
  issues: number;
}

/**
 * Issue structure
 */
interface Issue {
  id: string;
  title: string;
  severity: 'critical' | 'warning' | 'info';
  description: string;
}

// ============================================================
// ISR Configuration
// ============================================================

/**
 * Revalidation time in seconds (1 hour)
 * Enables ISR (Incremental Static Regeneration)
 */
export const revalidate = 3600;

// ============================================================
// Data Fetching
// ============================================================

/**
 * Fetches audit data from the public API
 * @param hash - The public audit hash
 * @returns Audit data or null if not found/private
 */
async function getAuditData(hash: string): Promise<PublicAuditData | null> {
  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.seoindicator.com';
    
    const res = await fetch(
      `${apiUrl}/api/v1/public-audit/${hash}`,
      {
        next: { revalidate: 3600 },
      }
    );

    if (!res.ok) {
      return null;
    }

    const data = await res.json();
    
    // Check if the response indicates success
    if (!data.success || !data.data) {
      return null;
    }

    return data.data as PublicAuditData;
  } catch {
    // Handle network errors or JSON parsing errors
    return null;
  }
}

// ============================================================
// Metadata Generation
// ============================================================

/**
 * Generates metadata for SEO optimization
 * @param props - Page props with params
 * @returns Metadata object for the page
 */
export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { hash } = await params;
  const audit = await getAuditData(hash);

  // Return noindex metadata for invalid/not found audits
  if (!audit) {
    return {
      title: 'Audit Not Found | SEOIndicator',
      robots: 'noindex',
    };
  }

  const canonicalUrl = `https://seoindicator.com/audit/${hash}/`;
  const ogImageUrl = `https://seoindicator.com/badge/${hash}/og.png`;

  return {
    title: `${audit.siteUrl} SEO Audit Report | SEOIndicator`,
    description: `SEO audit for ${audit.siteUrl} - Score: ${audit.overallScore}/100. View technical SEO analysis and recommendations.`,
    robots: 'index, follow',
    alternates: {
      canonical: canonicalUrl,
    },
    openGraph: {
      title: `${audit.siteUrl} SEO Score: ${audit.overallScore}/100`,
      description: `SEO audit results for ${audit.siteUrl} - Score: ${audit.overallScore}/100`,
      url: canonicalUrl,
      type: 'website',
      images: [
        {
          url: ogImageUrl,
          width: 1200,
          height: 630,
          alt: `SEO Score: ${audit.overallScore}/100`,
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title: `${audit.siteUrl} SEO Score: ${audit.overallScore}/100`,
      description: `SEO audit results for ${audit.siteUrl} - Score: ${audit.overallScore}/100`,
      images: [ogImageUrl],
    },
  };
}

// ============================================================
// Page Component
// ============================================================

/**
 * Public Audit Page Component
 * Renders the public audit page with audit data
 * @param props - Page props with params
 * @returns JSX element or calls notFound()
 */
export default async function Page({ params }: PageProps) {
  const { hash } = await params;
  const audit = await getAuditData(hash);

  // Return 404 for invalid, not found, or private audits
  if (!audit) {
    notFound();
  }

  return <PublicAuditPage audit={audit} />;
}
