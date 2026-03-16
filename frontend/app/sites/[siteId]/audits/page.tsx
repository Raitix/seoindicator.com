/**
 * Audits List Page
 * 
 * Task: TASK-14-15 - Create Audit List Page (Frontend)
 * Spec: SPEC-14 - Technical SEO Site Audit
 * 
 * Page component that displays the list of audits for a site
 */

import * as React from 'react';
import { AuditList } from '@/components/audit/AuditList';

// ============================================================
// Static Params for Dynamic Routes (required for output: export)
// ============================================================

export function generateStaticParams() {
  // Return empty array - pages are client-side rendered
  // This allows the dev server to handle dynamic routes
  return [];
}

// ============================================================
// Page Component
// ============================================================

export default function AuditsPage({ params }: { params: { siteId: string } }) {
  return (
    <main className="container mx-auto px-4 py-8 max-w-4xl">
      <AuditList siteId={params.siteId} />
    </main>
  );
}
